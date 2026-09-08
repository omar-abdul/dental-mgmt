<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = [
        'appointments',
        'treatments',
        'prescriptions',
        'invoices',
        'payments',
        'encounters',
        'lab_orders',
        'imaging_orders',
        'treatment_plans',
        'odontogram_teeth',
        'tooth_histories',
        'emergency_contacts',
        'patient_allergies',
        'patient_conditions',
        'patient_medications',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
