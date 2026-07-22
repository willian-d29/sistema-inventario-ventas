<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('document_type', 20)->nullable()->after('id');
            $table->string('document_number', 20)->nullable()->after('document_type');
            $table->string('name_or_business_name')->nullable()->after('document_number');
            $table->string('trade_name')->nullable()->after('name_or_business_name');
        });

        DB::table('customers')
            ->whereNull('name_or_business_name')
            ->update(['name_or_business_name' => DB::raw('name')]);

        Schema::table('customers', function (Blueprint $table) {
            $table->index(['document_type', 'document_number']);
        });

        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 30);
            $table->string('series', 10);
            $table->unsignedBigInteger('current_number')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['document_type', 'series']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_register_id')->constrained()->restrictOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_type', 30);
            $table->string('document_series', 10);
            $table->unsignedBigInteger('document_number');
            $table->string('full_document_number')->unique();
            $table->string('issue_status', 30)->default('internal');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('taxable_amount', 14, 2)->default(0);
            $table->decimal('igv', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->string('status', 30)->default('paid');
            $table->timestamp('sold_at');
            $table->timestamps();

            $table->index(['cashier_id', 'sold_at']);
            $table->index(['cash_register_id', 'sold_at']);
            $table->index(['document_type', 'document_series', 'document_number']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name_snapshot');
            $table->string('product_code_snapshot')->nullable();
            $table->decimal('quantity', 20, 8);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method', 30);
            $table->decimal('amount', 14, 2);
            $table->decimal('received_amount', 14, 2)->nullable();
            $table->decimal('change_amount', 14, 2)->nullable();
            $table->string('operation_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['payment_method', 'created_at']);
        });

        DB::table('document_sequences')->insert([
            [
                'document_type' => 'sale_note',
                'series' => 'NV01',
                'current_number' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'document_type' => 'receipt',
                'series' => 'B001',
                'current_number' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'document_type' => 'invoice',
                'series' => 'F001',
                'current_number' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('document_sequences');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['document_type', 'document_number']);
            $table->dropColumn([
                'document_type',
                'document_number',
                'name_or_business_name',
                'trade_name',
            ]);
        });
    }
};
