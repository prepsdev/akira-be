<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menus = [
            'dashboard',
            'upload',
            'queue',
            'verifikasi',
            'export',
            'repository',
            'ocr-template',
            'naming-rules',
            'users',
            'reports',
            'settings',
        ];

        $adminRole = \App\Models\Role::create([
            'name' => 'Admin',
            'description' => 'Full access to all features'
        ]);

        foreach ($menus as $menu) {
            \App\Models\RolePermission::create([
                'role_id' => $adminRole->id,
                'menu_name' => $menu,
                'can_access' => true
            ]);
        }

        $userRole = \App\Models\Role::create([
            'name' => 'Operator',
            'description' => 'Standard Operator access'
        ]);

        $userMenus = ['dashboard', 'upload', 'queue', 'verifikasi', 'repository'];
        foreach ($userMenus as $menu) {
            \App\Models\RolePermission::create([
                'role_id' => $userRole->id,
                'menu_name' => $menu,
                'can_access' => true
            ]);
        }

        \App\Models\User::create([
            'username' => 'admin',
            'name' => 'Administrator',
            'password' => bcrypt('admin123'),
            'role_id' => $adminRole->id
        ]);
    }
}
