<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Jobs\SendOrderConfirmationEmail;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OrderService
{
    /**
     * Creates a customer order with atomic stock validation and deduction.
     * All product lookups and references are done via UUID (not integer ID).
     *
     * @param array{customer_name: string, customer_email: string, items: array<int, array{product_uuid: string, quantity: int}>} $data
     * @throws InsufficientStockException
     */
    public function createOrder(array $data): Order
    {
        $email = strtolower(trim($data['customer_email']));
        $name  = trim($data['customer_name']);

        // Aggregate quantities by product_uuid
        $quantitiesByUuid = [];
        foreach ($data['items'] as $item) {
            $uuid = $item['product_uuid'];
            $qty  = (int) $item['quantity'];
            $quantitiesByUuid[$uuid] = ($quantitiesByUuid[$uuid] ?? 0) + $qty;
        }

        if (empty($quantitiesByUuid)) {
            throw new InvalidArgumentException('Order must contain at least one valid product.');
        }

        // Sort UUIDs ascending to acquire locks in a consistent order (deadlock prevention)
        $sortedUuids = array_keys($quantitiesByUuid);
        sort($sortedUuids);

        return DB::transaction(function () use ($email, $name, $quantitiesByUuid, $sortedUuids) {
            // Find or create customer
            $customer = Customer::firstOrCreate(
                ['email' => $email],
                ['name'  => $name]
            );

            // Pessimistically lock matching product rows by UUID for update
            $products = Product::whereIn('uuid', $sortedUuids)
                ->lockForUpdate()
                ->get()
                ->keyBy('uuid');   // keyed by UUID for easy lookup

            // 1. Validate existence and stock for ALL products before any changes
            foreach ($quantitiesByUuid as $uuid => $requestedQty) {
                $product = $products->get($uuid);

                if (! $product) {
                    throw new InvalidArgumentException("Product with UUID {$uuid} not found.");
                }

                if ($product->stock < $requestedQty) {
                    throw new InsufficientStockException($product, $requestedQty, $product->stock);
                }
            }

            // 2. Compute financial totals and deduct stock
            $subtotal       = 0.0;
            $taxTotal       = 0.0;
            $orderItemsData = [];

            foreach ($quantitiesByUuid as $uuid => $qty) {
                $product = $products->get($uuid);

                // Deduct stock atomically
                $product->decrement('stock', $qty);

                $unitPrice      = (float) $product->price;
                $taxPercentage  = (float) $product->tax_percentage;

                $lineSubtotal  = round($unitPrice * $qty, 2);
                $lineTaxAmount = round($lineSubtotal * ($taxPercentage / 100), 2);
                $lineTotal     = round($lineSubtotal + $lineTaxAmount, 2);

                $subtotal = round($subtotal + $lineSubtotal, 2);
                $taxTotal = round($taxTotal + $lineTaxAmount, 2);

                $orderItemsData[] = [
                    'product_id'     => $product->id,   // FK stored internally as integer ID
                    'quantity'       => $qty,
                    'unit_price'     => $unitPrice,
                    'tax_percentage' => $taxPercentage,
                    'subtotal'       => $lineSubtotal,
                    'tax_amount'     => $lineTaxAmount,
                    'total'          => $lineTotal,
                ];
            }

            $grandTotal  = round($subtotal + $taxTotal, 2);
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id'  => $customer->id,
                'subtotal'     => $subtotal,
                'tax_total'    => $taxTotal,
                'grand_total'  => $grandTotal,
                'status'       => 'completed',
            ]);

            foreach ($orderItemsData as $itemData) {
                $itemData['order_id'] = $order->id;
                OrderItem::create($itemData);
            }

            // Dispatch asynchronous confirmation job only after transaction commits
            DB::afterCommit(function () use ($order) {
                SendOrderConfirmationEmail::dispatch($order->id);
            });

            return $order->fresh(['customer', 'items.product']);
        });
    }
}
