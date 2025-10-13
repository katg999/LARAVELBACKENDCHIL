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
        Schema::table('durations', function (Blueprint $table) {
            $table->renameColumn('amount', 'general_price');
            $table->decimal('specialist_price', 10, 2)->nullable()->after('general_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('durations', function (Blueprint $table) {
            $table->dropColumn('specialist_price');
            $table->renameColumn('general_price', 'amount');
        });
    }
};
