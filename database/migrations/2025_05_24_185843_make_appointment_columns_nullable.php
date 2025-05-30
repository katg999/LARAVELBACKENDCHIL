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
            $table->unsignedBigInteger('school_id')->nullable()->change();
            $table->unsignedBigInteger('health_facility_id')->nullable()->change();
            $table->unsignedBigInteger('patient_id')->nullable()->change();
            $table->unsignedBigInteger('student_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->nullable(false)->change();
            $table->unsignedBigInteger('health_facility_id')->nullable(false)->change();
            $table->unsignedBigInteger('patient_id')->nullable(false)->change();
            $table->unsignedBigInteger('student_id')->nullable(false)->change();
        });
    }
};
