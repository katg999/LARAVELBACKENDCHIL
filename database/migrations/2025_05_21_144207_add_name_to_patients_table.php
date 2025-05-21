<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('patients', 'name')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->string('name')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('patients', 'name')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }
};
