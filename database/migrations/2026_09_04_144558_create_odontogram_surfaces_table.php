<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odontogram_surfaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('odontogram_tooth_id')->constrained('odontogram_teeth')->cascadeOnDelete();
            $table->string('surface', 1);
            $table->timestamps();

            $table->unique(['odontogram_tooth_id', 'surface']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odontogram_surfaces');
    }
};
