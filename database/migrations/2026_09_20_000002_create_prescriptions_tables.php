<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            // issued (written by a doctor) or uploaded (photo of another doctor's prescription)
            $table->string('source')->default('issued');
            // issued, pending_review, approved, rejected
            $table->string('status')->default('issued');
            $table->text('notes')->nullable();
            $table->text('review_note')->nullable();
            $table->string('image_path')->nullable();
            // requested, preparing, out_for_delivery, delivered, cancelled
            $table->string('delivery_status')->nullable();
            $table->string('delivery_address')->nullable();
            $table->string('delivery_phone')->nullable();
            $table->timestamp('delivery_requested_at')->nullable();
            $table->timestamps();
        });

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->cascadeOnDelete();
            $table->string('name');
            $table->string('dosage')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->string('instructions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
    }
};
