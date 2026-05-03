<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            [
                'name' => 'أدمن النظام',
                'email' => 'admin@aqdi.com',
                'password' => bcrypt('Admin@123'),
                'mobile' => '966501234500',
                'is_admin' => true,
            ],
            [
                'name' => 'أحمد المشرف',
                'email' => 'supervisor@aqdi.com',
                'password' => bcrypt('Supervisor@123'),
                'mobile' => '966501234501',
                'is_admin' => false,
            ],
            [
                'name' => 'سارة الموظفة',
                'email' => 'sara@aqdi.com',
                'password' => bcrypt('Sara@123'),
                'mobile' => '966501234502',
                'is_admin' => false,
            ],
        ];

        foreach ($admins as $admin) {
            Admin::updateOrCreate(
                ['email' => $admin['email']],
                $admin
            );
        }
    }
}
