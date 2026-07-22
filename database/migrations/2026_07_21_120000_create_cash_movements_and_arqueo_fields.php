<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->json('declared_amounts')->nullable()->after('expected_amount');
            $table->json('system_amounts')->nullable()->after('declared_amounts');
            $table->json('differences')->nullable()->after('system_amounts');
            $table->decimal('total_difference', 14, 2)->nullable()->after('differences');
            $table->string('difference_status', 20)->nullable()->after('total_difference');
            $table->foreignId('reviewed_by')->nullable()->after('difference_status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_notes')->nullable()->after('reviewed_at');
            $table->text('closing_notes')->nullable()->after('review_notes');
            $table->json('denominations')->nullable()->after('closing_notes');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('cash_register_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->boolean('paid_from_cash_register')->default(false)->after('cash_register_id');
        });

        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_register_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reversed_movement_id')->nullable()->constrained('cash_movements')->nullOnDelete();
            $table->string('type', 30);
            $table->string('direction', 20);
            $table->string('payment_method', 30)->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->unique('payment_id');
            $table->index(['cash_register_id', 'occurred_at']);
            $table->index(['cash_register_id', 'payment_method']);
            $table->index(['type', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_register_id');
            $table->dropColumn('paid_from_cash_register');
        });

        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'declared_amounts',
                'system_amounts',
                'differences',
                'total_difference',
                'difference_status',
                'reviewed_at',
                'review_notes',
                'closing_notes',
                'denominations',
            ]);
        });
    }
};
