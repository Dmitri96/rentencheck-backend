<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extends the users table with profile fields, account status, and consent flags.
     *
     * Squashed from two separate add_* migrations into one coherent extension.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Split name into first_name and last_name
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');

            // Contact and account information
            $table->string('phone')->nullable()->after('email');
            $table->string('company')->nullable()->after('phone');
            $table->string('plan')->default('free')->after('company');

            // Newsletter subscription and consent flags
            $table->boolean('newsletter')->default(false)->after('plan');
            $table->boolean('accept_terms')->default(false)->after('newsletter');
            $table->boolean('accept_privacy')->default(false)->after('accept_terms');

            // Account lifecycle status
            $table->enum('status', ['active', 'blocked', 'pending'])
                ->default('active')
                ->after('password');

            // Make name nullable since first_name/last_name are used instead
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
                'accept_privacy',
                'status',
            ]);

            $table->string('name')->nullable(false)->change();
        });
    }
};
