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
        Schema::create('lab_tests', function (Blueprint $table) {
    $table->id();
    $table->foreignId('school_id')->constrained();
    $table->foreignId('student_id')->constrained();
    $table->string('test_type');
    $table->text('notes')->nullable();
    $table->string('status')->default('pending');
    $table->text('results')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_tests');
    }
};
