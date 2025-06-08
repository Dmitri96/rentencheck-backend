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
        Schema::table('users', function (Blueprint $table) {
            // Split name into first_name and last_name
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            
            // Additional user information
            $table->string('phone')->nullable()->after('email');
            $table->string('company')->nullable()->after('phone');
            $table->string('plan')->default('free')->after('company');
            
            // Newsletter subscription
            $table->boolean('newsletter')->default(false)->after('plan');
            
            // Terms and privacy acceptance
            $table->boolean('accept_terms')->default(false)->after('newsletter');
            $table->boolean('accept_privacy')->default(false)->after('accept_terms');
            
            // Make name nullable since we'll use first_name and last_name
            $table->string('name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name', 
                'phone',
                'company',
                'plan',
                'newsletter',
                'accept_terms',
                'accept_privacy'
            ]);
            
            // Make name required again
            $table->string('name')->nullable(false)->change();
        });
    }
};
