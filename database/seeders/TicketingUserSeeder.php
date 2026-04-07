<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TicketingUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'fname' => 'Ticket',
                'lname' => 'Admin',
                'agency' => 'DILG',
                'position' => 'System Administrator',
                'region' => 'National Capital Region',
                'province' => 'Central Office',
                'office' => 'Central Office',
                'emailaddress' => 'ticket.admin@example.com',
                'mobileno' => '09170000001',
                'username' => 'ticket.admin',
                'role' => User::ROLE_SUPERADMIN,
            ],
            [
                'fname' => 'Rica',
                'lname' => 'Regional',
                'agency' => 'DILG',
                'position' => 'Project Evaluation Officer III',
                'region' => 'CAR',
                'province' => 'Regional Office',
                'office' => 'Regional Office',
                'emailaddress' => 'ticket.region@example.com',
                'mobileno' => '09170000002',
                'username' => 'ticket.region',
                'role' => User::ROLE_REGIONAL,
            ],
            [
                'fname' => 'Paolo',
                'lname' => 'Provincial',
                'agency' => 'DILG',
                'position' => 'Project Evaluation Officer II',
                'region' => 'CAR',
                'province' => 'Benguet',
                'office' => 'Provincial Office - Benguet',
                'emailaddress' => 'ticket.province@example.com',
                'mobileno' => '09170000003',
                'username' => 'ticket.province',
                'role' => User::ROLE_PROVINCIAL,
            ],
            [
                'fname' => 'Lia',
                'lname' => 'LGU',
                'agency' => 'LGU',
                'position' => 'Municipal Planning Officer',
                'region' => 'CAR',
                'province' => 'Benguet',
                'office' => 'La Trinidad',
                'emailaddress' => 'ticket.lgu@example.com',
                'mobileno' => '09170000004',
                'username' => 'ticket.lgu',
                'role' => User::ROLE_LGU,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['username' => $user['username']],
                array_merge($user, [
                    'password' => Hash::make('Password123!'),
                    'status' => 'active',
                    'access' => '',
                    'email_verified_at' => now(),
                    'verification_token' => null,
                ]),
            );
        }
    }
}
