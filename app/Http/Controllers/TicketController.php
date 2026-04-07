<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketCategory;
use App\Services\TicketEscalateRequest;
use App\Services\TicketForwardRequest;
use App\Services\TicketResolveRequest;
use App\Services\TicketStoreRequest;
use App\Services\TicketWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TicketController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'crud_permission:ticketing_system,view']);
    }

    public function create(Request $request)
    {
        if (!Gate::forUser($request->user())->allows('ticketing.submit')) {
            return $this->restricted();
        }

        return view('ticketing-submit', [
            'categories' => $this->activeCategories(),
            'priorities' => Ticket::priorityOptions(),
            'defaultContactInformation' => trim(implode(' | ', array_filter([
                $request->user()->emailaddress,
                $request->user()->mobileno,
            ]))),
        ]);
    }

    public function store(TicketStoreRequest $request, TicketWorkflowService $workflowService): RedirectResponse|Response
    {
        try {
            $ticket = $workflowService->submit(
                submitter: $request->user(),
                payload: $request->validated(),
                attachment: $request->file('attachment'),
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['ticket_submission' => $exception->getMessage()]);
        }

        return redirect()
            ->route('ticketing.show', $ticket)
            ->with('success', 'Ticket submitted successfully.');
    }

    public function myTickets(Request $request)
    {
        if (!$request->user()->isLguUser()) {
            return $this->restricted();
        }

        return view('ticketing-my-tickets', [
            'tickets' => $this->applyFilters($request, Ticket::query()
                ->with(['category', 'submitter', 'assignee'])
                ->visibleTo($request->user()))
                ->orderByDesc('created_at')
                ->paginate(12)
                ->withQueryString(),
            'categories' => $this->activeCategories(),
            'statuses' => Ticket::statusOptions(),
            'priorities' => Ticket::priorityOptions(),
        ]);
    }

    public function track(Request $request)
    {
        if (!$request->user()->isLguUser()) {
            return $this->restricted();
        }

        return view('ticketing-track', [
            'tickets' => $this->applyFilters($request, Ticket::query()
                ->with(['category', 'submitter', 'assignee'])
                ->visibleTo($request->user()))
                ->orderByDesc('last_status_changed_at')
                ->orderByDesc('updated_at')
                ->paginate(12)
                ->withQueryString(),
            'categories' => $this->activeCategories(),
            'statuses' => Ticket::statusOptions(),
            'priorities' => Ticket::priorityOptions(),
        ]);
    }

    public function provincialIndex(Request $request)
    {
        if (!$request->user()->isProvincialUser()) {
            return $this->restricted();
        }

        return view('ticketing-provincial', [
            'tickets' => $this->applyFilters($request, Ticket::query()
                ->with(['category', 'submitter', 'assignee'])
                ->visibleTo($request->user()))
                ->orderByRaw("
                    CASE status
                        WHEN '" . Ticket::STATUS_SUBMITTED . "' THEN 1
                        WHEN '" . Ticket::STATUS_UNDER_REVIEW_BY_PROVINCE . "' THEN 2
                        WHEN '" . Ticket::STATUS_ESCALATED_TO_REGION . "' THEN 3
                        WHEN '" . Ticket::STATUS_RESOLVED_BY_PROVINCE . "' THEN 4
                        ELSE 5
                    END
                ")
                ->orderByDesc('updated_at')
                ->paginate(12)
                ->withQueryString(),
            'categories' => $this->activeCategories(),
            'statuses' => Ticket::statusOptions(),
            'priorities' => Ticket::priorityOptions(),
        ]);
    }

    public function regionalIndex(Request $request)
    {
        if (!$request->user()->isRegionalUser()) {
            return $this->restricted();
        }

        return view('ticketing-regional', [
            'tickets' => $this->applyFilters($request, Ticket::query()
                ->with(['category', 'submitter', 'assignee'])
                ->visibleTo($request->user()))
                ->orderByRaw("
                    CASE status
                        WHEN '" . Ticket::STATUS_ESCALATED_TO_REGION . "' THEN 1
                        WHEN '" . Ticket::STATUS_UNDER_REVIEW_BY_REGION . "' THEN 2
                        WHEN '" . Ticket::STATUS_FORWARDED_TO_CENTRAL_OFFICE . "' THEN 3
                        WHEN '" . Ticket::STATUS_RESOLVED_BY_CENTRAL_OFFICE . "' THEN 4
                        WHEN '" . Ticket::STATUS_RESOLVED_BY_REGION . "' THEN 5
                        ELSE 6
                    END
                ")
                ->orderByDesc('updated_at')
                ->paginate(12)
                ->withQueryString(),
            'categories' => $this->activeCategories(),
            'statuses' => Ticket::statusOptions(),
            'priorities' => Ticket::priorityOptions(),
        ]);
    }

    public function show(Request $request, Ticket $ticket)
    {
        if (!Gate::forUser($request->user())->allows('ticketing.view', $ticket)) {
            return $this->restricted();
        }

        $ticket->load([
            'category',
            'submitter',
            'assignee',
            'attachments',
            'comments.user',
            'histories.actor',
        ]);

        return view('ticketing-show', [
            'ticket' => $ticket,
            'canAcceptProvince' => Gate::forUser($request->user())->allows('ticketing.acceptProvince', $ticket),
            'canAcceptRegion' => Gate::forUser($request->user())->allows('ticketing.acceptRegion', $ticket),
            'canManageProvince' => Gate::forUser($request->user())->allows('ticketing.manageProvince', $ticket),
            'canManageRegion' => Gate::forUser($request->user())->allows('ticketing.manageRegion', $ticket),
            'canManageAdmin' => Gate::forUser($request->user())->allows('ticketing.manageAdmin'),
        ]);
    }

    public function downloadAttachment(Request $request, Ticket $ticket, TicketAttachment $attachment): BinaryFileResponse|Response
    {
        if ((int) $attachment->ticket_id !== (int) $ticket->id || !Gate::forUser($request->user())->allows('ticketing.view', $ticket)) {
            return $this->restricted();
        }

        return Storage::disk($attachment->disk)->download($attachment->file_path, $attachment->original_name);
    }

    public function provinceStartReview(Request $request, Ticket $ticket, TicketWorkflowService $workflowService): RedirectResponse|Response
    {
        if (!Gate::forUser($request->user())->allows('ticketing.manageProvince', $ticket)) {
            return $this->restricted();
        }

        try {
            $workflowService->markProvinceUnderReview($ticket, $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['ticket_status' => $exception->getMessage()]);
        }

        return redirect()->route('ticketing.show', $ticket)->with('success', 'Ticket is now under provincial review.');
    }

    public function provinceAccept(Request $request, Ticket $ticket, TicketWorkflowService $workflowService): RedirectResponse|Response
    {
        if (!Gate::forUser($request->user())->allows('ticketing.acceptProvince', $ticket)) {
            return $this->restricted();
        }

        try {
            $workflowService->acceptByProvince($ticket, $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['ticket_acceptance' => $exception->getMessage()]);
        }

        return redirect()->route('ticketing.show', $ticket)->with('success', 'Ticket accepted successfully. You can now review and process it.');
    }

    public function provinceResolve(TicketResolveRequest $request, Ticket $ticket, TicketWorkflowService $workflowService): RedirectResponse|Response
    {
        if (!Gate::forUser($request->user())->allows('ticketing.manageProvince', $ticket)) {
            return $this->restricted();
        }

        try {
            $workflowService->resolveByProvince($ticket, $request->user(), $request->validated('resolution_note'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['ticket_resolution' => $exception->getMessage()]);
        }

        return redirect()->route('ticketing.show', $ticket)->with('success', 'Ticket resolved.');
    }

    public function provinceEscalate(TicketEscalateRequest $request, Ticket $ticket, TicketWorkflowService $workflowService): RedirectResponse|Response
    {
        if (!Gate::forUser($request->user())->allows('ticketing.manageProvince', $ticket)) {
            return $this->restricted();
        }

        try {
            $workflowService->escalateToRegion(
                ticket: $ticket,
                actor: $request->user(),
                reason: $request->validated('escalation_reason'),
                comment: $request->validated('comment'),
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['ticket_escalation' => $exception->getMessage()]);
        }

        return redirect()->route('ticketing.show', $ticket)->with('success', 'Ticket escalated to Regional.');
    }

    public function regionStartReview(Request $request, Ticket $ticket, TicketWorkflowService $workflowService): RedirectResponse|Response
    {
        if (!Gate::forUser($request->user())->allows('ticketing.manageRegion', $ticket)) {
            return $this->restricted();
        }

        try {
            $workflowService->markRegionUnderReview($ticket, $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['ticket_status' => $exception->getMessage()]);
        }

        return redirect()->route('ticketing.show', $ticket)->with('success', 'Ticket is now under regional review.');
    }

    public function regionAccept(Request $request, Ticket $ticket, TicketWorkflowService $workflowService): RedirectResponse|Response
    {
        if (!Gate::forUser($request->user())->allows('ticketing.acceptRegion', $ticket)) {
            return $this->restricted();
        }

        try {
            $workflowService->acceptByRegion($ticket, $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['ticket_acceptance' => $exception->getMessage()]);
        }

        return redirect()->route('ticketing.show', $ticket)->with('success', 'Ticket accepted successfully. You can now review and process it at the regional level.');
    }

    public function regionResolve(TicketResolveRequest $request, Ticket $ticket, TicketWorkflowService $workflowService): RedirectResponse|Response
    {
        if (!Gate::forUser($request->user())->allows('ticketing.manageRegion', $ticket)) {
            return $this->restricted();
        }

        try {
            $workflowService->resolveByRegion($ticket, $request->user(), $request->validated('resolution_note'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['ticket_resolution' => $exception->getMessage()]);
        }

        return redirect()->route('ticketing.show', $ticket)->with('success', 'Ticket resolved.');
    }

    public function regionForward(TicketForwardRequest $request, Ticket $ticket, TicketWorkflowService $workflowService): RedirectResponse|Response
    {
        if (!Gate::forUser($request->user())->allows('ticketing.manageRegion', $ticket)) {
            return $this->restricted();
        }

        try {
            $workflowService->forwardToCentralOffice($ticket, $request->user(), $request->validated('forward_note'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['ticket_forward' => $exception->getMessage()]);
        }

        return redirect()->route('ticketing.show', $ticket)->with('success', 'Ticket forwarded to Central Office.');
    }

    protected function activeCategories()
    {
        return TicketCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    protected function applyFilters(Request $request, Builder $query): Builder
    {
        return $query
            ->search($request->input('search'))
            ->when($request->filled('status'), fn (Builder $builder) => $builder->where('status', $request->input('status')))
            ->when($request->filled('priority'), fn (Builder $builder) => $builder->where('priority', $request->input('priority')))
            ->when($request->filled('category_id'), fn (Builder $builder) => $builder->where('category_id', $request->input('category_id')));
    }

    protected function restricted(): Response
    {
        return response()->view('errors.restricted', [], 403);
    }
}
