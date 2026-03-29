<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()
            ->create([
                'name' => 'admin',
                'email' => 'admin@example.com',
            ])
            ->assignRole(Role::Admin);

        User::factory(1)
            ->create()
            ->each(fn (User $user) => $user->assignRole(Role::Partner));
    }
}
