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
        Schema::table('lab_tests', function (Blueprint $table) {
            // Drop the old foreign key constraint
            $table->dropForeign(['student_id']);
            
            // Rename student_id column to patient_id
            $table->renameColumn('student_id', 'patient_id');
            
            // Add new foreign key constraint to patients table
            $table->foreign('patient_id')->references('id')->on('patients');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_tests', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['patient_id']);
            
            // Rename patient_id column back to student_id
            $table->renameColumn('patient_id', 'student_id');
            
            // Add back the old foreign key constraint to students table
            $table->foreign('student_id')->references('id')->on('students');
        });
    }
};
