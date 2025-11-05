<?php

namespace Tests\Feature;

use App\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function complete_rbac_workflow_works_end_to_end()
    {
        // Create permissions
        $viewDashboardPermission = Permission::create([
            'name' => 'View Dashboard',
            'slug' => 'view-dashboard'
        ]);

        $manageUsersPermission = Permission::create([
            'name' => 'Manage Users',
            'slug' => 'manage-users'
        ]);

        // Create roles
        $adminRole = Role::create([
            'name' => 'Administrator',
            'slug' => 'admin'
        ]);

        $staffRole = Role::create([
            'name' => 'Staff',
            'slug' => 'staff'
        ]);

        // Assign permissions to roles
        $adminRole->permissions()->attach([$viewDashboardPermission->id, $manageUsersPermission->id]);
        $staffRole->permissions()->attach([$viewDashboardPermission->id]);

        // Create users
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password')
        ]);

        $staffUser = User::create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'password' => bcrypt('password')
        ]);

        // Assign roles to users
        $adminUser->roles()->attach($adminRole);
        $staffUser->roles()->attach($staffRole);

        // Test role checks
        $this->assertTrue($adminUser->hasRole('admin'));
        $this->assertTrue($staffUser->hasRole('staff'));
        $this->assertFalse($adminUser->hasRole('staff'));
        $this->assertFalse($staffUser->hasRole('admin'));

        // Test permission checks
        $this->assertTrue($adminUser->hasPermission('view-dashboard'));
        $this->assertTrue($adminUser->hasPermission('manage-users'));
        $this->assertTrue($staffUser->hasPermission('view-dashboard'));
        $this->assertFalse($staffUser->hasPermission('manage-users'));

        // Test middleware access
        \Route::middleware(['auth', 'role:admin'])->get('/admin-only', function () {
            return response()->json(['message' => 'Admin only content']);
        });

        \Route::middleware(['auth', 'role:staff'])->get('/staff-only', function () {
            return response()->json(['message' => 'Staff only content']);
        });

        // Admin can access admin routes
        $response = $this->actingAs($adminUser)->get('/admin-only');
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Admin only content']);

        // Staff cannot access admin routes
        $response = $this->actingAs($staffUser)->get('/admin-only');
        $response->assertStatus(302); // Redirect to login when role not found
        $response->assertRedirect('/login');

        // Staff can access staff routes
        $response = $this->actingAs($staffUser)->get('/staff-only');
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Staff only content']);

        // Admin can also access staff routes (if they have the role, but in this case admin doesn't)
        $response = $this->actingAs($adminUser)->get('/staff-only');
        $response->assertStatus(302); // Redirect to login when role not found
        $response->assertRedirect('/login');
    }

    /** @test */
    public function role_and_permission_relationships_work_correctly()
    {
        // Create permissions
        $permission1 = Permission::create([
            'name' => 'Permission 1',
            'slug' => 'permission-1'
        ]);

        $permission2 = Permission::create([
            'name' => 'Permission 2',
            'slug' => 'permission-2'
        ]);

        // Create roles
        $role1 = Role::create([
            'name' => 'Role 1',
            'slug' => 'role-1'
        ]);

        $role2 = Role::create([
            'name' => 'Role 2',
            'slug' => 'role-2'
        ]);

        // Create user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);

        // Test role-permission relationships
        $role1->permissions()->attach([$permission1->id, $permission2->id]);
        $role2->permissions()->attach([$permission2->id]); // Shared permission

        $this->assertCount(2, $role1->permissions);
        $this->assertCount(1, $role2->permissions);
        $this->assertCount(1, $permission1->roles); // only role1 has permission1
        $this->assertCount(2, $permission2->roles); // role1 and role2 both have permission2

        // Test user-role relationships
        $user->roles()->attach([$role1->id, $role2->id]);

        $this->assertCount(2, $user->roles);
        $this->assertCount(1, $role1->users);
        $this->assertCount(1, $role2->users);

        // Test user permissions through roles
        $userPermissions = $user->permissions;
        $this->assertCount(2, $userPermissions); // permission1 and permission2
        $this->assertTrue($userPermissions->contains('slug', $permission1->slug));
        $this->assertTrue($userPermissions->contains('slug', $permission2->slug));
    }

    /** @test */
    public function rbac_seeder_creates_expected_data()
    {
        // Run the seeder
        $this->artisan('db:seed', ['--class' => 'RbacSeeder']);

        // Check roles were created
        $this->assertDatabaseHas('roles', ['slug' => 'admin']);
        $this->assertDatabaseHas('roles', ['slug' => 'school-admin']);
        $this->assertDatabaseHas('roles', ['slug' => 'school-staff']);
        $this->assertDatabaseHas('roles', ['slug' => 'health-facility-admin']);
        $this->assertDatabaseHas('roles', ['slug' => 'health-facility-medical-personnel']);

        // Check permissions were created
        $this->assertDatabaseHas('permissions', ['slug' => 'view-admin-dashboard']);
        $this->assertDatabaseHas('permissions', ['slug' => 'manage-users']);
        $this->assertDatabaseHas('permissions', ['slug' => 'view-school-dashboard']);
        $this->assertDatabaseHas('permissions', ['slug' => 'manage-school-students']);
        $this->assertDatabaseHas('permissions', ['slug' => 'view-health-facility-dashboard']);
        $this->assertDatabaseHas('permissions', ['slug' => 'manage-health-facility-patients']);

        // Check role-permission relationships
        $adminRole = Role::where('slug', 'admin')->first();
        $schoolRole = Role::where('slug', 'school-staff')->first();
        $healthRole = Role::where('slug', 'health-facility-medical-personnel')->first();

        $this->assertNotNull($adminRole);
        $this->assertNotNull($schoolRole);
        $this->assertNotNull($healthRole);

        // Admin should have admin permissions
        $this->assertTrue($adminRole->permissions()->where('slug', 'view-admin-dashboard')->exists());
        $this->assertTrue($adminRole->permissions()->where('slug', 'manage-users')->exists());

        // School staff should have school permissions
        $this->assertTrue($schoolRole->permissions()->where('slug', 'view-school-dashboard')->exists());
        $this->assertTrue($schoolRole->permissions()->where('slug', 'manage-school-students')->exists());

        // Health facility staff should have health facility permissions
        $this->assertTrue($healthRole->permissions()->where('slug', 'view-health-facility-dashboard')->exists());
        $this->assertTrue($healthRole->permissions()->where('slug', 'manage-health-facility-patients')->exists());
    }

    /** @test */
    public function user_can_be_assigned_multiple_roles_and_get_combined_permissions()
    {
        // Create permissions
        $adminPermission = Permission::create([
            'name' => 'Admin Permission',
            'slug' => 'admin-permission'
        ]);

        $schoolPermission = Permission::create([
            'name' => 'School Permission',
            'slug' => 'school-permission'
        ]);

        $sharedPermission = Permission::create([
            'name' => 'Shared Permission',
            'slug' => 'shared-permission'
        ]);

        // Create roles
        $adminRole = Role::create([
            'name' => 'Admin',
            'slug' => 'admin'
        ]);

        $schoolRole = Role::create([
            'name' => 'School Staff',
            'slug' => 'school-staff'
        ]);

        // Assign permissions to roles
        $adminRole->permissions()->attach([$adminPermission->id, $sharedPermission->id]);
        $schoolRole->permissions()->attach([$schoolPermission->id, $sharedPermission->id]);

        // Create user and assign both roles
        $user = User::create([
            'name' => 'Multi-Role User',
            'email' => 'multi@example.com',
            'password' => bcrypt('password')
        ]);

        $user->roles()->attach([$adminRole->id, $schoolRole->id]);

        // Test user has both roles
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('school-staff'));

        // Test user has all permissions from both roles
        $this->assertTrue($user->hasPermission('admin-permission'));
        $this->assertTrue($user->hasPermission('school-permission'));
        $this->assertTrue($user->hasPermission('shared-permission'));

        // Test permissions collection
        $userPermissions = $user->permissions;
        $this->assertCount(3, $userPermissions);
    }
}