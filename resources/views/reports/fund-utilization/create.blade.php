@extends('layouts.dashboard')

@section('title', 'Create Fund Utilization Report')
@section('page-title', 'Create Fund Utilization Report')

@section('content')
    <div class="content-header">
        <h1><i class="fas fa-plus" style="margin-right: 8px;"></i>Create Fund Utilization Report</h1>
        <p>Fill in the details to create a new fund utilization report</p>
    </div>

    @if ($errors->any())
        <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
        <form action="{{ route('fund-utilization.store') }}" method="POST">
            @csrf

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <!-- Project Code -->
                <div>
                    <label for="project_code" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Project Code *</label>
                    <input type="text" id="project_code" name="project_code" value="{{ old('project_code') }}" required
                           style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                </div>

                <!-- Province (Dropdown from registration) -->
                <div>
                    <label for="province" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Province *</label>
                    <select id="province" name="province" required
                            style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                        <option value="">-- Select Province --</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province }}" {{ old('province') === $province ? 'selected' : '' }}>{{ $province }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Implementing Unit (Dropdown - populated from selected province) -->
                <div>
                    <label for="implementing_unit" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Implementing Unit (City/Municipality) *</label>
                    <select id="implementing_unit" name="implementing_unit" required
                            style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                        <option value="">-- Select Province First --</option>
                    </select>
                    <small style="color: #9ca3af; font-size: 12px; margin-top: 4px; display: block;">Select a province above to see available municipalities/cities</small>
                </div>

                <!-- Fund Source (Dropdown) -->
                <div>
                    <label for="fund_source" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Fund Source *</label>
                    <select id="fund_source" name="fund_source" required
                            style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                        <option value="">-- Select Fund Source --</option>
                        @foreach($fundSources as $source)
                            <option value="{{ $source }}" {{ old('fund_source') === $source ? 'selected' : '' }}>{{ $source }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Funding Year (Dropdown) -->
                <div>
                    <label for="funding_year" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Funding Year *</label>
                    <select id="funding_year" name="funding_year" required
                            style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                        <option value="">-- Select Funding Year --</option>
                        @foreach($fundingYears as $year)
                            <option value="{{ $year }}" {{ old('funding_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Barangay -->
                <div>
                    <label for="barangay" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Barangay *</label>
                    <input type="text" id="barangay" name="barangay" value="{{ old('barangay') }}" required
                           style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                </div>

                <!-- Allocation -->
                <div>
                    <label for="allocation" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Allocation *</label>
                    <input type="number" id="allocation" name="allocation" value="{{ old('allocation') }}" step="0.01" min="0" required
                           style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                </div>

                <!-- Contract Amount -->
                <div>
                    <label for="contract_amount" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Contract Amount *</label>
                    <input type="number" id="contract_amount" name="contract_amount" value="{{ old('contract_amount') }}" step="0.01" min="0" required
                           style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                </div>

                <!-- Project Status -->
                <div>
                    <label for="project_status" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Project Status *</label>
                    <select id="project_status" name="project_status" required
                            style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                        <option value="">-- Select Project Status --</option>
                        <option value="Ongoing" {{ old('project_status') === 'Ongoing' ? 'selected' : '' }}>Ongoing</option>
                        <option value="Completed" {{ old('project_status') === 'Completed' ? 'selected' : '' }}>Completed</option>
                        <option value="Cancelled" {{ old('project_status') === 'Cancelled' ? 'selected' : '' }}><i class="fas fa-times" style="margin-right: 8px;"></i>Cancelled</option>
                        <option value="On Hold" {{ old('project_status') === 'On Hold' ? 'selected' : '' }}>On Hold</option>
                    </select>
                </div>

                <!-- Project Title (Full Width) -->
                <div style="grid-column: 1 / -1;">
                    <label for="project_title" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Project Title *</label>
                    <textarea id="project_title" name="project_title" required rows="3"
                              style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; resize: vertical;">{{ old('project_title') }}</textarea>
                </div>
            </div>

            <!-- Form Actions -->
            <div style="display: flex; gap: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <button type="submit" style="display: inline-flex; align-items: center; padding: 12px 24px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                    <i class="fas fa-save" style="margin-right: 8px;"></i> Create Report
                </button>
                <a href="{{ route('fund-utilization.index') }}" style="display: inline-flex; align-items: center; padding: 12px 24px; background-color: #6b7280; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s ease;">
                    <i class="fas fa-times" style="margin-right: 8px;"></i> Cancel
                </a>
            </div>
        </form>
    </div>

    <style>
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #002C76;
            box-shadow: 0 0 0 3px rgba(0, 44, 118, 0.1);
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23374151' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }

        button:hover, a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 44, 118, 0.2);
        }

        button[type="submit"]:hover {
            background-color: #001f59;
        }

        a[href*="fund-utilization.index"]:hover {
            background-color: #4b5563;
        }
    </style>

    <script>
        // Province to municipalities data
        const provinceMunicipalities = {!! json_encode($provinceMunicipalities) !!};

        // Handle province selection
        document.getElementById('province').addEventListener('change', function() {
            const selectedProvince = this.value;
            const implementingUnitSelect = document.getElementById('implementing_unit');
            
            // Clear previous options
            implementingUnitSelect.innerHTML = '';
            
            if (selectedProvince && provinceMunicipalities[selectedProvince]) {
                // Special case: City of Baguio
                if (selectedProvince === 'City of Baguio') {
                    const option = document.createElement('option');
                    option.value = 'City of Baguio';
                    option.textContent = 'City of Baguio';
                    option.selected = true;
                    implementingUnitSelect.appendChild(option);
                } else {
                    // Add default option
                    const defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = '-- Select City/Municipality --';
                    implementingUnitSelect.appendChild(defaultOption);
                    
                    // Add PLGU option (Provincial/Local Government Unit)
                    const plguOption = document.createElement('option');
                    plguOption.value = 'PLGU ' + selectedProvince;
                    plguOption.textContent = 'PLGU ' + selectedProvince;
                    implementingUnitSelect.appendChild(plguOption);
                    
                    // Add municipalities for selected province
                    provinceMunicipalities[selectedProvince].forEach(municipality => {
                        const option = document.createElement('option');
                        option.value = municipality;
                        option.textContent = municipality;
                        implementingUnitSelect.appendChild(option);
                    });
                }
            } else {
                // No province selected
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = '-- Select Province First --';
                implementingUnitSelect.appendChild(defaultOption);
            }
        });

        // Populate implementing_unit on page load if province is already selected (for form resubmission)
        document.addEventListener('DOMContentLoaded', function() {
            const provinceSelect = document.getElementById('province');
            if (provinceSelect.value) {
                provinceSelect.dispatchEvent(new Event('change'));
                // Restore selected municipality if it exists
                const oldImplementingUnit = "{{ old('implementing_unit') }}";
                if (oldImplementingUnit) {
                    document.getElementById('implementing_unit').value = oldImplementingUnit;
                }
            }
        });
    </script>
    <div class="content-header">
        <h1><i class="fas fa-plus" style="margin-right: 8px;"></i>Create Fund Utilization Report</h1>
        <p>Fill in the details to create a new fund utilization report</p>
    </div>

    @if ($errors->any())
        <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
        <form action="{{ route('fund-utilization.store') }}" method="POST">
            @csrf

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <!-- Project Code -->
                <div>
                    <label for="project_code" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Project Code *</label>
                    <input type="text" id="project_code" name="project_code" value="{{ old('project_code') }}" required
                           style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                </div>

                <!-- Province (Dropdown from registration) -->
                <div>
                    <label for="province" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Province *</label>
                    <select id="province" name="province" required
                            style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                        <option value="">-- Select Province --</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province }}" {{ old('province') === $province ? 'selected' : '' }}>{{ $province }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Implementing Unit (Dropdown - populated from selected province) -->
                <div>
                    <label for="implementing_unit" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Implementing Unit (City/Municipality) *</label>
                    <select id="implementing_unit" name="implementing_unit" required
                            style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                        <option value="">-- Select Province First --</option>
                    </select>
                    <small style="color: #9ca3af; font-size: 12px; margin-top: 4px; display: block;">Select a province above to see available municipalities/cities</small>
                </div>

                <!-- Fund Source (Dropdown) -->
                <div>
                    <label for="fund_source" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Fund Source *</label>
                    <select id="fund_source" name="fund_source" required
                            style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                        <option value="">-- Select Fund Source --</option>
                        @foreach($fundSources as $source)
                            <option value="{{ $source }}" {{ old('fund_source') === $source ? 'selected' : '' }}>{{ $source }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Funding Year (Dropdown) -->
                <div>
                    <label for="funding_year" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Funding Year *</label>
                    <select id="funding_year" name="funding_year" required
                            style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                        <option value="">-- Select Funding Year --</option>
                        @foreach($fundingYears as $year)
                            <option value="{{ $year }}" {{ old('funding_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Barangay -->
                <div>
                    <label for="barangay" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Barangay *</label>
                    <input type="text" id="barangay" name="barangay" value="{{ old('barangay') }}" required
                           style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                </div>

                <!-- Allocation -->
                <div>
                    <label for="allocation" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Allocation *</label>
                    <input type="number" id="allocation" name="allocation" value="{{ old('allocation') }}" step="0.01" min="0" required
                           style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                </div>

                <!-- Contract Amount -->
                <div>
                    <label for="contract_amount" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Contract Amount *</label>
                    <input type="number" id="contract_amount" name="contract_amount" value="{{ old('contract_amount') }}" step="0.01" min="0" required
                           style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                </div>

                <!-- Project Status -->
                <div>
                    <label for="project_status" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Project Status *</label>
                    <select id="project_status" name="project_status" required
                            style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                        <option value="">-- Select Project Status --</option>
                        <option value="Ongoing" {{ old('project_status') === 'Ongoing' ? 'selected' : '' }}>Ongoing</option>
                        <option value="Completed" {{ old('project_status') === 'Completed' ? 'selected' : '' }}>Completed</option>
                        <option value="Cancelled" {{ old('project_status') === 'Cancelled' ? 'selected' : '' }}><i class="fas fa-times" style="margin-right: 8px;"></i>Cancelled</option>
                        <option value="On Hold" {{ old('project_status') === 'On Hold' ? 'selected' : '' }}>On Hold</option>
                    </select>
                </div>

                <!-- Project Title (Full Width) -->
                <div style="grid-column: 1 / -1;">
                    <label for="project_title" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Project Title *</label>
                    <textarea id="project_title" name="project_title" required rows="3"
                              style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; resize: vertical;">{{ old('project_title') }}</textarea>
                </div>
            </div>

            <!-- Form Actions -->
            <div style="display: flex; gap: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <button type="submit" style="display: inline-flex; align-items: center; padding: 12px 24px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0, 44, 118, 0.2);">
                    <i class="fas fa-save" style="margin-right: 8px;"></i> Create Report
                </button>
                <a href="{{ route('fund-utilization.index') }}" style="display: inline-flex; align-items: center; padding: 12px 24px; background-color: #6b7280; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s ease;">
                    <i class="fas fa-times" style="margin-right: 8px;"></i> Cancel
                </a>
            </div>
        </form>
    </div>

    <style>
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #002C76;
            box-shadow: 0 0 0 3px rgba(0, 44, 118, 0.1);
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23374151' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }

        button:hover, a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 44, 118, 0.2);
        }

        button[type="submit"]:hover {
            background-color: #001f59;
        }

        a[href*="fund-utilization.index"]:hover {
            background-color: #4b5563;
        }
    </style>

    <script>
        // Province to municipalities data
        const provinceMunicipalities = {!! json_encode($provinceMunicipalities) !!};

        // Handle province selection
        document.getElementById('province').addEventListener('change', function() {
            const selectedProvince = this.value;
            const implementingUnitSelect = document.getElementById('implementing_unit');
            
            // Clear previous options
            implementingUnitSelect.innerHTML = '';
            
            if (selectedProvince && provinceMunicipalities[selectedProvince]) {
                // Special case: City of Baguio
                if (selectedProvince === 'City of Baguio') {
                    const option = document.createElement('option');
                    option.value = 'City of Baguio';
                    option.textContent = 'City of Baguio';
                    option.selected = true;
                    implementingUnitSelect.appendChild(option);
                } else {
                    // Add default option
                    const defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = '-- Select City/Municipality --';
                    implementingUnitSelect.appendChild(defaultOption);
                    
                    // Add PLGU option (Provincial/Local Government Unit)
                    const plguOption = document.createElement('option');
                    plguOption.value = 'PLGU ' + selectedProvince;
                    plguOption.textContent = 'PLGU ' + selectedProvince;
                    implementingUnitSelect.appendChild(plguOption);
                    
                    // Add municipalities for selected province
                    provinceMunicipalities[selectedProvince].forEach(municipality => {
                        const option = document.createElement('option');
                        option.value = municipality;
                        option.textContent = municipality;
                        implementingUnitSelect.appendChild(option);
                    });
                }
            } else {
                // No province selected
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = '-- Select Province First --';
                implementingUnitSelect.appendChild(defaultOption);
            }
        });

        // Populate implementing_unit on page load if province is already selected (for form resubmission)
        document.addEventListener('DOMContentLoaded', function() {
            const provinceSelect = document.getElementById('province');
            if (provinceSelect.value) {
                provinceSelect.dispatchEvent(new Event('change'));
                // Restore selected municipality if it exists
                const oldImplementingUnit = "{{ old('implementing_unit') }}";
                if (oldImplementingUnit) {
                    document.getElementById('implementing_unit').value = oldImplementingUnit;
                }
            }
        });
    </script>
@endsection
