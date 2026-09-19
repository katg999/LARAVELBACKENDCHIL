<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One-time codes for patient login by phone
        Schema::create('patient_login_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->index();
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        // Who looked at or changed sensitive data, and when
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type');          // doctor, health_facility, patient, system
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['actor_type', 'actor_id']);
        });

        // Patient agreement to a video visit (recorded when they press Join)
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->string('type');                // video_visit
            $table->string('ip', 45)->nullable();
            $table->timestamp('granted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('patient_login_codes');
    }
};
