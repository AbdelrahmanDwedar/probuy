<?php

namespace App\Logging;

use App\Services\TenantContext;
use Monolog\Processor\ProcessorInterface;

class TenantLogger implements ProcessorInterface
{
    public function __invoke(array $record): array
    {
        if (TenantContext::has()) {
            $record['extra']['tenant_id'] = TenantContext::getId();
        }

        $record['extra']['request_id'] = request()->header('X-Request-ID', uniqid());
        $record['extra']['ip'] = request()->ip();
        $record['extra']['user_agent'] = request()->userAgent();

        if (auth()->check()) {
            $record['extra']['user_id'] = auth()->id();
            $record['extra']['user_type'] = get_class(auth()->user());
        }

        return $record;
    }
}
