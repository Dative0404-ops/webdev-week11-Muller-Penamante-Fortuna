<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Week 11 - Steps 6 & 8: indexes that survive migrate:fresh --seed.
 *
 *   1. GIN on `details` (jsonb)              -> speeds up JSONB containment (@>)
 *   2. B-tree expression index on
 *      (details->>'category')                -> speeds up the arrow-form equality
 *   3. GIN on to_tsvector('english', desc)   -> speeds up full-text search
 *
 * Laravel's schema builder supports `$table->gin('details')`, but expression
 * indexes (2 and 3) cannot be created with the schema builder, so they are
 * added with a raw DB statement.
 */
return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL-specific indexes are not needed by the SQLite test suite.
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // 1) GIN index on the whole JSONB column.
        //    Supports `details @> '{"category":"astronomy"}'`.
        Schema::table('events', function ($table) {
            $table->index(['details'], 'events_details_gin_idx', 'gin');
        });

        // 2) Expression B-tree index on the extracted `category` key.
        //    Supports `where details->>'category' = 'astronomy'`.
        DB::statement("CREATE INDEX events_details_category_idx ON events ((details->>'category'));");

        // 3) GIN index on the full-text vector of `description`.
        //    Supports `to_tsvector('english', description) @@ plainto_tsquery(...)`.
        DB::statement("CREATE INDEX events_description_fts_idx ON events USING gin (to_tsvector('english', description));");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS events_description_fts_idx;');
        DB::statement('DROP INDEX IF EXISTS events_details_category_idx;');

        Schema::table('events', function ($table) {
            $table->dropIndex(['details']);
        });
    }
};
