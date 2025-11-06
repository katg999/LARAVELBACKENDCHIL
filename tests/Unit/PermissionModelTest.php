<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PermissionModelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_can_create_a_permission()
    {
        $permission = Permission::create([
            'name' => 'Test Permission',
            'slug' => 'test-permission',
            'description' => 'A test permission for testing purposes'
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'Test Permission',
            'slug' => 'test-permission',
            'description' => 'A test permission for testing purposes'
        ]);

        $this->assertEquals('Test Permission', $permission->name);
        $this->assertEquals('test-permission', $permission->slug);
    }

    /** @test */
    public function it_belongs_to_many_roles()
    {
        $permission = Permission::create([
            'name' => 'Test Permission',
            'slug' => 'test-permission'
        ]);

        $role1 = Role::create([
            'name' => 'Test Role 1',
            'slug' => 'test-role-1'
        ]);

        $role2 = Role::create([
            'name' => 'Test Role 2',
            'slug' => 'test-role-2'
        ]);

        $permission->roles()->attach([$role1->id, $role2->id]);

        $this->assertCount(2, $permission->roles);
        $this->assertTrue($permission->roles->contains($role1));
        $this->assertTrue($permission->roles->contains($role2));
    }

    /** @test */
    public function permission_slug_must_be_unique()
    {
        Permission::create([
            'name' => 'Test Permission 1',
            'slug' => 'test-permission'
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Permission::create([
            'name' => 'Test Permission 2',
            'slug' => 'test-permission' // Duplicate slug
        ]);
    }
}