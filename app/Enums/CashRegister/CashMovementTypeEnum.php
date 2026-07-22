<?php

namespace App\Enums\CashRegister;

enum CashMovementTypeEnum: string
{
    case OPENING = 'opening';
    case SALE = 'sale';
    case MANUAL_INCOME = 'manual_income';
    case WITHDRAWAL = 'withdrawal';
    case EXPENSE = 'expense';
    case REFUND = 'refund';
    case ADJUSTMENT = 'adjustment';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
