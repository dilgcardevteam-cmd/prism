<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $lgu = User::where('username', 'ticket.lgu')->first();
        $province = User::where('username', 'ticket.province')->first();
        $region = User::where('username', 'ticket.region')->first();
        $admin = User::where('username', 'ticket.admin')->first();

        if (!$lgu || !$province || !$region || !$admin) {
            return;
        }

        $categories = TicketCategory::query()->pluck('id', 'name');

        $this->seedTicket(
            title: 'Unable to open report submission page',
            description: 'The LGU user cannot access the report submission page after login.',
            categoryId: $categories['System Issue'] ?? null,
            priority: Ticket::PRIORITY_HIGH,
            submittedBy: $lgu,
            assignedTo: null,
            status: Ticket::STATUS_SUBMITTED,
            currentLevel: Ticket::LEVEL_PROVINCIAL,
            daysAgo: 4,
            comments: [
                [$lgu, 'Issue encountered after the latest deployment.'],
            ],
            historyDescriptions: [
                ['ticket_created', 'Ticket submitted and routed to the provincial queue.', null, Ticket::STATUS_SUBMITTED, null, Ticket::LEVEL_PROVINCIAL, $lgu],
            ],
        );

        $this->seedTicket(
            title: 'Clarification on liquidation workflow',
            description: 'Provincial office needs regional guidance on the liquidation workflow for delayed submissions.',
            categoryId: $categories['Process Inquiry'] ?? null,
            priority: Ticket::PRIORITY_MEDIUM,
            submittedBy: $lgu,
            assignedTo: null,
            status: Ticket::STATUS_ESCALATED_TO_REGION,
            currentLevel: Ticket::LEVEL_REGIONAL,
            daysAgo: 3,
            escalationReason: 'Provincial office requires regional guidance on the requested workflow.',
            escalatedBy: $province,
            comments: [
                [$province, 'Escalating for regional policy clarification.'],
            ],
            historyDescriptions: [
                ['ticket_created', 'Ticket submitted and routed to the provincial queue.', null, Ticket::STATUS_SUBMITTED, null, Ticket::LEVEL_PROVINCIAL, $lgu],
                ['ticket_accepted_by_province', 'Ticket accepted from the provincial queue by a Provincial User.', Ticket::STATUS_SUBMITTED, Ticket::STATUS_SUBMITTED, Ticket::LEVEL_PROVINCIAL, Ticket::LEVEL_PROVINCIAL, $province],
                ['ticket_escalated_to_region', 'Ticket escalated to the regional queue.', Ticket::STATUS_SUBMITTED, Ticket::STATUS_ESCALATED_TO_REGION, Ticket::LEVEL_PROVINCIAL, Ticket::LEVEL_REGIONAL, $province],
            ],
        );

        $this->seedTicket(
            title: 'Regional concern requiring central office decision',
            description: 'Regional office needs Central Office direction for a workflow concern affecting multiple provinces.',
            categoryId: $categories['Others'] ?? null,
            priority: Ticket::PRIORITY_URGENT,
            submittedBy: $lgu,
            assignedTo: $admin,
            status: Ticket::STATUS_FORWARDED_TO_CENTRAL_OFFICE,
            currentLevel: Ticket::LEVEL_CENTRAL_OFFICE,
            daysAgo: 2,
            escalationReason: 'Escalated from provincial level to region for wider guidance.',
            forwardedToCentralOffice: true,
            escalatedBy: $province,
            forwardedBy: $region,
            subcategory: 'Central Office policy clarification for a cross-province workflow case',
            comments: [
                [$region, 'Forwarding to Central Office for final decision.'],
            ],
            historyDescriptions: [
                ['ticket_created', 'Ticket submitted and routed to the provincial queue.', null, Ticket::STATUS_SUBMITTED, null, Ticket::LEVEL_PROVINCIAL, $lgu],
                ['ticket_accepted_by_province', 'Ticket accepted from the provincial queue by a Provincial User.', Ticket::STATUS_SUBMITTED, Ticket::STATUS_SUBMITTED, Ticket::LEVEL_PROVINCIAL, Ticket::LEVEL_PROVINCIAL, $province],
                ['ticket_escalated_to_region', 'Ticket escalated to the regional queue.', Ticket::STATUS_SUBMITTED, Ticket::STATUS_ESCALATED_TO_REGION, Ticket::LEVEL_PROVINCIAL, Ticket::LEVEL_REGIONAL, $province],
                ['ticket_accepted_by_region', 'Ticket accepted from the regional queue by a Regional User.', Ticket::STATUS_ESCALATED_TO_REGION, Ticket::STATUS_ESCALATED_TO_REGION, Ticket::LEVEL_REGIONAL, Ticket::LEVEL_REGIONAL, $region],
                ['forwarded_to_central_office', 'Ticket marked as Forwarded to Central Office.', Ticket::STATUS_ESCALATED_TO_REGION, Ticket::STATUS_FORWARDED_TO_CENTRAL_OFFICE, Ticket::LEVEL_REGIONAL, Ticket::LEVEL_CENTRAL_OFFICE, $region],
            ],
        );

        $this->seedTicket(
            title: 'Encoding issue already resolved by province',
            description: 'Attachment upload failed earlier, but the provincial office already guided the user through the fix.',
            categoryId: $categories['Data Concern'] ?? null,
            priority: Ticket::PRIORITY_LOW,
            submittedBy: $lgu,
            assignedTo: $province,
            status: Ticket::STATUS_RESOLVED_BY_PROVINCE,
            currentLevel: Ticket::LEVEL_PROVINCIAL,
            daysAgo: 1,
            resolvedBy: $province,
            comments: [
                [$province, 'Issue resolved after browser cache was cleared.'],
            ],
            historyDescriptions: [
                ['ticket_created', 'Ticket submitted and routed to the provincial queue.', null, Ticket::STATUS_SUBMITTED, null, Ticket::LEVEL_PROVINCIAL, $lgu],
                ['ticket_accepted_by_province', 'Ticket accepted from the provincial queue by a Provincial User.', Ticket::STATUS_SUBMITTED, Ticket::STATUS_SUBMITTED, Ticket::LEVEL_PROVINCIAL, Ticket::LEVEL_PROVINCIAL, $province],
                ['province_review_started', 'Provincial review started.', Ticket::STATUS_SUBMITTED, Ticket::STATUS_UNDER_REVIEW_BY_PROVINCE, Ticket::LEVEL_PROVINCIAL, Ticket::LEVEL_PROVINCIAL, $province],
                ['province_resolved', 'Ticket resolved by the Provincial User.', Ticket::STATUS_UNDER_REVIEW_BY_PROVINCE, Ticket::STATUS_RESOLVED_BY_PROVINCE, Ticket::LEVEL_PROVINCIAL, Ticket::LEVEL_PROVINCIAL, $province],
            ],
        );
    }

    protected function seedTicket(
        string $title,
        string $description,
        ?int $categoryId,
        string $priority,
        User $submittedBy,
        ?User $assignedTo,
        string $status,
        string $currentLevel,
        int $daysAgo,
        ?string $escalationReason = null,
        bool $forwardedToCentralOffice = false,
        ?User $escalatedBy = null,
        ?User $forwardedBy = null,
        ?User $resolvedBy = null,
        ?string $subcategory = null,
        array $comments = [],
        array $historyDescriptions = [],
    ): void {
        $submittedAt = Carbon::now()->subDays($daysAgo);

        $ticket = Ticket::updateOrCreate(
            [
                'title' => $title,
                'submitted_by' => $submittedBy->getKey(),
            ],
            [
                'description' => $description,
                'category_id' => $categoryId,
                'subcategory' => $subcategory,
                'priority' => $priority,
                'status' => $status,
                'current_level' => $currentLevel,
                'assigned_role' => $assignedTo?->role ?? User::ROLE_PROVINCIAL,
                'contact_information' => trim(implode(' | ', array_filter([$submittedBy->emailaddress, $submittedBy->mobileno]))),
                'region_scope' => $submittedBy->region,
                'province_scope' => $submittedBy->province,
                'office_scope' => $submittedBy->office,
                'assigned_to' => $assignedTo?->getKey(),
                'escalation_reason' => $escalationReason,
                'escalated_by' => $escalatedBy?->getKey(),
                'escalated_at' => $currentLevel !== Ticket::LEVEL_PROVINCIAL ? $submittedAt->copy()->addDay() : null,
                'forwarded_to_central_office' => $forwardedToCentralOffice,
                'forwarded_by' => $forwardedBy?->getKey(),
                'forwarded_at' => $forwardedToCentralOffice ? $submittedAt->copy()->addDays(2) : null,
                'resolved_by' => $resolvedBy?->getKey(),
                'resolved_at' => $resolvedBy ? $submittedAt->copy()->addDays(2) : null,
                'date_submitted' => $submittedAt,
                'last_status_changed_at' => $submittedAt->copy()->addDays(2),
            ],
        );

        $ticket->comments()->delete();
        $ticket->histories()->delete();

        foreach ($comments as [$actor, $comment]) {
            $ticket->comments()->create([
                'user_id' => $actor?->getKey(),
                'comment' => $comment,
                'created_at' => $submittedAt->copy()->addHours(3),
                'updated_at' => $submittedAt->copy()->addHours(3),
            ]);
        }

        foreach ($historyDescriptions as $index => [$action, $descriptionText, $fromStatus, $toStatus, $fromLevel, $toLevel, $actor]) {
            $ticket->histories()->create([
                'actor_id' => $actor?->getKey(),
                'action' => $action,
                'description' => $descriptionText,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'from_level' => $fromLevel,
                'to_level' => $toLevel,
                'created_at' => $submittedAt->copy()->addHours($index + 1),
                'updated_at' => $submittedAt->copy()->addHours($index + 1),
            ]);
        }
    }
}
