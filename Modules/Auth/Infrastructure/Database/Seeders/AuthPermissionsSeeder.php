<?php

namespace Modules\Auth\Infrastructure\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Domain\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AuthPermissionsSeeder extends Seeder
{
    /**
     * Auth Seeding
     */
    public function run()
    {
        $user=User::create([
            'email' => 'clyuptv@clyup.com',
            'magento_user_id' => "admin",

        ]);
        $role=Role::create(['name' => 'admin']);
        $user->assignRole($role);
    }
}