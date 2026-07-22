<?php

namespace App\Enums\Product;

enum StockMovementTypeEnum: string
{
    case SALE = 'sale';
    case SALE_VOID = 'sale_void';
    case PURCHASE = 'purchase';
    case ADJUSTMENT_IN = 'adjustment_in';
    case ADJUSTMENT_OUT = 'adjustment_out';
    case LOSS = 'loss';
    case RETURN = 'return';
}
