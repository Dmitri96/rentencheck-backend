<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rentencheck_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rentencheck_id')->constrained()->onDelete('cascade');
            
            // Contract details
            $table->enum('category', ['payout', 'pension', 'additional_income']);
            $table->string('contract_type'); // e.g., "Lebensversicherung", "Riester", etc.
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            
            // Ordering within category
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['rentencheck_id', 'category']);
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
