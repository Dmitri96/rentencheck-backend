<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the rentencheck_contracts table with all fields required for
     * comprehensive contract data storage and pension analysis.
     *
     * Squashed from original create + follow-up update migration.
     */
    public function up(): void
    {
        Schema::create('rentencheck_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rentencheck_id')->constrained()->onDelete('cascade');

            // Contract classification
            $table->enum('category', ['payout', 'pension', 'additional_income']);
            $table->string('contract')->nullable();
            $table->string('company')->nullable();
            $table->string('contract_type');

            // Financial parameters
            $table->decimal('interest_rate', 5, 2)->nullable();
            $table->year('maturity_year')->nullable();
            $table->year('pension_start_year')->nullable();
            $table->decimal('guaranteed_amount', 12, 2)->nullable();
            $table->decimal('projected_amount', 12, 2)->nullable();
            $table->decimal('monthly_amount', 10, 2)->nullable();
            $table->year('start_year')->nullable();
            $table->enum('frequency', ['Einmalig', 'Monatlich', 'Jährlich'])->nullable();

            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['rentencheck_id', 'category']);
            $table->index(['category', 'sort_order']);
            $table->index('maturity_year');
            $table->index('pension_start_year');
            $table->index('start_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rentencheck_contracts');
    }
};
