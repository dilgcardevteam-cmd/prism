<?php

namespace App\Http\Controllers;

use App\Services\RlipLimeDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Shuchkin\SimpleXLS;

class SystemManagementController extends Controller
{
    private const IMPORT_HISTORY_TABLE = 'subaybayan_import_histories';
    private const RLIP_LIME_IMPORT_HISTORY_TABLE = 'rlip_lime_import_histories';
    private const LEGACY_SUBAYBAYAN_TEMPLATE_PATH = 'templates/legacy-subaybayan-template.xls';
    private const SUBAYBAYAN_TEMPLATE_HEADERS = [
        'program',
        'project_code',
        'project_title',
        'region',
        'province',
        'city_municipality',
        'barangay',
        'exact_location',
        'type',
        'project_description',
        'road_length_in_km',
        'funding_year',
        'type_of_project',
        'sub_type_of_project',
        'procurement_type',
        'procurement',
        'beneficiaries',
        'status',
        'remarks',
        'profile_approval_status',
        'national_subsidy_original_allocation',
        'lgu_counterpart_original_allocation',
        'national_subsidy_cancelled_allocation',
        'lgu_counterpart_cancelled_allocation',
        'national_subsidy_reverted_amount',
        'lgu_counterpart_reverted_amount',
        'national_subsidy_revised_allocation',
        'lgu_counterpart_revised_allocation',
        'total_project_cost',
        'implementing_unit',
        'moi',
        'total_estimated_cost_of_project',
        'duration',
        'intended_completion_date',
        'actual_start_of_construction',
        'unit_implementing_the_project',
        'name_of_contractor',
        'contract_price',
        'contract_duration',
        'office_address',
        'date_of_perfection_of_contract',
        'intended_completion_date_2',
        'date_of_receipt_of_ntp',
        'date_of_expiration_of_contract',
        'uploaded_images_w_geotag',
        'uploaded_images_without_geotag',
        'before_w_geotag',
        'before_without_geotag',
        'project_billboard_w_geotag',
        'project_billboard_without_geotag',
        'photo_20_40_w_geotag',
        'photo_20_40_without_geotag',
        'photo_50_70_w_geotag',
        'photo_50_70_without_geotag',
        'photo_90_w_geotag',
        'photo_90_without_geotag',
        'completed_w_geotag',
        'completed_without_geotag',
        'during_the_operation_w_geotag',
        'during_the_operation_without_geotag',
        'total_accomplishment',
        'date',
        'obligation',
        'disbursement',
        'liquidations',
        'bid_opening_bid_evaluation',
        'bid_opening_evaluation',
        'date_of_nadai',
        'date_of_receipt_of_notice_to_proceed',
        'ded_pow_preparation',
        'ded_pow_prep_notarized_lce_cert',
        'ded_pow_review_and_approval',
        'ded_pow_review_and_approval_2',
        'endorsement_of_projects_to_dbm_for_the_release_of_saro',
        'fs_technical_specification_and_ded_pow_preparation',
        'fs_technical_specification_and_ded_pow_review_approval',
        'fs_technical_specification_preparation',
        'installation_of_community_billboard',
        'installation_of_community_billboard_2',
        'invitation_to_bid_ib_posted',
        'moa_signing',
        'no_objection_1',
        'no_objection_2',
        'no_objection_3',
        'noa_issuance',
        'project_billboard',
        'submission_of_certificate_on_the_receipt_of_funds',
    ];
    private const LEGACY_SUBAYBAYAN_TEMPLATE_COLUMN_MAP = [
        0 => 'program',
        1 => 'project_code',
        2 => 'project_title',
        3 => 'region',
        4 => 'province',
        5 => 'city_municipality',
        6 => 'barangay',
        7 => 'exact_location',
        8 => 'type',
        9 => 'project_description',
        10 => 'road_length_in_km',
        11 => 'funding_year',
        12 => 'type_of_project',
        13 => 'sub_type_of_project',
        14 => 'procurement_type',
        15 => 'procurement',
        16 => 'beneficiaries',
        17 => 'status',
        18 => 'remarks',
        19 => 'profile_approval_status',
        20 => 'national_subsidy_original_allocation',
        21 => 'lgu_counterpart_original_allocation',
        22 => 'national_subsidy_cancelled_allocation',
        23 => 'lgu_counterpart_cancelled_allocation',
        24 => 'national_subsidy_reverted_amount',
        25 => 'lgu_counterpart_reverted_amount',
        26 => 'national_subsidy_revised_allocation',
        27 => 'lgu_counterpart_revised_allocation',
        28 => 'total_project_cost',
        29 => 'implementing_unit',
        30 => 'moi',
        31 => 'total_estimated_cost_of_project',
        32 => 'duration',
        33 => 'intended_completion_date',
        34 => 'actual_start_of_construction',
        35 => 'unit_implementing_the_project',
        36 => 'name_of_contractor',
        37 => 'contract_price',
        38 => 'contract_duration',
        39 => 'office_address',
        40 => 'date_of_perfection_of_contract',
        41 => 'intended_completion_date_2',
        42 => 'date_of_receipt_of_ntp',
        43 => 'date_of_expiration_of_contract',
        44 => 'uploaded_images_w_geotag',
        45 => 'uploaded_images_without_geotag',
        46 => 'before_w_geotag',
        47 => 'before_without_geotag',
        48 => 'project_billboard_w_geotag',
        49 => 'project_billboard_without_geotag',
        50 => 'photo_20_40_w_geotag',
        51 => 'photo_20_40_without_geotag',
        52 => 'photo_50_70_w_geotag',
        53 => 'photo_50_70_without_geotag',
        54 => 'photo_90_w_geotag',
        55 => 'photo_90_without_geotag',
        56 => 'completed_w_geotag',
        57 => 'completed_without_geotag',
        58 => 'during_the_operation_w_geotag',
        59 => 'during_the_operation_without_geotag',
        60 => 'total_accomplishment',
        61 => 'date',
        62 => 'obligation',
        63 => 'disbursement',
        64 => 'liquidations',
        65 => 'bid_opening_bid_evaluation',
        66 => 'bid_opening_evaluation',
        67 => 'date_of_nadai',
        68 => 'date_of_receipt_of_notice_to_proceed',
        69 => 'ded_pow_preparation',
        70 => 'ded_pow_prep_notarized_lce_cert',
        71 => 'ded_pow_review_and_approval',
        72 => 'ded_pow_review_and_approval_2',
        73 => 'endorsement_of_projects_to_dbm_for_the_release_of_saro',
        74 => 'fs_technical_specification_and_ded_pow_preparation',
        75 => 'fs_technical_specification_and_ded_pow_review_approval',
        76 => 'fs_technical_specification_preparation',
        77 => 'installation_of_community_billboard',
        78 => 'installation_of_community_billboard_2',
        79 => 'invitation_to_bid_ib_posted',
        80 => 'moa_signing',
        81 => 'no_objection_1',
        82 => 'no_objection_2',
        83 => 'no_objection_3',
        84 => 'noa_issuance',
        85 => 'project_billboard',
        86 => 'submission_of_certificate_on_the_receipt_of_funds',
    ];
    private const SGLGIF_TEMPLATE_HEADERS = [
        'LGU Reference Code',
        'Beneficiaries',
        'Year',
        'Region',
        'Province',
        'LGU',
        'Level',
        'Subsidy',
        'Title',
        'Amount',
        'Type',
        'Category',
        'Status',
        'Financial',
        'Physical',
        'Attachment',
        'Overall',
    ];
    private const SGLGIF_TEMPLATE_HEADER_MAP = [
        'lgu_reference_code' => 'project_code',
        'beneficiaries' => 'beneficiaries',
        'year' => 'funding_year',
        'region' => 'region',
        'province' => 'province',
        'lgu' => 'city_municipality',
        'level' => 'sglgif_level',
        'subsidy' => 'national_subsidy_original_allocation',
        'title' => 'project_title',
        'amount' => 'total_project_cost',
        'type' => 'type_of_project',
        'category' => 'sub_type_of_project',
        'status' => 'status',
        'financial' => 'sglgif_financial',
        'physical' => 'total_accomplishment',
        'attachment' => 'sglgif_attachment',
        'overall' => 'sglgif_overall',
    ];

