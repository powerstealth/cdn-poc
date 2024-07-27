<?php

namespace Modules\Auth\Infrastructure\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AuthPermissionsSeeder extends Seeder
{
    /**
     * Auth Seeding
     */
    public function run()
    {
        Role::create(['name' => 'admin']);
    }
}