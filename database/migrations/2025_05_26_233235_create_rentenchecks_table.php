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
        Schema::create('rentenchecks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            
            // Rentencheck metadata
            $table->string('status')->default('draft'); // draft, completed, archived
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            
            // Step completion tracking
            $table->json('completed_steps')->default('[]');
            
            // Step 1: Personal and Financial Information
            $table->json('step_1_data')->nullable();
            
            // Step 2: Expectations
            $table->json('step_2_data')->nullable();
            
            // Step 3: Contract Overview (basic flags)
            $table->json('step_3_data')->nullable();
            
            // Step 4: Important Aspects
            $table->json('step_4_data')->nullable();
            
            // Step 5: Conclusion
            $table->json('step_5_data')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'client_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rentenchecks');
    }
};
