<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        (new Collection(Permission::cases()))
            ->each(fn (Permission $permission) => SpatiePermission::create(['name' => $permission->value]));

        SpatieRole::create(['name' => Role::Admin->value])
            ->givePermissionTo(Permission::ViewRestaurants, Permission::ViewOthersRestaurants);

        SpatieRole::create(['name' => Role::Partner->value])
            ->givePermissionTo(Permission::ViewRestaurants);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
