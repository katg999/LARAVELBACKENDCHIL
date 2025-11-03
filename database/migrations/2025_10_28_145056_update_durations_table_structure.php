<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add price column if it doesn't exist
        Schema::table('durations', function (Blueprint $table) {
            if (!Schema::hasColumn('durations', 'price')) {
                $table->decimal('price', 10, 2)->nullable()->after('minutes');
            }
        });

        // 2. Migrate existing data safely
        if (Schema::hasColumn('durations', 'type')) {
            DB::statement("
                UPDATE durations 
                SET price = CASE 
                    WHEN type = 'general' THEN general_price 
                    ELSE specialist_price 
                END
                WHERE general_price IS NOT NULL OR specialist_price IS NOT NULL
            ");
        }

        // 3. Rename 'type' to 'duration_type' if exists
        if (Schema::hasColumn('durations', 'type')) {
            Schema::table('durations', function (Blueprint $table) {
                $table->renameColumn('type', 'duration_type');
            });
        }

        // 4. Drop old price columns if they exist
        Schema::table('durations', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('durations', 'general_price')) {
                $columnsToDrop[] = 'general_price';
            }
            if (Schema::hasColumn('durations', 'specialist_price')) {
                $columnsToDrop[] = 'specialist_price';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // 5. Add unique constraint if it doesn't exist
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 
                    FROM pg_constraint 
                    WHERE conname = 'durations_minutes_duration_type_unique'
                ) THEN
                    ALTER TABLE durations 
                    ADD CONSTRAINT durations_minutes_duration_type_unique 
                    UNIQUE (minutes, duration_type);
                END IF;
            END$$;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Drop unique constraint if exists
        DB::statement("
            DO $$
            BEGIN
                IF EXISTS (
                    SELECT 1 FROM pg_constraint 
                    WHERE conname = 'durations_minutes_duration_type_unique'
                ) THEN
                    ALTER TABLE durations DROP CONSTRAINT durations_minutes_duration_type_unique;
                END IF;
            END$$;
        ");

        // 2. Rename 'duration_type' back to 'type' if exists
        if (Schema::hasColumn('durations', 'duration_type')) {
            Schema::table('durations', function (Blueprint $table) {
                $table->renameColumn('duration_type', 'type');
            });
        }

        // 3. Add back old price columns if missing
        Schema::table('durations', function (Blueprint $table) {
            if (!Schema::hasColumn('durations', 'general_price')) {
                $table->decimal('general_price', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('durations', 'specialist_price')) {
                $table->decimal('specialist_price', 10, 2)->nullable();
            }
        });

        // 4. Drop 'price' column if exists
        Schema::table('durations', function (Blueprint $table) {
            if (Schema::hasColumn('durations', 'price')) {
                $table->dropColumn('price');
            }
        });
    }
};
