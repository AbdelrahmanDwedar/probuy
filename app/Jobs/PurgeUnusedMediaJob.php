<?php

namespace App\Jobs;

use App\Services\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PurgeUnusedMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        public string $tenantId,
        public int $olderThanDays = 30
    ) {
        $this->onQueue('maintenance');
    }

    public function handle(): void
    {
        TenantContext::setById($this->tenantId);

        // Find media older than specified days with no associated model
        $cutoffDate = now()->subDays($this->olderThanDays);

        $orphanedMedia = Media::where('tenant_id', $this->tenantId)
            ->where('created_at', '<', $cutoffDate)
            ->whereDoesntHave('model')
            ->get();

        $count = 0;
        foreach ($orphanedMedia as $media) {
            try {
                $media->delete();
                $count++;
            } catch (\Exception $e) {
                Log::error("Failed to delete orphaned media", [
                    'media_id' => $media->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($count > 0) {
            Log::info("Purged orphaned media", [
                'tenant_id' => $this->tenantId,
                'count' => $count,
            ]);
        }
    }
}

