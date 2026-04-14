<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('tbusers') || !Schema::hasTable('activity_logs')) {
            return;
        }

        if (ActivityLog::query()->exists()) {
            return;
        }

        $admin = User::query()->firstOrCreate(
            ['username' => 'activity.admin'],
            [
                'fname' => 'System',
                'lname' => 'Administrator',
                'agency' => 'DILG',
                'position' => 'Super Administrator',
                'region' => 'Region I',
                'province' => 'La Union',
                'office' => 'Regional Office',
                'emailaddress' => 'activity.admin@example.com',
                'mobileno' => '09171234567',
                'password' => Hash::make('Password123!'),
                'role' => User::ROLE_SUPERADMIN,
                'status' => 'active',
                'access' => User::ACCESS_SCOPE_ALL,
                'email_verified_at' => now(),
            ],
        );

        $staff = User::query()->firstOrCreate(
            ['username' => 'activity.user'],
            [
                'fname' => 'Audit',
                'lname' => 'Officer',
                'agency' => 'DILG',
                'position' => 'Provincial Focal',
                'region' => 'Region I',
                'province' => 'La Union',
                'office' => 'Provincial Office',
                'emailaddress' => 'activity.user@example.com',
                'mobileno' => '09179876543',
                'password' => Hash::make('Password123!'),
                'role' => User::ROLE_PROVINCIAL,
                'status' => 'active',
                'access' => null,
                'email_verified_at' => now(),
            ],
        );

        $timezone = config('app.timezone', 'UTC');
        $baseTime = Carbon::now()->subDays(2)->startOfHour();

        $entries = [
            [
                'user' => $staff,
                'action' => ActivityLog::ACTION_REGISTER,
                'description' => 'Registered a new user account.',
                'ip_address' => '192.168.1.25',
                'created_at' => $baseTime->copy()->addMinutes(5),
                'properties' => ['module' => 'registration'],
            ],
            [
                'user' => $staff,
                'action' => ActivityLog::ACTION_LOGIN,
                'description' => 'User signed in successfully.',
                'ip_address' => '192.168.1.25',
                'created_at' => $baseTime->copy()->addMinutes(8),
                'properties' => ['module' => 'auth'],
            ],
            [
                'user' => null,
                'action' => ActivityLog::ACTION_FAILED_LOGIN,
                'description' => 'Failed login attempt for username "activity.user".',
                'ip_address' => '192.168.1.99',
                'created_at' => $baseTime->copy()->addMinutes(12),
                'properties' => ['username' => 'activity.user', 'reason' => 'invalid_credentials'],
            ],
            [
                'user' => $admin,
                'action' => ActivityLog::ACTION_CREATE,
                'description' => 'Created project record PRJ-2026-001.',
                'ip_address' => '192.168.1.10',
                'created_at' => $baseTime->copy()->addMinutes(18),
                'properties' => ['module' => 'projects', 'project_code' => 'PRJ-2026-001'],
            ],
            [
                'user' => $admin,
                'action' => ActivityLog::ACTION_UPLOAD,
                'description' => 'Uploaded file via Project Documents Store (project-profile.pdf).',
                'ip_address' => '192.168.1.10',
                'created_at' => $baseTime->copy()->addMinutes(21),
                'properties' => ['module' => 'projects', 'uploaded_files' => [['name' => 'project-profile.pdf']]],
            ],
            [
                'user' => $admin,
                'action' => ActivityLog::ACTION_UPDATE,
                'description' => 'Updated project record PRJ-2026-001.',
                'ip_address' => '192.168.1.10',
                'created_at' => $baseTime->copy()->addMinutes(24),
                'properties' => ['module' => 'projects', 'project_code' => 'PRJ-2026-001'],
            ],
            [
                'user' => $admin,
                'action' => ActivityLog::ACTION_ROLE_CHANGE,
                'description' => 'Changed role for "activity.user" from user_lgu to user_provincial.',
                'ip_address' => '192.168.1.10',
                'created_at' => $baseTime->copy()->addMinutes(31),
                'properties' => ['target_username' => 'activity.user', 'from_role' => User::ROLE_LGU, 'to_role' => User::ROLE_PROVINCIAL],
            ],
            [
                'user' => $staff,
                'action' => ActivityLog::ACTION_VALIDATION_FAILED,
                'description' => 'Validation failed on Project Submission Form.',
                'ip_address' => '192.168.1.25',
                'created_at' => $baseTime->copy()->addMinutes(36),
                'properties' => ['module' => 'projects', 'fields' => ['project_title', 'budget']],
            ],
            [
                'user' => $staff,
                'action' => ActivityLog::ACTION_PASSWORD_RESET_REQUEST,
                'description' => 'Requested password reset OTP.',
                'ip_address' => '192.168.1.25',
                'created_at' => $baseTime->copy()->addMinutes(39),
                'properties' => ['module' => 'auth'],
            ],
            [
                'user' => $staff,
                'action' => ActivityLog::ACTION_PASSWORD_RESET,
                'description' => 'Completed password reset.',
                'ip_address' => '192.168.1.25',
                'created_at' => $baseTime->copy()->addMinutes(45),
                'properties' => ['module' => 'auth'],
            ],
            [
                'user' => $admin,
                'action' => ActivityLog::ACTION_MAINTENANCE_MODE_CHANGE,
                'description' => 'Enabled system maintenance mode.',
                'ip_address' => '192.168.1.10',
                'created_at' => $baseTime->copy()->addMinutes(50),
                'properties' => ['previous_state' => false, 'enabled' => true],
            ],
            [
                'user' => $admin,
                'action' => ActivityLog::ACTION_DELETE,
                'description' => 'Deleted attachment from project PRJ-2026-001.',
                'ip_address' => '192.168.1.10',
                'created_at' => $baseTime->copy()->addMinutes(53),
                'properties' => ['module' => 'projects', 'project_code' => 'PRJ-2026-001', 'resource' => 'attachment'],
            ],
            [
                'user' => $staff,
                'action' => ActivityLog::ACTION_LOGOUT,
                'description' => 'User signed out.',
                'ip_address' => '192.168.1.25',
                'created_at' => $baseTime->copy()->addMinutes(58),
                'properties' => ['module' => 'auth'],
            ],
        ];

        $desktopUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36';
        $mobileUserAgent = 'Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Mobile Safari/537.36';

        foreach ($entries as $entry) {
            $user = $entry['user'];
            $userAgent = $user?->isSuperAdmin() ? $desktopUserAgent : $mobileUserAgent;

            ActivityLog::query()->create([
                'user_id' => $user?->idno,
                'username' => $user?->username ?? ($entry['properties']['username'] ?? null),
                'action' => $entry['action'],
                'description' => $entry['description'],
                'timezone' => $timezone,
                'ip_address' => $entry['ip_address'],
                'user_agent' => $userAgent,
                'device' => $user?->isSuperAdmin() ? 'Desktop · Chrome · Windows' : 'Mobile · Chrome · Android',
                'properties' => $entry['properties'],
                'created_at' => $entry['created_at'],
            ]);
        }
    }
}
