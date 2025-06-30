<?php 

namespace App\Enums\Order;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum OrderStatusEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case PAID         = 'paid';
    case UNPAID       = 'unpaid';
    case PARTIAL_PAID = 'partial_paid';
    case OVER_PAID    = 'over_paid';

    public static function labels(): array
    {
        return [
            self::PAID->value         => "Pagado",
            self::UNPAID->value       => "No pagado",
            self::PARTIAL_PAID->value => "Pago parcial",
            self::OVER_PAID->value    => "Sobrepago",
        ];
    }
}
