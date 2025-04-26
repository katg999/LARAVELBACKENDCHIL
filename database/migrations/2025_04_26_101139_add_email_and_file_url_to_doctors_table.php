<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('doctors', function (Blueprint $table) {
            // Add email column after name (unique)
            $table->string('email')
                  ->nullable()   //nullable for now
                  ->after('name');
                 
                  
            // Add file_url column after contact (nullable)
            $table->string('file_url')
                  ->nullable()
                  ->after('contact');
        });
    }

    public function down()
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn(['email', 'file_url']);
        });
    }
};