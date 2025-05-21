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
        Schema::create('patients', function (Blueprint $table) {
        $table->id(); // Only once
        $table->foreignId('health_facility_id')->constrained()->onDelete('cascade');
        $table->string('name');
        $table->string('gender');
        $table->date('birth_date');
        $table->string('contact_number')->nullable();
        $table->text('medical_history')->nullable();
        $table->timestamps(); // Only once

        // Optional: Add index for better performance
        $table->index('health_facility_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
