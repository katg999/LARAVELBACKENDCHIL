<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Patients can send their own insurance details; staff review them before use.
        Schema::table('member_policies', function (Blueprint $table) {
            $table->string('submitted_by')->default('staff')->after('status');   // staff or patient
            $table->string('card_image_path')->nullable()->after('submitted_by');
            $table->text('review_note')->nullable()->after('card_image_path');
            $table->timestamp('reviewed_at')->nullable()->after('review_note');
        });

        Schema::table('prescription_items', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->nullable()->after('quantity');
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('coverage_type')->default('self_pay')->after('status');      // self_pay or insurance
            $table->foreignId('member_policy_id')->nullable()->after('coverage_type')->constrained('member_policies')->nullOnDelete();
            $table->decimal('total_amount', 12, 2)->nullable()->after('member_policy_id');
            $table->decimal('insurer_amount', 12, 2)->nullable()->after('total_amount');
            $table->decimal('patient_amount', 12, 2)->nullable()->after('insurer_amount');
            // pending (asked the insurer), approved, declined, submitted (in a claim export)
            $table->string('insurance_status')->nullable()->after('patient_amount');
            $table->string('insurer_reference')->nullable()->after('insurance_status');
            $table->text('insurance_note')->nullable()->after('insurer_reference');
            // unpaid, pending (mobile money asked), paid
            $table->string('payment_status')->nullable()->after('insurance_note');
            $table->string('payment_method')->nullable()->after('payment_status');
            $table->string('payment_reference')->nullable()->index()->after('payment_method');
            $table->timestamp('priced_at')->nullable()->after('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_policy_id');
            $table->dropColumn(['coverage_type', 'total_amount', 'insurer_amount', 'patient_amount', 'insurance_status',
                'insurer_reference', 'insurance_note', 'payment_status', 'payment_method', 'payment_reference', 'priced_at']);
        });
        Schema::table('prescription_items', fn (Blueprint $t) => $t->dropColumn('unit_price'));
        Schema::table('member_policies', fn (Blueprint $t) => $t->dropColumn(['submitted_by', 'card_image_path', 'review_note', 'reviewed_at']));
    }
};
