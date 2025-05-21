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
    if (!Schema::hasColumn('patients', 'health_facility_id')) {
        Schema::table('patients', function (Blueprint $table) {
            $table->unsignedBigInteger('health_facility_id')->nullable();
            // Add foreign key if needed:
            // $table->foreign('health_facility_id')->references('id')->on('health_facilities');
        });
    }
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
        $table->dropForeign(['health_facility_id']);
        $table->dropColumn('health_facility_id');
        });
    }
};
