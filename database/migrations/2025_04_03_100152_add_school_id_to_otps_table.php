<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
{
    Schema::table('otps', function (Blueprint $table) {
        $table->foreignId('school_id')->constrained()->onDelete('cascade');
    });
}

public function down()
{
    Schema::table('otps', function (Blueprint $table) {
        $table->dropForeign(['school_id']);
        $table->dropColumn('school_id');
    });
}

};
