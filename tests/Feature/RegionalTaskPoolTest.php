<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\NotificationCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegionalTaskPoolTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_regional_users_share_a_task_and_read_state(): void
    {
        $regionalUserOne = User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'region' => 'Region I',
            'status' => 'active',
        ]);
        $regionalUserTwo = User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'region' => 'Region I',
            'status' => 'active',
        ]);
        User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'region' => 'Region II',
            'status' => 'active',
        ]);

        $url = '/fund-utilization/PROJECT-001/approve/mov/Q1';
        DB::table('tbnotifications')->insert([
            [
                'user_id' => $regionalUserOne->getKey(),
                'message' => 'PROJECT-001 is awaiting DILG Regional Office validation.',
                'url' => $url,
                'document_type' => 'mov',
                'quarter' => 'Q1',
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $regionalUserTwo->getKey(),
                'message' => 'PROJECT-001 is awaiting DILG Regional Office validation.',
                'url' => $url,
                'document_type' => 'mov',
                'quarter' => 'Q1',
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->assertCount(3, NotificationCenter::regionalPoolUserIds($regionalUserOne));

        $this->actingAs($regionalUserOne)->get('/notifications/1/read');

        $this->assertSame(0, DB::table('tbnotifications')->whereNull('read_at')->count());
    }
}