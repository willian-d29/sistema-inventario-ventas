<?php

namespace Database\Seeders;

use App\Services\BusinessSettingsService;
use Illuminate\Database\Seeder;

class BusinessSettingsSeeder extends Seeder
{
    public function run(): void
    {
        app(BusinessSettingsService::class)->seedDefaults();
    }
}
