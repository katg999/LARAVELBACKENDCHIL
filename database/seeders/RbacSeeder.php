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
            // School permissions
            ['name' => 'View School Dashboard', 'slug' => 'view-school-dashboard', 'description' => 'Access to school dashboard and related features'],
            ['name' => 'Manage School Students', 'slug' => 'manage-school-students', 'description' => 'Create, edit, delete school students'],
            ['name' => 'View School Appointments', 'slug' => 'view-school-appointments', 'description' => 'View school appointments and lab tests'],
            ['name' => 'Manage School Appointments', 'slug' => 'manage-school-appointments', 'description' => 'Create and manage school appointments'],
            ['name' => 'Manage School Staff', 'slug' => 'manage-school-staff', 'description' => 'Send invitations and manage school staff members'],
            
            // Health Facility permissions
            ['name' => 'View Health Facility Dashboard', 'slug' => 'view-health-facility-dashboard', 'description' => 'Access to health facility dashboard and related features'],
            ['name' => 'Manage Health Facility Patients', 'slug' => 'manage-health-facility-patients', 'description' => 'Create, edit, delete health facility patients'],
            ['name' => 'View Health Facility Appointments', 'slug' => 'view-health-facility-appointments', 'description' => 'View health facility appointments'],
            ['name' => 'Manage Health Facility Appointments', 'slug' => 'manage-health-facility-appointments', 'description' => 'Create and manage health facility appointments'],
            ['name' => 'Manage Health Facility Staff', 'slug' => 'manage-health-facility-staff', 'description' => 'Send invitations and manage health facility staff members'],
            
            // Admin permissions
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
                'name' => 'School Admin',
                'slug' => 'school-admin',
                'description' => 'Full access to school management features including staff management',
                'permissions' => [
                    'view-school-dashboard', 
                    'manage-school-students', 
                    'view-school-appointments', 
                    'manage-school-appointments',
                    'manage-school-staff'
                ]
            ],
            [
                'name' => 'School Staff',
                'slug' => 'school-staff',
                'description' => 'Access to school management features',
                'permissions' => [
                    'view-school-dashboard', 
                    'manage-school-students', 
                    'view-school-appointments', 
                    'manage-school-appointments'
                ]
            ],
            [
                'name' => 'Health Facility Admin',
                'slug' => 'health-facility-admin',
                'description' => 'Full access to health facility management features including staff management',
                'permissions' => [
                    'view-health-facility-dashboard', 
                    'manage-health-facility-patients', 
                    'view-health-facility-appointments', 
                    'manage-health-facility-appointments',
                    'manage-health-facility-staff'
                ]
            ],
            [
                'name' => 'Health Facility Medical Personnel',
                'slug' => 'health-facility-medical-personnel',
                'description' => 'Access to health facility patient care and appointment features',
                'permissions' => [
                    'view-health-facility-dashboard', 
                    'manage-health-facility-patients', 
                    'view-health-facility-appointments', 
                    'manage-health-facility-appointments'
                ]
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
