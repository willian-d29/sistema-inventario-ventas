<?php

namespace Database\Seeders;

use App\Enums\UnitType\UnitTypeFieldsEnum;
use App\Models\UnitType;
use Illuminate\Database\Seeder;

class UnitTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unitTypes = [
            'pz'  => 'Pieza',
            'kg'  => 'Kilogramo',
            'g'   => 'Gramo',
            'l'   => 'Litro',
            'ml'  => 'Mililitro',
            'm'   => 'Metro',
            'cm'  => 'Centímetro',
            'u'   => 'Unidad',
            'tb'  => 'Tabla',
            'cj'  => 'Caja',
        ];

        foreach ($unitTypes as $symbol => $unitType) {
            UnitType::query()->updateOrCreate(
                [
                    UnitTypeFieldsEnum::NAME->value   => $unitType,
                    UnitTypeFieldsEnum::SYMBOL->value => $symbol
                ],
            );
        }

        $this->command->info(' Tipos de unidad insertados en español.');
    }
}
