<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->string('category')->default('general');
            $table->unsignedInteger('amount_cents');
            $table->date('expense_date');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('daily_cash_closings', function (Blueprint $table) {
            $table->id();
            $table->date('closing_date')->unique();
            $table->unsignedInteger('system_cash_total_cents');
            $table->unsignedInteger('counted_cash_cents');
            $table->integer('difference_cents');
            $table->foreignId('closed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('closed_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('total_cents');
            $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->date('due_date');
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->string('provider');
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mobile_money_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->date('reconciliation_date');
            $table->string('provider');
            $table->unsignedInteger('transaction_count');
            $table->unsignedInteger('system_total_cents');
            $table->unsignedInteger('provider_total_cents');
            $table->integer('difference_cents');
            $table->foreignId('reconciled_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('reconciled_at');
            $table->string('status')->default('reconciled');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['reconciliation_date', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_money_reconciliations');
        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('installments');
        Schema::dropIfExists('payment_plans');
        Schema::dropIfExists('daily_cash_closings');
        Schema::dropIfExists('expenses');
    }
};
