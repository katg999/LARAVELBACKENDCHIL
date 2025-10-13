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
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('duration_id')->nullable()->after('doctor_id');
        });

        // Map existing duration values to duration_ids
        // Assuming durations table is seeded with: 15->1, 20->2, 30->3, 45->4, 60->5
        DB::statement("
            UPDATE appointments 
            SET duration_id = CASE 
                WHEN duration = 15 THEN 1
                WHEN duration = 20 THEN 2  
                WHEN duration = 30 THEN 3
                WHEN duration = 45 THEN 4
                WHEN duration = 60 THEN 5
                ELSE 1 END
            WHERE duration IS NOT NULL
        ");

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('duration_id')->references('id')->on('durations');
            $table->dropColumn(['duration', 'amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->integer('duration')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
        });

        // Map duration_ids back to duration values
        DB::statement("
            UPDATE appointments 
            SET duration = CASE 
                WHEN duration_id = 1 THEN 15
                WHEN duration_id = 2 THEN 20  
                WHEN duration_id = 3 THEN 30
                WHEN duration_id = 4 THEN 45
                WHEN duration_id = 5 THEN 60
                ELSE 15 END,
                amount = CASE 
                WHEN duration_id = 1 THEN 5000
                WHEN duration_id = 2 THEN 7500  
                WHEN duration_id = 3 THEN 10000
                WHEN duration_id = 4 THEN 15000
                WHEN duration_id = 5 THEN 20000
                ELSE 5000 END
            WHERE duration_id IS NOT NULL
        ");

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['duration_id']);
            $table->dropColumn('duration_id');
        });
    }
};
