@extends('layouts.dashboard')

@section('title', 'Edit Fund Utilization Report - ' . $report->project_code)
@section('page-title', 'Edit Fund Utilization Report')

@section('content')
    <div class="content-header">
        <h1>Edit Fund Utilization Report</h1>
        <p><i class="fas fa-edit" style="margin-right: 8px;"></i>Update project information for {{ $report->project_code }}</p>
        <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
            <a href="{{ route('fund-utilization.show', $report->project_code) }}" style="display: inline-block; padding: 12px 24px; background-color: #6b7280; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                <i class="fas fa-arrow-left" style="margin-right: 8px;"></i> Back to Project
            </a>
        </div>
    </div>

    @if (session('success'))
        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-exclamation-triangle"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div style="background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); margin-bottom: 20px;">
        <form method="POST" action="{{ route('fund-utilization.update', $report->project_code) }}" style="max-width: 800px; margin: 0 auto;">
            @csrf
            @method('PUT')

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                <!-- Project Code -->
                <div>
                    <label for="project_code" style="display: block; color: #374151; font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                        Project Code <span style="color: #dc2626;">*</span>
                    </label>
                    <input
                        type="text"
                        id="project_code"
                        name="project_code"
                        value="{{ old('project_code', $report->project_code) }}"
                        required
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background-color: #f9fafb; transition: all 0.3s ease;"
                        placeholder="Enter project code"
                    >
                    @error('project_code')
                        <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Province -->
                <div>
                    <label for="province" style="display: block; color: #374151; font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                        Province/City <span style="color: #dc2626;">*</span>
                    </label>
                    <select
                        id="province"
                        name="province"
                        required
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background-color: #f9fafb; transition: all 0.3s ease;"
                        onchange="updateMunicipalities()"
                    >
                        <option value="">Select Province/City</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province }}" {{ old('province', $report->province) === $province ? 'selected' : '' }}>
                                {{ $province }}
                            </option>
                        @endforeach
                    </select>
                    @error('province')
                        <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Implementing Unit -->
                <div>
                    <label for="implementing_unit" style="display: block; color: #374151; font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                        City/Municipality <span style="color: #dc2626;">*</span>
                    </label>
                    <select
                        id="implementing_unit"
                        name="implementing_unit"
                        required
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background-color: #f9fafb; transition: all 0.3s ease;"
                    >
                        <option value="">Select City/Municipality</option>
                        @if(old('province', $report->province))
                            @foreach($provinceMunicipalities[old('province', $report->province)] ?? [] as $municipality)
                                <option value="{{ $municipality }}" {{ old('implementing_unit', $report->implementing_unit) === $municipality ? 'selected' : '' }}>
                                    {{ $municipality }}
                                </option>
                            @endforeach
                        @else
                            @foreach($provinceMunicipalities[$report->province] ?? [] as $municipality)
                                <option value="{{ $municipality }}" {{ $report->implementing_unit === $municipality ? 'selected' : '' }}>
                                    {{ $municipality }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    @error('implementing_unit')
                        <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Barangay -->
                <div>
                    <label for="barangay" style="display: block; color: #374151; font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                        Barangay <span style="color: #dc2626;">*</span>
                    </label>
                    <input
                        type="text"
                        id="barangay"
                        name="barangay"
                        value="{{ old('barangay', $report->barangay) }}"
                        required
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background-color: #f9fafb; transition: all 0.3s ease;"
                        placeholder="Enter barangay"
                    >
                    @error('barangay')
                        <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Fund Source -->
                <div>
                    <label for="fund_source" style="display: block; color: #374151; font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                        Fund Source <span style="color: #dc2626;">*</span>
                    </label>
                    <select
                        id="fund_source"
                        name="fund_source"
                        required
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background-color: #f9fafb; transition: all 0.3s ease;"
                    >
                        <option value="">Select Fund Source</option>
                        @foreach($fundSources as $source)
                            <option value="{{ $source }}" {{ old('fund_source', $report->fund_source) === $source ? 'selected' : '' }}>
                                {{ $source }}
                            </option>
                        @endforeach
                    </select>
                    @error('fund_source')
                        <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Funding Year -->
                <div>
                    <label for="funding_year" style="display: block; color: #374151; font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                        Funding Year <span style="color: #dc2626;">*</span>
                    </label>
                    <select
                        id="funding_year"
                        name="funding_year"
                        required
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background-color: #f9fafb; transition: all 0.3s ease;"
                    >
                        <option value="">Select Funding Year</option>
                        @foreach($fundingYears as $year)
                            <option value="{{ $year }}" {{ old('funding_year', $report->funding_year) == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                    @error('funding_year')
                        <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Allocation -->
                <div>
                    <label for="allocation" style="display: block; color: #374151; font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                        Allocation (₱) <span style="color: #dc2626;">*</span>
                    </label>
                    <input
                        type="number"
                        id="allocation"
                        name="allocation"
                        value="{{ old('allocation', $report->allocation) }}"
                        required
                        min="0"
                        step="0.01"
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background-color: #f9fafb; transition: all 0.3s ease;"
                        placeholder="0.00"
                    >
                    @error('allocation')
                        <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Contract Amount -->
                <div>
                    <label for="contract_amount" style="display: block; color: #374151; font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                        Contract Amount (₱) <span style="color: #dc2626;">*</span>
                    </label>
                    <input
                        type="number"
                        id="contract_amount"
                        name="contract_amount"
                        value="{{ old('contract_amount', $report->contract_amount) }}"
                        required
                        min="0"
                        step="0.01"
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background-color: #f9fafb; transition: all 0.3s ease;"
                        placeholder="0.00"
                    >
                    @error('contract_amount')
                        <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Project Status -->
                <div>
                    <label for="project_status" style="display: block; color: #374151; font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                        Project Status <span style="color: #dc2626;">*</span>
                    </label>
                    <select
                        id="project_status"
                        name="project_status"
                        required
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background-color: #f9fafb; transition: all 0.3s ease;"
                    >
                        <option value="">Select Project Status</option>
                        @foreach($projectStatuses as $status)
                            <option value="{{ $status }}" {{ old('project_status', $report->project_status) === $status ? 'selected' : '' }}>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>
                    @error('project_status')
                        <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Project Title (Full Width) -->
                <div style="grid-column: 1 / -1;">
                    <label for="project_title" style="display: block; color: #374151; font-weight: 600; font-size: 14px; margin-bottom: 8px;">
                        Project Title <span style="color: #dc2626;">*</span>
                    </label>
                    <textarea
                        id="project_title"
                        name="project_title"
                        required
                        rows="3"
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background-color: #f9fafb; transition: all 0.3s ease; resize: vertical;"
                        placeholder="Enter project title"
                    >{{ old('project_title', $report->project_title) }}</textarea>
                    @error('project_title')
                        <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Submit Button -->
            <div style="margin-top: 40px; text-align: center;">
                <button
                    type="submit"
                    style="padding: 14px 32px; background-color: #f59e0b; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 16px; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2);"
                    onmouseover="this.style.backgroundColor='#d97706'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(245, 158, 11, 0.3)';"
                    onmouseout="this.style.backgroundColor='#f59e0b'; this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(245, 158, 11, 0.2)';"
                >
                    <i class="fas fa-save" style="margin-right: 8px;"></i> Update Project
                </button>
            </div>
        </form>
    </div>

    <style>
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
            background-color: white;
        }

        input:hover, select:hover, textarea:hover {
            border-color: #f59e0b;
        }

        @media (max-width: 768px) {
            .content-header h1 {
                font-size: 24px;
            }

            div[style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    <script>
        // Province to municipalities mapping
        const provinceMunicipalities = @json($provinceMunicipalities);

        function updateMunicipalities() {
            const provinceSelect = document.getElementById('province');
            const municipalitySelect = document.getElementById('implementing_unit');
            const selectedProvince = provinceSelect.value;

            // Clear current options
            municipalitySelect.innerHTML = '<option value="">Select City/Municipality</option>';

            // Add new options
            if (selectedProvince && provinceMunicipalities[selectedProvince]) {
                provinceMunicipalities[selectedProvince].forEach(municipality => {
                    const option = document.createElement('option');
                    option.value = municipality;
                    option.textContent = municipality;
                    municipalitySelect.appendChild(option);
                });
            }
        }

        // Initialize municipalities on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateMunicipalities();
        });
    </script>
@endsection
