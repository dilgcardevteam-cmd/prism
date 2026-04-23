<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectAtRiskController extends Controller
{
    private const IMPORT_HISTORY_TABLE = 'project_at_risk_import_histories';
    private const IMPORT_STORAGE_DIRECTORY = 'project-at-risk-imports';
    private const TEMPLATE_HEADERS = [
        'Project Code',
        'LGU',
        'Region',
        'Province',
        'City/Municipality',
        'Barangays',
        'Funding Year',
        'Program',
        'Project Title',
        'Procurement Type',
        'National Subsidy (Original Allocation)',
        'Status',
        'Target',
        'Actual',
        'Slippage',
        'Date of Accomplishment',
        'Date of Extraction',
        'Aging',
        'Risk Level as to Slippage',
        'Risk Level as to Aging',
    ];

    public function __construct()
    {
        $this->middleware('auth')->except(['mobileSlippageSummary', 'mobileAgingSummary', 'mobileProjectUpdateStatusSummary']);
        $this->middleware('crud_permission:project_at_risk_projects,view')->only(['index', 'export']);
        $this->middleware('crud_permission:project_at_risk_data_uploads,view')->only(['uploadManager', 'downloadTemplate', 'downloadImport']);
        $this->middleware('crud_permission:project_at_risk_data_uploads,add')->only(['import']);
        $this->middleware('crud_permission:project_at_risk_data_uploads,update')->only(['loadImport']);
        $this->middleware('crud_permission:project_at_risk_data_uploads,delete')->only(['deleteImport']);
    }

    private function normalizeRiskLevel($riskLevel): ?string
    {
        $raw = strtoupper(trim((string) $riskLevel));
        if ($raw === '') {
            return null;
        }

        $compact = preg_replace('/[^A-Z]/', '', $raw) ?? '';
        if ($compact === '') {
            return null;
        }

        if (str_contains($compact, 'AHEAD')) {
            return 'Ahead';
        }
        if (str_contains($compact, 'ONSCHEDULE')) {
            return 'On Schedule';
        }
        if (str_contains($compact, 'NORISK')) {
            return 'No Risk';
        }
        if (str_contains($compact, 'HIGHRISK')) {
            return 'High Risk';
        }
        if (str_contains($compact, 'MODERATERISK')) {
            return 'Moderate Risk';
        }
        if (str_contains($compact, 'LOWRISK')) {
            return 'Low Risk';
        }

        return null;
    }

    public function mobileSlippageSummary()
    {
        $summaryOrder = ['On Schedule', 'Ahead', 'No Risk', 'Low Risk', 'Moderate Risk', 'High Risk'];
        $chartOrder = ['Ahead', 'No Risk', 'On Schedule', 'High Risk', 'Moderate Risk', 'Low Risk'];
        $counts = array_fill_keys($summaryOrder, 0);

        if (!Schema::hasTable('project_at_risks')) {
            return response()->json([
                'data' => collect($summaryOrder)->map(fn ($label) => ['label' => $label, 'count' => 0])->values(),
                'meta' => ['total' => 0, 'chart_order' => $chartOrder, 'summary_order' => $summaryOrder],
            ]);
        }

        $riskBaseQuery = DB::table('project_at_risks as par')
            ->selectRaw('UPPER(TRIM(par.project_code)) as project_code')
            ->selectRaw('TRIM(COALESCE(par.risk_level, "")) as risk_level_value')
            ->selectRaw("COALESCE(par.date_of_extraction, '1900-01-01') as extraction_date")
            ->addSelect('par.id')
            ->whereNotNull('par.project_code')
            ->whereRaw('TRIM(par.project_code) <> ""');

        if (Auth::check()) {
            $this->applyUserScopeToProjectAtRiskQuery($riskBaseQuery);
        }

        $latestExtractionByProject = DB::query()
            ->fromSub($riskBaseQuery, 'risk_base')
            ->selectRaw('risk_base.project_code')
            ->selectRaw('MAX(risk_base.extraction_date) as latest_extraction')
            ->groupBy('risk_base.project_code');

        $latestRowsByExtraction = DB::query()
            ->fromSub($riskBaseQuery, 'risk_base')
            ->joinSub($latestExtractionByProject, 'risk_latest', function ($join) {
                $join->on('risk_base.project_code', '=', 'risk_latest.project_code')
                    ->on('risk_base.extraction_date', '=', 'risk_latest.latest_extraction');
            })
            ->select('risk_base.project_code', 'risk_base.id', 'risk_base.risk_level_value');

        $latestIdByProject = DB::query()
            ->fromSub($latestRowsByExtraction, 'risk_rows')
            ->selectRaw('risk_rows.project_code')
            ->selectRaw('MAX(risk_rows.id) as latest_id')
            ->groupBy('risk_rows.project_code');

        $finalRiskRows = DB::query()
            ->fromSub($riskBaseQuery, 'risk_base')
            ->joinSub($latestIdByProject, 'risk_latest_id', function ($join) {
                $join->on('risk_base.project_code', '=', 'risk_latest_id.project_code')
                    ->on('risk_base.id', '=', 'risk_latest_id.latest_id');
            })
            ->select('risk_base.risk_level_value')
            ->get();

        foreach ($finalRiskRows as $row) {
            $riskLabel = $this->normalizeRiskLevel($row->risk_level_value ?? null);
            if ($riskLabel !== null && array_key_exists($riskLabel, $counts)) {
                $counts[$riskLabel] += 1;
            }
        }

        $rows = collect($summaryOrder)->map(function ($label) use ($counts) {
            return [
                'label' => $label,
                'count' => (int) ($counts[$label] ?? 0),
            ];
        })->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'total' => (int) $rows->sum('count'),
                'chart_order' => $chartOrder,
                'summary_order' => $summaryOrder,
            ],
        ]);
    }

    public function mobileAgingSummary()
    {
        $summaryOrder = ['High Risk', 'Low Risk', 'No Risk'];
        $counts = array_fill_keys($summaryOrder, 0);

        if (!Schema::hasTable('project_at_risks')) {
            return response()->json([
                'data' => collect($summaryOrder)->map(fn ($label) => ['label' => $label, 'count' => 0])->values(),
                'meta' => ['total' => 0, 'summary_order' => $summaryOrder],
            ]);
        }

        $riskBaseQuery = DB::table('project_at_risks as par')
            ->selectRaw('UPPER(TRIM(par.project_code)) as project_code')
            ->selectRaw('TRIM(COALESCE(par.aging, "")) as aging_value')
            ->selectRaw("COALESCE(par.date_of_extraction, '1900-01-01') as extraction_date")
            ->addSelect('par.id')
            ->whereNotNull('par.project_code')
            ->whereRaw('TRIM(par.project_code) <> ""');

        if (Auth::check()) {
            $this->applyUserScopeToProjectAtRiskQuery($riskBaseQuery);
        }

        $latestExtractionByProject = DB::query()
            ->fromSub($riskBaseQuery, 'risk_base')
            ->selectRaw('risk_base.project_code')
            ->selectRaw('MAX(risk_base.extraction_date) as latest_extraction')
            ->groupBy('risk_base.project_code');

        $latestRowsByExtraction = DB::query()
            ->fromSub($riskBaseQuery, 'risk_base')
            ->joinSub($latestExtractionByProject, 'risk_latest', function ($join) {
                $join->on('risk_base.project_code', '=', 'risk_latest.project_code')
                    ->on('risk_base.extraction_date', '=', 'risk_latest.latest_extraction');
            })
            ->select('risk_base.project_code', 'risk_base.id', 'risk_base.aging_value');

        $latestIdByProject = DB::query()
            ->fromSub($latestRowsByExtraction, 'risk_rows')
            ->selectRaw('risk_rows.project_code')
            ->selectRaw('MAX(risk_rows.id) as latest_id')
            ->groupBy('risk_rows.project_code');

        $finalAgingRows = DB::query()
            ->fromSub($riskBaseQuery, 'risk_base')
            ->joinSub($latestIdByProject, 'risk_latest_id', function ($join) {
                $join->on('risk_base.project_code', '=', 'risk_latest_id.project_code')
                    ->on('risk_base.id', '=', 'risk_latest_id.latest_id');
            })
            ->select('risk_base.aging_value')
            ->get();

        foreach ($finalAgingRows as $row) {
            $rawAging = trim((string) ($row->aging_value ?? ''));
            if ($rawAging === '') {
                continue;
            }

            if (is_numeric($rawAging)) {
                $agingValue = (float) $rawAging;
            } else {
                $cleanedAging = preg_replace('/[^0-9\.\-]/', '', $rawAging);
                if ($cleanedAging === null || $cleanedAging === '' || !is_numeric($cleanedAging)) {
                    continue;
                }
                $agingValue = (float) $cleanedAging;
            }

            if ($agingValue >= 60) {
                $riskLabel = 'High Risk';
            } elseif ($agingValue > 30 && $agingValue < 60) {
                $riskLabel = 'Low Risk';
            } else {
                $riskLabel = 'No Risk';
            }

            if (array_key_exists($riskLabel, $counts)) {
                $counts[$riskLabel] += 1;
            }
        }

        $rows = collect($summaryOrder)->map(function ($label) use ($counts) {
            return [
                'label' => $label,
                'count' => (int) ($counts[$label] ?? 0),
            ];
        })->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'total' => (int) $rows->sum('count'),
                'summary_order' => $summaryOrder,
            ],
        ]);
    }

    public function mobileProjectUpdateStatusSummary()
    {
        $summaryOrder = ['High Risk', 'Low Risk', 'No Risk'];
        $counts = array_fill_keys($summaryOrder, 0);

        if (!Schema::hasTable('subay_project_profiles')) {
            return response()->json([
                'data' => collect($summaryOrder)->map(fn ($label) => ['label' => $label, 'count' => 0])->values(),
                'meta' => ['total' => 0, 'summary_order' => $summaryOrder],
            ]);
        }

        $projectUpdateRowsQuery = DB::table('subay_project_profiles as spp')
            ->whereNotNull('spp.project_code')
            ->whereRaw('TRIM(COALESCE(spp.project_code, "")) <> ""')
            ->whereRaw('UPPER(TRIM(COALESCE(spp.project_code, ""))) NOT LIKE ?', ['SGLGIF%'])
            ->whereRaw('UPPER(TRIM(COALESCE(spp.program, ""))) <> ?', ['SGLGIF'])
            ->selectRaw('LOWER(TRIM(COALESCE(spp.status, ""))) as status_raw')
            ->selectRaw($this->projectUpdateParsedDateExpression() . ' as latest_update_date');

        $aggregatedCounts = DB::query()
            ->fromSub($projectUpdateRowsQuery, 'project_updates')
            ->where('project_updates.status_raw', '!=', 'completed')
            ->selectRaw('SUM(CASE WHEN project_updates.latest_update_date IS NOT NULL AND DATEDIFF(CURDATE(), project_updates.latest_update_date) >= 60 THEN 1 ELSE 0 END) as high_risk_total')
            ->selectRaw('SUM(CASE WHEN project_updates.latest_update_date IS NOT NULL AND DATEDIFF(CURDATE(), project_updates.latest_update_date) > 30 AND DATEDIFF(CURDATE(), project_updates.latest_update_date) < 60 THEN 1 ELSE 0 END) as low_risk_total')
            ->selectRaw('SUM(CASE WHEN project_updates.latest_update_date IS NOT NULL AND DATEDIFF(CURDATE(), project_updates.latest_update_date) <= 30 THEN 1 ELSE 0 END) as no_risk_total')
            ->first();

        $counts['High Risk'] = (int) ($aggregatedCounts->high_risk_total ?? 0);
        $counts['Low Risk'] = (int) ($aggregatedCounts->low_risk_total ?? 0);
        $counts['No Risk'] = (int) ($aggregatedCounts->no_risk_total ?? 0);

        $rows = collect($summaryOrder)->map(function ($label) use ($counts) {
            return [
                'label' => $label,
                'count' => (int) ($counts[$label] ?? 0),
            ];
        })->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'total' => (int) $rows->sum('count'),
                'summary_order' => $summaryOrder,
            ],
        ]);
    }

    private function projectUpdateParsedDateExpression(): string
    {
        return "
            COALESCE(
                IF(
                    TRIM(COALESCE(spp.date, '')) REGEXP '^[0-9]+(\\\\.[0-9]+)?$',
                    DATE_ADD('1899-12-30', INTERVAL FLOOR(CAST(TRIM(COALESCE(spp.date, '')) AS DECIMAL(12,4))) DAY),
                    NULL
                ),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%Y-%m-%d'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%Y-%m-%d %H:%i:%s'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%m/%d/%Y'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%m/%d/%Y %H:%i'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%m/%d/%Y %H:%i:%s'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%m/%d/%Y %h:%i:%s %p'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%m/%d/%y'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%d/%m/%Y'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%d-%m-%Y'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%d-%b-%Y'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%b %e, %Y'),
                STR_TO_DATE(TRIM(COALESCE(spp.date, '')), '%M %e, %Y')
            )
        ";
    }

    public function index(Request $request)
    {
        $filters = [
            'search' => $request->input('search'),
            'province' => $request->input('province'),
            'city_municipality' => $request->input('city_municipality'),
            'funding_year' => $request->input('funding_year'),
            'program' => $request->input('program'),
            'risk_level' => $request->input('risk_level'),
            'aging_range' => $request->input('aging_range'),
            'extraction_month' => $request->input('extraction_month'),
            'extraction_year' => $request->input('extraction_year'),
        ];

        $records = $this->buildExportQuery($request)
            ->orderBy('project_code')
            ->paginate(15)
            ->withQueryString();

        $scopedOptionsBaseQuery = DB::table('project_at_risks');
        $this->applyUserScopeToProjectAtRiskQuery($scopedOptionsBaseQuery);

        $filterOptions = [
            'provinces' => (clone $scopedOptionsBaseQuery)
                ->select('province')
                ->whereNotNull('province')
                ->where('province', '!=', '')
                ->distinct()
                ->orderBy('province')
                ->pluck('province'),
            'cities' => (clone $scopedOptionsBaseQuery)
                ->select('city_municipality')
                ->whereNotNull('city_municipality')
                ->where('city_municipality', '!=', '')
                ->distinct()
                ->orderBy('city_municipality')
                ->pluck('city_municipality'),
            'funding_years' => (clone $scopedOptionsBaseQuery)
                ->select('funding_year')
                ->whereNotNull('funding_year')
                ->distinct()
                ->orderBy('funding_year')
                ->pluck('funding_year'),
            'programs' => (clone $scopedOptionsBaseQuery)
                ->select('name_of_program')
                ->whereNotNull('name_of_program')
                ->where('name_of_program', '!=', '')
                ->distinct()
                ->orderBy('name_of_program')
                ->pluck('name_of_program'),
            'risk_levels' => (clone $scopedOptionsBaseQuery)
                ->select('risk_level')
                ->whereNotNull('risk_level')
                ->where('risk_level', '!=', '')
                ->distinct()
                ->orderBy('risk_level')
                ->pluck('risk_level'),
            'extraction_months' => (clone $scopedOptionsBaseQuery)
                ->selectRaw('MONTH(date_of_extraction) as month')
                ->whereNotNull('date_of_extraction')
                ->distinct()
                ->orderBy('month')
                ->pluck('month'),
            'extraction_years' => (clone $scopedOptionsBaseQuery)
                ->selectRaw('YEAR(date_of_extraction) as year')
                ->whereNotNull('date_of_extraction')
                ->distinct()
                ->orderBy('year')
                ->pluck('year'),
        ];

        return view('projects.at-risk', compact('records', 'filters', 'filterOptions'));
    }

    public function uploadManager()
    {
        $tableMissing = !Schema::hasTable('project_at_risks');
        $importHistoryTableMissing = !Schema::hasTable(self::IMPORT_HISTORY_TABLE);

        $importHistoryRows = $importHistoryTableMissing
            ? collect()
            : DB::table(self::IMPORT_HISTORY_TABLE)
                ->orderByDesc('imported_at')
                ->orderByDesc('id')
                ->paginate(15);

        return view('system-management.upload-project-at-risk', [
            'tableMissing' => $tableMissing,
            'importHistoryRows' => $importHistoryRows,
            'importHistoryTableMissing' => $importHistoryTableMissing,
        ]);
    }

    public function downloadTemplate()
    {
        return response()->streamDownload(function () {
            echo "\xEF\xBB\xBF";
            $handle = fopen('php://output', 'w');
            fputcsv($handle, self::TEMPLATE_HEADERS);
            fclose($handle);
        }, 'project-at-risk-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(Request $request)
    {
        if (!Schema::hasTable('project_at_risks')) {
            return back()->with('error', 'Project At Risk data table is not available yet.');
        }

        $request->validate(
            [
                'file' => ['required', 'file', 'mimes:csv,txt', 'max:51200'],
            ],
            [
                'file.mimes' => 'Please upload a CSV file. If your data is in Excel, save it as CSV first.',
            ]
        );

        $file = $request->file('file');
        if (!$file) {
            return back()->with('error', 'No file was uploaded.');
        }

        $originalFileName = (string) $file->getClientOriginalName();
        $storageFileName = $this->generateImportStorageFileName($originalFileName);
        $storedPath = $file->storeAs(self::IMPORT_STORAGE_DIRECTORY, $storageFileName, 'local');
        if (!$storedPath) {
            return back()->with('error', 'Unable to store the uploaded file.');
        }

        if (!Schema::hasTable(self::IMPORT_HISTORY_TABLE)) {
            Storage::disk('local')->delete($storedPath);
            return back()->with('error', 'Import history table is not available yet. Please run migration first.');
        }

        $now = now();
        DB::table(self::IMPORT_HISTORY_TABLE)->insert([
            'original_file_name' => $originalFileName !== '' ? $originalFileName : basename($storedPath),
            'stored_file_path' => $storedPath,
            'file_size_bytes' => $file->getSize(),
            'imported_at' => $now,
            'last_loaded_at' => null,
            'created_by' => auth()->id(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return redirect()
            ->route('system-management.upload-project-at-risk')
            ->with('success', 'CSV file added to import history. Click Load to replace the current Project At Risk data.');
    }

    public function loadImport($importId)
    {
        if (!Schema::hasTable('project_at_risks')) {
            return back()->with('error', 'Project At Risk data table is not available yet.');
        }

        if (!Schema::hasTable(self::IMPORT_HISTORY_TABLE)) {
            return back()->with('error', 'Import history table is not available yet. Please run migration first.');
        }

        $record = DB::table(self::IMPORT_HISTORY_TABLE)
            ->where('id', (int) $importId)
            ->first();

        if (!$record) {
            return back()->with('error', 'Selected import record was not found.');
        }

        $storedPath = trim((string) ($record->stored_file_path ?? ''));
        if ($storedPath === '' || !Storage::disk('local')->exists($storedPath)) {
            return back()->with('error', 'The selected imported file is no longer available.');
        }

        try {
            $inserted = $this->loadCsvSnapshot(Storage::disk('local')->path($storedPath));
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        DB::table(self::IMPORT_HISTORY_TABLE)
            ->where('id', (int) $importId)
            ->update([
                'last_loaded_at' => now(),
                'updated_at' => now(),
            ]);

        $displayName = trim((string) ($record->original_file_name ?? ''));
        if ($displayName === '') {
            $displayName = basename($storedPath);
        }

        return back()->with('success', "Loaded {$inserted} rows from {$displayName}.");
    }

    public function deleteImport($importId)
    {
        if (!Schema::hasTable(self::IMPORT_HISTORY_TABLE)) {
            return back()->with('error', 'Import history table is not available yet. Please run migration first.');
        }

        $record = DB::table(self::IMPORT_HISTORY_TABLE)
            ->where('id', (int) $importId)
            ->first();

        if (!$record) {
            return back()->with('error', 'Selected import record was not found.');
        }

        $storedPath = trim((string) ($record->stored_file_path ?? ''));
        if ($storedPath !== '' && Storage::disk('local')->exists($storedPath)) {
            Storage::disk('local')->delete($storedPath);
        }

        DB::table(self::IMPORT_HISTORY_TABLE)
            ->where('id', (int) $importId)
            ->delete();

        return back()->with('success', 'Imported file record deleted successfully.');
    }

    public function downloadImport($importId)
    {
        if (!Schema::hasTable(self::IMPORT_HISTORY_TABLE)) {
            return back()->with('error', 'Import history table is not available yet. Please run migration first.');
        }

        $record = DB::table(self::IMPORT_HISTORY_TABLE)
            ->where('id', (int) $importId)
            ->first();

        if (!$record) {
            return back()->with('error', 'Selected import record was not found.');
        }

        $storedPath = trim((string) ($record->stored_file_path ?? ''));
        if ($storedPath === '' || !Storage::disk('local')->exists($storedPath)) {
            return back()->with('error', 'The selected imported file is no longer available.');
        }

        $downloadName = trim((string) ($record->original_file_name ?? ''));
        if ($downloadName === '') {
            $downloadName = basename($storedPath);
        }
        $downloadName = basename($downloadName);

        $extension = strtolower(pathinfo($downloadName, PATHINFO_EXTENSION));
        $contentType = in_array($extension, ['csv', 'txt'], true)
            ? 'text/csv; charset=UTF-8'
            : 'application/octet-stream';

        return response()->download(
            Storage::disk('local')->path($storedPath),
            $downloadName,
            [
                'Content-Type' => $contentType,
            ]
        );
    }

    public function export(Request $request)
    {
        $query = $this->buildExportQuery($request);
        $headers = [
            'Project Code',
            'LGU',
            'Barangay/s',
            'Funding Year',
            'Program',
            'Project Title',
            'National Subsidy (Original Allocation)',
            'Slippage',
            'Risk Level as to Slippage',
            'Aging',
        ];

        $maxLengths = array_map('strlen', $headers);
        $widthQuery = clone $query;
        $widthQuery->orderBy('project_code')->chunk(500, function ($rows) use (&$maxLengths) {
            foreach ($rows as $row) {
                $values = $this->mapExportRow($row);
                foreach ($values as $index => $value) {
                    $length = $this->maxLineLength($value);
                    if ($length > ($maxLengths[$index] ?? 0)) {
                        $maxLengths[$index] = $length;
                    }
                }
            }
        });

        $timestamp = now()->format('Ymd_His');
        $filename = "project-at-risk-{$timestamp}.xls";
        $responseHeaders = [
            'Content-Type' => 'application/vnd.ms-excel',
        ];

        return response()->streamDownload(function () use ($query, $headers, $maxLengths) {
            $write = function ($value) {
                echo $value;
            };

            $write('<?xml version="1.0"?>' . "\n");
            $write('<?mso-application progid="Excel.Sheet"?>' . "\n");
            $write('<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ');
            $write('xmlns:o="urn:schemas-microsoft-com:office:office" ');
            $write('xmlns:x="urn:schemas-microsoft-com:office:excel" ');
            $write('xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">');

            $write('<Styles>');
            $write('<Style ss:ID="Header">');
            $write('<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>');
            $write('<Font ss:Bold="1"/>');
            $write('<Interior ss:Color="#F3F4F6" ss:Pattern="Solid"/>');
            $write('</Style>');
            $write('<Style ss:ID="Cell">');
            $write('<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>');
            $write('</Style>');
            $write('<Style ss:ID="Money">');
            $write('<Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>');
            $write('<NumberFormat ss:Format="#,##0.00"/>');
            $write('</Style>');
            $write('</Styles>');

            $write('<Worksheet ss:Name="Project At Risk">');
            $write('<Table>');

            foreach ($maxLengths as $length) {
                $width = $this->columnWidth($length);
                $write('<Column ss:AutoFitWidth="1" ss:Width="' . $width . '"/>');
            }

            $write('<Row>');
            foreach ($headers as $header) {
                $write('<Cell ss:StyleID="Header"><Data ss:Type="String">' . $this->escapeXml($header) . '</Data></Cell>');
            }
            $write('</Row>');

            $query->orderBy('project_code')->chunk(500, function ($rows) use ($write) {
                foreach ($rows as $row) {
                    $values = $this->mapExportRow($row);
                    $write('<Row>');
                    foreach ($values as $index => $value) {
                        if ($index === 6 && $value !== '') {
                            $write('<Cell ss:StyleID="Money"><Data ss:Type="Number">' . $this->escapeXml($value) . '</Data></Cell>');
                            continue;
                        }

                        $write('<Cell ss:StyleID="Cell"><Data ss:Type="String">' . $this->escapeXml($value) . '</Data></Cell>');
                    }
                    $write('</Row>');
                }
            });

            $write('</Table></Worksheet></Workbook>');
        }, $filename, $responseHeaders);
    }

    private function buildHeaderMap(array $header): array
    {
        $known = [
            'projectcode' => 'project_code',
            'projectid' => 'project_code',
            'lgu' => 'lgu',
            'localgovernmentunit' => 'lgu',
            'region' => 'region',
            'province' => 'province',
            'citymunicipality' => 'city_municipality',
            'citymun' => 'city_municipality',
            'municipality' => 'city_municipality',
            'barangays' => 'barangays',
            'barangay' => 'barangays',
            'fundingyear' => 'funding_year',
            'program' => 'name_of_program',
            'nameofprogram' => 'name_of_program',
            'programname' => 'name_of_program',
            'projecttitle' => 'project_title',
            'procurementtype' => 'procurement_type',
            'modeofprocurement' => 'procurement_type',
            'nationalsubsidy' => 'national_subsidy',
            'nationalsubsidyoriginalallocation' => 'national_subsidy',
            'originalallocation' => 'national_subsidy',
            'status' => 'status',
            'target' => 'target',
            'actual' => 'actual',
            'slippage' => 'slippage',
            'dateofaccomplishment' => 'date_of_accomplishment',
            'dateofaccomplushment' => 'date_of_accomplishment',
            'dateofextraction' => 'date_of_extraction',
            'aging' => 'aging',
            'risklevel' => 'risk_level',
            'risklevelastosubaybayan' => 'risk_level',
            'risklevelassubaybayan' => 'risk_level',
            'risklevelastoslippage' => 'risk_level',
            'risklevelasslippage' => 'risk_level',
            'risklevelaging' => 'risk_level_aging',
            'risklevelastoaging' => 'risk_level_aging',
            'risklevelasstoaging' => 'risk_level_aging',
        ];

        $map = [];
        foreach ($header as $index => $label) {
            $key = $this->normalizeHeader($label);
            if ($key !== '' && isset($known[$key])) {
                $map[$index] = $known[$key];
            }
        }

        return $map;
    }

    private function loadCsvSnapshot(string $path): int
    {
        if (!is_readable($path)) {
            throw new \RuntimeException('Unable to read the selected file.');
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open the selected file.');
        }

        try {
            $header = fgetcsv($handle);
            if ($header === false) {
                throw new \RuntimeException('The selected file appears to be empty.');
            }

            $headerMap = $this->buildHeaderMap($header);
            if (empty($headerMap)) {
                throw new \RuntimeException('No recognizable columns were found in the CSV file.');
            }

            return DB::transaction(function () use ($handle, $headerMap) {
                $now = now();
                $rows = [];
                $inserted = 0;

                DB::table('project_at_risks')->delete();

                while (($data = fgetcsv($handle)) !== false) {
                    if ($this->rowIsEmpty($data)) {
                        continue;
                    }

                    $row = $this->mapRow($data, $headerMap);
                    if (empty($row)) {
                        continue;
                    }

                    $row['created_at'] = $now;
                    $row['updated_at'] = $now;
                    $rows[] = $row;

                    if (count($rows) >= 500) {
                        DB::table('project_at_risks')->insert($rows);
                        $inserted += count($rows);
                        $rows = [];
                    }
                }

                if (!empty($rows)) {
                    DB::table('project_at_risks')->insert($rows);
                    $inserted += count($rows);
                }

                if ($inserted === 0) {
                    throw new \RuntimeException('No valid rows were found in the selected import file.');
                }

                return $inserted;
            });
        } finally {
            fclose($handle);
        }
    }

    private function generateImportStorageFileName(string $originalFileName): string
    {
        $extension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
        $baseName = pathinfo($originalFileName, PATHINFO_FILENAME);
        $baseNameSlug = Str::slug($baseName);
        if ($baseNameSlug === '') {
            $baseNameSlug = 'project-at-risk';
        }

        $timestamp = now()->format('Ymd_His');
        $randomSuffix = Str::lower(Str::random(8));
        $fileName = $timestamp . '_' . $baseNameSlug . '_' . $randomSuffix;

        return $fileName . ($extension !== '' ? '.' . $extension : '.csv');
    }

    private function normalizeHeader($value): string
    {
        $value = is_string($value) ? $value : '';
        $value = ltrim($value, "\xEF\xBB\xBF");
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]/', '', $value);
        return $value ?? '';
    }

    private function rowIsEmpty(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function mapRow(array $data, array $map): array
    {
        $row = [];
        foreach ($map as $index => $column) {
            $value = $data[$index] ?? null;
            $value = is_string($value) ? trim($value) : $value;

            if ($value === '' || $value === null) {
                $row[$column] = null;
                continue;
            }

            switch ($column) {
                case 'funding_year':
                    $row[$column] = $this->parseYear($value);
                    break;
                case 'national_subsidy':
                case 'target':
                case 'actual':
                case 'slippage':
                    $row[$column] = $this->parseDecimal($value);
                    break;
                case 'aging':
                    $row[$column] = $this->parseInteger($value);
                    break;
                case 'date_of_accomplishment':
                case 'date_of_extraction':
                    $row[$column] = $this->parseDate($value);
                    break;
                default:
                    $row[$column] = $value;
            }
        }

        return $row;
    }

    private function buildExportQuery(Request $request)
    {
        $query = DB::table('project_at_risks');
        $this->applyUserScopeToProjectAtRiskQuery($query);

        if ($request->filled('province')) {
            $query->where('province', $request->input('province'));
        }
        if ($request->filled('city_municipality')) {
            $query->where('city_municipality', $request->input('city_municipality'));
        }
        if ($request->filled('funding_year')) {
            $query->where('funding_year', $request->input('funding_year'));
        }
        if ($request->filled('program')) {
            $query->where('name_of_program', $request->input('program'));
        }
        if ($request->filled('risk_level')) {
            $selectedRiskLevel = trim((string) $request->input('risk_level'));
            $normalizedRiskLevel = strtoupper(preg_replace('/[^A-Z]/', '', $selectedRiskLevel) ?? '');
            $canonicalRiskLevels = [
                'AHEAD',
                'ONSCHEDULE',
                'NORISK',
                'LOWRISK',
                'MODERATERISK',
                'HIGHRISK',
            ];

            if ($normalizedRiskLevel !== '' && in_array($normalizedRiskLevel, $canonicalRiskLevels, true)) {
                $query->whereRaw(
                    "REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE(risk_level, ''))), ' ', ''), '-', ''), '_', '') = ?",
                    [$normalizedRiskLevel]
                );
            } else {
                $query->where('risk_level', $selectedRiskLevel);
            }
        }
        $extractionMonth = $request->input('extraction_month');
        if ($extractionMonth !== 'all') {
            if ($extractionMonth === null || $extractionMonth === '') {
                $extractionMonth = now()->month;
            }
            $query->whereMonth('date_of_extraction', $extractionMonth);
        }

        $extractionYear = $request->input('extraction_year');
        if ($extractionYear !== 'all') {
            if ($extractionYear === null || $extractionYear === '') {
                $extractionYear = now()->year;
            }
            $query->whereYear('date_of_extraction', $extractionYear);
        }
        if ($request->filled('aging_range')) {
            $range = $request->input('aging_range');
            if ($range === 'gt_30') {
                $query->where('aging', '>', 30);
            } elseif ($range === 'between_11_30') {
                $query->whereBetween('aging', [11, 30]);
            } elseif ($range === 'lte_10') {
                $query->where('aging', '<=', 10);
            }
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            if ($search !== '') {
                $query->where(function ($subQuery) use ($search) {
                    $like = '%' . $search . '%';
                    $subQuery->where('project_code', 'like', $like)
                        ->orWhere('barangays', 'like', $like)
                        ->orWhere('name_of_program', 'like', $like)
                        ->orWhere('project_title', 'like', $like)
                        ->orWhere('province', 'like', $like)
                        ->orWhere('city_municipality', 'like', $like)
                        ->orWhere('region', 'like', $like)
                        ->orWhere('risk_level', 'like', $like)
                        ->orWhere('slippage', 'like', $like)
                        ->orWhere('aging', 'like', $like);
                });
            }
        }

        return $query;
    }

    private function applyUserScopeToProjectAtRiskQuery($query): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $province = trim((string) $user->province);
        $office = trim((string) $user->office);
        $region = trim((string) $user->region);
        $provinceLower = $user->normalizedProvince();
        $officeLower = $user->normalizedOffice();
        $regionLower = $user->normalizedRegion();
        $officeComparableLower = $user->normalizedOfficeComparable();
        $isRegionalOfficeUser = $user->isRegionalOfficeAssignment();

        if ($user->isLguScopedUser()) {
            if ($province !== '') {
                $query->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', [$provinceLower]);
            }
            if ($office !== '') {
                $this->applyOfficeScopeToProjectAtRiskQuery($query, $officeLower, $officeComparableLower);
            }

            return;
        }

        if (!$user->isDilgUser()) {
            return;
        }

        if ($isRegionalOfficeUser) {
            return;
        }

        if ($province !== '') {
            $query->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', [$provinceLower]);
            return;
        }

        if ($region !== '') {
            $query->whereRaw('LOWER(TRIM(COALESCE(region, ""))) = ?', [$regionLower]);
        }
    }

    private function applyOfficeScopeToProjectAtRiskQuery($query, string $officeLower, string $officeComparableLower): void
    {
        if ($officeLower === '') {
            return;
        }

        $officeNeedle = $officeComparableLower !== '' ? $officeComparableLower : $officeLower;
        $cityComparableExpression = "TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(SUBSTRING_INDEX(COALESCE(city_municipality, ''), ',', 1)), '(capital)', ''), 'municipality of ', ''), 'city of ', ''), ' municipality', ''), ' city', ''), '  ', ' '))";

        $query->where(function ($subQuery) use ($officeLower, $officeNeedle, $cityComparableExpression) {
            $subQuery->whereRaw('LOWER(TRIM(COALESCE(city_municipality, ""))) = ?', [$officeLower])
                ->orWhereRaw("{$cityComparableExpression} = ?", [$officeNeedle]);
        });
    }

    private function mapExportRow($row): array
    {
        $city = trim((string) ($row->city_municipality ?? ''));
        $province = trim((string) ($row->province ?? ''));
        $region = trim((string) ($row->region ?? ''));
        $lguLines = [];
        if ($city !== '') {
            $lguLines[] = $city;
        }
        if ($province !== '') {
            $lguLines[] = $province;
        }
        if ($region !== '') {
            $lguLines[] = $region;
        }
        $lgu = $lguLines ? implode("\n", $lguLines) : '';

        $amount = $row->national_subsidy;
        $amountValue = $amount !== null && $amount !== '' ? number_format((float) $amount, 2, '.', '') : '';

        return [
            (string) ($row->project_code ?? ''),
            $lgu,
            (string) ($row->barangays ?? ''),
            (string) ($row->funding_year ?? ''),
            (string) ($row->name_of_program ?? ''),
            (string) ($row->project_title ?? ''),
            $amountValue,
            $row->slippage !== null && $row->slippage !== '' ? number_format((float) $row->slippage, 2, '.', '') . '%' : '',
            (string) ($row->risk_level ?? ''),
            (string) ($row->aging ?? ''),
        ];
    }

    private function maxLineLength(string $value): int
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $lines = explode("\n", $value);
        $lengths = array_map('strlen', $lines);
        return $lengths ? max($lengths) : 0;
    }

    private function columnWidth(int $maxLength): float
    {
        $minChars = 8;
        $maxChars = 60;
        $chars = max($minChars, min($maxLength, $maxChars));
        return $chars * 7;
    }

    private function escapeXml(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $escaped = htmlspecialchars($value, ENT_XML1);
        return str_replace("\n", '&#10;', $escaped);
    }

    private function parseYear($value): ?int
    {
        $clean = preg_replace('/[^0-9]/', '', (string) $value);
        if (strlen($clean) === 4) {
            return (int) $clean;
        }

        try {
            return Carbon::parse($value)->year;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parseDecimal($value): ?float
    {
        $clean = preg_replace('/[^0-9\.\-]/', '', (string) $value);
        return $clean === '' ? null : (float) $clean;
    }

    private function parseInteger($value): ?int
    {
        $clean = preg_replace('/[^0-9\-]/', '', (string) $value);
        return $clean === '' ? null : (int) $clean;
    }

    private function parseDate($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $serial = (int) $value;
            if ($serial > 0) {
                return Carbon::create(1899, 12, 30)->addDays($serial)->toDateString();
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
