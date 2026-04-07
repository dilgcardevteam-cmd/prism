@extends('layouts.dashboard')

@section('title', 'Create User')
@section('page-title', 'Create User')

@section('content')
    @php
        $roleOptions = \App\Models\User::roleOptions();
        $requestedRole = strtolower(trim((string) request()->query('role', '')));
        $defaultRole = array_key_exists($requestedRole, $roleOptions) ? $requestedRole : '';
        $selectedRoleValue = old('role', $defaultRole);
    @endphp
    <div class="content-header">
        <h1>Create New User</h1>
        <p>Add a new user to the system</p>
    </div>

    <form action="{{ route('users.store') }}" method="POST" id="createUserForm">
        @csrf

        <!-- User Form Card -->
        <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
            <!-- Header Section -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;">
                <div style="display: flex; align-items: center;">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #002C76 0%, #003d99 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 32px; margin-right: 20px;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <h2 style="margin: 0 0 4px; color: #002C76; font-size: 20px;">New User Information</h2>
                        <p style="margin: 0; color: #6b7280; font-size: 14px;">Enter the user's details below</p>
                    </div>
                </div>
                <a href="{{ route('users.index') }}" style="padding: 10px 20px; background-color: #e5e7eb; color: #374151; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease; white-space: nowrap;">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <!-- Personal Information Section -->
            <div style="border-top: 1px solid #e5e7eb; padding-top: 30px; margin-bottom: 30px;">
                <h3 style="color: #002C76; font-size: 16px; margin: 0 0 20px; font-weight: 600;">Personal Information</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- First Name -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">First Name <span style="color: #dc2626;">*</span></label>
                        <input type="text" name="fname" value="{{ old('fname') }}" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('fname') border-color: #dc2626; @enderror">
                        @error('fname')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Last Name <span style="color: #dc2626;">*</span></label>
                        <input type="text" name="lname" value="{{ old('lname') }}" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('lname') border-color: #dc2626; @enderror">
                        @error('lname')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Email Address <span style="color: #dc2626;">*</span></label>
                        <input type="email" name="emailaddress" value="{{ old('emailaddress') }}" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('emailaddress') border-color: #dc2626; @enderror">
                        @error('emailaddress')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Mobile Number -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Mobile Number <span style="color: #dc2626;">*</span></label>
                        <input type="text" name="mobileno" value="{{ old('mobileno') }}" required maxlength="11" pattern="[0-9]{11}" inputmode="numeric" style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('mobileno') border-color: #dc2626; @enderror">
                        @error('mobileno')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Organization Information Section -->
            <div style="border-top: 1px solid #e5e7eb; padding-top: 30px; margin-bottom: 30px;">
                <h3 style="color: #002C76; font-size: 16px; margin: 0 0 20px; font-weight: 600;">Organization Information</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- Agency -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Agency/LGU <span style="color: #dc2626;">*</span></label>
                        <div style="position: relative;">
                            <div style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#9ca3af; display:flex; align-items:center; pointer-events:none">
                                <i class="fas fa-building" style="width: 16px; height: 16px;"></i>
                            </div>
                            <select id="agencySelect" name="agency" required style="width: 100%; padding: 10px 44px 10px 40px; border-radius:8px; border: 1px solid #e6eef8; background: white; font-size: 14px; color: #111827; @error('agency') border-color: #dc2626; @enderror">
                                <option value="" disabled selected>Select Agency/LGU</option>
                                <option value="DILG" @selected(old('agency') === 'DILG')>DILG</option>
                                <option value="LGU" @selected(old('agency') === 'LGU')>LGU</option>
                            </select>
                        </div>
                        @error('agency')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Position -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Position <span style="color: #dc2626;">*</span></label>
                        <div style="position: relative;">
                            <div style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#9ca3af; display:flex; align-items:center; pointer-events:none">
                                <i class="fas fa-briefcase" style="width: 16px; height: 16px;"></i>
                            </div>
                            <select id="positionSelect" name="position" required style="width: 100%; padding: 10px 44px 10px 40px; border-radius:8px; border: 1px solid #e6eef8; background: white; font-size: 14px; color: #111827; @error('position') border-color: #dc2626; @enderror">
                                <option value="" disabled selected>Select Position</option>
                            </select>
                        </div>
                        @error('position')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Region -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Region <span style="color: #dc2626;">*</span></label>
                        <div style="position: relative;">
                            <div style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#9ca3af; display:flex; align-items:center; pointer-events:none">
                                <i class="fas fa-map" style="width: 16px; height: 16px;"></i>
                            </div>
                            <input type="text" name="region" value="{{ old('region') }}" required style="width: 100%; padding: 10px 44px 10px 40px; border-radius:8px; border: 1px solid #e6eef8; background: white; font-size: 14px; color: #111827; @error('region') border-color: #dc2626; @enderror">
                        </div>
                        @error('region')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Province -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Province <span style="color: #dc2626;">*</span></label>
                        <div style="position: relative;">
                            <div style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#9ca3af; display:flex; align-items:center; pointer-events:none">
                                <i class="fas fa-map-pin" style="width: 16px; height: 16px;"></i>
                            </div>
                            <select id="provinceSelect" name="province" required style="width: 100%; padding: 10px 44px 10px 40px; border-radius:8px; border: 1px solid #e6eef8; background: white; font-size: 14px; color: #111827; @error('province') border-color: #dc2626; @enderror">
                                <option value="" disabled selected>Select Province</option>
                            </select>
                        </div>
                        @error('province')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Office -->
                    <div style="grid-column: 1 / -1;">
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Office</label>
                        <div style="position: relative;">
                            <div style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#9ca3af; display:flex; align-items:center; pointer-events:none">
                                <i class="fas fa-home" style="width: 16px; height: 16px;"></i>
                            </div>
                            <select id="officeSelect" name="office" style="width: 100%; padding: 10px 44px 10px 40px; border-radius:8px; border: 1px solid #e6eef8; background: white; font-size: 14px; color: #111827; @error('office') border-color: #dc2626; @enderror">
                                <option value="" selected>Select Office (Optional)</option>
                            </select>
                        </div>
                        @error('office')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Account Information Section -->
            <div style="border-top: 1px solid #e5e7eb; padding-top: 30px; margin-bottom: 30px;">
                <h3 style="color: #002C76; font-size: 16px; margin: 0 0 20px; font-weight: 600;">Account Information</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- Username -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Username <span style="color: #dc2626;">*</span></label>
                        <input type="text" name="username" value="{{ old('username') }}" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('username') border-color: #dc2626; @enderror">
                        @error('username')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Role -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Role <span style="color: #dc2626;">*</span></label>
                        <select id="roleSelect" name="role" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('role') border-color: #dc2626; @enderror">
                            <option value="" disabled @selected($selectedRoleValue === '')>Select Role</option>
                            @foreach($roleOptions as $roleValue => $roleLabel)
                                <option value="{{ $roleValue }}" @selected($selectedRoleValue === $roleValue)>{{ $roleLabel }}</option>
                            @endforeach
                        </select>
                        <p style="color: #64748b; font-size: 12px; margin-top: 6px;">Role selection controls access scope only. Agency, province, and office stay based on the values you choose in the profile fields.</p>
                        @error('role')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Password <span style="color: #dc2626;">*</span></label>
                        <div style="position: relative;">
                            <input type="password" name="password" id="password" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('password') border-color: #dc2626; @enderror">
                            <i class="fas fa-eye" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; cursor: pointer;" onclick="togglePasswordVisibility('password')"></i>
                        </div>
                        @error('password')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Confirm Password <span style="color: #dc2626;">*</span></label>
                        <div style="position: relative;">
                            <input type="password" name="password_confirmation" id="passwordConfirmation" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                            <i class="fas fa-eye" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; cursor: pointer;" onclick="togglePasswordVisibility('passwordConfirmation')"></i>
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Status <span style="color: #dc2626;">*</span></label>
                        <select name="status" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('status') border-color: #dc2626; @enderror">
                            <option value="" disabled selected>Select Status</option>
                            <option value="active" @selected(old('status') === 'active' || !old('status'))>Active</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                        </select>
                        @error('status')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; display: flex; gap: 15px;">
                <button type="submit" style="flex: 1; padding: 12px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s ease;">
                    <i class="fas fa-save" style="margin-right: 8px;"></i> Create User
                </button>
                <a href="{{ route('users.index') }}" style="flex: 1; padding: 12px; background-color: #e5e7eb; color: #374151; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease;">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </div>
    </form>

    <style>
        input[type="password"], input[type="text"], input[type="email"], select {
            transition: all 0.3s ease;
        }

        input:focus, select:focus {
            border-color: #002C76;
            box-shadow: 0 0 0 3px rgba(0, 44, 118, 0.1);
            outline: none;
        }

        button:hover {
            background-color: #001f59 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 44, 118, 0.2);
        }

        a:hover {
            background-color: #d1d5db !important;
            transform: translateY(-2px);
        }

        i[style*="cursor: pointer"] {
            user-select: none;
        }

        i[style*="cursor: pointer"]:hover {
            color: #374151 !important;
        }
    </style>

    <script>
        const positions = {
            'DILG': [
                'Engineer II',
                'Engineer III',
                'Unit Chief',
                'Assistant Unit Chief',
                'Financial Analyst II',
                'Financial Analyst III',
                'Project Evaluation Officer II',
                'Project Evaluation Officer III',
                'Information Systems Analyst III'
            ],
            'LGU': [
                'Municipal Engineer I',
                'Municipal Engineer II',
                'Municipal Engineer III',
                'Planning Officer II',
                'Planning Officer III'
            ]
        };

        const provinces = [
            'Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'
        ];

        const offices = {
            'Abra': [
                'PLGU Abra', 'Bangued', 'Boliney', 'Bucay', 'Bucloc', 'Daguioman', 'Danglas', 'Dolores', 'La Paz', 'Lacub', 'Lagangilang', 'Lagayan', 'Langiden', 'Licuan-Baay', 'Luba', 'Malibcong', 'Manabo', 'Peñarrubia', 'Pidigan', 'Pilar', 'Sallapadan', 'San Isidro', 'San Juan', 'San Quintin', 'Tayum', 'Tineg', 'Tubo', 'Villaviciosa'
            ],
            'Apayao': [
                'PLGU Apayao', 'Calanasan', 'Conner', 'Flora', 'Kabugao', 'Luna', 'Pudtol', 'Santa Marcela'
            ],
            'Benguet': [
                'PLGU Benguet', 'Atok', 'Bakun', 'Bokod', 'Buguias', 'Itogon', 'Kabayan', 'Kapangan', 'Kibungan', 'La Trinidad', 'Mankayan', 'Sablan', 'Tuba', 'Tublay'
            ],
            'City of Baguio': [
                'PLGU City of Baguio', 'City of Baguio'
            ],
            'Ifugao': [
                'PLGU Ifugao', 'Aguinaldo', 'Alfonso Lista', 'Asipulo', 'Banaue', 'Hingyon', 'Hungduan', 'Kiangan', 'Lagawe', 'Lamut', 'Mayoyao', 'Tinoc'
            ],
            'Kalinga': [
                'PLGU Kalinga', 'Balbalan', 'Lubuagan', 'Pasil', 'Pinukpuk', 'Rizal', 'Tabuk', 'Tanudan'
            ],
            'Mountain Province': [
                'PLGU Mountain Province', 'Barlig', 'Bauko', 'Besao', 'Bontoc', 'Natonin', 'Paracelis', 'Sabangan', 'Sadanga', 'Sagada', 'Tadian'
            ]
        };

        const agencySelect = document.getElementById('agencySelect');
        const positionSelect = document.getElementById('positionSelect');
        const provinceSelect = document.getElementById('provinceSelect');
        const officeSelect = document.getElementById('officeSelect');

        // Update position dropdown based on agency
        agencySelect.addEventListener('change', function() {
            const selectedValue = this.value;
            positionSelect.innerHTML = '<option value="" disabled selected>Select Position</option>';
            if(positions[selectedValue]) {
                positions[selectedValue].forEach(function(position) {
                    const option = document.createElement('option');
                    option.value = position;
                    option.textContent = position;
                    if (position === '{{ old("position") }}') {
                        option.selected = true;
                    }
                    positionSelect.appendChild(option);
                });
            }
            // Also update province when agency changes
            updateProvinceDropdown();
        });

        // Update province dropdown based on agency
        function updateProvinceDropdown() {
            const selectedAgency = agencySelect.value;
            const currentProvince = '{{ old("province") }}';
            provinceSelect.innerHTML = '<option value="" disabled selected>Select Province</option>';
            
            if(selectedAgency === 'DILG'){
                const regionalOption = document.createElement('option');
                regionalOption.value = 'Regional Office';
                regionalOption.textContent = 'Regional Office';
                if (currentProvince === 'Regional Office') {
                    regionalOption.selected = true;
                }
                provinceSelect.appendChild(regionalOption);
            }
            
            provinces.forEach(function(province) {
                const option = document.createElement('option');
                option.value = province;
                option.textContent = province;
                if (province === currentProvince) {
                    option.selected = true;
                }
                provinceSelect.appendChild(option);
            });
        }

        // Populate province dropdown initially
        updateProvinceDropdown();

        // Update office dropdown based on province (for LGU only)
        provinceSelect.addEventListener('change', function() {
            const selectedAgency = agencySelect.value;
            const selectedProvince = this.value;
            const currentOffice = '{{ old("office") }}';
            
            // Clear office dropdown and reset to default
            officeSelect.innerHTML = '<option value="" selected>Select Office (Optional)</option>';
            
            // If LGU agency, populate office options
            if(selectedAgency === 'LGU' && offices[selectedProvince]) {
                offices[selectedProvince].forEach(function(office) {
                    const option = document.createElement('option');
                    option.value = office;
                    option.textContent = office;
                    if (office === currentOffice) {
                        option.selected = true;
                    }
                    officeSelect.appendChild(option);
                });
            }
        });

        // Trigger change event to populate position if agency is pre-selected
        if (agencySelect.value) {
            agencySelect.dispatchEvent(new Event('change'));
        }
        if (provinceSelect.value) {
            provinceSelect.dispatchEvent(new Event('change'));
        }

        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const icon = event.target;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
@endsection
