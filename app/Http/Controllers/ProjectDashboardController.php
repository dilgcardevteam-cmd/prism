<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProjectDashboardController
{
    public function __invoke(Request $request): View
    {
        // Extract the $renderProjectDashboard closure logic here
        // MOVED TO THIS METHOD FROM routes/web.php inline closure
        
        $activeProjectTab = $request->query('tab', 'locally-funded');
        
        // Paste the full $renderProjectDashboard logic here...
        // For now, return placeholder - full migration in next step
        return view('dashboard.index', [
            'activeProjectTab' => $activeProjectTab,
            // Add all compact vars here after migration
        ]);
    }
    
    /**
     * Get filter options for cascading dropdowns (AJAX)
     */
    public function getFilterOptions(Request $request, string $type): Response
    {
        // Reuse logic - will be extracted to service
        $filterType = strtolower(trim($type));
        $validTypes = ['cities', 'barangays', 'programs', 'funding_years', 'project_types', 'project_statuses'];
        if (!in_array($filterType, $validTypes)) {
            return response()->json(['error' => 'Invalid filter type'], 400);
        }

        // TODO: Extract common query builder from __invoke
        $options = collect();
        $preserve = [];
        
        return response()->json([
            'options' => $options->values()->all(),
            'preserve' => $preserve
        ]);
    }
}

