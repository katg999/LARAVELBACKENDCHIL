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
            $table->enum('type', ['general', 'specialist'])->after('specialist_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('durations', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
