<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->string('tooth_fdi', 2)->nullable();
            $table->unsignedInteger('fee_cents')->default(0);
            $table->string('acceptance_status')->default('proposed');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_items');
    }
};
