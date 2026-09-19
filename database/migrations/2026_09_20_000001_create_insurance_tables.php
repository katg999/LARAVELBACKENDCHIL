<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            // How visit records reach the insurer: csv (staff upload) or smart_access (integration, when available)
            $table->string('handoff_method')->default('csv');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('member_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('insurer_id')->constrained('insurers')->cascadeOnDelete();
            $table->string('member_number');
            $table->string('scheme_name')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['insurer_id', 'member_number']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->string('coverage_type')->default('self_pay')->after('payment_status');
            $table->foreignId('member_policy_id')->nullable()->after('coverage_type')
                ->constrained('member_policies')->nullOnDelete();
            $table->string('visit_code')->nullable()->after('member_policy_id');
            // pending, verified, rejected, submitted
            $table->string('insurance_status')->nullable()->after('visit_code');
            $table->text('insurance_note')->nullable()->after('insurance_status');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_policy_id');
            $table->dropColumn(['coverage_type', 'visit_code', 'insurance_status', 'insurance_note']);
        });
        Schema::dropIfExists('member_policies');
        Schema::dropIfExists('insurers');
    }
};
