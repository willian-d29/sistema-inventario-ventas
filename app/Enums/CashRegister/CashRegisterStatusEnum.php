<?php

namespace App\Enums\CashRegister;

enum CashRegisterStatusEnum: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case REVIEWED = 'reviewed';
    case CANCELLED = 'cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
