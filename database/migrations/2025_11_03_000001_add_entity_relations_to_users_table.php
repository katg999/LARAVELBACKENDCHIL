<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEntityRelationsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->nullable()->after('email');
            $table->unsignedBigInteger('health_facility_id')->nullable()->after('school_id');
            
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('health_facility_id')->references('id')->on('health_facilities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropForeign(['health_facility_id']);
            $table->dropColumn(['school_id', 'health_facility_id']);
        });
    }
}
