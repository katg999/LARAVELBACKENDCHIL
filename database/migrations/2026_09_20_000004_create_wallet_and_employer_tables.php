<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->unique()->constrained('patients')->cascadeOnDelete();
            $table->decimal('balance', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->string('type');                          // credit or debit
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('employers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_phone')->nullable();
            // Total the employer will pay for in one calendar month. Null means no cap.
            $table->decimal('monthly_cap', 12, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('employer_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('employers')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['employer_id', 'patient_id']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            // mobile_money, wallet, insurance, employer, cash
            $table->string('payment_method')->nullable()->after('payment_status');
            $table->foreignId('employer_id')->nullable()->after('member_policy_id')->constrained('employers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employer_id');
            $table->dropColumn('payment_method');
        });
        Schema::dropIfExists('employer_members');
        Schema::dropIfExists('employers');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
