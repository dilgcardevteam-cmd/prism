<?php

namespace App\Http\Controllers;

use App\Models\ConfirmationOfFundReceiptDocument;
use App\Models\NadaiManagementDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConfirmationOfFundReceiptController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('crud_permission:pre_implementation_documents,view')->only(['index', 'show', 'viewDocument', 'viewConfirmationDocument']);
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

    private function canUploadConfirmationDocument(string $officeName): bool
    {
        return $this->canAcceptDocument($officeName);
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
        $latestConfirmationDocumentsByOffice = collect();
        $pendingAcceptanceCountsByOffice = collect();
        $pendingCfrUploadCountsByOffice = collect();
        if (!empty($officeNames)) {
            $nadaiDocuments = NadaiManagementDocument::query()
                ->whereIn('office', $officeNames)
                ->orderByDesc('uploaded_at')
                ->orderByDesc('nadai_date')
                ->orderByDesc('id')
                ->get();

            $submissionCountsByOffice = $nadaiDocuments
                ->groupBy('office')
                ->map(fn ($documents) => $documents->count());

            $latestDocumentsByOffice = $nadaiDocuments
                ->unique('office')
                ->keyBy('office');

            $confirmationDocuments = ConfirmationOfFundReceiptDocument::query()
                ->whereIn('nadai_document_id', $nadaiDocuments->pluck('id')->all())
                ->orderByDesc('uploaded_at')
                ->orderByDesc('confirmation_date')
                ->orderByDesc('id')
                ->get();

            $confirmationDocumentsByNadaiId = $confirmationDocuments
                ->unique('nadai_document_id')
                ->keyBy('nadai_document_id');

            $latestConfirmationDocumentsByOffice = $confirmationDocuments
                ->unique('office')
                ->keyBy('office');

            $pendingAcceptanceCountsByOffice = $nadaiDocuments
                ->groupBy('office')
                ->map(fn ($documents) => $documents->whereNull('confirmation_accepted_at')->count());

            $pendingCfrUploadCountsByOffice = $nadaiDocuments
                ->groupBy('office')
                ->map(function ($documents) use ($confirmationDocumentsByNadaiId) {
                    return $documents->filter(function ($document) use ($confirmationDocumentsByNadaiId) {
                        return $document->confirmation_accepted_at && !$confirmationDocumentsByNadaiId->has($document->id);
                    })->count();
                });
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
            'latestConfirmationDocumentsByOffice',
            'pendingAcceptanceCountsByOffice',
            'pendingCfrUploadCountsByOffice',
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

        $confirmationDocumentsByNadaiId = $documents->isEmpty()
            ? collect()
            : ConfirmationOfFundReceiptDocument::query()
                ->whereIn('nadai_document_id', $documents->pluck('id')->all())
                ->orderByDesc('uploaded_at')
                ->orderByDesc('confirmation_date')
                ->orderByDesc('id')
                ->get()
                ->unique('nadai_document_id')
                ->keyBy('nadai_document_id');

        $documents = $documents
            ->sort(function ($leftDocument, $rightDocument) use ($confirmationDocumentsByNadaiId) {
                $leftPendingAction = $leftDocument->confirmation_accepted_at && !$confirmationDocumentsByNadaiId->has($leftDocument->id) ? 1 : 0;
                $rightPendingAction = $rightDocument->confirmation_accepted_at && !$confirmationDocumentsByNadaiId->has($rightDocument->id) ? 1 : 0;

                if ($leftPendingAction !== $rightPendingAction) {
                    return $rightPendingAction <=> $leftPendingAction;
                }

                $leftUploadedAt = $leftDocument->uploaded_at?->getTimestamp() ?? 0;
                $rightUploadedAt = $rightDocument->uploaded_at?->getTimestamp() ?? 0;
                if ($leftUploadedAt !== $rightUploadedAt) {
                    return $rightUploadedAt <=> $leftUploadedAt;
                }

                $leftNadaiDate = $leftDocument->nadai_date?->getTimestamp() ?? 0;
                $rightNadaiDate = $rightDocument->nadai_date?->getTimestamp() ?? 0;
                if ($leftNadaiDate !== $rightNadaiDate) {
                    return $rightNadaiDate <=> $leftNadaiDate;
                }

                return $rightDocument->id <=> $leftDocument->id;
            })
            ->values();

        $usersById = $documents->pluck('uploaded_by')
            ->merge($documents->pluck('confirmation_accepted_by'))
            ->merge($confirmationDocumentsByNadaiId->pluck('uploaded_by'))
            ->filter()
            ->unique()
            ->pipe(function ($ids) {
                return $ids->isEmpty()
                    ? collect()
                    : User::query()->whereIn('idno', $ids->all())->get()->keyBy('idno');
            });

        $canAccept = $this->canAcceptDocument($officeName);
        $canUploadConfirmation = $this->canUploadConfirmationDocument($officeName);

        return view('reports.one-time.confirmation-of-fund-receipt.show', compact(
            'officeName',
            'province',
            'documents',
            'confirmationDocumentsByNadaiId',
            'usersById',
            'canAccept',
            'canUploadConfirmation'
        ));
    }

    public function store(Request $request, string $office, int $docId)
    {
        $officeName = $office;
        $province = $this->findProvinceByOffice($officeName);
        if (!$province) {
            abort(404);
        }

        if (!$this->canAccessOffice($officeName, $province)) {
            abort(403);
        }

        if (!$this->canUploadConfirmationDocument($officeName)) {
            abort(403, 'Only the assigned LGU user can upload the Confirmation of Fund Receipt attachment.');
        }

        $nadaiDocument = NadaiManagementDocument::query()
            ->where('office', $officeName)
            ->where('id', $docId)
            ->firstOrFail();

        if (!$nadaiDocument->confirmation_accepted_at) {
            return redirect()
                ->route('reports.one-time.confirmation-of-fund-receipt.show', ['office' => $officeName])
                ->withErrors(['confirmation_document' => 'Please accept the uploaded NADAI before uploading the Confirmation of Fund Receipt attachment.']);
        }

        $validated = $request->validate([
            'project_title' => ['required', 'string', 'max:255'],
            'confirmation_date' => ['required', 'date'],
            'document' => ['required', 'file', 'mimes:pdf', 'max:15360'],
        ]);

        $file = $request->file('document');
        $officeSlug = Str::slug($officeName, '_');
        $timestamp = now()->format('Ymd_His');
        $storedFilename = $timestamp . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '_') . '.pdf';
        $path = $file->storeAs('confirmation-of-fund-receipt/' . $officeSlug, $storedFilename, 'public');

        $existingDocument = ConfirmationOfFundReceiptDocument::query()
            ->where('nadai_document_id', $nadaiDocument->id)
            ->first();

        if ($existingDocument && $existingDocument->file_path && Storage::disk('public')->exists($existingDocument->file_path)) {
            Storage::disk('public')->delete($existingDocument->file_path);
        }

        ConfirmationOfFundReceiptDocument::updateOrCreate(
            ['nadai_document_id' => $nadaiDocument->id],
            [
                'office' => $officeName,
                'province' => $province,
                'project_title' => trim((string) $validated['project_title']),
                'confirmation_date' => $validated['confirmation_date'],
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'uploaded_by' => auth()->id(),
                'uploaded_at' => now(),
            ]
        );

        return redirect()
            ->route('reports.one-time.confirmation-of-fund-receipt.show', ['office' => $officeName])
            ->with('success', 'Confirmation of Fund Receipt attachment uploaded successfully.');
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

        $validated = request()->validate([
            'acceptance_remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $document = NadaiManagementDocument::query()
            ->where('office', $officeName)
            ->where('id', $docId)
            ->firstOrFail();

        if (!$document->confirmation_accepted_at) {
            $document->update([
                'confirmation_accepted_at' => now(),
                'confirmation_accepted_by' => auth()->id(),
                'confirmation_acceptance_remarks' => $validated['acceptance_remarks'] ?? null,
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

    public function viewConfirmationDocument(string $office, int $attachmentId)
    {
        $officeName = $office;
        $province = $this->findProvinceByOffice($officeName);
        if (!$province) {
            abort(404);
        }

        if (!$this->canAccessOffice($officeName, $province)) {
            abort(403);
        }

        $document = ConfirmationOfFundReceiptDocument::query()
            ->where('office', $officeName)
            ->where('id', $attachmentId)
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
