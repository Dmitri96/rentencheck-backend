<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Frozen copy of the pension analysis (incl. parameters_used) taken at
 * completion time, so re-generated PDFs reproduce identical numbers even
 * after the admin changes the reference values.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentenchecks', function (Blueprint $table) {
            $table->json('analysis_snapshot')->nullable()->after('step_5_data');
        });
    }

    public function down(): void
    {
        Schema::table('rentenchecks', function (Blueprint $table) {
            $table->dropColumn('analysis_snapshot');
        });
    }
};
