@extends('layouts.dashboard')

@section('title', 'Location Configuration')
@section('page-title', 'Location Configuration')

@section('content')
    @if (session('success'))
        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 14px 16px; border-radius: 8px; margin-bottom: 16px;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div style="background-color: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 14px 16px; border-radius: 8px; margin-bottom: 16px;">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="background-color: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 14px 16px; border-radius: 8px; margin-bottom: 16px;">
            <ul style="margin: 0; padding-left: 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; margin-bottom: 12px; flex-wrap: wrap;">
        <div class="content-header" style="margin-bottom: 0;">
            <h1>Location Configuration</h1>
            <p>Manage the location-related configuration used across the application.</p>
        </div>

        <a href="{{ route('utilities.system-setup.index') }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 8px; background: linear-gradient(180deg, #0a4cb3 0%, #002c76 100%); color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 600; border: 1px solid #002c76; box-shadow: 0 8px 18px rgba(0, 44, 118, 0.18);">
            <i class="fas fa-arrow-left"></i>
            <span>Back to System Setup</span>
        </a>
    </div>

    <section style="background: white; padding: 28px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08); margin-bottom: 20px;">
        <div style="display: flex; align-items: flex-start; gap: 14px; margin-bottom: 18px;">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: #dbeafe; color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <div>
                <h2 style="margin: 0; color: #002C76; font-size: 20px;">Location Configuration</h2>
                <p style="margin: 6px 0 0; color: #6b7280; font-size: 14px; line-height: 1.6;">
                    This page is reserved for configuring provinces, municipalities, barangays, and other location references used by the system.
                </p>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 18px;">
            @foreach ($locationDatasets as $dataset)
                @php
                    $lastUpdated = !empty($dataset['last_updated_at'])
                        ? \Carbon\Carbon::parse($dataset['last_updated_at'])->setTimezone(config('app.timezone'))
                        : null;
                    $importHistoryRows = $dataset['import_history_rows'] ?? collect();
                @endphp
                <article style="background: #ffffff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow-x: auto;">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 14px;">
                        <div style="display: flex; align-items: flex-start; gap: 12px;">
                            <div style="width: 46px; height: 46px; border-radius: 12px; background: #eff6ff; color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;">
                                <i class="{{ $dataset['icon'] }}"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; color: #002C76; font-size: 17px;">{{ $dataset['label'] }}</h3>
                                <p style="margin: 6px 0 0; color: #64748b; font-size: 13px; line-height: 1.6;">{{ $dataset['description'] }}</p>
                            </div>
                        </div>
                    </div>

                    @if ($dataset['table_exists'])
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
                            <h4 style="color: #002C76; font-size: 18px; margin: 0;">Imported {{ $dataset['label'] }} Files</h4>
                            <button type="button" onclick="openLocationImportModal('{{ $dataset['key'] }}')" style="padding: 8px 14px; background: linear-gradient(180deg, #0a4cb3 0%, #002C76 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; box-shadow: 0 6px 16px rgba(0, 44, 118, 0.2);">
                                Import CSV
                            </button>
                        </div>

                        @if ($dataset['history_table_missing'])
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
                                            @forelse ($importHistoryRows as $historyRow)
                                                @php
                                                    $importedAt = !empty($historyRow->imported_at)
                                                        ? \Carbon\Carbon::parse($historyRow->imported_at)->setTimezone(config('app.timezone'))
                                                        : null;
                                                    $lastLoadedAt = !empty($historyRow->last_loaded_at)
                                                        ? \Carbon\Carbon::parse($historyRow->last_loaded_at)->setTimezone(config('app.timezone'))
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
                                                        <div>{{ $historyRow->original_file_name ?: '-' }}</div>
                                                        @if ($lastLoadedAt)
                                                            <span style="display: inline-flex; align-items: center; margin-top: 6px; padding: 4px 8px; border-radius: 999px; background: #dcfce7; color: #166534; font-size: 10px; font-weight: 700;">
                                                                Loaded {{ $lastLoadedAt->format('M d, Y h:i A') }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td style="padding: 10px; color: #374151; vertical-align: top;">
                                                        <div style="display: flex; justify-content: center; gap: 6px; flex-wrap: wrap;">
                                                            <form method="POST" action="{{ route('utilities.location-configuration.load', ['dataset' => $dataset['key'], 'importId' => $historyRow->id]) }}" onsubmit="return confirm('Loading this file will replace the current {{ $dataset['label'] }} data. Continue?');">
                                                                @csrf
                                                                <button type="submit" style="padding: 6px 10px; background-color: #002C76; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 11px; font-weight: 600;">
                                                                    Load
                                                                </button>
                                                            </form>
                                                            <a href="{{ route('utilities.location-configuration.download', ['dataset' => $dataset['key'], 'importId' => $historyRow->id]) }}" style="display: inline-flex; align-items: center; justify-content: center; padding: 6px 10px; background-color: #0f766e; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 11px; font-weight: 600; text-decoration: none;">
                                                                Download CSV
                                                            </a>
                                                            <form method="POST" action="{{ route('utilities.location-configuration.delete', ['dataset' => $dataset['key'], 'importId' => $historyRow->id]) }}" onsubmit="return confirm('Delete this imported file record?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" style="padding: 6px 10px; background-color: #dc2626; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 11px; font-weight: 600;">
                                                                    Delete
                                                                </button>
                                                            </form>
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
                        @endif

                        <div id="importModal-{{ $dataset['key'] }}" style="display: none; position: fixed; inset: 0; background-color: rgba(0,0,0,0.45); z-index: 1000; align-items: center; justify-content: center;">
                            <div style="background: white; padding: 24px; border-radius: 10px; width: 100%; max-width: 480px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
                                <h3 style="margin: 0 0 12px 0; color: #111827; font-size: 18px; font-weight: 600;">Import {{ $dataset['label'] }} Data (CSV)</h3>
                                <form method="POST" action="{{ $dataset['route'] }}" enctype="multipart/form-data">
                                    @csrf
                                    <div style="margin-bottom: 16px;">
                                        <label for="upload-{{ $dataset['key'] }}" style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Upload CSV File</label>
                                        <input id="upload-{{ $dataset['key'] }}" class="dashboard-file-input" type="file" name="file" accept=".csv" required>
                                        <div style="margin-top: 6px; font-size: 11px; color: #6b7280;">Excel users: Save As CSV first.</div>
                                    </div>
                                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                                        <button type="button" onclick="closeLocationImportModal('{{ $dataset['key'] }}')" style="padding: 8px 14px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">Cancel</button>
                                        <button type="submit" style="padding: 8px 14px; background-color: #002C76; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">Upload</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @else
                        <div style="padding: 12px 14px; border: 1px solid #fecaca; border-radius: 10px; background: #fff5f5; color: #991b1b; font-size: 12px; line-height: 1.6;">
                            The {{ $dataset['label'] }} table is not available yet. Run the latest migration before uploading files.
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    </section>

    <script>
        function openLocationImportModal(datasetKey) {
            const modal = document.getElementById(`importModal-${datasetKey}`);
            if (modal) {
                modal.style.display = 'flex';
            }
        }

        function closeLocationImportModal(datasetKey) {
            const modal = document.getElementById(`importModal-${datasetKey}`);
            if (modal) {
                modal.style.display = 'none';
            }
        }

        window.openLocationImportModal = openLocationImportModal;
        window.closeLocationImportModal = closeLocationImportModal;
    </script>
@endsection
