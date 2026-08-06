<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'employee_number' => 'SADM-001',
                'name' => 'Super Administrator',
                'username' => 'superadmin',
                'email' => 'superadmin@presensi.local',
                'role' => 'superadmin',
                'password' => 'superadmin123',
                'is_active' => true,
                'attendance_required' => false,
            ],
            [
                'employee_number' => 'ADM-001',
                'name' => 'Administrator',
                'username' => 'admin',
                'email' => 'admin@presensi.local',
                'role' => 'admin',
                'password' => 'admin123',
                'is_active' => true,
                'attendance_required' => true,
            ],
            [
                'employee_number' => 'EMP-0001',
                'name' => 'Budi Santoso',
                'username' => 'budi',
                'email' => 'budi@presensi.local',
                'role' => 'employee',
                'password' => 'budi123',
                'is_active' => true,
                'attendance_required' => true,
            ],
            [
                'employee_number' => 'EMP-0002',
                'name' => 'Siti Aminah',
                'username' => 'siti',
                'email' => 'siti@presensi.local',
                'role' => 'employee',
                'password' => 'siti123',
                'is_active' => true,
                'attendance_required' => true,
            ],
        ];

        foreach ($rows as $r) {
            User::firstOrCreate(
                ['employee_number' => $r['employee_number']],
                $r
            );
        }
    }
}
