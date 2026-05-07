<?php

namespace App\Http\Controllers;

use App\Mail\NadaiUploadedMail;
use App\Models\NadaiManagementDocument;
use App\Support\ProjectLocationFilterHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NadaiManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('crud_permission:pre_implementation_documents,view')->only(['index', 'show', 'viewDocument', 'downloadDocument', 'openDocumentAndRedirect']);
        $this->middleware('crud_permission:pre_implementation_documents,add')->only(['store', 'updateDocument']);
    }

    private function getOffices(): array
    {
        return [
            'Abra' => [
                'PLGU Abra', 'Bangued', 'Boliney', 'Bucay', 'Bucloc', 'Daguioman', 'Danglas', 'Dolores',
                'La Paz', 'Lacub', 'Lagangilang', 'Lagayan', 'Langiden', 'Licuan-Baay', 'Luba', 'Malibcong',
                'Manabo', 'Peñarrubia', 'Pidigan', 'Pilar', 'Sallapadan', 'San Isidro', 'San Juan',
                'San Quintin', 'Tayum', 'Tineg', 'Tubo', 'Villaviciosa',
            ],
            'Apayao' => [
                'PLGU Apayao', 'Calanasan', 'Conner', 'Flora', 'Kabugao', 'Luna', 'Pudtol', 'Santa Marcela',
            ],
            'Benguet' => [
                'PLGU Benguet', 'Atok', 'Bakun', 'Bokod', 'Buguias', 'Itogon', 'Kabayan', 'Kapangan',
                'Kibungan', 'La Trinidad', 'Mankayan', 'Sablan', 'Tuba', 'Tublay',
            ],
            'City of Baguio' => [
                'City of Baguio',
            ],
            'Ifugao' => [
                'PLGU Ifugao', 'Aguinaldo', 'Alfonso Lista', 'Asipulo', 'Banaue', 'Hingyon', 'Hungduan',
                'Kiangan', 'Lagawe', 'Lamut', 'Mayoyao', 'Tinoc',
            ],
            'Kalinga' => [
                'PLGU Kalinga', 'Balbalan', 'Lubuagan', 'Pasil', 'Pinukpuk', 'Rizal', 'Tabuk', 'Tanudan',
            ],
            'Mountain Province' => [
                'PLGU Mountain Province', 'Barlig', 'Bauko', 'Besao', 'Bontoc', 'Natonin', 'Paracelis',
                'Sabangan', 'Sadanga', 'Sagada', 'Tadian',
            ],
        ];
    }

    private function getSortedOfficesByProvince(): array
    {
        $officesByProvince = $this->getOffices();
        ksort($officesByProvince, SORT_NATURAL | SORT_FLAG_CASE);

        foreach ($officesByProvince as $province => $offices) {
            usort($offices, function (string $a, string $b): int {
                $aIsPlgu = str_starts_with($a, 'PLGU ');
                $bIsPlgu = str_starts_with($b, 'PLGU ');

                if ($aIsPlgu && !$bIsPlgu) {
                    return -1;
                }

                if (!$aIsPlgu && $bIsPlgu) {
                    return 1;
                }

                return strcasecmp($a, $b);
            });

            $officesByProvince[$province] = $offices;
        }

        return $officesByProvince;
    }

    private function buildOfficeRows(array $officesByProvince): array
    {
        $officeRows = [];
        foreach ($officesByProvince as $province => $offices) {
            foreach ($offices as $office) {
                $officeRows[] = [
                    'province' => $province,
                    'city_municipality' => $office,
                ];
            }
        }

        return $officeRows;
    }

    private function findProvinceByOffice(string $officeName): ?string
    {
        foreach ($this->getOffices() as $province => $offices) {
            if (in_array($officeName, $offices, true)) {
                return $province;
            }
        }

        return null;
    }

    private function canAccessOffice(string $officeName, string $province): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        $userProvince = trim((string) $user->province);

        if ($user->isLguScopedUser()) {
            return $user->matchesAssignedOffice($officeName);
        }

        if ($user->isDilgUser()) {
            if ($userProvince === '' || $userProvince === 'Regional Office') {
                return true;
            }

            return $userProvince === $province;
        }

        return true;
    }

    private function canUploadNadai(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->isSuperAdmin() || ($user->isDilgUser() && $user->isRegionalOfficeAssignment()));
    }

    private function canDeleteNadai(): bool
    {
        $user = auth()->user();

        return $user
            && $user->isDilgUser()
            && $user->isRegionalOfficeAssignment();
    }

    private function isProvinceWideOffice(string $officeName): bool
    {
        return str_starts_with($officeName, 'PLGU ');
    }

    private function resolveConfiguredProvinceLabel(string $provinceName, array $configuredProvinceLabels): string
    {
        $normalizedProvince = ProjectLocationFilterHelper::normalizeLabel($provinceName);
        if ($normalizedProvince === '') {
            return '';
        }

        foreach ($configuredProvinceLabels as $configuredProvinceLabel) {
            if (strcasecmp($configuredProvinceLabel, $normalizedProvince) === 0) {
                return $configuredProvinceLabel;
            }
        }

        $comparableProvince = ProjectLocationFilterHelper::normalizeComparableLocationLabel($normalizedProvince);
        foreach ($configuredProvinceLabels as $configuredProvinceLabel) {
            if (ProjectLocationFilterHelper::normalizeComparableLocationLabel($configuredProvinceLabel) === $comparableProvince) {
                return $configuredProvinceLabel;
            }
        }

        return $normalizedProvince;
    }

    private function resolveConfiguredMunicipalityLabel(string $officeName, array $municipalityOptions): ?string
    {
        $normalizedOfficeName = ProjectLocationFilterHelper::normalizeLabel($officeName);
        if ($normalizedOfficeName === '') {
            return null;
        }

        foreach ($municipalityOptions as $municipalityOption) {
            if (strcasecmp($municipalityOption, $normalizedOfficeName) === 0) {
                return $municipalityOption;
            }
        }

        $comparableOfficeName = ProjectLocationFilterHelper::normalizeComparableLocationLabel($normalizedOfficeName);
        foreach ($municipalityOptions as $municipalityOption) {
            if (ProjectLocationFilterHelper::normalizeComparableLocationLabel($municipalityOption) === $comparableOfficeName) {
                return $municipalityOption;
            }
        }

        return null;
    }

    private function buildSubayProfileOptions(): array
    {
        if (!Schema::hasTable('subay_project_profiles')) {
            return [
                'funding_years' => [],
                'programs' => [],
            ];
        }

        $query = DB::table('subay_project_profiles as spp')
            ->whereNotNull('spp.project_code')
            ->whereRaw("TRIM(COALESCE(spp.project_code, '')) <> ''");

        $programs = (clone $query)
            ->select('spp.program')
            ->whereRaw("TRIM(COALESCE(spp.program, '')) <> ''")
            ->distinct()
            ->orderBy('spp.program')
            ->pluck('spp.program')
            ->map(fn ($value) => $this->normalizeProgramValue($value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $fundingYears = (clone $query)
            ->select('spp.funding_year')
            ->whereRaw("TRIM(COALESCE(spp.funding_year, '')) <> ''")
            ->distinct()
            ->orderByRaw("CAST(COALESCE(NULLIF(TRIM(spp.funding_year), ''), '0') AS UNSIGNED) DESC")
            ->pluck('spp.funding_year')
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();

        return [
            'funding_years' => $fundingYears,
            'programs' => $programs,
        ];
    }

    private function normalizeProgramValue($value): string
    {
        $normalized = strtoupper(trim((string) $value));
        if ($normalized === '') {
            return '';
        }

        $normalized = preg_replace('/^\d+\s*/', '', $normalized) ?? $normalized;

        return match ($normalized) {
            'SUPPORT TO THE BARANGAY DEVELOPMENT PROGRAM', 'SBDP' => 'SBDP',
            'FINANCIAL ASSISTANCE TO LOCAL GOVERNMENT UNIT',
            'FINANCIAL ASSISTANCE TO LOCAL GOVERNMENT UNIT PROGRAM',
            'FALGU' => 'FALGU',
            default => trim((string) $value),
        };
    }

    private function buildUploadFormOptions(string $officeName, string $provinceName): array
    {
        $configuredProvinceLabels = ProjectLocationFilterHelper::buildConfiguredProvinceLabels();
        $resolvedProvince = $this->resolveConfiguredProvinceLabel($provinceName, $configuredProvinceLabels);
        $provinceOptions = array_values(array_filter([$resolvedProvince]));

        $provinceMunicipalityMap = ProjectLocationFilterHelper::buildConfiguredProvinceCityMapFromHierarchy($provinceOptions);
        $municipalityOptions = $provinceMunicipalityMap[$resolvedProvince] ?? [];
        $matchedMunicipality = $this->resolveConfiguredMunicipalityLabel($officeName, $municipalityOptions);

        if ($matchedMunicipality !== null && !$this->isProvinceWideOffice($officeName)) {
            $municipalityOptions = [$matchedMunicipality];
        } elseif (empty($municipalityOptions) && !$this->isProvinceWideOffice($officeName)) {
            $fallbackMunicipality = ProjectLocationFilterHelper::normalizeLabel($officeName);
            if ($fallbackMunicipality !== '') {
                $municipalityOptions = [$fallbackMunicipality];
                $matchedMunicipality = $fallbackMunicipality;
            }
        }

        $provinceMunicipalityMap[$resolvedProvince] = $municipalityOptions;

        $municipalityBarangayMap = ProjectLocationFilterHelper::buildConfiguredCityBarangayMapFromHierarchy($provinceOptions);
        if (!empty($municipalityOptions)) {
            $municipalityBarangayMap = array_intersect_key(
                $municipalityBarangayMap,
                array_fill_keys($municipalityOptions, true)
            );
        }

        $subayOptions = $this->buildSubayProfileOptions();

        return [
            'provinces' => $provinceOptions,
            'province_municipality_map' => $provinceMunicipalityMap,
            'municipalities' => $municipalityOptions,
            'municipality_barangay_map' => $municipalityBarangayMap,
            'funding_years' => $subayOptions['funding_years'],
            'programs' => $subayOptions['programs'],
            'default_province' => $resolvedProvince,
            'default_municipality' => $matchedMunicipality ?? '',
            'default_barangay' => '',
            'default_funding_year' => '',
            'default_program' => '',
        ];
    }

    private function buildIndexFilterOptions($user, array $officeRows): array
    {
        $scopedOfficeRows = collect($officeRows)
            ->map(function (array $row): array {
                return [
                    'province' => trim((string) ($row['province'] ?? '')),
                    'city_municipality' => trim((string) ($row['city_municipality'] ?? '')),
                ];
            })
            ->filter(fn (array $row) => $row['province'] !== '' && $row['city_municipality'] !== '')
            ->values();

        return [
            'provinces' => $scopedOfficeRows
                ->pluck('province')
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'provinceMunicipalities' => $scopedOfficeRows
                ->groupBy('province')
                ->map(function ($rows) {
                    return $rows->pluck('city_municipality')
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values()
                        ->all();
                })
                ->toArray(),
        ];
    }

    private function sendNadaiUploadEmailNotifications(
        NadaiManagementDocument $document,
        string $officeName,
        string $province,
    ): array {
        $recipients = User::query()
            ->whereNotNull('emailaddress')
            ->get()
            ->filter(function (User $user) use ($officeName) {
                return $user->isActive()
                    && $user->isLguScopedUser()
                    && $user->matchesAssignedOffice($officeName);
            })
            ->values();

        $emailedCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        foreach ($recipients as $recipient) {
            $emailAddress = strtolower(trim((string) $recipient->emailaddress));

            if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                $skippedCount++;
                continue;
            }

            try {
                Mail::to($emailAddress)->send(new NadaiUploadedMail(
                    recipient: $recipient,
                    document: $document,
                    officeName: $officeName,
                    province: $province,
                    actionUrl: route('nadai-management.open-document', ['office' => $officeName, 'docId' => $document->id]),
                    senderName: auth()->user()?->fullName() ?: 'DILG Regional Office',
                ));

                $emailedCount++;
            } catch (\Throwable $exception) {
                $failedCount++;

                Log::warning('NADAI upload email delivery failed.', [
                    'recipient_id' => $recipient->idno,
                    'email' => $emailAddress,
                    'office' => $officeName,
                    'document_id' => $document->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'emailed' => $emailedCount,
            'failed' => $failedCount,
            'skipped' => $skippedCount,
        ];
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $filters = [
            'province' => trim((string) $request->query('province', '')),
            'city' => trim((string) $request->query('city', '')),
        ];
        $allowedPerPage = [10, 15, 25, 50];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 15;
        }

        $officeRows = $this->buildOfficeRows($this->getSortedOfficesByProvince());
        $user = auth()->user();

        if ($user && $user->isLguScopedUser() && $user->normalizedOffice() !== '') {
            $officeRows = array_values(array_filter($officeRows, function ($row) use ($user) {
                return $user->matchesAssignedOffice((string) ($row['city_municipality'] ?? ''));
            }));
        } elseif ($user && $user->isDilgUser() && !empty($user->province) && $user->province !== 'Regional Office') {
            $officeRows = array_values(array_filter($officeRows, function ($row) use ($user) {
                return $row['province'] === $user->province;
            }));
        }

        $filterOptions = $this->buildIndexFilterOptions($user, $officeRows);

        if ($filters['province'] !== '') {
            $officeRows = array_values(array_filter($officeRows, function ($row) use ($filters) {
                return (string) ($row['province'] ?? '') === $filters['province'];
            }));
        }

        if ($filters['city'] !== '') {
            $officeRows = array_values(array_filter($officeRows, function ($row) use ($filters) {
                return (string) ($row['city_municipality'] ?? '') === $filters['city'];
            }));
        }

        $totalProvinces = count(array_unique(array_map(fn ($row) => $row['province'], $officeRows)));
        $totalOffices = count($officeRows);

        $officeRowsCollection = collect($officeRows);
        $officeNames = $officeRowsCollection->pluck('city_municipality')->unique()->values()->all();

        $submissionCountsByOffice = collect();
        $latestDocumentsByOffice = collect();
        if (!empty($officeNames)) {
            $submissionCountsByOffice = NadaiManagementDocument::query()
                ->whereIn('office', $officeNames)
                ->selectRaw('office, COUNT(*) as total')
                ->groupBy('office')
                ->pluck('total', 'office');

            $latestDocumentsByOffice = NadaiManagementDocument::query()
                ->whereIn('office', $officeNames)
                ->orderByDesc('uploaded_at')
                ->orderByDesc('nadai_date')
                ->orderByDesc('id')
                ->get()
                ->unique('office')
                ->keyBy('office');
        }

        $officeRowsCollection = $officeRowsCollection
            ->sort(function (array $leftRow, array $rightRow) use ($latestDocumentsByOffice) {
                $leftDocument = $latestDocumentsByOffice->get($leftRow['city_municipality']);
                $rightDocument = $latestDocumentsByOffice->get($rightRow['city_municipality']);

                $leftHasSubmission = $leftDocument ? 1 : 0;
                $rightHasSubmission = $rightDocument ? 1 : 0;
                if ($leftHasSubmission !== $rightHasSubmission) {
                    return $rightHasSubmission <=> $leftHasSubmission;
                }

                $leftUploadedAt = $leftDocument?->uploaded_at?->getTimestamp() ?? 0;
                $rightUploadedAt = $rightDocument?->uploaded_at?->getTimestamp() ?? 0;
                if ($leftUploadedAt !== $rightUploadedAt) {
                    return $rightUploadedAt <=> $leftUploadedAt;
                }

                $provinceComparison = strcasecmp((string) ($leftRow['province'] ?? ''), (string) ($rightRow['province'] ?? ''));
                if ($provinceComparison !== 0) {
                    return $provinceComparison;
                }

                return strcasecmp((string) ($leftRow['city_municipality'] ?? ''), (string) ($rightRow['city_municipality'] ?? ''));
            })
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage('page');
        $officeRows = (new LengthAwarePaginator(
            $officeRowsCollection->forPage($page, $perPage)->values(),
            $officeRowsCollection->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        ))->withQueryString();

        return view('nadai-management.index', compact(
            'officeRows',
            'submissionCountsByOffice',
            'latestDocumentsByOffice',
            'totalProvinces',
            'totalOffices',
            'perPage',
            'filters',
            'filterOptions'
        ));
    }

    public function show(string $office)
    {
        $officeName = $office;
        $province = $this->findProvinceByOffice($officeName);
        if (!$province) {
            abort(404);
        }

        if (!$this->canAccessOffice($officeName, $province)) {
            abort(403);
        }

        $documents = NadaiManagementDocument::query()
            ->where('office', $officeName)
            ->orderByDesc('uploaded_at')
            ->orderByDesc('nadai_date')
            ->orderByDesc('id')
            ->get();

        $usersById = $documents->pluck('uploaded_by')
            ->filter()
            ->unique()
            ->pipe(function ($ids) {
                return $ids->isEmpty()
                    ? collect()
                    : User::query()->whereIn('idno', $ids->all())->get()->keyBy('idno');
            });

        $canUpload = $this->canUploadNadai();
        $canDelete = $this->canDeleteNadai();
        $uploadFormOptions = $this->buildUploadFormOptions($officeName, $province);

        return view('nadai-management.show', compact(
            'officeName',
            'province',
            'documents',
            'usersById',
            'canUpload',
            'canDelete',
            'uploadFormOptions'
        ));
    }

    public function store(Request $request, string $office)
    {
        $officeName = $office;
        $province = $this->findProvinceByOffice($officeName);
        if (!$province) {
            abort(404);
        }

        if (!$this->canAccessOffice($officeName, $province)) {
            abort(403);
        }

        if (!$this->canUploadNadai()) {
            abort(403, 'Only DILG Regional Office users can upload NADAI documents.');
        }

        $uploadFormOptions = $this->buildUploadFormOptions($officeName, $province);
        $barangayOptions = $uploadFormOptions['municipality_barangay_map'][
            trim((string) $request->input('municipality'))
        ] ?? [];

        $validated = $request->validate([
            'province' => array_merge(['required', 'string'], !empty($uploadFormOptions['provinces'])
                ? [Rule::in($uploadFormOptions['provinces'])]
                : []),
            'municipality' => array_merge(['required', 'string'], !empty($uploadFormOptions['municipalities'])
                ? [Rule::in($uploadFormOptions['municipalities'])]
                : []),
            'barangay' => array_merge(['required', 'string'], !empty($barangayOptions)
                ? [Rule::in($barangayOptions)]
                : []),
            'funding_year' => array_merge(['required', 'string'], !empty($uploadFormOptions['funding_years'])
                ? [Rule::in($uploadFormOptions['funding_years'])]
                : []),
            'program' => array_merge(['required', 'string'], !empty($uploadFormOptions['programs'])
                ? [Rule::in($uploadFormOptions['programs'])]
                : []),
            'project_title' => ['required', 'string', 'max:255'],
            'nadai_date' => ['required', 'date'],
            'document' => ['required', 'file', 'mimes:pdf', 'max:15360'],
        ]);

        $file = $request->file('document');
        $officeSlug = Str::slug($officeName, '_');
        $timestamp = now()->format('Ymd_His');
        $storedFilename = $timestamp . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '_') . '.pdf';
        $path = $file->storeAs('nadai-management/' . $officeSlug, $storedFilename, 'public');

        $document = NadaiManagementDocument::create([
            'office' => $officeName,
            'province' => trim((string) $validated['province']),
            'municipality' => trim((string) $validated['municipality']),
            'barangay' => trim((string) $validated['barangay']),
            'funding_year' => trim((string) $validated['funding_year']),
            'program' => trim((string) $validated['program']),
            'project_title' => trim((string) $validated['project_title']),
            'nadai_date' => $validated['nadai_date'],
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
        ]);

        $emailResults = $this->sendNadaiUploadEmailNotifications($document, $officeName, $province);

        $summaryParts = ['NADAI document uploaded successfully.'];
        if ($emailResults['emailed'] > 0) {
            $summaryParts[] = 'Email sent to ' . number_format($emailResults['emailed']) . ' LGU user(s).';
        }
        if ($emailResults['skipped'] > 0) {
            $summaryParts[] = number_format($emailResults['skipped']) . ' recipient(s) were skipped because no valid email address was available.';
        }
        if ($emailResults['failed'] > 0) {
            $summaryParts[] = 'Email delivery failed for ' . number_format($emailResults['failed']) . ' recipient(s). Check the mail configuration or logs.';
        }

        return redirect()
            ->route('nadai-management.show', ['office' => $officeName])
            ->with('success', implode(' ', $summaryParts));
    }

    public function viewDocument(string $office, int $docId)
    {
        $officeName = $office;
        $province = $this->findProvinceByOffice($officeName);
        if (!$province) {
            abort(404);
        }

        if (!$this->canAccessOffice($officeName, $province)) {
            abort(403);
        }

        $document = NadaiManagementDocument::query()
            ->where('office', $officeName)
            ->where('id', $docId)
            ->firstOrFail();

        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }

        return response()->file(
            Storage::disk('public')->path($document->file_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . ($document->original_filename ?: basename($document->file_path)) . '"',
            ]
        );
    }

    public function downloadDocument(string $office, int $docId)
    {
        $officeName = $office;
        $province = $this->findProvinceByOffice($officeName);
        if (!$province) {
            abort(404);
        }

        if (!$this->canAccessOffice($officeName, $province)) {
            abort(403);
        }

        $document = NadaiManagementDocument::query()
            ->where('office', $officeName)
            ->where('id', $docId)
            ->firstOrFail();

        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk('public')->download(
            $document->file_path,
            $document->original_filename ?: basename($document->file_path)
        );
    }

    public function updateDocument(Request $request, string $office, int $docId)
    {
        $officeName = $office;
        $province = $this->findProvinceByOffice($officeName);
        if (!$province) {
            abort(404);
        }

        if (!$this->canAccessOffice($officeName, $province)) {
            abort(403);
        }

        if (!$this->canUploadNadai()) {
            abort(403, 'Only DILG Regional Office users can edit NADAI documents.');
        }

        $document = NadaiManagementDocument::query()
            ->where('office', $officeName)
            ->where('id', $docId)
            ->firstOrFail();

        $uploadFormOptions = $this->buildUploadFormOptions($officeName, $province);
        $barangayOptions = $uploadFormOptions['municipality_barangay_map'][
            trim((string) $request->input('municipality'))
        ] ?? [];

        $validated = $request->validate([
            'edit_document_id' => ['required', 'integer', 'in:' . $document->id],
            'province' => array_merge(['required', 'string'], !empty($uploadFormOptions['provinces'])
                ? [Rule::in($uploadFormOptions['provinces'])]
                : []),
            'municipality' => array_merge(['required', 'string'], !empty($uploadFormOptions['municipalities'])
                ? [Rule::in($uploadFormOptions['municipalities'])]
                : []),
            'barangay' => array_merge(['required', 'string'], !empty($barangayOptions)
                ? [Rule::in($barangayOptions)]
                : []),
            'funding_year' => array_merge(['required', 'string'], !empty($uploadFormOptions['funding_years'])
                ? [Rule::in($uploadFormOptions['funding_years'])]
                : []),
            'program' => array_merge(['required', 'string'], !empty($uploadFormOptions['programs'])
                ? [Rule::in($uploadFormOptions['programs'])]
                : []),
            'project_title' => ['required', 'string', 'max:255'],
            'nadai_date' => ['required', 'date'],
            'document' => ['nullable', 'file', 'mimes:pdf', 'max:15360'],
        ]);

        $oldFilePath = $document->file_path;
        $file = $request->file('document');

        if ($file) {
            $officeSlug = Str::slug($officeName, '_');
            $timestamp = now()->format('Ymd_His');
            $storedFilename = $timestamp . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '_') . '.pdf';
            $document->file_path = $file->storeAs('nadai-management/' . $officeSlug, $storedFilename, 'public');
            $document->original_filename = $file->getClientOriginalName();
        }

        $document->province = trim((string) $validated['province']);
        $document->municipality = trim((string) $validated['municipality']);
        $document->barangay = trim((string) $validated['barangay']);
        $document->funding_year = trim((string) $validated['funding_year']);
        $document->program = trim((string) $validated['program']);
        $document->project_title = trim((string) $validated['project_title']);
        $document->nadai_date = $validated['nadai_date'];
        $document->save();

        if ($file && $oldFilePath && $oldFilePath !== $document->file_path && Storage::disk('public')->exists($oldFilePath)) {
            Storage::disk('public')->delete($oldFilePath);
        }

        return redirect()
            ->route('nadai-management.show', ['office' => $officeName])
            ->with('success', 'NADAI document updated successfully.');
    }

    public function openDocumentAndRedirect(string $office, int $docId)
    {
        $officeName = $office;
        $province = $this->findProvinceByOffice($officeName);
        if (!$province) {
            abort(404);
        }

        if (!$this->canAccessOffice($officeName, $province)) {
            abort(403);
        }

        $document = NadaiManagementDocument::query()
            ->where('office', $officeName)
            ->where('id', $docId)
            ->firstOrFail();

        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }

        return view('nadai-management.open-document', [
            'officeName' => $officeName,
            'document' => $document,
            'downloadUrl' => route('nadai-management.download-document', ['office' => $officeName, 'docId' => $document->id]),
            'redirectUrl' => route('nadai-management.show', ['office' => $officeName]),
        ]);
    }

    public function deleteDocument(string $office, int $docId)
    {
        if (!$this->canDeleteNadai()) {
            abort(403, 'Only DILG Regional Office users can delete NADAI documents.');
        }

        $document = NadaiManagementDocument::query()
            ->where('office', $office)
            ->where('id', $docId)
            ->firstOrFail();

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()
            ->route('nadai-management.show', ['office' => $office])
            ->with('success', 'NADAI document deleted successfully.');
    }
}
