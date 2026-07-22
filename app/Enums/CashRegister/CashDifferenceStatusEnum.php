<?php

namespace App\Enums\CashRegister;

enum CashDifferenceStatusEnum: string
{
    case BALANCED = 'balanced';
    case SURPLUS = 'surplus';
    case SHORTAGE = 'shortage';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
