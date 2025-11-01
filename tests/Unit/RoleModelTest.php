<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\Permission;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_role()
    {
        $role = Role::create([
            'name' => 'Test Role',
            'slug' => 'test-role',
            'description' => 'A test role for testing purposes'
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'Test Role',
            'slug' => 'test-role',
            'description' => 'A test role for testing purposes'
        ]);

        $this->assertEquals('Test Role', $role->name);
        $this->assertEquals('test-role', $role->slug);
    }

    /** @test */
    public function it_has_many_permissions_through_pivot_table()
    {
        $role = Role::create([
            'name' => 'Test Role',
            'slug' => 'test-role'
        ]);

        $permission1 = Permission::create([
            'name' => 'Test Permission 1',
            'slug' => 'test-permission-1'
        ]);

        $permission2 = Permission::create([
            'name' => 'Test Permission 2',
            'slug' => 'test-permission-2'
        ]);

        $role->permissions()->attach([$permission1->id, $permission2->id]);

        $this->assertCount(2, $role->permissions);
        $this->assertTrue($role->permissions->contains($permission1));
        $this->assertTrue($role->permissions->contains($permission2));
    }

    /** @test */
    public function it_belongs_to_many_users()
    {
        $role = Role::create([
            'name' => 'Test Role',
            'slug' => 'test-role'
        ]);

        $user1 = User::create([
            'name' => 'Test User 1',
            'email' => 'user1@example.com',
            'password' => bcrypt('password')
        ]);

        $user2 = User::create([
            'name' => 'Test User 2',
            'email' => 'user2@example.com',
            'password' => bcrypt('password')
        ]);

        $role->users()->attach([$user1->id, $user2->id]);

        $this->assertCount(2, $role->users);
        $this->assertTrue($role->users->contains($user1));
        $this->assertTrue($role->users->contains($user2));
    }

    /** @test */
    public function it_can_sync_permissions()
    {
        $role = Role::create([
            'name' => 'Test Role',
            'slug' => 'test-role'
        ]);

        $permission1 = Permission::create([
            'name' => 'Test Permission 1',
            'slug' => 'test-permission-1'
        ]);

        $permission2 = Permission::create([
            'name' => 'Test Permission 2',
            'slug' => 'test-permission-2'
        ]);

        $permission3 = Permission::create([
            'name' => 'Test Permission 3',
            'slug' => 'test-permission-3'
        ]);

        // Attach initial permissions
        $role->permissions()->attach([$permission1->id, $permission2->id]);

        $this->assertCount(2, $role->permissions);

        // Sync with new set of permissions
        $role->permissions()->sync([$permission2->id, $permission3->id]);

        $role->refresh();
        $this->assertCount(2, $role->permissions);
        $this->assertTrue($role->permissions->contains($permission2));
        $this->assertTrue($role->permissions->contains($permission3));
        $this->assertFalse($role->permissions->contains($permission1));
    }

    /** @test */
    public function role_slug_must_be_unique()
    {
        Role::create([
            'name' => 'Test Role 1',
            'slug' => 'test-role'
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Role::create([
            'name' => 'Test Role 2',
            'slug' => 'test-role' // Duplicate slug
        ]);
    }
}