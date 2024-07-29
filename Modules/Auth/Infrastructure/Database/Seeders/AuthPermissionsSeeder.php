<?php

namespace Modules\Auth\Infrastructure\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Domain\Models\User;
use Spatie\Permission\Models\Role;

class AuthPermissionsSeeder extends Seeder
{
    /**
     * Auth Seeding
     */
    public function run()
    {
        try {
            $user=User::create([
                'email' => 'clyuptv@clyup.com',
                'magento_user_id' => "admin",

            ]);
            $role=Role::create(['name' => 'admin']);
            $user->assignRole($role);
        }catch (\Exception $e) {
            echo "Skip seeding";
        }

    }
}