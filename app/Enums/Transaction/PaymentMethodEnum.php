<?php

namespace App\Enums\Transaction;

enum PaymentMethodEnum: string
{
    case CASH = 'cash';
    case YAPE = 'yape';
    case PLIN = 'plin';
    case CARD = 'card';
    case TRANSFER = 'transfer';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return [
            ['value' => self::CASH->value, 'label' => 'Efectivo'],
            ['value' => self::YAPE->value, 'label' => 'Yape'],
            ['value' => self::PLIN->value, 'label' => 'Plin'],
            ['value' => self::CARD->value, 'label' => 'Tarjeta'],
            ['value' => self::TRANSFER->value, 'label' => 'Transferencia'],
        ];
    }
}