    public function uploadSubaybayan()
    {
        return $this->renderSubaybayanUploadManager(
            $this->resolveSubaybayanUploadPage('system-management.upload-subaybayan')
        );
    }

    public function uploadSubaybayan2025()
    {
        return $this->renderSubaybayanUploadManager(
            $this->resolveSubaybayanUploadPage('system-management.upload-subaybayan-2025')
        );
    }

    public function uploadRssa()
    {
        return $this->renderSubaybayanUploadManager(
            $this->resolveSubaybayanUploadPage('system-management.upload-rssa')
        );
    }

    public function uploadSglgif()
    {
        return $this->renderSubaybayanUploadManager(
            $this->resolveSubaybayanUploadPage('system-management.upload-sglgif')
        );
    }

    public function downloadSubaybayanTemplate(Request $request)
    {
        $uploadPage = $this->resolveSubaybayanUploadPage($request->route()?->getName());

        $templateSourcePath = $uploadPage['templateSourcePath'] ?? null;
        if (is_string($templateSourcePath) && is_file($templateSourcePath)) {
            return response()->download(
                $templateSourcePath,
                $uploadPage['templateFileName'],
                [
                    'Content-Type' => $uploadPage['templateContentType'] ?? 'application/octet-stream',
                ]
            );
        }

        return response()->streamDownload(function () use ($uploadPage) {
            echo "\xEF\xBB\xBF";
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $uploadPage['templateHeaders'] ?? self::SUBAYBAYAN_TEMPLATE_HEADERS);
            fclose($handle);
        }, $uploadPage['templateFileName'], [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importSubaybayan(Request $request)
    {
        $uploadPage = $this->resolveSubaybayanUploadPage($request->route()?->getName());

        if (!Schema::hasTable('subay_project_profiles')) {
            return back()->with('error', $uploadPage['entityLabel'] . ' data table is not available yet.');
        }

        $request->validate(
            [
                'file' => ['required', 'file', 'mimes:csv,txt,xls', 'max:51200'],
            ],
            [
                'file.mimes' => 'Please upload a CSV or legacy Excel (.xls) file.',
            ]
        );

        $file = $request->file('file');
        if (!$file) {
            return back()->with('error', 'No file was uploaded.');
        }

        $originalFileName = (string) $file->getClientOriginalName();
        $storageFileName = $this->generateImportStorageFileName($originalFileName, $uploadPage['storageSlug']);
        $storedPath = $file->storeAs($uploadPage['storageFolder'], $storageFileName, 'local');
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

        return back()->with('success', 'File added to import history. Click Load to import it into ' . $uploadPage['entityLabel'] . ' data.');
    }

    public function loadSubaybayanImport(Request $request, $importId)
    {
        $uploadPage = $this->resolveSubaybayanUploadPage($request->route()?->getName());

        if (!Schema::hasTable('subay_project_profiles')) {
            return back()->with('error', $uploadPage['entityLabel'] . ' data table is not available yet.');
        }

        if (!Schema::hasTable(self::IMPORT_HISTORY_TABLE)) {
            return back()->with('error', 'Import history table is not available yet. Please run migration first.');
        }

        $record = $this->findImportHistoryRecord((int) $importId, $uploadPage);

        if (!$record) {
            return back()->with('error', 'Selected import record was not found.');
        }

        $storedPath = (string) ($record->stored_file_path ?? '');
        if ($storedPath === '' || !Storage::disk('local')->exists($storedPath)) {
            return back()->with('error', 'The selected imported file is no longer available.');
        }

        $absolutePath = Storage::disk('local')->path($storedPath);
        try {
            $inserted = $this->importSnapshot($absolutePath, $uploadPage);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if ($inserted === 0) {
            return back()->with('error', 'No valid rows were loaded from the selected import file.');
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

    public function deleteSubaybayanImport(Request $request, $importId)
    {
        $uploadPage = $this->resolveSubaybayanUploadPage($request->route()?->getName());

        if (!Schema::hasTable(self::IMPORT_HISTORY_TABLE)) {
            return back()->with('error', 'Import history table is not available yet. Please run migration first.');
        }

        $record = $this->findImportHistoryRecord((int) $importId, $uploadPage);

        if (!$record) {
            return back()->with('error', 'Selected import record was not found.');
        }

        $storedPath = (string) ($record->stored_file_path ?? '');
        if ($storedPath !== '' && Storage::disk('local')->exists($storedPath)) {
            Storage::disk('local')->delete($storedPath);
        }

        DB::table(self::IMPORT_HISTORY_TABLE)
            ->where('id', (int) $importId)
            ->delete();

        return back()->with('success', 'Imported file record deleted successfully.');
    }

    public function downloadSubaybayanImport(Request $request, $importId)
    {
        $uploadPage = $this->resolveSubaybayanUploadPage($request->route()?->getName());

        if (!Schema::hasTable(self::IMPORT_HISTORY_TABLE)) {
            return back()->with('error', 'Import history table is not available yet. Please run migration first.');
        }

        $record = $this->findImportHistoryRecord((int) $importId, $uploadPage);

        if (!$record) {
            return back()->with('error', 'Selected import record was not found.');
        }

        $storedPath = (string) ($record->stored_file_path ?? '');
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

    private function renderSubaybayanUploadManager(array $uploadPage)
    {
        if (!Schema::hasTable('subay_project_profiles')) {
            return view('system-management.upload-subaybayan', [
                'tableMissing' => true,
                'filters' => [],
                'filterOptions' => [],
                'importHistoryRows' => collect(),
                'importHistoryTableMissing' => !Schema::hasTable(self::IMPORT_HISTORY_TABLE),
                'uploadPage' => $uploadPage,
            ]);
        }

        $filters = [
            'province' => request('province'),
            'city_municipality' => request('city_municipality'),
            'barangay' => request('barangay'),
            'program' => request('program'),
            'status' => request('status'),
            'funding_year' => request('funding_year'),
            'procurement_type' => request('procurement_type'),
            'project_code' => request('project_code'),
            'project_title' => request('project_title'),
            'procurement' => request('procurement'),
            'type_of_project' => request('type_of_project'),
            'implementing_unit' => request('implementing_unit'),
            'profile_approval_status' => request('profile_approval_status'),
        ];

        $filterOptions = [
            'provinces' => DB::table('subay_project_profiles')
                ->select('province')
                ->whereNotNull('province')
                ->where('province', '!=', '')
                ->distinct()
                ->orderBy('province')
                ->pluck('province'),
            'cities' => DB::table('subay_project_profiles')
                ->select('city_municipality')
                ->whereNotNull('city_municipality')
                ->where('city_municipality', '!=', '')
                ->distinct()
                ->orderBy('city_municipality')
                ->pluck('city_municipality'),
            'barangays' => DB::table('subay_project_profiles')
                ->select('barangay')
                ->whereNotNull('barangay')
                ->where('barangay', '!=', '')
                ->distinct()
                ->orderBy('barangay')
                ->pluck('barangay'),
            'programs' => DB::table('subay_project_profiles')
                ->select('program')
                ->whereNotNull('program')
                ->where('program', '!=', '')
                ->distinct()
                ->orderBy('program')
                ->pluck('program'),
            'statuses' => DB::table('subay_project_profiles')
                ->select('status')
                ->whereNotNull('status')
                ->where('status', '!=', '')
                ->distinct()
                ->orderBy('status')
                ->pluck('status'),
            'funding_years' => DB::table('subay_project_profiles')
                ->select('funding_year')
                ->whereNotNull('funding_year')
                ->where('funding_year', '!=', '')
                ->distinct()
                ->orderBy('funding_year')
                ->pluck('funding_year'),
            'procurement_types' => DB::table('subay_project_profiles')
                ->select('procurement_type')
                ->whereNotNull('procurement_type')
                ->where('procurement_type', '!=', '')
                ->distinct()
                ->orderBy('procurement_type')
                ->pluck('procurement_type'),
            'procurements' => DB::table('subay_project_profiles')
                ->select('procurement')
                ->whereNotNull('procurement')
                ->where('procurement', '!=', '')
                ->distinct()
                ->orderBy('procurement')
                ->pluck('procurement'),
            'project_types' => DB::table('subay_project_profiles')
                ->select('type_of_project')
                ->whereNotNull('type_of_project')
                ->where('type_of_project', '!=', '')
                ->distinct()
                ->orderBy('type_of_project')
                ->pluck('type_of_project'),
            'implementing_units' => DB::table('subay_project_profiles')
                ->select('implementing_unit')
                ->whereNotNull('implementing_unit')
                ->where('implementing_unit', '!=', '')
                ->distinct()
                ->orderBy('implementing_unit')
                ->pluck('implementing_unit'),
            'profile_statuses' => DB::table('subay_project_profiles')
                ->select('profile_approval_status')
                ->whereNotNull('profile_approval_status')
                ->where('profile_approval_status', '!=', '')
                ->distinct()
                ->orderBy('profile_approval_status')
                ->pluck('profile_approval_status'),
        ];

        $importHistoryTableMissing = !Schema::hasTable(self::IMPORT_HISTORY_TABLE);
        $importHistoryRows = $importHistoryTableMissing
            ? collect()
            : DB::table(self::IMPORT_HISTORY_TABLE)
                ->where('stored_file_path', 'like', $uploadPage['storageFolder'] . '/%')
                ->orderByDesc('imported_at')
                ->orderByDesc('id')
                ->paginate(15, ['*'], 'imports_page')
                ->withQueryString();

        return view('system-management.upload-subaybayan', [
            'tableMissing' => false,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'importHistoryRows' => $importHistoryRows,
            'importHistoryTableMissing' => $importHistoryTableMissing,
            'uploadPage' => $uploadPage,
        ]);
    }

    private function resolveSubaybayanUploadPage(?string $routeName = null): array
    {
        if (Str::startsWith((string) $routeName, 'system-management.upload-rssa')) {
            return [
                'title' => 'Upload RSSA Data',
                'pageTitle' => 'Upload RSSA Data',
                'heading' => 'Upload RSSA Data',
                'description' => 'Upload RSSA data files for system processing.',
                'listTitle' => 'Imported RSSA Files',
                'entityLabel' => 'RSSA',
                'modalTitle' => 'Import RSSA Data (CSV/XLS)',
                'routeBase' => 'system-management.upload-rssa',
                'templateFileName' => 'rssa-template.xls',
                'templateSourcePath' => resource_path(self::LEGACY_SUBAYBAYAN_TEMPLATE_PATH),
                'templateContentType' => 'application/vnd.ms-excel',
                'storageSlug' => 'rssa',
                'storageFolder' => 'rssa-imports',
                'templateHeaders' => self::SUBAYBAYAN_TEMPLATE_HEADERS,
                'customHeaderMap' => [],
                'rowDefaults' => [],
                'snapshotScope' => 'subaybayan',
            ];
        }

        if (Str::startsWith((string) $routeName, 'system-management.upload-subaybayan-2025')) {
            return [
                'title' => 'Upload LFP Data (2025 Above Projects)',
                'pageTitle' => 'Upload LFP Data (2025 Above Projects)',
                'heading' => 'Upload LFP Data (2025 Above Projects)',
                'description' => 'Upload SubayBAYAN data files for 2025 above projects.',
                'listTitle' => 'Imported 2025 Above Project Files',
                'entityLabel' => 'SubayBAYAN 2025 Above Projects',
                'modalTitle' => 'Import 2025 Above Project Data (CSV/XLS)',
                'routeBase' => 'system-management.upload-subaybayan-2025',
                'templateFileName' => 'subaybayan-2025-template.xls',
                'templateSourcePath' => resource_path(self::LEGACY_SUBAYBAYAN_TEMPLATE_PATH),
                'templateContentType' => 'application/vnd.ms-excel',
                'storageSlug' => 'subaybayan-2025',
                'storageFolder' => 'subaybayan-2025-imports',
                'templateHeaders' => self::SUBAYBAYAN_TEMPLATE_HEADERS,
                'customHeaderMap' => [],
                'rowDefaults' => [
                    'subaybayan_dataset_group' => '2025_above',
                ],
                'snapshotScope' => 'subaybayan_2025_above',
            ];
        }

        if (Str::startsWith((string) $routeName, 'system-management.upload-sglgif')) {
            return [
                'title' => 'Upload SGLGIF Data',
                'pageTitle' => 'Upload SGLGIF Data',
                'heading' => 'Upload SGLGIF Data',
                'description' => 'Upload SGLGIF data files for system processing.',
                'listTitle' => 'Imported SGLGIF Files',
                'entityLabel' => 'SGLGIF',
                'modalTitle' => 'Import SGLGIF Data (CSV/XLS)',
                'routeBase' => 'system-management.upload-sglgif',
                'templateFileName' => 'sglgif-template.csv',
                'templateSourcePath' => null,
                'templateContentType' => 'text/csv; charset=UTF-8',
                'storageSlug' => 'sglgif',
                'storageFolder' => 'sglgif-imports',
                'templateHeaders' => self::SGLGIF_TEMPLATE_HEADERS,
                'customHeaderMap' => self::SGLGIF_TEMPLATE_HEADER_MAP,
                'rowDefaults' => [
                    'program' => 'SGLGIF',
                ],
                'snapshotScope' => 'sglgif',
            ];
        }

        return [
            'title' => 'Upload SubayBAYAN Data',
            'pageTitle' => 'Upload SubayBAYAN Data',
            'heading' => 'Upload SubayBAYAN Data',
            'description' => 'Upload SubayBAYAN data files for system processing.',
            'listTitle' => 'Imported SubayBAYAN Files',
            'entityLabel' => 'SubayBAYAN',
            'modalTitle' => 'Import SubayBAYAN Data (CSV/XLS)',
            'routeBase' => 'system-management.upload-subaybayan',
            'templateFileName' => 'subaybayan-template.xls',
            'templateSourcePath' => resource_path(self::LEGACY_SUBAYBAYAN_TEMPLATE_PATH),
            'templateContentType' => 'application/vnd.ms-excel',
            'storageSlug' => 'subaybayan',
            'storageFolder' => 'subaybayan-imports',
            'templateHeaders' => self::SUBAYBAYAN_TEMPLATE_HEADERS,
            'customHeaderMap' => [],
            'rowDefaults' => [],
            'snapshotScope' => 'subaybayan',
        ];
    }

    public function uploadRlipLime()
    {
        $importHistoryTableMissing = !Schema::hasTable(self::RLIP_LIME_IMPORT_HISTORY_TABLE);
        $importHistoryRows = $importHistoryTableMissing
            ? collect()
            : DB::table(self::RLIP_LIME_IMPORT_HISTORY_TABLE)
                ->orderByDesc('imported_at')
                ->orderByDesc('id')
                ->paginate(15, ['*'], 'imports_page')
                ->withQueryString();

        $activeImportId = null;
        if (!$importHistoryTableMissing) {
            $activeImportId = DB::table(self::RLIP_LIME_IMPORT_HISTORY_TABLE)
                ->whereNotNull('last_loaded_at')
                ->orderByDesc('last_loaded_at')
                ->orderByDesc('id')
                ->value('id');
        }

        return view('system-management.upload-rlip-lime', [
            'importHistoryRows' => $importHistoryRows,
            'importHistoryTableMissing' => $importHistoryTableMissing,
            'activeImportId' => $activeImportId !== null ? (int) $activeImportId : null,
        ]);
    }

    public function importRlipLime(Request $request, RlipLimeDataService $rlipLimeDataService)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:51200',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (!$value instanceof \Illuminate\Http\UploadedFile) {
                        $fail('Please upload a CSV or Excel (.csv or .xls) file using the RLIP master-list format.');
                        return;
                    }

                    $extension = strtolower((string) $value->getClientOriginalExtension());
                    if (!in_array($extension, ['csv', 'xls'], true)) {
                        $fail('Please upload a CSV or Excel (.csv or .xls) file using the RLIP master-list format.');
                    }
                },
            ],
        ]);

        $file = $request->file('file');
        if (!$file) {
            return back()->with('error', 'No file was uploaded.');
        }

        if (!Schema::hasTable(self::RLIP_LIME_IMPORT_HISTORY_TABLE)) {
            return back()->with('error', 'RLIP/LIME import history table is not available yet. Please run migration first.');
        }

        $originalFileName = (string) $file->getClientOriginalName();
        $sourceExtension = strtolower((string) $file->getClientOriginalExtension());
        if ($sourceExtension === '') {
            $sourceExtension = 'csv';
        }

        $storageFileName = $this->generateImportStorageFileName($originalFileName, 'rlip-lime', $sourceExtension);
        $storedPath = $file->storeAs('rlip-lime-imports', $storageFileName, 'local');
        if (!$storedPath) {
            return back()->with('error', 'Unable to store the uploaded file.');
        }

        $now = now();
        $importId = (int) DB::table(self::RLIP_LIME_IMPORT_HISTORY_TABLE)->insertGetId([
            'original_file_name' => $originalFileName !== '' ? $originalFileName : basename($storedPath),
            'stored_file_path' => $storedPath,
            'file_size_bytes' => $file->getSize(),
            'imported_at' => $now,
            'last_loaded_at' => $now,
            'created_by' => auth()->id(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $absolutePath = Storage::disk('local')->path($storedPath);
        $sourceLabel = 'storage/app/' . str_replace('\\', '/', $storedPath);

        try {
            $dataset = $rlipLimeDataService->refreshDatasetCacheFromPath($absolutePath, $sourceLabel, $importId);
        } catch (\RuntimeException $exception) {
            if (Storage::disk('local')->exists($storedPath)) {
                Storage::disk('local')->delete($storedPath);
            }
            DB::table(self::RLIP_LIME_IMPORT_HISTORY_TABLE)
                ->where('id', $importId)
                ->delete();

            return back()->with('error', $exception->getMessage());
        }

        $oldRecords = DB::table(self::RLIP_LIME_IMPORT_HISTORY_TABLE)
            ->where('id', '!=', $importId)
            ->get(['id', 'stored_file_path']);

        DB::table(self::RLIP_LIME_IMPORT_HISTORY_TABLE)
            ->where('id', '!=', $importId)
            ->delete();

        foreach ($oldRecords as $oldRecord) {
            $oldPath = (string) ($oldRecord->stored_file_path ?? '');
            if ($oldPath !== '' && Storage::disk('local')->exists($oldPath)) {
                Storage::disk('local')->delete($oldPath);
            }
        }

        $loadedRows = (int) ($dataset['meta']['row_count'] ?? 0);
        return back()->with('success', "Imported and replaced RLIP data with {$loadedRows} rows.");
    }

    public function loadRlipLimeImport($importId, RlipLimeDataService $rlipLimeDataService)
    {
        if (!Schema::hasTable(self::RLIP_LIME_IMPORT_HISTORY_TABLE)) {
            return back()->with('error', 'RLIP/LIME import history table is not available yet. Please run migration first.');
        }

        $record = DB::table(self::RLIP_LIME_IMPORT_HISTORY_TABLE)
            ->where('id', (int) $importId)
            ->first();

        if (!$record) {
            return back()->with('error', 'Selected import record was not found.');
        }

        $storedPath = (string) ($record->stored_file_path ?? '');
        if ($storedPath === '' || !Storage::disk('local')->exists($storedPath)) {
            return back()->with('error', 'The selected imported file is no longer available.');
        }

        $absolutePath = Storage::disk('local')->path($storedPath);
        $sourceLabel = 'storage/app/' . str_replace('\\', '/', $storedPath);

        try {
            $dataset = $rlipLimeDataService->refreshDatasetCacheFromPath($absolutePath, $sourceLabel, (int) $importId);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $now = now();
        DB::transaction(function () use ($importId, $now) {
            DB::table(self::RLIP_LIME_IMPORT_HISTORY_TABLE)
                ->whereNotNull('last_loaded_at')
                ->update([
                    'last_loaded_at' => null,
                    'updated_at' => $now,
                ]);

            DB::table(self::RLIP_LIME_IMPORT_HISTORY_TABLE)
                ->where('id', (int) $importId)
                ->update([
                    'last_loaded_at' => $now,
                    'updated_at' => $now,
                ]);
        });

        $displayName = trim((string) ($record->original_file_name ?? ''));
        if ($displayName === '') {
            $displayName = basename($storedPath);
        }

        $loadedRows = (int) ($dataset['meta']['row_count'] ?? 0);
        return back()->with('success', "Loaded {$loadedRows} RLIP rows from {$displayName}.");
    }

    public function deleteRlipLimeImport($importId, RlipLimeDataService $rlipLimeDataService)
    {
        if (!Schema::hasTable(self::RLIP_LIME_IMPORT_HISTORY_TABLE)) {
            return back()->with('error', 'RLIP/LIME import history table is not available yet. Please run migration first.');
        }

        $record = DB::table(self::RLIP_LIME_IMPORT_HISTORY_TABLE)
            ->where('id', (int) $importId)
            ->first();

        if (!$record) {
            return back()->with('error', 'Selected import record was not found.');
        }

        $wasLoaded = !empty($record->last_loaded_at);
        $storedPath = (string) ($record->stored_file_path ?? '');
        if ($storedPath !== '' && Storage::disk('local')->exists($storedPath)) {
            Storage::disk('local')->delete($storedPath);
        }

        DB::table(self::RLIP_LIME_IMPORT_HISTORY_TABLE)
            ->where('id', (int) $importId)
            ->delete();

        if ($wasLoaded) {
            $rlipLimeDataService->clearDatasetCache();
        }

        return back()->with('success', 'RLIP imported file record deleted successfully.');
    }

    public function downloadRlipLimeImport($importId)
    {
        if (!Schema::hasTable(self::RLIP_LIME_IMPORT_HISTORY_TABLE)) {
            return back()->with('error', 'RLIP/LIME import history table is not available yet. Please run migration first.');
        }

        $record = DB::table(self::RLIP_LIME_IMPORT_HISTORY_TABLE)
            ->where('id', (int) $importId)
            ->first();

        if (!$record) {
            return back()->with('error', 'Selected import record was not found.');
        }

        $storedPath = (string) ($record->stored_file_path ?? '');
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
            : 'application/vnd.ms-excel';

        return response()->download(
            Storage::disk('local')->path($storedPath),
            $downloadName,
            [
                'Content-Type' => $contentType,
            ]
        );
    }

    private function importSnapshot(string $path, array $uploadPage): int
    {
        $rows = $this->readImportedRows($path);
        if (empty($rows)) {
            throw new \RuntimeException('The selected file appears to be empty.');
        }

        $columns = Schema::getColumnListing('subay_project_profiles');
        $structure = $this->resolveImportStructure($rows, $columns, $uploadPage['customHeaderMap'] ?? []);
        $headerMap = $structure['headerMap'];
        $dataStartRow = $structure['dataStartRow'];

        if (empty($headerMap)) {
            throw new \RuntimeException('No recognizable columns were found in the selected file.');
        }

        return DB::transaction(function () use ($rows, $headerMap, $dataStartRow, $uploadPage) {
            $now = now();
            $batchRows = [];
            $inserted = 0;
            $rowDefaults = $uploadPage['rowDefaults'] ?? [];

            // Replace only the rows owned by the active import scope.
            $this->clearImportScopeRows($uploadPage);

            for ($rowIndex = $dataStartRow; $rowIndex < count($rows); $rowIndex++) {
                $data = $rows[$rowIndex] ?? [];
                if (!is_array($data) || $this->rowIsEmpty($data)) {
                    continue;
                }

                $row = [];
                foreach ($headerMap as $index => $column) {
                    $row[$column] = $this->normalizeImportedCell($data[$index] ?? null);
                }

                foreach ($rowDefaults as $column => $value) {
                    if (!array_key_exists($column, $row) || $row[$column] === null || $row[$column] === '') {
                        $row[$column] = $value;
                    }
                }

                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $row['created_at'] = $now;
                $row['updated_at'] = $now;
                $batchRows[] = $row;

                if (count($batchRows) >= 500) {
                    DB::table('subay_project_profiles')->insert($batchRows);
                    $inserted += count($batchRows);
                    $batchRows = [];
                }
            }

            if (!empty($batchRows)) {
                DB::table('subay_project_profiles')->insert($batchRows);
                $inserted += count($batchRows);
            }

            return $inserted;
        });
    }

    private function readImportedRows(string $path): array
    {
        if (!is_readable($path)) {
            throw new \RuntimeException('Unable to read the selected file.');
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($extension, ['csv', 'txt'], true)) {
            $handle = fopen($path, 'r');
            if ($handle === false) {
                throw new \RuntimeException('Unable to open the selected file.');
            }

            try {
                $rows = [];
                while (($row = fgetcsv($handle)) !== false) {
                    $rows[] = $row;
                }

                return $rows;
            } finally {
                fclose($handle);
            }
        }

        if ($extension !== 'xls') {
            throw new \RuntimeException('Unsupported file type. Please upload a CSV or legacy Excel (.xls) file.');
        }

        $xls = SimpleXLS::parse($path);
        if (!$xls) {
            throw new \RuntimeException('Unable to parse the uploaded Excel file: ' . (SimpleXLS::parseError() ?: 'Unknown parser error'));
        }

        return $xls->rows();
    }

    private function resolveImportStructure(array $rows, array $columns, array $routeCustomMap = []): array
    {
        $rowCount = count($rows);
        if ($rowCount === 0) {
            return [
                'headerMap' => [],
                'dataStartRow' => 0,
            ];
        }

        if ($this->matchesLegacySubaybayanTemplate($rows)) {
            $columnLookup = array_fill_keys($columns, true);
            $headerMap = [];

            foreach (self::LEGACY_SUBAYBAYAN_TEMPLATE_COLUMN_MAP as $index => $column) {
                if (isset($columnLookup[$column])) {
                    $headerMap[$index] = $column;
                }
            }

            return [
                'headerMap' => $headerMap,
                'dataStartRow' => 3,
            ];
        }

        $bestHeaderMap = [];
        $bestHeaderRows = 1;
        $maxHeaderRows = min(3, $rowCount);

        for ($headerRows = 1; $headerRows <= $maxHeaderRows; $headerRows++) {
            $headers = $headerRows === 1
                ? ($rows[0] ?? [])
                : $this->buildMultiRowHeaders(array_slice($rows, 0, $headerRows));

            $headerMap = $this->buildHeaderMap($headers, $columns, $routeCustomMap);
            if (
                count($headerMap) > count($bestHeaderMap)
                || (count($headerMap) === count($bestHeaderMap) && $headerRows < $bestHeaderRows)
            ) {
                $bestHeaderMap = $headerMap;
                $bestHeaderRows = $headerRows;
            }
        }

        return [
            'headerMap' => $bestHeaderMap,
            'dataStartRow' => $bestHeaderRows,
        ];
    }

    private function matchesLegacySubaybayanTemplate(array $rows): bool
    {
        if (count($rows) < 3) {
            return false;
        }

        $row0col0 = strtoupper(trim((string) ($rows[0][0] ?? '')));
        $row1col0 = strtoupper(trim((string) ($rows[1][0] ?? '')));
        $row1col1 = strtoupper(trim((string) ($rows[1][1] ?? '')));
        $row1col44 = strtoupper(trim((string) ($rows[1][44] ?? '')));
        $row2col44 = strtoupper(trim((string) ($rows[2][44] ?? '')));
        $row1col60 = strtoupper(trim((string) ($rows[1][60] ?? '')));
        $row1col65 = strtoupper(trim((string) ($rows[1][65] ?? '')));

        return $row0col0 === 'PROJECT PROFILE'
            && $row1col0 === 'PROGRAM'
            && $row1col1 === 'PROJECT CODE'
            && $row1col44 === 'UPLOADED IMAGES'
            && $row2col44 === 'W/ GEOTAG'
            && $row1col60 === 'TOTAL ACCOMPLISHMENT'
            && $row1col65 === 'BID OPENING/BID EVALUATION';
    }

    private function buildMultiRowHeaders(array $headerRows): array
    {
        $columnCount = 0;
        foreach ($headerRows as $headerRow) {
            if (is_array($headerRow)) {
                $columnCount = max($columnCount, count($headerRow));
            }
        }

        $headers = [];
        for ($columnIndex = 0; $columnIndex < $columnCount; $columnIndex++) {
            $selectedHeader = '';
            foreach ($headerRows as $headerRow) {
                $value = trim((string) ($headerRow[$columnIndex] ?? ''));
                if ($value !== '') {
                    $selectedHeader = $value;
                }
            }

            $headers[$columnIndex] = $selectedHeader;
        }

        return $headers;
    }

    private function normalizeImportedCell(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return $this->sanitizeValue($value);
    }

    private function generateImportStorageFileName(
        string $originalFileName,
        string $fallbackBaseName = 'subaybayan',
        string $fallbackExtension = 'csv'
    ): string
    {
        $extension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
        $baseName = pathinfo($originalFileName, PATHINFO_FILENAME);
        $baseNameSlug = Str::slug($baseName);
        if ($baseNameSlug === '') {
            $baseNameSlug = Str::slug($fallbackBaseName);
        }
        if ($baseNameSlug === '') {
            $baseNameSlug = 'import';
        }

        $timestamp = now()->format('Ymd_His');
        $randomSuffix = Str::lower(Str::random(8));
        $fileName = $timestamp . '_' . $baseNameSlug . '_' . $randomSuffix;

        $fallbackExtension = trim(strtolower($fallbackExtension));
        if ($fallbackExtension === '') {
            $fallbackExtension = 'dat';
        }

        return $fileName . ($extension !== '' ? '.' . $extension : '.' . $fallbackExtension);
    }

    private function buildHeaderMap(array $headers, array $columns, array $routeCustomMap = []): array
    {
        $columnLookup = array_fill_keys($columns, true);
        $customMap = array_merge([
            'barangay_s' => 'barangay',
            'barangays' => 'barangay',
            'amount' => 'obligation',
            'amount_2' => 'disbursement',
            'amount_3' => 'liquidations',
            'ded_pow_preparation_and_submission_of_notarized_lce_certification' => 'ded_pow_prep_notarized_lce_cert',
        ], $routeCustomMap);

        $headerMap = [];
        $counts = [];

        foreach ($headers as $index => $header) {
            $base = $this->normalizeHeader($header);
            if ($base === '') {
                continue;
            }

            $counts[$base] = ($counts[$base] ?? 0) + 1;
            $candidate = $base;
            if ($counts[$base] > 1) {
                $candidate = $base . '_' . $counts[$base];
            }

            if (isset($customMap[$candidate])) {
                $column = $customMap[$candidate];
            } elseif (isset($customMap[$base])) {
                $column = $customMap[$base];
            } elseif (isset($columnLookup[$candidate])) {
                $column = $candidate;
            } elseif (isset($columnLookup[$base])) {
                $column = $base;
            } else {
                continue;
            }

            $headerMap[$index] = $column;
        }

        return $headerMap;
    }

    private function findImportHistoryRecord(int $importId, array $uploadPage): ?object
    {
        return DB::table(self::IMPORT_HISTORY_TABLE)
            ->where('id', $importId)
            ->where('stored_file_path', 'like', $uploadPage['storageFolder'] . '/%')
            ->first();
    }

    private function clearImportScopeRows(array $uploadPage): void
    {
        $scope = (string) ($uploadPage['snapshotScope'] ?? 'subaybayan');

        if ($scope === 'sglgif') {
            DB::table('subay_project_profiles')
                ->whereRaw('UPPER(TRIM(COALESCE(program, ""))) = ?', ['SGLGIF'])
                ->delete();

            return;
        }

        if ($scope === 'subaybayan_2025_above') {
            DB::table('subay_project_profiles')
                ->where('subaybayan_dataset_group', '2025_above')
                ->delete();

            return;
        }

        DB::table('subay_project_profiles')
            ->where(function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->whereNull('program')
                        ->orWhereRaw('UPPER(TRIM(COALESCE(program, ""))) <> ?', ['SGLGIF']);
                })->where(function ($subQuery) {
                    $subQuery->whereNull('subaybayan_dataset_group')
                        ->orWhere('subaybayan_dataset_group', '!=', '2025_above');
                });
            })
            ->delete();
    }

    private function normalizeHeader($value): string
    {
        $value = is_string($value) ? $value : '';
        $value = ltrim($value, "\xEF\xBB\xBF");
        $value = str_replace(["\r", "\n"], ' ', $value);
        $value = str_replace('&', ' and ', $value);
        $value = preg_replace('/[\\/\\(\\)\\#\\-:]/', ' ', $value);
        $value = preg_replace('/\\s+/', ' ', $value ?? '');
        $value = trim(strtolower($value ?? ''));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim($value, '_');
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

    private function sanitizeValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $clean = $value;
        if (function_exists('mb_convert_encoding')) {
            $clean = mb_convert_encoding($clean, 'UTF-8', 'UTF-8,ISO-8859-1,WINDOWS-1252');
        } elseif (function_exists('utf8_encode')) {
            $clean = utf8_encode($clean);
        }

        if (function_exists('iconv')) {
            $iconv = @iconv('UTF-8', 'UTF-8//IGNORE', $clean);
            if ($iconv !== false) {
                $clean = $iconv;
            }
        }

        return $clean;
    }

    private function buildSubaybayanQuery(Request $request)
    {
        $query = DB::table('subay_project_profiles');

        $filters = [
            'province' => 'province',
            'city_municipality' => 'city_municipality',
            'barangay' => 'barangay',
            'program' => 'program',
            'status' => 'status',
            'funding_year' => 'funding_year',
            'procurement_type' => 'procurement_type',
            'procurement' => 'procurement',
            'type_of_project' => 'type_of_project',
            'implementing_unit' => 'implementing_unit',
            'profile_approval_status' => 'profile_approval_status',
        ];

        foreach ($filters as $param => $column) {
            if ($request->filled($param)) {
                $query->where($column, $request->input($param));
            }
        }

        if ($request->filled('project_code')) {
            $code = trim((string) $request->input('project_code'));
            if ($code !== '') {
                $query->where('project_code', 'like', '%' . $code . '%');
            }
        }

        if ($request->filled('project_title')) {
            $title = trim((string) $request->input('project_title'));
            if ($title !== '') {
                $query->where('project_title', 'like', '%' . $title . '%');
            }
        }

        return $query;
    }
}
