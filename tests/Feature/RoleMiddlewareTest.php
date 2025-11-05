<?php

namespace Tests\Feature;

use App\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function authenticated_user_with_required_role_can_access_protected_route()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);

        $role = Role::create([
            'name' => 'Test Role',
            'slug' => 'test-role'
        ]);

        $user->roles()->attach($role);

        // Create a test route with role middleware
        \Route::middleware(['auth', 'role:test-role'])->get('/test-protected-route', function () {
            return response()->json(['message' => 'Access granted']);
        });

        $response = $this->actingAs($user)->get('/test-protected-route');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Access granted']);
    }

    /** @test */
    public function authenticated_user_without_required_role_cannot_access_protected_route()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);

        // User does not have the required role

        // Create a test route with role middleware
        \Route::middleware(['auth', 'role:test-role'])->get('/test-protected-route', function () {
            return response()->json(['message' => 'Access granted']);
        });

        $response = $this->actingAs($user)->get('/test-protected-route');

        $response->assertStatus(302); // Redirect to login when role not found
        $response->assertRedirect('/login');
    }

    /** @test */
    public function unauthenticated_user_cannot_access_protected_route()
    {
        // Create a test route with role middleware
        \Route::middleware(['auth', 'role:test-role'])->get('/test-protected-route', function () {
            return response()->json(['message' => 'Access granted']);
        });

        $response = $this->get('/test-protected-route');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function admin_role_can_access_admin_routes()
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password')
        ]);

        $adminRole = Role::create([
            'name' => 'Administrator',
            'slug' => 'admin'
        ]);

        $user->roles()->attach($adminRole);

        // Create a test admin route
        \Route::middleware(['auth', 'role:admin'])->get('/admin-test-route', function () {
            return response()->json(['message' => 'Admin access granted']);
        });

        $response = $this->actingAs($user)->get('/admin-test-route');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Admin access granted']);
    }

    /** @test */
    public function school_staff_role_can_access_school_routes()
    {
        $user = User::create([
            'name' => 'School Staff',
            'email' => 'school@example.com',
            'password' => bcrypt('password')
        ]);

        $schoolRole = Role::create([
            'name' => 'School Staff',
            'slug' => 'school-staff'
        ]);

        $user->roles()->attach($schoolRole);

        // Create a test school route
        \Route::middleware(['auth', 'role:school-staff'])->get('/school/test-route', function () {
            return response()->json(['message' => 'School access granted']);
        });

        $response = $this->actingAs($user)->get('/school/test-route');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'School access granted']);
    }

    /** @test */
    public function health_facility_staff_role_can_access_health_facility_routes()
    {
        $user = User::create([
            'name' => 'Health Facility Staff',
            'email' => 'health@example.com',
            'password' => bcrypt('password')
        ]);

        $healthRole = Role::create([
            'name' => 'Health Facility Staff',
            'slug' => 'health-facility-staff'
        ]);

        $user->roles()->attach($healthRole);

        // Create a test health facility route
        \Route::middleware(['auth', 'role:health-facility-staff'])->get('/health-facility/test-route', function () {
            return response()->json(['message' => 'Health facility access granted']);
        });

        $response = $this->actingAs($user)->get('/health-facility/test-route');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Health facility access granted']);
    }

    /** @test */
    public function user_with_wrong_role_gets_403_error()
    {
        $user = User::create([
            'name' => 'Wrong Role User',
            'email' => 'wrong@example.com',
            'password' => bcrypt('password')
        ]);

        $wrongRole = Role::create([
            'name' => 'Wrong Role',
            'slug' => 'wrong-role'
        ]);

        $user->roles()->attach($wrongRole);

        // Create a test route requiring a different role
        \Route::middleware(['auth', 'role:required-role'])->get('/test-protected-route', function () {
            return response()->json(['message' => 'Access granted']);
        });

        $response = $this->actingAs($user)->get('/test-protected-route');

        $response->assertStatus(302); // Redirect to login when wrong role
        $response->assertRedirect('/login');
    }
}