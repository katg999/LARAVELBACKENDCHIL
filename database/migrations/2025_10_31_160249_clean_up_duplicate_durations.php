<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Find duplicates based on (minutes, duration_type) - database agnostic approach
        $duplicateGroups = DB::table('durations')
            ->select('minutes', 'duration_type', DB::raw('MIN(id) as keep_id'))
            ->groupBy('minutes', 'duration_type')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $keepId = (int) $group->keep_id;

            // Get all IDs for this group
            $allIds = DB::table('durations')
                ->where('minutes', $group->minutes)
                ->where('duration_type', $group->duration_type)
                ->pluck('id')
                ->toArray();

            // Remove the keep_id from the list (these are the IDs to delete)
            $deleteIds = array_filter($allIds, fn($id) => $id != $keepId);

            if (!empty($deleteIds)) {
                // 2. Update appointments to point to the kept duration
                DB::table('appointments')
                    ->whereIn('duration_id', $deleteIds)
                    ->update(['duration_id' => $keepId]);

                // 3. Delete the duplicate durations
                DB::table('durations')
                    ->whereIn('id', $deleteIds)
                    ->delete();
            }
        }
    }

    public function down(): void
    {
        // Cannot fully reverse data deletion
    }
};
