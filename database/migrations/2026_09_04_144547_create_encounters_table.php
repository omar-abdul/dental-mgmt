<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounters', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dentist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('treatment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->dateTime('visited_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounters');
    }
};
