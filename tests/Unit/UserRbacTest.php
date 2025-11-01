<?php

namespace Tests\Unit;

use App\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRbacTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_have_multiple_roles()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);

        $role1 = Role::create([
            'name' => 'Role 1',
            'slug' => 'role-1'
        ]);

        $role2 = Role::create([
            'name' => 'Role 2',
            'slug' => 'role-2'
        ]);

        $user->roles()->attach([$role1->id, $role2->id]);

        $this->assertCount(2, $user->roles);
        $this->assertTrue($user->roles->contains($role1));
        $this->assertTrue($user->roles->contains($role2));
    }

    /** @test */
    public function user_can_check_if_they_have_a_specific_role()
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

        $this->assertFalse($user->hasRole('test-role'));

        $user->roles()->attach($role);

        $this->assertTrue($user->hasRole('test-role'));
    }

    /** @test */
    public function user_can_check_if_they_have_a_specific_permission()
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

        $permission = Permission::create([
            'name' => 'Test Permission',
            'slug' => 'test-permission'
        ]);

        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->assertTrue($user->hasPermission('test-permission'));
    }

    /** @test */
    public function user_returns_false_for_permission_they_dont_have()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);

        $this->assertFalse($user->hasPermission('non-existent-permission'));
    }

    /** @test */
    public function user_can_assign_a_role()
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

        $user->assignRole('test-role');

        $this->assertTrue($user->hasRole('test-role'));
        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->id,
            'role_id' => $role->id
        ]);
    }

    /** @test */
    public function assigning_same_role_twice_does_not_create_duplicates()
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

        $user->assignRole('test-role');
        $user->assignRole('test-role'); // Assign again

        $this->assertCount(1, $user->roles);
        $this->assertEquals(1, \DB::table('role_user')->where('user_id', $user->id)->where('role_id', $role->id)->count());
    }

    /** @test */
    public function user_can_remove_a_role()
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
        $this->assertTrue($user->hasRole('test-role'));

        $user->removeRole('test-role');

        $this->assertFalse($user->hasRole('test-role'));
        $this->assertDatabaseMissing('role_user', [
            'user_id' => $user->id,
            'role_id' => $role->id
        ]);
    }

    /** @test */
    public function removing_non_assigned_role_does_not_cause_errors()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);

        // This should not throw an exception
        $user->removeRole('non-existent-role');

        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    /** @test */
    public function user_permissions_relationship_works_correctly()
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

        $permission1 = Permission::create([
            'name' => 'Permission 1',
            'slug' => 'permission-1'
        ]);

        $permission2 = Permission::create([
            'name' => 'Permission 2',
            'slug' => 'permission-2'
        ]);

        $role->permissions()->attach([$permission1->id, $permission2->id]);
        $user->roles()->attach($role);

        $userPermissions = $user->permissions;

        $this->assertCount(2, $userPermissions);
        $this->assertTrue($userPermissions->contains('slug', $permission1->slug));
        $this->assertTrue($userPermissions->contains('slug', $permission2->slug));
    }

    /** @test */
    public function user_permissions_from_multiple_roles_are_combined()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);

        $role1 = Role::create([
            'name' => 'Role 1',
            'slug' => 'role-1'
        ]);

        $role2 = Role::create([
            'name' => 'Role 2',
            'slug' => 'role-2'
        ]);

        $permission1 = Permission::create([
            'name' => 'Permission 1',
            'slug' => 'permission-1'
        ]);

        $permission2 = Permission::create([
            'name' => 'Permission 2',
            'slug' => 'permission-2'
        ]);

        $permission3 = Permission::create([
            'name' => 'Permission 3',
            'slug' => 'permission-3'
        ]);

        $role1->permissions()->attach([$permission1->id, $permission2->id]);
        $role2->permissions()->attach([$permission2->id, $permission3->id]); // permission2 is shared
        $user->roles()->attach([$role1->id, $role2->id]);

        $userPermissions = $user->permissions;

        $this->assertCount(3, $userPermissions);
        $this->assertTrue($userPermissions->contains('slug', $permission1->slug));
        $this->assertTrue($userPermissions->contains('slug', $permission2->slug));
        $this->assertTrue($userPermissions->contains('slug', $permission3->slug));
    }
}