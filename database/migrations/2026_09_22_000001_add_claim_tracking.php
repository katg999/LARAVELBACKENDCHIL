<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['appointments', 'prescriptions'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                // submitted, queried, accepted, rejected, paid. Null until the claim file is handed to the insurer.
                $t->string('claim_status')->nullable()->index();
                $t->string('claim_reference')->nullable();           // the insurer's own claim number
                $t->text('claim_note')->nullable();                  // the insurer's query or reason
                $t->decimal('claim_paid_amount', 12, 2)->nullable(); // what the insurer actually paid
                $t->timestamp('claim_submitted_at')->nullable();
                $t->timestamp('claim_updated_at')->nullable();
            });
        }

        // Claims already marked submitted before this change get a matching claim status.
        \DB::table('appointments')->where('insurance_status', 'submitted')->whereNull('claim_status')
            ->update(['claim_status' => 'submitted', 'claim_submitted_at' => now(), 'claim_updated_at' => now()]);
        \DB::table('prescriptions')->where('insurance_status', 'submitted')->whereNull('claim_status')
            ->update(['claim_status' => 'submitted', 'claim_submitted_at' => now(), 'claim_updated_at' => now()]);
    }

    public function down(): void
    {
        foreach (['appointments', 'prescriptions'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['claim_status']);
                $t->dropColumn(['claim_status', 'claim_reference', 'claim_note', 'claim_paid_amount', 'claim_submitted_at', 'claim_updated_at']);
            });
        }
    }
};
