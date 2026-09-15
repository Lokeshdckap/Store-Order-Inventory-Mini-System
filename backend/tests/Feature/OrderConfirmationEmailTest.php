<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmationEmail;
use App\Mail\OrderConfirmationMail;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderConfirmationEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_order_confirmation_email_job_sends_mailable_to_customer(): void
    {
        Mail::fake();

        $customer = Customer::create([
            'name'  => 'Robert Taylor',
            'email' => 'robert@example.com',
        ]);

        $product = Product::create([
            'name'           => 'Wireless Earbuds Pro',
            'code'           => 'SKU-EP01',
            'price'          => 89.99,
            'tax_percentage' => 18.00,
            'stock'          => 10,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-MAIL-TEST-001',
            'customer_id'  => $customer->id,
            'subtotal'     => 89.99,
            'tax_total'    => 16.20,
            'grand_total'  => 106.19,
            'status'       => 'completed',
        ]);

        OrderItem::create([
            'order_id'       => $order->id,
            'product_id'     => $product->id,
            'quantity'       => 1,
            'unit_price'     => 89.99,
            'tax_percentage' => 18.00,
            'subtotal'       => 89.99,
            'tax_amount'     => 16.20,
            'total'          => 106.19,
        ]);

        // Dispatch the job directly
        $job = new SendOrderConfirmationEmail($order->id);
        $job->handle();

        // Assert email was sent to customer
        Mail::assertSent(OrderConfirmationMail::class, function ($mail) use ($order) {
            return $mail->hasTo('robert@example.com')
                && $mail->order->id === $order->id
                && $mail->order->order_number === 'ORD-MAIL-TEST-001';
        });
    }

    public function test_send_order_confirmation_email_job_works_with_uuid(): void
    {
        Mail::fake();

        $customer = Customer::create([
            'name'  => 'Sarah Connor',
            'email' => 'sarah@example.com',
        ]);

        $product = Product::create([
            'name'           => 'Monitor Stand Dual',
            'code'           => 'SKU-MSD01',
            'price'          => 49.99,
            'tax_percentage' => 12.00,
            'stock'          => 5,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-UUID-TEST-002',
            'customer_id'  => $customer->id,
            'subtotal'     => 49.99,
            'tax_total'    => 6.00,
            'grand_total'  => 55.99,
            'status'       => 'completed',
        ]);

        OrderItem::create([
            'order_id'       => $order->id,
            'product_id'     => $product->id,
            'quantity'       => 1,
            'unit_price'     => 49.99,
            'tax_percentage' => 12.00,
            'subtotal'       => 49.99,
            'tax_amount'     => 6.00,
            'total'          => 55.99,
        ]);

        // Dispatch the job using order UUID
        $job = new SendOrderConfirmationEmail($order->uuid);
        $job->handle();

        // Assert email was sent to customer
        Mail::assertSent(OrderConfirmationMail::class, function ($mail) use ($order) {
            return $mail->hasTo('sarah@example.com')
                && $mail->order->uuid === $order->uuid;
        });
    }

    public function test_mailable_renders_order_details_and_totals(): void
    {
        $customer = Customer::create([
            'name'  => 'Emily Clark',
            'email' => 'emily@example.com',
        ]);

        $product = Product::create([
            'name'           => 'Mechanical Keyboard',
            'code'           => 'SKU-KB99',
            'price'          => 120.00,
            'tax_percentage' => 18.00,
            'stock'          => 10,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-RENDER-003',
            'customer_id'  => $customer->id,
            'subtotal'     => 120.00,
            'tax_total'    => 21.60,
            'grand_total'  => 141.60,
            'status'       => 'completed',
        ]);

        OrderItem::create([
            'order_id'       => $order->id,
            'product_id'     => $product->id,
            'quantity'       => 1,
            'unit_price'     => 120.00,
            'tax_percentage' => 18.00,
            'subtotal'       => 120.00,
            'tax_amount'     => 21.60,
            'total'          => 141.60,
        ]);

        $order->load(['customer', 'items.product']);

        $mailable = new OrderConfirmationMail($order);

        $mailable->assertSeeInHtml('ORD-RENDER-003');
        $mailable->assertSeeInHtml('Emily Clark');
        $mailable->assertSeeInHtml('Mechanical Keyboard');
        $mailable->assertSeeInHtml('$141.60');
    }
}
