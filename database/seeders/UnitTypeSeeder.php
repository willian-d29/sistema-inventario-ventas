<?php

namespace Database\Seeders;

use App\Enums\UnitType\UnitTypeFieldsEnum;
use App\Models\UnitType;
use Illuminate\Database\Seeder;

class UnitTypeSeeder extends Seeder
{
    public static function minimarketUnitTypes(): array
    {
        return [
            'und' => 'Unidad',
            'pack' => 'Pack',
            'kg' => 'Kilogramo',
            'g' => 'Gramo',
            'l' => 'Litro',
            'ml' => 'Mililitro',
            'cj' => 'Caja',
            'doc' => 'Docena',
            'bol' => 'Bolsa',
            'bot' => 'Botella',
            'lat' => 'Lata',
            'paq' => 'Paquete',
        ];
    }

    public function run(): void
    {
        foreach (self::minimarketUnitTypes() as $symbol => $unitType) {
            UnitType::query()->updateOrCreate([
                UnitTypeFieldsEnum::NAME->value => $unitType,
                UnitTypeFieldsEnum::SYMBOL->value => $symbol,
            ]);
        }
    }
}
