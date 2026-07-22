<?php

namespace App\Enums\CashRegister;

enum CashMovementDirectionEnum: string
{
    case INCOME = 'income';
    case EXPENSE = 'expense';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
