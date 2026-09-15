<?php

namespace App\Jobs;

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendOrderConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    /**
     * Create a new job instance.
     * Accepts either an integer ID, a string UUID, or an Order instance.
     */
    public function __construct(
        public readonly int|string $orderId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Resolve order by either integer ID or string UUID
        $query = Order::with(['customer', 'items.product']);
        
        $order = is_numeric($this->orderId)
            ? $query->find((int) $this->orderId)
            : $query->where('uuid', $this->orderId)->first();

        if (! $order) {
            Log::warning("SendOrderConfirmationEmail: Order '{$this->orderId}' could not be found.");
            return;
        }

        if (! $order->customer || empty($order->customer->email)) {
            Log::warning("SendOrderConfirmationEmail: Customer or email missing for Order #{$order->order_number}.");
            return;
        }

        $recipientEmail = $order->customer->email;
        $recipientName  = $order->customer->name;

        try {
            Log::info("SendOrderConfirmationEmail: Sending confirmation email for Order #{$order->order_number} to {$recipientEmail} via configured mailer.");

            Mail::to($recipientEmail, $recipientName)
                ->send(new OrderConfirmationMail($order));

            Log::info("SendOrderConfirmationEmail: Order confirmation email successfully sent for Order #{$order->order_number} to {$recipientEmail}.");
        } catch (Throwable $e) {
            Log::error("SendOrderConfirmationEmail FAILED for Order #{$order->order_number} to {$recipientEmail}: " . $e->getMessage(), [
                'exception' => $e,
                'order_id'  => $this->orderId,
            ]);

            // Re-throw so Laravel queue can retry the job according to $tries
            throw $e;
        }
    }
}
