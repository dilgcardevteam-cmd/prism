<?php

namespace App\Http\Controllers;

use App\Models\NadaiManagementDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class ConfirmationOfFundReceiptController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('crud_permission:pre_implementation_documents,view')->only(['index', 'show', 'viewDocument']);
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

    private function canAcceptDocument(string $officeName): bool
    {
        $user = auth()->user();

        return $user
            && $user->isLguScopedUser()
            && $user->matchesAssignedOffice($officeName);
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

        $scopedOfficeRows = collect($officeRows);
        $filterOptions = [
            'provinces' => $scopedOfficeRows->pluck('province')->filter()->unique()->sort()->values()->all(),
            'provinceMunicipalities' => $scopedOfficeRows
                ->groupBy('province')
                ->map(fn ($rows) => $rows->pluck('city_municipality')->filter()->values()->all())
                ->toArray(),
        ];

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

        return view('reports.one-time.confirmation-of-fund-receipt.index', compact(
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
            ->merge($documents->pluck('confirmation_accepted_by'))
            ->filter()
            ->unique()
            ->pipe(function ($ids) {
                return $ids->isEmpty()
                    ? collect()
                    : User::query()->whereIn('idno', $ids->all())->get()->keyBy('idno');
            });

        $canAccept = $this->canAcceptDocument($officeName);

        return view('reports.one-time.confirmation-of-fund-receipt.show', compact(
            'officeName',
            'province',
            'documents',
            'usersById',
            'canAccept'
        ));
    }

    public function store(Request $request, string $office)
    {
        abort(404);
    }

    public function acceptDocument(string $office, int $docId)
    {
        $officeName = $office;
        $province = $this->findProvinceByOffice($officeName);
        if (!$province) {
            abort(404);
        }

        if (!$this->canAccessOffice($officeName, $province)) {
            abort(403);
        }

        if (!$this->canAcceptDocument($officeName)) {
            abort(403, 'Only the assigned LGU user can accept this uploaded NADAI document.');
        }

        $document = NadaiManagementDocument::query()
            ->where('office', $officeName)
            ->where('id', $docId)
            ->firstOrFail();

        if (!$document->confirmation_accepted_at) {
            $document->update([
                'confirmation_accepted_at' => now(),
                'confirmation_accepted_by' => auth()->id(),
            ]);
        }

        return redirect()
            ->route('reports.one-time.confirmation-of-fund-receipt.show', ['office' => $officeName])
            ->with('success', 'Uploaded NADAI document accepted successfully for Confirmation of Fund Receipt.');
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

    public function deleteDocument(string $office, int $docId)
    {
        abort(404);
    }
}
