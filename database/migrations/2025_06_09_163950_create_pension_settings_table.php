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
        Schema::create('pension_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('category');
            $table->decimal('value', 10, 4);
            $table->string('unit')->default('%'); // %, €, years
            $table->string('description');
            $table->string('description_de');
            $table->boolean('is_active')->default(true);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->timestamps();
            
            $table->index(['category', 'is_active']);
            $table->index(['valid_from', 'valid_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pension_settings');
    }
};
