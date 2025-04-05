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
        Schema::table('otps', function (Blueprint $table) {
            // Add the email column as nullable first
            $table->string('email')->nullable(); // Set it nullable initially
            $table->dropConstrainedForeignId('school_id');
        });

        // Update existing records with a default email
        DB::table('otps')->update(['email' => 'briankuberwa@gmail.com']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            // Drop the email column
            $table->dropColumn('email');
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
        });
    }
};