<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('patients', function (Blueprint $table) {
            if (!Schema::hasColumn('patients', 'name')) {
                $table->string('name')->nullable();
            }

            if (!Schema::hasColumn('patients', 'gender')) {
                $table->string('gender')->nullable();
            }

            if (!Schema::hasColumn('patients', 'birth_date')) {
                $table->dateTime('birth_date')->nullable();
            }

            if (!Schema::hasColumn('patients', 'contact_number')) {
                $table->string('contact_number')->nullable();
            }

            if (!Schema::hasColumn('patients', 'medical_history')) {
                $table->text('medical_history')->nullable();
            }

            if (!Schema::hasColumn('patients', 'health_facility_id')) {
                $table->unsignedBigInteger('health_facility_id')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'name')) {
                $table->dropColumn('name');
            }

            if (Schema::hasColumn('patients', 'gender')) {
                $table->dropColumn('gender');
            }

            if (Schema::hasColumn('patients', 'birth_date')) {
                $table->dropColumn('birth_date');
            }

            if (Schema::hasColumn('patients', 'contact_number')) {
                $table->dropColumn('contact_number');
            }

            if (Schema::hasColumn('patients', 'medical_history')) {
                $table->dropColumn('medical_history');
            }

            if (Schema::hasColumn('patients', 'health_facility_id')) {
                $table->dropColumn('health_facility_id');
            }
        });
    }
};
