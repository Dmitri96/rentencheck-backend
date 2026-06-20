<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite index for the pension settings repository hot path.
 *
 * PensionSettingRepository::getValue() filters by `key` then `is_active`;
 * a composite index lets sqlite/postgres stop after one row.
 *
 * Note: rentenchecks(user_id, client_id) already exists from the table's
 * create migration — no second index needed there.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pension_settings', function (Blueprint $table): void {
            $table->index(['key', 'is_active'], 'pension_settings_key_is_active_index');
        });
    }

    public function down(): void
    {
        Schema::table('pension_settings', function (Blueprint $table): void {
            $table->dropIndex('pension_settings_key_is_active_index');
        });
    }
};
