<?php

namespace App\Enums\Category;

use App\Enums\BaseEnumInterface;
use App\Enums\BaseEnumTrait;

enum CategoryFieldsEnum: string implements BaseEnumInterface
{
    use BaseEnumTrait;

    case ID         = 'id';
    case NAME       = 'name';
    case SLUG       = 'slug';         // <-- Campo agregado
    case CREATED_AT = 'created_at';

    public static function labels(): array
    {
        return [
            self::ID->value   => "Id",
            self::NAME->value => "Nombre",     // Puedes traducirlo si gustas
            self::SLUG->value => "Slug",       // <-- Etiqueta agregada
        ];
    }
}
