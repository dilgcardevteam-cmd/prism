<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TicketHistoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'crud_permission:ticketing_system,view']);
    }

    public function index(Request $request, Ticket $ticket): JsonResponse|Response
    {
        if (!Gate::forUser($request->user())->allows('ticketing.view', $ticket)) {
            return response()->view('errors.restricted', [], 403);
        }

        $ticket->load(['histories.actor']);

        return response()->json([
            'ticket_number' => $ticket->ticket_number,
            'history' => $ticket->histories->map(function ($entry) {
                return [
                    'action' => $entry->action,
                    'description' => $entry->description,
                    'from_status' => $entry->from_status,
                    'to_status' => $entry->to_status,
                    'from_level' => $entry->from_level,
                    'to_level' => $entry->to_level,
                    'actor' => $entry->actor?->fullName(),
                    'created_at' => optional($entry->created_at)->toDateTimeString(),
                    'metadata' => $entry->metadata,
                ];
            }),
        ]);
    }
}
