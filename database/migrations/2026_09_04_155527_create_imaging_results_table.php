<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imaging_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imaging_order_id')->constrained()->cascadeOnDelete();
            $table->text('findings')->nullable();
            $table->text('impression')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imaging_results');
    }
};
