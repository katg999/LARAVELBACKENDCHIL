<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('meeting_room')->nullable()->unique()->after('payment_status');
        });

        // Give existing appointments their own private room.
        DB::table('appointments')->whereNull('meeting_room')->orderBy('id')->each(function ($row) {
            DB::table('appointments')->where('id', $row->id)
                ->update(['meeting_room' => 'ketiai-' . Str::lower(Str::random(24))]);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique(['meeting_room']);
            $table->dropColumn('meeting_room');
        });
    }
};
