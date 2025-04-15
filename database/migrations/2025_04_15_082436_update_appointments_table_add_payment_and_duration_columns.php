<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->integer('duration')->nullable(); // 15 or 20 minute slots
            $table->decimal('amount', 10, 2)->nullable(); // Payment amount
            $table->string('payment_reference')->nullable(); // MoMo reference
            $table->string('status')->default('pending_payment')->change(); // Update default status
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['duration', 'amount', 'payment_reference']);
            // Optional: revert status change back to 'pending' only if necessary
            $table->string('status')->default('pending')->change();
        });
    }
};
