<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            ['name' => 'View School Dashboard', 'slug' => 'view-school-dashboard', 'description' => 'Access to school dashboard and related features'],
            ['name' => 'Manage School Students', 'slug' => 'manage-school-students', 'description' => 'Create, edit, delete school students'],
            ['name' => 'View School Appointments', 'slug' => 'view-school-appointments', 'description' => 'View school appointments and lab tests'],
            ['name' => 'Manage School Appointments', 'slug' => 'manage-school-appointments', 'description' => 'Create and manage school appointments'],
            ['name' => 'View Health Facility Dashboard', 'slug' => 'view-health-facility-dashboard', 'description' => 'Access to health facility dashboard and related features'],
            ['name' => 'Manage Health Facility Patients', 'slug' => 'manage-health-facility-patients', 'description' => 'Create, edit, delete health facility patients'],
            ['name' => 'View Health Facility Appointments', 'slug' => 'view-health-facility-appointments', 'description' => 'View health facility appointments'],
            ['name' => 'Manage Health Facility Appointments', 'slug' => 'manage-health-facility-appointments', 'description' => 'Create and manage health facility appointments'],
            ['name' => 'View Admin Dashboard', 'slug' => 'view-admin-dashboard', 'description' => 'Access to admin dashboard and management features'],
            ['name' => 'Manage Users', 'slug' => 'manage-users', 'description' => 'Create, edit, delete users'],
            ['name' => 'Manage Schools', 'slug' => 'manage-schools', 'description' => 'Create, edit, delete schools'],
            ['name' => 'Manage Health Facilities', 'slug' => 'manage-health-facilities', 'description' => 'Create, edit, delete health facilities'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // Create roles
        $roles = [
            [
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'Full system access',
                'permissions' => ['view-admin-dashboard', 'manage-users', 'manage-schools', 'manage-health-facilities']
            ],
            [
                'name' => 'School Staff',
                'slug' => 'school-staff',
                'description' => 'Access to school management features',
                'permissions' => ['view-school-dashboard', 'manage-school-students', 'view-school-appointments', 'manage-school-appointments']
            ],
            [
                'name' => 'Health Facility Staff',
                'slug' => 'health-facility-staff',
                'description' => 'Access to health facility management features',
                'permissions' => ['view-health-facility-dashboard', 'manage-health-facility-patients', 'view-health-facility-appointments', 'manage-health-facility-appointments']
            ],
        ];

        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                [
                    'name' => $roleData['name'],
                    'description' => $roleData['description']
                ]
            );

            // Attach permissions to role
            $permissionSlugs = $roleData['permissions'];
            $permissions = Permission::whereIn('slug', $permissionSlugs)->get();
            $role->permissions()->sync($permissions->pluck('id'));
        }
    }
}
