<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Updates the rentencheck_contracts table to support comprehensive
     * contract data structure with all required fields for pension analysis.
     */
    public function up(): void
    {
        Schema::table('rentencheck_contracts', function (Blueprint $table) {
            // Add new comprehensive contract fields
            $table->string('contract')->nullable()->after('category');
            $table->string('company')->nullable()->after('contract');
            $table->decimal('interest_rate', 5, 2)->nullable()->after('contract_type');
            $table->year('maturity_year')->nullable()->after('interest_rate');
            $table->year('pension_start_year')->nullable()->after('maturity_year');
            $table->decimal('guaranteed_amount', 12, 2)->nullable()->after('pension_start_year');
            $table->decimal('projected_amount', 12, 2)->nullable()->after('guaranteed_amount');
            $table->decimal('monthly_amount', 10, 2)->nullable()->after('projected_amount');
            $table->year('start_year')->nullable()->after('monthly_amount');
            $table->enum('frequency', ['Einmalig', 'Monatlich', 'Jährlich'])->nullable()->after('start_year');
            
            // Remove old amount field as it's replaced by specific amount fields
            $table->dropColumn('amount');
            
            // Add indexes for better query performance
            $table->index(['category', 'sort_order']);
            $table->index('maturity_year');
            $table->index('pension_start_year');
            $table->index('start_year');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Restores the original table structure by removing new fields
     * and restoring the original amount column.
     */
    public function down(): void
    {
        Schema::table('rentencheck_contracts', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['category', 'sort_order']);
            $table->dropIndex(['maturity_year']);
            $table->dropIndex(['pension_start_year']);
            $table->dropIndex(['start_year']);
            
            // Remove new fields
            $table->dropColumn([
                'contract',
                'company',
                'interest_rate',
                'maturity_year',
                'pension_start_year',
                'guaranteed_amount',
                'projected_amount',
                'monthly_amount',
                'start_year',
                'frequency',
            ]);
            
            // Restore original amount field
            $table->decimal('amount', 10, 2)->nullable()->after('contract_type');
        });
    }
}; 