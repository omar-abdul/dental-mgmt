<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tooth_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('tooth_fdi', 2);
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->json('surfaces')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tooth_histories');
    }
};
