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
        Schema::table('schools', function (Blueprint $table) {
            // Drop both 'document' and 'document_path' columns if they exist
            if (Schema::hasColumn('schools', 'document')) {
                $table->dropColumn('document');
            }
            if (Schema::hasColumn('schools', 'document_path')) {
                $table->dropColumn('document_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // Recreate both columns in the reverse migration
            $table->string('document')->nullable();
            $table->string('document_path')->nullable();
        });
    }
};