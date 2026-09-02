<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Database\Seeders\TicketCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketSubmissionValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TicketCategorySeeder::class);
    }

    public function test_can_submit_ticket_with_program_related_category_and_valid_program(): void
    {
        $province = 'Benguet';

        $user = User::factory()->create([
            'role' => User::ROLE_LGU,
            'province' => $province,
            'access' => 'crud:ticketing_system.view,ticketing_system.add',
            'status' => 'active',
        ]);

        User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'province' => $province,
            'status' => 'active',
        ]);

        $category = TicketCategory::where('name', 'Program Related')->first();

        $response = $this->actingAs($user)->post(route('ticketing.store'), [
            'title' => 'Test Ticket',
            'description' => 'Test Description',
            'category_id' => $category->id,
            'subcategory' => 'RLIP/LIME',
            'priority' => Ticket::PRIORITY_LOW,
            'contact_information' => 'test@example.com',
        ]);

        $response->assertRedirect(route('ticketing.show', 1));
        $this->assertDatabaseHas('tickets', [
            'title' => 'Test Ticket',
            'category_id' => $category->id,
            'subcategory' => 'RLIP/LIME',
        ]);
    }

    public function test_cannot_submit_ticket_with_program_related_category_and_invalid_program(): void
    {
        $province = 'Benguet';

        $user = User::factory()->create([
            'role' => User::ROLE_LGU,
            'province' => $province,
            'access' => 'crud:ticketing_system.view,ticketing_system.add',
            'status' => 'active',
        ]);

        User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'province' => $province,
            'status' => 'active',
        ]);

        $category = TicketCategory::where('name', 'Program Related')->first();

        $response = $this->actingAs($user)->post(route('ticketing.store'), [
            'title' => 'Test Ticket',
            'description' => 'Test Description',
            'category_id' => $category->id,
            'subcategory' => 'INVALID_PROGRAM',
            'priority' => Ticket::PRIORITY_LOW,
            'contact_information' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors(['subcategory']);
    }

    public function test_cannot_submit_ticket_with_program_related_category_and_empty_program(): void
    {
        $province = 'Benguet';

        $user = User::factory()->create([
            'role' => User::ROLE_LGU,
            'province' => $province,
            'access' => 'crud:ticketing_system.view,ticketing_system.add',
            'status' => 'active',
        ]);

        User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'province' => $province,
            'status' => 'active',
        ]);

        $category = TicketCategory::where('name', 'Program Related')->first();

        $response = $this->actingAs($user)->post(route('ticketing.store'), [
            'title' => 'Test Ticket',
            'description' => 'Test Description',
            'category_id' => $category->id,
            'subcategory' => '',
            'priority' => Ticket::PRIORITY_LOW,
            'contact_information' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors(['subcategory']);
    }

    public function test_can_view_and_download_attachment(): void
    {
        $province = 'Benguet';

        $user = User::factory()->create([
            'role' => User::ROLE_LGU,
            'province' => $province,
            'access' => 'crud:ticketing_system.view,ticketing_system.add',
            'status' => 'active',
        ]);

        User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'province' => $province,
            'status' => 'active',
        ]);

        $ticket = Ticket::create([
            'title' => 'Test Ticket',
            'description' => 'Test Description',
            'category_id' => TicketCategory::first()->id,
            'priority' => Ticket::PRIORITY_LOW,
            'status' => Ticket::STATUS_SUBMITTED,
            'current_level' => Ticket::LEVEL_PROVINCIAL,
            'assigned_role' => User::ROLE_PROVINCIAL,
            'contact_information' => 'test@example.com',
            'region_scope' => $user->region,
            'province_scope' => $user->province,
            'submitted_by' => $user->getKey(),
            'date_submitted' => now(),
            'last_status_changed_at' => now(),
        ]);

        \Illuminate\Support\Facades\Storage::fake('local');
        $file = \Illuminate\Http\UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf');
        $filePath = $file->store('ticket-attachments', 'local');

        $attachment = $ticket->attachments()->create([
            'file_path' => $filePath,
            'original_name' => 'proof.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 102400,
            'disk' => 'local',
            'uploaded_by' => $user->getKey(),
        ]);

        // Test view attachment endpoint
        $viewResponse = $this->actingAs($user)->get(route('ticketing.attachments.view', [$ticket, $attachment]));
        $viewResponse->assertStatus(200);
        $viewResponse->assertHeader('Content-Disposition', 'inline; filename=proof.pdf');

        // Test download attachment endpoint
        $downloadResponse = $this->actingAs($user)->get(route('ticketing.attachments.download', [$ticket, $attachment]));
        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('Content-Disposition', 'attachment; filename=proof.pdf');
    }

    public function test_missing_attachment_returns_404(): void
    {
        $province = 'Benguet';

        $user = User::factory()->create([
            'role' => User::ROLE_LGU,
            'province' => $province,
            'access' => 'crud:ticketing_system.view,ticketing_system.add',
            'status' => 'active',
        ]);

        User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'province' => $province,
            'status' => 'active',
        ]);

        $ticket = Ticket::create([
            'title' => 'Test Ticket',
            'description' => 'Test Description',
            'category_id' => TicketCategory::first()->id,
            'priority' => Ticket::PRIORITY_LOW,
            'status' => Ticket::STATUS_SUBMITTED,
            'current_level' => Ticket::LEVEL_PROVINCIAL,
            'assigned_role' => User::ROLE_PROVINCIAL,
            'contact_information' => 'test@example.com',
            'region_scope' => $user->region,
            'province_scope' => $user->province,
            'submitted_by' => $user->getKey(),
            'date_submitted' => now(),
            'last_status_changed_at' => now(),
        ]);

        \Illuminate\Support\Facades\Storage::fake('local');

        $attachment = $ticket->attachments()->create([
            'file_path' => 'ticket-attachments/non-existent.pdf',
            'original_name' => 'non-existent.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 102400,
            'disk' => 'local',
            'uploaded_by' => $user->getKey(),
        ]);

        // Test view attachment returns 404
        $viewResponse = $this->actingAs($user)->get(route('ticketing.attachments.view', [$ticket, $attachment]));
        $viewResponse->assertStatus(404);

        // Test download attachment returns 404
        $downloadResponse = $this->actingAs($user)->get(route('ticketing.attachments.download', [$ticket, $attachment]));
        $downloadResponse->assertStatus(404);
    }

    public function test_provincial_user_submits_ticket_directly_to_regional_office(): void
    {
        $province = 'Benguet';

        $provincialUser = User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'province' => $province,
            'access' => 'crud:ticketing_system.view,ticketing_system.add',
            'status' => 'active',
        ]);

        $category = TicketCategory::where('name', 'System Issue')->first();

        $response = $this->actingAs($provincialUser)->post(route('ticketing.store'), [
            'title' => 'Provincial Submitter Ticket',
            'description' => 'This should go directly to the region.',
            'category_id' => $category->id,
            'priority' => Ticket::PRIORITY_MEDIUM,
            'contact_information' => 'provincial@example.com',
        ]);

        $response->assertRedirect(route('ticketing.show', 1));

        $this->assertDatabaseHas('tickets', [
            'title' => 'Provincial Submitter Ticket',
            'submitted_by' => $provincialUser->getKey(),
            'current_level' => Ticket::LEVEL_REGIONAL,
            'status' => Ticket::STATUS_ESCALATED_TO_REGION,
            'assigned_role' => User::ROLE_REGIONAL,
        ]);
    }

    public function test_regional_user_accepts_ticket_automatically_starts_review(): void
    {
        $province = 'Benguet';

        $regionalUser = User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'status' => 'active',
        ]);

        $ticket = Ticket::create([
            'title' => 'Regional Escalated Ticket',
            'description' => 'Test',
            'category_id' => TicketCategory::first()->id,
            'priority' => Ticket::PRIORITY_LOW,
            'status' => Ticket::STATUS_ESCALATED_TO_REGION,
            'current_level' => Ticket::LEVEL_REGIONAL,
            'assigned_role' => User::ROLE_REGIONAL,
            'contact_information' => 'test@example.com',
            'region_scope' => $regionalUser->region,
            'province_scope' => $province,
            'submitted_by' => User::factory()->create()->idno,
            'date_submitted' => now(),
            'last_status_changed_at' => now(),
        ]);

        $response = $this->actingAs($regionalUser)->post(route('ticketing.region.accept', $ticket));
        $response->assertRedirect(route('ticketing.show', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assigned_to' => $regionalUser->getKey(),
            'status' => Ticket::STATUS_UNDER_REVIEW_BY_REGION,
        ]);
    }
}
