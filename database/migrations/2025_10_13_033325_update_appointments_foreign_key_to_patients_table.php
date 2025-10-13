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
        Schema::table('appointments', function (Blueprint $table) {
            // student_id column was already dropped, just ensure patient_id is not nullable
            $table->unsignedBigInteger('patient_id')->nullable(false)->change();
            // Foreign key constraint already exists from previous migration
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Remove the foreign key constraint to durations
            $table->dropForeign(['duration_id']);
            $table->dropColumn('duration_id');
            
            // Add back the amount column
            $table->decimal('amount', 10, 2)->nullable();
            
            // Make patient_id nullable again if needed
            $table->unsignedBigInteger('patient_id')->nullable()->change();
        });
    }
};
