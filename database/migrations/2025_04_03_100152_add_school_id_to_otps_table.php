<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Optional for PostgreSQL check workaround

return new class extends Migration
{
    public function up()
    {
        // Check if the column already exists before trying to add it
        if (!Schema::hasColumn('otps', 'school_id')) {
            Schema::table('otps', function (Blueprint $table) {
                $table->foreignId('school_id')->constrained()->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        // Drop only if it exists, to prevent issues
        if (Schema::hasColumn('otps', 'school_id')) {
            Schema::table('otps', function (Blueprint $table) {
                $table->dropForeign(['school_id']);
                $table->dropColumn('school_id');
            });
        }
    }
};
