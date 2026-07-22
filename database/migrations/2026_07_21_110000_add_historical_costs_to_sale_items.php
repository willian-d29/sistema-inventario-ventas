<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 14, 2)->default(0)->after('subtotal');
            $table->decimal('cost_subtotal', 14, 2)->default(0)->after('unit_cost');
            $table->decimal('gross_profit', 14, 2)->default(0)->after('cost_subtotal');
            $table->boolean('cost_is_estimated')->default(false)->after('gross_profit');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn([
                'unit_cost',
                'cost_subtotal',
                'gross_profit',
                'cost_is_estimated',
            ]);
        });
    }
};
