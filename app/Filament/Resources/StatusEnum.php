<?php

namespace App\Filament\Resources;

use Filament\Support\Contracts\HasLabel;

enum Status: string implements HasLabel
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Declined = 'declined';
    case Canceled = 'canceled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'pending',
            self::Processing => 'processing',
            self::Completed => 'completed',
            self::Declined => 'declined',
            self::Canceled => 'canceled',
        };
    }
}
