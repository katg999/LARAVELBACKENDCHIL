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
        // Try to add constraint, catch error if it already exists
        try {
            Schema::table('durations', function (Blueprint $table) {
                $table->unique(['minutes', 'duration_type'], 'durations_minutes_duration_type_unique');
            });
        } catch (\Exception $e) {
            // Constraint already exists or other error, continue
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Drop unique constraint if exists
        try {
            Schema::table('durations', function (Blueprint $table) {
                $table->dropUnique('durations_minutes_duration_type_unique');
            });
        } catch (\Exception $e) {
            // Constraint doesn't exist or other error, continue
        }

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
