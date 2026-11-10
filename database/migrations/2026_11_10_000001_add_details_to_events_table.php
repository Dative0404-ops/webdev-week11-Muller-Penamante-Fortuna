<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Week 11 - Step 1: add the JSONB `details` column to events.
 *
 * `description` (text) and `starts_at` (dateTime) already exist from Week 10,
 * so this migration only adds the new structured-data column.
 *
 * `details` holds flexible, searchable metadata such as:
 *   - dress_code  (string)
 *   - capacity    (int)
 *   - tags        (list of strings)
 *   - category    (string)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->jsonb('details')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('details');
        });
    }
};
