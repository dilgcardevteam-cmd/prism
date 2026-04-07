<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Services\TicketCategoryRequest;
use App\Services\TicketResolveRequest;
use App\Services\TicketWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'crud_permission:ticketing_system,view', 'role:admin']);
    }

    public function index(Request $request)
    {
        $tickets = $this->applyFilters($request, Ticket::query()
            ->with(['category', 'submitter', 'assignee']))
            ->orderByDesc('updated_at')
            ->paginate(12)
            ->withQueryString();

        return view('ticketing-admin', [
            'tickets' => $tickets,
            'categories' => TicketCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'statuses' => Ticket::statusOptions(),
            'priorities' => Ticket::priorityOptions(),
        ]);
    }

    public function storeCategory(TicketCategoryRequest $request): RedirectResponse
    {
        TicketCategory::create([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($request->validated('sort_order') ?? 0),
        ]);

        return back()->with('success', 'Ticket category saved successfully.');
    }

    public function updateCategory(TicketCategoryRequest $request, TicketCategory $category): RedirectResponse
    {
        $category->update([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($request->validated('sort_order') ?? 0),
        ]);

        return back()->with('success', 'Ticket category updated successfully.');
    }

    public function destroyCategory(TicketCategory $category): RedirectResponse
    {
        if ($category->tickets()->exists()) {
            $category->update(['is_active' => false]);

            return back()->with('success', 'Category has ticket records, so it was deactivated instead of deleted.');
        }

        $category->delete();

        return back()->with('success', 'Ticket category deleted successfully.');
    }

    public function closeTicket(TicketResolveRequest $request, Ticket $ticket, TicketWorkflowService $workflowService): RedirectResponse|Response
    {
        try {
            $workflowService->close($ticket, $request->user(), $request->validated('resolution_note'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['ticket_close' => $exception->getMessage()]);
        }

        return redirect()->route('ticketing.show', $ticket)->with('success', 'Ticket closed successfully.');
    }

    protected function applyFilters(Request $request, Builder $query): Builder
    {
        return $query
            ->search($request->input('search'))
            ->when($request->filled('status'), fn (Builder $builder) => $builder->where('status', $request->input('status')))
            ->when($request->filled('priority'), fn (Builder $builder) => $builder->where('priority', $request->input('priority')))
            ->when($request->filled('category_id'), fn (Builder $builder) => $builder->where('category_id', $request->input('category_id')));
    }
}
