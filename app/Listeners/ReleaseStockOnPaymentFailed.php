<?php

namespace App\Listeners;

use App\Events\PaymentFailed;
use Illuminate\Contracts\Queue\ShouldQueue;

class ReleaseStockOnPaymentFailed implements ShouldQueue
{
    public function handle(PaymentFailed $event): void
    {
        $payment = $event->payment;
        $order = $payment->order;

        // Release all active reservations for this order
        foreach ($order->reservations()->active()->get() as $reservation) {
            $reservation->inventoryItem->release($reservation);
        }

        // Update order status
        $order->update(['status' => 'cancelled']);
    }
}

