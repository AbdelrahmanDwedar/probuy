<?php

namespace App\Jobs;

use App\Events\PaymentCaptured;
use App\Events\PaymentFailed;
use App\Models\Order;
use App\Models\Payment;
use App\Services\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public string $tenantId,
        public string $orderId,
        public string $provider = 'stripe',
        public array $paymentData = []
    ) {
        $this->onQueue('high');
    }

    public function handle(): void
    {
        TenantContext::setById($this->tenantId);

        $order = Order::findOrFail($this->orderId);

        // Create payment record
        $payment = Payment::create([
            'tenant_id' => $this->tenantId,
            'order_id' => $order->id,
            'provider' => $this->provider,
            'status' => 'pending',
            'amount_cents' => $order->total_cents,
            'currency' => $order->currency,
        ]);

        try {
            // TODO: Integrate with actual payment gateway (Stripe, PayPal, etc.)
            // For now, simulate payment processing
            $result = $this->processWithProvider($payment);

            $payment->update([
                'status' => 'captured',
                'provider_transaction_id' => $result['transaction_id'] ?? null,
                'provider_payload' => $result,
            ]);

            event(new PaymentCaptured($payment));

            Log::info("Payment captured", [
                'tenant_id' => $this->tenantId,
                'order_id' => $this->orderId,
                'payment_id' => $payment->id,
            ]);
        } catch (\Exception $e) {
            $payment->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            event(new PaymentFailed($payment));

            throw $e;
        }
    }

    private function processWithProvider(Payment $payment): array
    {
        // Simulate payment gateway call
        // In production, integrate with Stripe, PayPal, etc.
        return [
            'transaction_id' => 'txn_' . uniqid(),
            'status' => 'success',
            'processed_at' => now()->toIso8601String(),
        ];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Payment processing failed", [
            'tenant_id' => $this->tenantId,
            'order_id' => $this->orderId,
            'error' => $exception->getMessage(),
        ]);
    }
}

