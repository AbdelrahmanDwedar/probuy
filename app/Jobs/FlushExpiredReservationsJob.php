<?php

namespace App\Jobs;

use App\Models\StockReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FlushExpiredReservationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;

    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    public function handle(): void
    {
        $expiredReservations = StockReservation::expired()->get();

        $count = 0;
        foreach ($expiredReservations as $reservation) {
            try {
                $reservation->inventoryItem->release($reservation);
                $count++;
            } catch (\Exception $e) {
                Log::error("Failed to release expired reservation", [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($count > 0) {
            Log::info("Released expired stock reservations", ['count' => $count]);
        }
    }
}

