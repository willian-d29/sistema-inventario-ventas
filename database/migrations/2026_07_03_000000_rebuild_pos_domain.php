<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'vendedor', 'cliente', 'cajero') NOT NULL DEFAULT 'cajero'");
        }
        DB::table('users')->where('role', 'vendedor')->update(['role' => 'cajero']);
        DB::table('users')->where('role', 'cliente')->delete();
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'cajero') NOT NULL DEFAULT 'cajero'");

            DB::statement('ALTER TABLE customers MODIFY email VARCHAR(255) NULL');
            DB::statement('ALTER TABLE customers MODIFY phone VARCHAR(255) NULL');
        }
        Schema::table('customers', fn (Blueprint $table) => $table->unique('email'));

        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode')->nullable()->unique()->after('product_code');
        });

        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('opening_amount', 14, 2)->default(0);
            $table->decimal('closing_amount', 14, 2)->nullable();
            $table->decimal('expected_amount', 14, 2)->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->string('status', 20)->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('cashier_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('cash_register_id')->nullable()->after('cashier_id')->constrained()->nullOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE transactions MODIFY amount DECIMAL(14,2) NOT NULL');
        }
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('received_amount', 14, 2)->nullable()->after('amount');
            $table->decimal('change_amount', 14, 2)->default(0)->after('received_amount');
            $table->string('reference')->nullable()->after('paid_through');
            $table->string('status', 20)->default('confirmed')->after('reference');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->decimal('quantity', 20, 8);
            $table->decimal('previous_quantity', 20, 8);
            $table->decimal('new_quantity', 20, 8);
            $table->nullableMorphs('reference');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', fn (Blueprint $table) => $table->dropConstrainedForeignId('user_id'));
        Schema::dropIfExists('stock_movements');

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['received_amount', 'change_amount', 'reference', 'status']);
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE transactions MODIFY amount VARCHAR(255) NOT NULL');
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_register_id');
            $table->dropConstrainedForeignId('cashier_id');
        });
        Schema::dropIfExists('cash_registers');

        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('barcode'));
        Schema::table('customers', fn (Blueprint $table) => $table->dropUnique(['email']));
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE customers MODIFY email VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE customers MODIFY phone VARCHAR(255) NOT NULL');
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'vendedor', 'cliente', 'cajero') NOT NULL DEFAULT 'cliente'");
        }
        DB::table('users')->where('role', 'cajero')->update(['role' => 'vendedor']);
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'vendedor', 'cliente') NOT NULL DEFAULT 'cliente'");
        }
    }
};
