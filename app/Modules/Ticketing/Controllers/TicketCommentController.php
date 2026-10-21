<?php

namespace App\Modules\Ticketing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ticketing\Models\Ticket;
use App\Modules\Ticketing\Requests\TicketCommentStoreRequest;
use App\Modules\Ticketing\Services\TicketWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TicketCommentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'crud_permission:ticketing_system,view']);
    }

    public function store(TicketCommentStoreRequest $request, Ticket $ticket, TicketWorkflowService $workflowService): RedirectResponse|Response
    {
        if (!Gate::forUser($request->user())->allows('ticketing.addComment', $ticket)) {
            return response()->view('errors.restricted', [], 403);
        }

        $workflowService->addComment($ticket, $request->user(), $request->validated('comment'));

        return redirect()
            ->route('ticketing.show', $ticket)
            ->with('success', 'Ticket remark saved successfully.');
    }
}
