<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Seed Default Admin User
        $this->call(AdminSeeder::class);

        // 1. Seed Products
        $products = [
            ['name' => 'Mechanical Keyboard RGB', 'code' => 'SKU-KB01', 'price' => 79.99, 'tax_percentage' => 18.00, 'stock' => 15],
            ['name' => 'Wireless Gaming Mouse', 'code' => 'SKU-MS02', 'price' => 29.50, 'tax_percentage' => 18.00, 'stock' => 25],
            ['name' => 'USB-C Hub 7-in-1', 'code' => 'SKU-HB03', 'price' => 45.00, 'tax_percentage' => 18.00, 'stock' => 4],
            ['name' => '4K HDMI Cable 2m', 'code' => 'SKU-CB04', 'price' => 12.99, 'tax_percentage' => 12.00, 'stock' => 40],
            ['name' => 'Ergonomic Laptop Stand', 'code' => 'SKU-ST05', 'price' => 34.00, 'tax_percentage' => 12.00, 'stock' => 2],
            ['name' => 'Noise Cancelling Headphones', 'code' => 'SKU-HP06', 'price' => 149.00, 'tax_percentage' => 18.00, 'stock' => 8],
            ['name' => 'Desk Mat XXL Waterproof', 'code' => 'SKU-DM07', 'price' => 19.99, 'tax_percentage' => 5.00, 'stock' => 30],
            ['name' => 'Screen Cleaning Kit Pro', 'code' => 'SKU-CK08', 'price' => 8.50, 'tax_percentage' => 5.00, 'stock' => 1],
            ['name' => 'Bluetooth Desktop Speaker', 'code' => 'SKU-SP09', 'price' => 55.00, 'tax_percentage' => 18.00, 'stock' => 12],
            ['name' => '65W GaN Fast Charger', 'code' => 'SKU-CH10', 'price' => 39.99, 'tax_percentage' => 18.00, 'stock' => 0],
            ['name' => 'Webcam 1080p HD Pro', 'code' => 'SKU-WC11', 'price' => 49.00, 'tax_percentage' => 18.00, 'stock' => 14],
            ['name' => 'Cable Management Pack', 'code' => 'SKU-CC12', 'price' => 6.99, 'tax_percentage' => 5.00, 'stock' => 50],
            ['name' => 'Portable SSD 1TB NVMe', 'code' => 'SKU-SD13', 'price' => 99.00, 'tax_percentage' => 18.00, 'stock' => 3],
            ['name' => 'Smart LED Desk Lamp', 'code' => 'SKU-LP14', 'price' => 38.50, 'tax_percentage' => 12.00, 'stock' => 18],
            ['name' => 'Dual Monitor Arm Mount', 'code' => 'SKU-MA15', 'price' => 69.00, 'tax_percentage' => 12.00, 'stock' => 7],
        ];

        $createdProducts = [];
        foreach ($products as $prod) {
            $createdProducts[$prod['code']] = Product::create($prod);
        }

        // 2. Seed Customers
        $alice = Customer::create([
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
        ]);

        $bob = Customer::create([
            'name' => 'Bob Smith',
            'email' => 'bob.smith@example.com',
        ]);

        $carol = Customer::create([
            'name' => 'Carol Davis',
            'email' => 'carol.davis@example.com',
        ]);

        Customer::create([
            'name' => 'David Wilson',
            'email' => 'david.wilson@example.com',
        ]);

        // 3. Seed Sample Orders for Alice
        $kb = $createdProducts['SKU-KB01'];
        $ms = $createdProducts['SKU-MS02'];

        $item1Subtotal = round(1 * $kb->price, 2);
        $item1Tax = round($item1Subtotal * ($kb->tax_percentage / 100), 2);
        $item1Total = round($item1Subtotal + $item1Tax, 2);

        $item2Subtotal = round(2 * $ms->price, 2);
        $item2Tax = round($item2Subtotal * ($ms->tax_percentage / 100), 2);
        $item2Total = round($item2Subtotal + $item2Tax, 2);

        $orderSubtotal = round($item1Subtotal + $item2Subtotal, 2);
        $orderTaxTotal = round($item1Tax + $item2Tax, 2);
        $orderGrandTotal = round($orderSubtotal + $orderTaxTotal, 2);

        $order1 = Order::create([
            'order_number' => 'ORD-20260901-0001',
            'customer_id' => $alice->id,
            'subtotal' => $orderSubtotal,
            'tax_total' => $orderTaxTotal,
            'grand_total' => $orderGrandTotal,
            'status' => 'completed',
            'created_at' => now()->subDays(10),
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $kb->id,
            'quantity' => 1,
            'unit_price' => $kb->price,
            'tax_percentage' => $kb->tax_percentage,
            'subtotal' => $item1Subtotal,
            'tax_amount' => $item1Tax,
            'total' => $item1Total,
            'created_at' => now()->subDays(10),
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $ms->id,
            'quantity' => 2,
            'unit_price' => $ms->price,
            'tax_percentage' => $ms->tax_percentage,
            'subtotal' => $item2Subtotal,
            'tax_amount' => $item2Tax,
            'total' => $item2Total,
            'created_at' => now()->subDays(10),
        ]);

        // Seed Sample Order for Bob
        $hp = $createdProducts['SKU-HP06'];
        $hpSubtotal = round(1 * $hp->price, 2);
        $hpTax = round($hpSubtotal * ($hp->tax_percentage / 100), 2);
        $hpTotal = round($hpSubtotal + $hpTax, 2);

        $order2 = Order::create([
            'order_number' => 'ORD-20260905-0002',
            'customer_id' => $bob->id,
            'subtotal' => $hpSubtotal,
            'tax_total' => $hpTax,
            'grand_total' => $hpTotal,
            'status' => 'completed',
            'created_at' => now()->subDays(6),
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => $hp->id,
            'quantity' => 1,
            'unit_price' => $hp->price,
            'tax_percentage' => $hp->tax_percentage,
            'subtotal' => $hpSubtotal,
            'tax_amount' => $hpTax,
            'total' => $hpTotal,
            'created_at' => now()->subDays(6),
        ]);
    }
}
