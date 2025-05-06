<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('doctor_availabilities', function (Blueprint $table) {
        $table->id();
        $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
        $table->string('day'); // e.g. "monday"
        $table->boolean('available')->default(false);
        $table->integer('max_appointments')->default(0);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_availabilities');
    }
};
