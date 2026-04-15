@extends('layouts.dashboard')

@section('title', 'Upload Project-at-Risk Data')
@section('page-title', 'Upload Project-at-Risk Data')

@section('content')
    @php
        $canAddUpload = Auth::user()->hasCrudPermission('project_at_risk_data_uploads', 'add');
        $canUpdateUpload = Auth::user()->hasCrudPermission('project_at_risk_data_uploads', 'update');
        $canDeleteUpload = Auth::user()->hasCrudPermission('project_at_risk_data_uploads', 'delete');
    @endphp
    <div class="content-header">
        <h1>Upload Project-at-Risk Data</h1>
        <p>Upload CSV files, review the import history, then load the selected file to replace the current Project At Risk dataset.</p>
    </div>

    @if (session('success'))
        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 14px 16px; border-radius: 8px; margin-top: 16px;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div style="background-color: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 14px 16px; border-radius: 8px; margin-top: 16px;">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="background-color: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 14px 16px; border-radius: 8px; margin-top: 16px;">
            <ul style="margin: 0; padding-left: 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($tableMissing ?? false)
        <div style="background-color: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 14px 16px; border-radius: 8px; margin-top: 16px;">
            Project At Risk data table is not available yet. Please run the migration first.
        </div>
    @else
        <div style="background: white; padding: 24px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 16px; overflow-x: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
                <div>
                    <h2 style="color: #002C76; font-size: 18px; margin: 0;">Imported Project-at-Risk Files</h2>
                    <p style="margin: 6px 0 0 0; color: #6b7280; font-size: 12px;">Loading a file will delete the current Project At Risk records first, then import the selected CSV.</p>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <a href="{{ route('system-management.upload-project-at-risk.template') }}" data-page-loading="false" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 8px 14px; background: linear-gradient(180deg, #008c4d 0%, #007542 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; text-decoration: none; box-shadow: 0 6px 16px rgba(0, 117, 66, 0.18);">
                        <i class="fas fa-file-excel" aria-hidden="true"></i>
                        <span>Download Template</span>
                    </a>
                    @if($canAddUpload)
                        <button type="button" onclick="openImportModal()" style="padding: 8px 14px; background: linear-gradient(180deg, #0a4cb3 0%, #002C76 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; box-shadow: 0 6px 16px rgba(0, 44, 118, 0.2);">
                            Import CSV
                        </button>
                    @endif
                </div>
            </div>

            @if($importHistoryTableMissing ?? false)
                <div style="background-color: #fff7ed; border: 1px solid #fdba74; color: #9a3412; padding: 12px 14px; border-radius: 8px; margin-top: 16px; font-size: 12px;">
                    Import history table is not available yet. Run migration to enable the Date/Time/File Name/Action list.
                </div>
            @else
                <div style="max-height: 520px; overflow: auto; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px; table-layout: auto;">
                        <thead>
                            <tr style="background-color: #f3f4f6; border-bottom: 2px solid #d1d5db;">
                                <th style="padding: 10px; text-align: left; font-weight: 600; color: #374151; position: sticky; top: 0; background-color: #f3f4f6; z-index: 1;">Date</th>
                                <th style="padding: 10px; text-align: left; font-weight: 600; color: #374151; position: sticky; top: 0; background-color: #f3f4f6; z-index: 1;">Time</th>
                                <th style="padding: 10px; text-align: left; font-weight: 600; color: #374151; position: sticky; top: 0; background-color: #f3f4f6; z-index: 1;">File Name</th>
                                <th style="padding: 10px; text-align: center; font-weight: 600; color: #374151; position: sticky; top: 0; background-color: #f3f4f6; z-index: 1;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($importHistoryRows as $historyRow)
                                @php
                                    $importedAt = !empty($historyRow->imported_at)
                                        ? \Carbon\Carbon::parse($historyRow->imported_at)->setTimezone(config('app.timezone'))
                                        : null;
                                @endphp
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 10px; color: #374151; vertical-align: top; white-space: nowrap;">
                                        {{ $importedAt ? $importedAt->format('M d, Y') : '-' }}
                                    </td>
                                    <td style="padding: 10px; color: #374151; vertical-align: top; white-space: nowrap;">
                                        {{ $importedAt ? $importedAt->format('h:i A') : '-' }}
                                    </td>
                                    <td style="padding: 10px; color: #374151; vertical-align: top; word-break: break-word;">
                                        {{ $historyRow->original_file_name ?: '-' }}
                                    </td>
                                    <td style="padding: 10px; color: #374151; vertical-align: top;">
                                        <div style="display: flex; justify-content: center; gap: 8px; flex-wrap: wrap;">
                                            @if($canUpdateUpload)
                                                <form method="POST" action="{{ route('system-management.upload-project-at-risk.load', ['importId' => $historyRow->id]) }}" onsubmit="return confirm('Loading this file will replace the current Project At Risk data. Continue?');">
                                                    @csrf
                                                    <button type="submit" style="padding: 6px 10px; background-color: #002C76; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 11px; font-weight: 600;">
                                                        Load
                                                    </button>
                                                </form>
                                            @endif
                                            <a href="{{ route('system-management.upload-project-at-risk.download', ['importId' => $historyRow->id]) }}" data-page-loading="false" style="display: inline-flex; align-items: center; justify-content: center; padding: 6px 10px; background-color: #0f766e; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 11px; font-weight: 600; text-decoration: none;">
                                                Download CSV
                                            </a>
                                            @if($canDeleteUpload)
                                                <form method="POST" action="{{ route('system-management.upload-project-at-risk.delete', ['importId' => $historyRow->id]) }}" onsubmit="return confirm('Delete this imported file record?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" style="padding: 6px 10px; background-color: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 11px; font-weight: 600;">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="padding: 20px; text-align: center; color: #6b7280;">
                                        No imported files yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(method_exists($importHistoryRows, 'hasPages') && $importHistoryRows->hasPages())
                    <div style="margin-top: 16px; display: flex; justify-content: space-between; gap: 12px; align-items: center; flex-wrap: wrap;">
                        <div style="font-size: 12px; color: #6b7280;">
                            Page {{ $importHistoryRows->currentPage() }} of {{ $importHistoryRows->lastPage() }} ·
                            Showing {{ $importHistoryRows->firstItem() ?? 0 }}–{{ $importHistoryRows->lastItem() ?? 0 }} of {{ $importHistoryRows->total() }}
                        </div>
                        <div style="display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap;">
                            @if($importHistoryRows->onFirstPage())
                                <span style="padding: 8px 12px; background-color: #e5e7eb; color: #9ca3af; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-chevron-left"></i> Back
                                </span>
                            @else
                                <a href="{{ $importHistoryRows->previousPageUrl() }}" style="padding: 8px 12px; background-color: #ffffff; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                    <i class="fas fa-chevron-left"></i> Back
                                </a>
                            @endif

                            @if($importHistoryRows->hasMorePages())
                                <a href="{{ $importHistoryRows->nextPageUrl() }}" style="padding: 8px 12px; background-color: #002C76; color: white; border: 1px solid #002C76; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            @else
                                <span style="padding: 8px 12px; background-color: #e5e7eb; color: #9ca3af; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                    Next <i class="fas fa-chevron-right"></i>
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            @endif
        </div>
    @endif

    @if($canAddUpload)
        <div id="importModal" style="display: none; position: fixed; inset: 0; background-color: rgba(0,0,0,0.45); z-index: 1000; align-items: center; justify-content: center;">
            <div style="background: white; padding: 24px; border-radius: 10px; width: 100%; max-width: 480px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
                <h3 style="margin: 0 0 12px 0; color: #111827; font-size: 18px; font-weight: 600;">Import Project-at-Risk Data (CSV)</h3>
                <form method="POST" action="{{ route('system-management.upload-project-at-risk.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div style="margin-bottom: 16px;">
                        <label for="import-file" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Upload CSV File</label>
                        <input id="import-file" class="dashboard-file-input" type="file" name="file" accept=".csv" required>
                        <div style="margin-top: 6px; font-size: 11px; color: #6b7280;">Excel users: Save As CSV first.</div>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" onclick="closeImportModal()" style="padding: 8px 14px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">Cancel</button>
                        <button type="submit" style="padding: 8px 14px; background-color: #002C76; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script>
        function openImportModal() {
            const modal = document.getElementById('importModal');
            if (modal) {
                modal.style.display = 'flex';
            }
        }

        function closeImportModal() {
            const modal = document.getElementById('importModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        window.openImportModal = openImportModal;
        window.closeImportModal = closeImportModal;
    </script>
@endsection
