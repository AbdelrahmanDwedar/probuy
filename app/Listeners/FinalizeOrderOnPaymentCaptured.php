<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Events\PaymentCaptured;
use App\Jobs\GenerateShippingLabelJob;
use App\Models\Shipment;
use Illuminate\Contracts\Queue\ShouldQueue;

class FinalizeOrderOnPaymentCaptured implements ShouldQueue
{
    public function handle(PaymentCaptured $event): void
    {
        $payment = $event->payment;
        $order = $payment->order;

        // Update order status
        $order->update(['status' => 'paid', 'placed_at' => $order->placed_at ?? now()]);

        // Commit stock reservations
        foreach ($order->reservations()->active()->get() as $reservation) {
            $reservation->inventoryItem->commit($reservation);
        }

        // Create shipment
        $shipment = Shipment::create([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'carrier' => 'ups', // TODO: Make configurable
            'status' => 'pending',
        ]);

        // Dispatch shipping label generation
        GenerateShippingLabelJob::dispatch($order->tenant_id, $shipment->id);

        // Emit OrderPaid event
        event(new OrderPaid($order));
    }
}

