<?php

namespace App\Jobs;

use App\Models\Shipment;
use App\Services\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateShippingLabelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 60;

    public function __construct(
        public string $tenantId,
        public string $shipmentId
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        TenantContext::setById($this->tenantId);

        $shipment = Shipment::findOrFail($this->shipmentId);

        try {
            // TODO: Integrate with carrier API (UPS, FedEx, USPS, etc.)
            // For now, simulate label generation
            $label = $this->generateLabelWithCarrier($shipment);

            $shipment->update([
                'status' => 'label_generated',
                'tracking_number' => $label['tracking_number'],
                'label_url' => $label['label_url'],
                'estimated_delivery_at' => $label['estimated_delivery_at'] ?? null,
            ]);

            Log::info("Shipping label generated", [
                'tenant_id' => $this->tenantId,
                'shipment_id' => $this->shipmentId,
                'tracking_number' => $label['tracking_number'],
            ]);
        } catch (\Exception $e) {
            $shipment->update(['status' => 'failed']);
            throw $e;
        }
    }

    private function generateLabelWithCarrier(Shipment $shipment): array
    {
        // Simulate carrier API call
        // In production, integrate with ShipStation, EasyPost, or carrier APIs
        return [
            'tracking_number' => strtoupper($shipment->carrier ?? 'UPS') . uniqid(),
            'label_url' => 'https://example.com/labels/' . $shipment->id . '.pdf',
            'estimated_delivery_at' => now()->addDays(3),
        ];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Failed to generate shipping label", [
            'tenant_id' => $this->tenantId,
            'shipment_id' => $this->shipmentId,
            'error' => $exception->getMessage(),
        ]);
    }
}

