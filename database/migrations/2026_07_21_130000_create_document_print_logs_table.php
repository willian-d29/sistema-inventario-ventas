<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_print_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cash_register_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cash_movement_id')->nullable()->constrained('cash_movements')->nullOnDelete();
            $table->string('document_type');
            $table->string('output_type');
            $table->string('action_type');
            $table->boolean('is_reprint')->default(false);
            $table->timestamp('requested_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'output_type', 'action_type']);
            $table->index(['sale_id', 'document_type']);
            $table->index(['cash_register_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_print_logs');
    }
};
