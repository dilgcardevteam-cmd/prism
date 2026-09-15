<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TicketWorkflowService
{
    public function __construct(
        protected TicketRoutingService $routingService,
        protected TicketNotificationService $notificationService,
    ) {
    }

    public function submit(User $submitter, array $payload, ?UploadedFile $attachment = null): Ticket
    {
        $isProvincialSubmitter = $submitter->isProvincialUser();
        $isRegionalSubmitter = $submitter->isRegionalUser();

        if (!$isProvincialSubmitter && !$isRegionalSubmitter && !$this->routingService->hasProvincialHandlers($submitter)) {
            throw new RuntimeException('No active Provincial User is configured for the submitter province yet.');
        }

        return DB::transaction(function () use ($submitter, $payload, $attachment, $isProvincialSubmitter, $isRegionalSubmitter): Ticket {
            $superadminAssignee = $isRegionalSubmitter
                ? $this->routingService->resolveSuperadminAssignee()
                : null;

            $ticket = Ticket::create([
                'title' => $payload['title'],
                'description' => $payload['description'],
                'category_id' => $payload['category_id'],
                'subcategory' => $payload['subcategory'] ?? null,
                'priority' => $payload['priority'],
                'status' => $isRegionalSubmitter
                    ? Ticket::STATUS_ESCALATED_TO_REGION
                    : ($isProvincialSubmitter ? Ticket::STATUS_ESCALATED_TO_REGION : Ticket::STATUS_SUBMITTED),
                'current_level' => $isRegionalSubmitter
                    ? Ticket::LEVEL_REGIONAL
                    : ($isProvincialSubmitter ? Ticket::LEVEL_REGIONAL : Ticket::LEVEL_PROVINCIAL),
                'assigned_role' => $isRegionalSubmitter
                    ? User::ROLE_SUPERADMIN
                    : ($isProvincialSubmitter ? User::ROLE_REGIONAL : User::ROLE_PROVINCIAL),
                'contact_information' => $payload['contact_information'],
                'region_scope' => $submitter->region,
                'province_scope' => $submitter->province,
                'office_scope' => $submitter->office,
                'submitted_by' => $submitter->getKey(),
                'assigned_to' => $superadminAssignee?->getKey(),
                'date_submitted' => now(),
                'last_status_changed_at' => now(),
            ]);

            if ($attachment) {
                $this->storeAttachment($ticket, $submitter, $attachment);
            }

            if ($isRegionalSubmitter) {
                $this->recordHistory(
                    ticket: $ticket,
                    actor: $submitter,
                    action: 'ticket_created',
                    description: 'Ticket submitted and assigned to the Superadmin.',
                    fromStatus: null,
                    toStatus: Ticket::STATUS_ESCALATED_TO_REGION,
                    fromLevel: null,
                    toLevel: Ticket::LEVEL_REGIONAL,
                    metadata: [
                        'assigned_role' => User::ROLE_SUPERADMIN,
                        'assigned_to' => $superadminAssignee?->fullName(),
                        'queue' => 'superadmin',
                    ],
                );

                $this->notificationService->notifySuperadmin($ticket, $submitter);
            } elseif ($isProvincialSubmitter) {
                $this->recordHistory(
                    ticket: $ticket,
                    actor: $submitter,
                    action: 'ticket_created',
                    description: 'Ticket submitted and routed to the regional queue.',
                    fromStatus: null,
                    toStatus: Ticket::STATUS_ESCALATED_TO_REGION,
                    fromLevel: null,
                    toLevel: Ticket::LEVEL_REGIONAL,
                    metadata: [
                        'assigned_role' => User::ROLE_REGIONAL,
                        'queue' => 'regional',
                        'region_scope' => $submitter->region,
                    ],
                );

                $this->notificationService->notifyRegionalQueue($ticket, $submitter);
            } else {
                $this->recordHistory(
                    ticket: $ticket,
                    actor: $submitter,
                    action: 'ticket_created',
                    description: 'Ticket submitted and routed to the provincial queue.',
                    fromStatus: null,
                    toStatus: Ticket::STATUS_SUBMITTED,
                    fromLevel: null,
                    toLevel: Ticket::LEVEL_PROVINCIAL,
                    metadata: [
                        'assigned_role' => User::ROLE_PROVINCIAL,
                        'queue' => 'provincial',
                        'province_scope' => $submitter->province,
                    ],
                );

                $this->notificationService->notifyProvincialQueue($ticket, $submitter);
            }

            return $ticket->fresh(['category', 'submitter', 'assignee', 'attachments', 'histories', 'comments']);
        });
    }

    public function acceptByProvince(Ticket $ticket, User $actor): Ticket
    {
        return DB::transaction(function () use ($ticket, $actor): Ticket {
            /** @var Ticket|null $lockedTicket */
            $lockedTicket = Ticket::query()->lockForUpdate()->find($ticket->id);

            if (!$lockedTicket) {
                throw new RuntimeException('The selected ticket could not be found anymore.');
            }

            if ($lockedTicket->current_level !== Ticket::LEVEL_PROVINCIAL || $lockedTicket->status !== Ticket::STATUS_SUBMITTED) {
                throw new RuntimeException('Only newly submitted provincial tickets can be accepted from the provincial queue.');
            }

            if ($lockedTicket->assigned_to !== null) {
                if ((int) $lockedTicket->assigned_to === (int) $actor->getKey()) {
                    throw new RuntimeException('You already accepted this ticket.');
                }

                throw new RuntimeException('This ticket was already accepted by another Provincial User.');
            }

            $lockedTicket->fill([
                'assigned_to' => $actor->getKey(),
                'assigned_role' => User::ROLE_PROVINCIAL,
                'last_status_changed_at' => now(),
            ]);
            $lockedTicket->save();

            $this->recordHistory(
                ticket: $lockedTicket,
                actor: $actor,
                action: 'ticket_accepted_by_province',
                description: 'Ticket accepted from the provincial queue by a Provincial User.',
                fromStatus: $lockedTicket->status,
                toStatus: $lockedTicket->status,
                fromLevel: $lockedTicket->current_level,
                toLevel: $lockedTicket->current_level,
                metadata: [
                    'assigned_to' => $actor->fullName(),
                ],
            );

            return $lockedTicket->fresh(['category', 'submitter', 'assignee', 'attachments', 'histories', 'comments']);
        });
    }

    public function addComment(Ticket $ticket, User $actor, string $comment): TicketComment
    {
        return DB::transaction(function () use ($ticket, $actor, $comment): TicketComment {
            $ticketComment = $ticket->comments()->create([
                'user_id' => $actor->getKey(),
                'comment' => $comment,
            ]);

            $this->recordHistory(
                ticket: $ticket,
                actor: $actor,
                action: 'comment_added',
                description: 'Added a ticket remark/comment.',
                fromStatus: $ticket->status,
                toStatus: $ticket->status,
                fromLevel: $ticket->current_level,
                toLevel: $ticket->current_level,
            );

            return $ticketComment;
        });
    }

    public function markProvinceUnderReview(Ticket $ticket, User $actor): Ticket
    {
        if ($ticket->current_level !== Ticket::LEVEL_PROVINCIAL || $ticket->status !== Ticket::STATUS_SUBMITTED) {
            throw new RuntimeException('Only newly submitted provincial tickets can be moved into review.');
        }

        $this->ensureProvinceTicketAssignedToActor($ticket, $actor);

        return $this->updateTicketState(
            ticket: $ticket,
            actor: $actor,
            action: 'province_review_started',
            description: 'Provincial review started.',
            updates: [
                'status' => Ticket::STATUS_UNDER_REVIEW_BY_PROVINCE,
                'last_status_changed_at' => now(),
            ],
            toStatus: Ticket::STATUS_UNDER_REVIEW_BY_PROVINCE,
            toLevel: Ticket::LEVEL_PROVINCIAL,
        );
    }

    public function resolveByProvince(Ticket $ticket, User $actor, ?string $resolutionNote = null): Ticket
    {
        if ($ticket->current_level !== Ticket::LEVEL_PROVINCIAL || !in_array($ticket->status, [
            Ticket::STATUS_SUBMITTED,
            Ticket::STATUS_UNDER_REVIEW_BY_PROVINCE,
        ], true)) {
            throw new RuntimeException('Only provincial tickets under active review can be resolved at the provincial level.');
        }

        $this->ensureProvinceTicketAssignedToActor($ticket, $actor);

        return DB::transaction(function () use ($ticket, $actor, $resolutionNote): Ticket {
            if ($resolutionNote) {
                $ticket->comments()->create([
                    'user_id' => $actor->getKey(),
                    'comment' => $resolutionNote,
                ]);
            }

            return $this->updateTicketState(
                ticket: $ticket,
                actor: $actor,
                action: 'province_resolved',
                description: 'Ticket resolved by the Provincial User.',
                updates: [
                    'status' => Ticket::STATUS_RESOLVED_BY_PROVINCE,
                    'resolved_by' => $actor->getKey(),
                    'resolved_at' => now(),
                    'last_status_changed_at' => now(),
                ],
                toStatus: Ticket::STATUS_RESOLVED_BY_PROVINCE,
                toLevel: Ticket::LEVEL_PROVINCIAL,
            );
        });
    }

    public function escalateToRegion(Ticket $ticket, User $actor, string $reason, ?string $comment = null): Ticket
    {
        if ($ticket->current_level !== Ticket::LEVEL_PROVINCIAL || !in_array($ticket->status, [
            Ticket::STATUS_SUBMITTED,
            Ticket::STATUS_UNDER_REVIEW_BY_PROVINCE,
        ], true)) {
            throw new RuntimeException('Only provincial tickets can be escalated to the Regional User.');
        }

        $this->ensureProvinceTicketAssignedToActor($ticket, $actor);

        $updatedTicket = DB::transaction(function () use ($ticket, $actor, $reason, $comment): Ticket {
            if ($comment) {
                $ticket->comments()->create([
                    'user_id' => $actor->getKey(),
                    'comment' => $comment,
                ]);
            }

            return $this->updateTicketState(
                ticket: $ticket,
                actor: $actor,
                action: 'ticket_escalated_to_region',
                description: 'Ticket escalated to the regional queue.',
                updates: [
                    'status' => Ticket::STATUS_ESCALATED_TO_REGION,
                    'current_level' => Ticket::LEVEL_REGIONAL,
                    'assigned_role' => User::ROLE_REGIONAL,
                    'assigned_to' => null,
                    'escalation_reason' => $reason,
                    'escalated_by' => $actor->getKey(),
                    'escalated_at' => now(),
                    'last_status_changed_at' => now(),
                ],
                toStatus: Ticket::STATUS_ESCALATED_TO_REGION,
                toLevel: Ticket::LEVEL_REGIONAL,
                metadata: [
                    'reason' => $reason,
                    'queue' => 'regional',
                    'region_scope' => $ticket->region_scope,
                ],
            );
        });

        $this->notificationService->notifyRegionalQueue($updatedTicket, $actor);

        return $updatedTicket;
    }

    public function acceptByRegion(Ticket $ticket, User $actor): Ticket
    {
        return DB::transaction(function () use ($ticket, $actor): Ticket {
            /** @var Ticket|null $lockedTicket */
            $lockedTicket = Ticket::query()->lockForUpdate()->find($ticket->id);

            if (!$lockedTicket) {
                throw new RuntimeException('The selected ticket could not be found anymore.');
            }

            if ($lockedTicket->current_level !== Ticket::LEVEL_REGIONAL || $lockedTicket->status !== Ticket::STATUS_ESCALATED_TO_REGION) {
                throw new RuntimeException('Only escalated regional tickets can be accepted from the regional queue.');
            }

            if ($lockedTicket->assigned_to !== null) {
                if ((int) $lockedTicket->assigned_to === (int) $actor->getKey()) {
                    throw new RuntimeException('You already accepted this ticket.');
                }

                throw new RuntimeException('This ticket was already accepted by another Regional User.');
            }

            $lockedTicket->fill([
                'assigned_to' => $actor->getKey(),
                'assigned_role' => User::ROLE_REGIONAL,
                'status' => Ticket::STATUS_UNDER_REVIEW_BY_REGION,
                'last_status_changed_at' => now(),
            ]);
            $lockedTicket->save();

            $this->recordHistory(
                ticket: $lockedTicket,
                actor: $actor,
                action: 'ticket_accepted_by_region',
                description: 'Ticket accepted from the regional queue by a Regional User and marked under review.',
                fromStatus: Ticket::STATUS_ESCALATED_TO_REGION,
                toStatus: Ticket::STATUS_UNDER_REVIEW_BY_REGION,
                fromLevel: $lockedTicket->current_level,
                toLevel: $lockedTicket->current_level,
                metadata: [
                    'assigned_to' => $actor->fullName(),
                ],
            );

            return $lockedTicket->fresh(['category', 'submitter', 'assignee', 'attachments', 'histories', 'comments']);
        });
    }

    public function markRegionUnderReview(Ticket $ticket, User $actor): Ticket
    {
        if ($ticket->current_level !== Ticket::LEVEL_REGIONAL || $ticket->status !== Ticket::STATUS_ESCALATED_TO_REGION) {
            throw new RuntimeException('Only escalated regional tickets can be marked under review.');
        }

        $this->ensureRegionTicketAssignedToActor($ticket, $actor);

        return $this->updateTicketState(
            ticket: $ticket,
            actor: $actor,
            action: 'region_review_started',
            description: 'Regional review started.',
            updates: [
                'status' => Ticket::STATUS_UNDER_REVIEW_BY_REGION,
                'last_status_changed_at' => now(),
            ],
            toStatus: Ticket::STATUS_UNDER_REVIEW_BY_REGION,
            toLevel: Ticket::LEVEL_REGIONAL,
        );
    }

    public function resolveByRegion(Ticket $ticket, User $actor, ?string $resolutionNote = null): Ticket
    {
        $isRegionalQueueTicket = $ticket->current_level === Ticket::LEVEL_REGIONAL
            && in_array($ticket->status, [
                Ticket::STATUS_ESCALATED_TO_REGION,
                Ticket::STATUS_UNDER_REVIEW_BY_REGION,
            ], true);
        if (!$isRegionalQueueTicket) {
            throw new RuntimeException('Only active regional tickets can be resolved at the regional level.');
        }

        $this->ensureRegionTicketAssignedToActor($ticket, $actor);

        return DB::transaction(function () use ($ticket, $actor, $resolutionNote): Ticket {
            if ($resolutionNote) {
                $ticket->comments()->create([
                    'user_id' => $actor->getKey(),
                    'comment' => $resolutionNote,
                ]);
            }

            return $this->updateTicketState(
                ticket: $ticket,
                actor: $actor,
                action: 'region_resolved',
                description: 'Ticket resolved by the Regional User.',
                updates: [
                    'status' => Ticket::STATUS_RESOLVED_BY_REGION,
                    'current_level' => Ticket::LEVEL_REGIONAL,
                    'assigned_role' => User::ROLE_REGIONAL,
                    'assigned_to' => $actor->getKey(),
                    'forwarded_to_central_office' => false,
                    'resolved_by' => $actor->getKey(),
                    'resolved_at' => now(),
                    'last_status_changed_at' => now(),
                ],
                toStatus: Ticket::STATUS_RESOLVED_BY_REGION,
                toLevel: Ticket::LEVEL_REGIONAL,
            );
        });
    }

    public function close(Ticket $ticket, User $actor, ?string $closeNote = null): Ticket
    {
        if (!in_array($ticket->status, [
            Ticket::STATUS_RESOLVED_BY_PROVINCE,
            Ticket::STATUS_RESOLVED_BY_REGION,
        ], true)) {
            throw new RuntimeException('Only resolved or forwarded tickets can be closed.');
        }

        return DB::transaction(function () use ($ticket, $actor, $closeNote): Ticket {
            if ($closeNote) {
                $ticket->comments()->create([
                    'user_id' => $actor->getKey(),
                    'comment' => $closeNote,
                ]);
            }

            return $this->updateTicketState(
                ticket: $ticket,
                actor: $actor,
                action: 'ticket_closed',
                description: 'Ticket closed by Superadmin.',
                updates: [
                    'status' => Ticket::STATUS_CLOSED,
                    'assigned_role' => User::ROLE_SUPERADMIN,
                    'assigned_to' => $actor->getKey(),
                    'closed_at' => now(),
                    'last_status_changed_at' => now(),
                ],
                toStatus: Ticket::STATUS_CLOSED,
                toLevel: $ticket->current_level,
            );
        });
    }

    protected function updateTicketState(
        Ticket $ticket,
        User $actor,
        string $action,
        string $description,
        array $updates,
        ?string $toStatus,
        ?string $toLevel,
        array $metadata = [],
    ): Ticket {
        return DB::transaction(function () use ($ticket, $actor, $action, $description, $updates, $toStatus, $toLevel, $metadata): Ticket {
            $fromStatus = $ticket->status;
            $fromLevel = $ticket->current_level;

            $ticket->fill($updates);
            $ticket->save();

            $this->recordHistory(
                ticket: $ticket,
                actor: $actor,
                action: $action,
                description: $description,
                fromStatus: $fromStatus,
                toStatus: $toStatus,
                fromLevel: $fromLevel,
                toLevel: $toLevel,
                metadata: $metadata,
            );

            return $ticket->fresh(['category', 'submitter', 'assignee', 'attachments', 'histories', 'comments']);
        });
    }

    protected function storeAttachment(Ticket $ticket, User $actor, UploadedFile $attachment): TicketAttachment
    {
        $storedPath = $attachment->store('ticket-attachments/' . $ticket->id, 'public');

        return $ticket->attachments()->create([
            'uploaded_by' => $actor->getKey(),
            'disk' => 'public',
            'file_path' => $storedPath,
            'original_name' => $attachment->getClientOriginalName(),
            'mime_type' => $attachment->getClientMimeType(),
            'file_size' => $attachment->getSize(),
        ]);
    }

    protected function recordHistory(
        Ticket $ticket,
        ?User $actor,
        string $action,
        string $description,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $fromLevel,
        ?string $toLevel,
        array $metadata = [],
    ): TicketHistory {
        return $ticket->histories()->create([
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'description' => $description,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'from_level' => $fromLevel,
            'to_level' => $toLevel,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    protected function ensureProvinceTicketAssignedToActor(Ticket $ticket, User $actor): void
    {
        if ((int) $ticket->assigned_to === (int) $actor->getKey()) {
            return;
        }

        if ($ticket->assigned_to === null) {
            throw new RuntimeException('Accept the ticket first before performing provincial actions.');
        }

        throw new RuntimeException('This ticket is currently assigned to another Provincial User.');
    }

    protected function ensureRegionTicketAssignedToActor(Ticket $ticket, User $actor): void
    {
        if ((int) $ticket->assigned_to === (int) $actor->getKey()) {
            return;
        }

        if (
            $ticket->status === Ticket::STATUS_FORWARDED_TO_CENTRAL_OFFICE
            && (int) $ticket->forwarded_by === (int) $actor->getKey()
        ) {
            return;
        }

        if ($ticket->assigned_to === null) {
            throw new RuntimeException('Accept the ticket first before performing regional actions.');
        }

        throw new RuntimeException('This ticket is currently assigned to another Regional User.');
    }
}
