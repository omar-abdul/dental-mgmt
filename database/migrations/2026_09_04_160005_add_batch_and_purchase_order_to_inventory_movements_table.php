<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('inventory_batch_id')
                ->nullable()
                ->after('inventory_item_id')
                ->constrained('inventory_batches')
                ->nullOnDelete();
            $table->foreignId('purchase_order_id')
                ->nullable()
                ->after('inventory_batch_id')
                ->constrained('purchase_orders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_order_id');
            $table->dropConstrainedForeignId('inventory_batch_id');
        });
    }
};
