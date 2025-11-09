<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the admin role
        $adminRole = Role::where('slug', 'admin')->first();

        if ($adminRole) {
            // Find all users with is_admin = true who don't have the admin role
            $adminUsers = User::where('is_admin', true)
                ->whereDoesntHave('roles', function ($query) {
                    $query->where('slug', 'admin');
                })
                ->get();

            foreach ($adminUsers as $user) {
                $user->roles()->attach($adminRole->id);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get the admin role
        $adminRole = Role::where('slug', 'admin')->first();

        if ($adminRole) {
            // Remove admin role from users who have is_admin = true
            // This is a data migration, so we don't want to remove roles that were legitimately assigned
            // Only remove if they still have is_admin = true (meaning they were assigned via this migration)
            $adminUsers = User::where('is_admin', true)
                ->whereHas('roles', function ($query) {
                    $query->where('slug', 'admin');
                })
                ->get();

            foreach ($adminUsers as $user) {
                $user->roles()->detach($adminRole->id);
            }
        }
    }
};
