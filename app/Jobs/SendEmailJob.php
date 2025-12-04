<?php

namespace App\Jobs;

use App\Services\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;
    public int $timeout = 10;

    public function __construct(
        public string $tenantId,
        public string $to,
        public string $subject,
        public string $template,
        public array $data = []
    ) {
        $this->onQueue('low');
    }

    public function handle(): void
    {
        TenantContext::setById($this->tenantId);

        try {
            // TODO: Implement actual email sending with templates
            // For now, log the email
            Log::info("Email sent", [
                'tenant_id' => $this->tenantId,
                'to' => $this->to,
                'subject' => $this->subject,
                'template' => $this->template,
            ]);

            // Example with Laravel Mail:
            // Mail::to($this->to)->send(new OrderConfirmation($this->data));
        } catch (\Exception $e) {
            Log::error("Failed to send email", [
                'tenant_id' => $this->tenantId,
                'to' => $this->to,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

