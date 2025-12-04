<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Shipment::with(['order']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        $shipments = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $shipments->items(),
            'meta' => [
                'current_page' => $shipments->currentPage(),
                'per_page' => $shipments->perPage(),
                'total' => $shipments->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $shipment = Shipment::with(['order.lines'])->findOrFail($id);

        return response()->json([
            'data' => $shipment,
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function track(string $trackingNumber): JsonResponse
    {
        $shipment = Shipment::where('tracking_number', $trackingNumber)->firstOrFail();

        return response()->json([
            'data' => [
                'shipment' => $shipment,
                'order' => $shipment->order,
                'tracking_url' => $this->getTrackingUrl($shipment),
            ],
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function markAsDelivered(string $id): JsonResponse
    {
        $shipment = Shipment::findOrFail($id);
        $shipment->markAsDelivered();

        return response()->json([
            'data' => $shipment,
            'meta' => [
                'message' => 'Shipment marked as delivered',
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    private function getTrackingUrl(Shipment $shipment): ?string
    {
        if (!$shipment->tracking_number) {
            return null;
        }

        return match(strtolower($shipment->carrier)) {
            'ups' => "https://www.ups.com/track?tracknum={$shipment->tracking_number}",
            'fedex' => "https://www.fedex.com/fedextrack/?trknbr={$shipment->tracking_number}",
            'usps' => "https://tools.usps.com/go/TrackConfirmAction?tLabels={$shipment->tracking_number}",
            default => null,
        };
    }
}

