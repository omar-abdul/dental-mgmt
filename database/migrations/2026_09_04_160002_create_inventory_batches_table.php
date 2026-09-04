<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number');
            $table->unsignedInteger('quantity')->default(0);
            $table->date('expiry_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['inventory_item_id', 'batch_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
