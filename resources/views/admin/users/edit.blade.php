@extends('layouts.dashboard')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
    @php($roleOptions = \App\Models\User::roleOptions())
    <div class="content-header">
        <h1>Edit User</h1>
        <p>Update user information and settings</p>
    </div>

    <form action="{{ route('users.update', $user->idno) }}" method="POST" id="editUserForm">
        @csrf
        @method('PUT')

        <!-- User Form Card -->
        <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
            <!-- Header Section -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; gap: 16px;">
                <div style="display: flex; align-items: center;">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #002C76 0%, #003d99 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 32px; margin-right: 20px;">
                        <i data-feather="edit-3" style="width: 32px; height: 32px;"></i>
                    </div>
                    <div>
                        <h2 style="margin: 0 0 4px; color: #002C76; font-size: 20px;">{{ $user->fname }} {{ $user->lname }}</h2>
                        <p style="margin: 0; color: #6b7280; font-size: 14px;">{{ $user->username }}</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <a href="{{ route('users.index') }}" style="padding: 10px 20px; background-color: #e5e7eb; color: #374151; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease; white-space: nowrap;">
                        <i data-feather="arrow-left" style="width: 16px; height: 16px;"></i> Back
                    </a>
                    <button type="submit" style="padding: 10px 20px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s ease; white-space: nowrap;">
                        <i data-feather="save" style="margin-right: 8px; width: 16px; height: 16px; display: inline;"></i> Update User
                    </button>
                    <a href="{{ route('users.index') }}" style="padding: 10px 20px; background-color: #e5e7eb; color: #374151; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease; white-space: nowrap;">
                        <i data-feather="x" style="width: 16px; height: 16px;"></i> Cancel
                    </a>
                </div>
            </div>

            <!-- Personal Information Section -->
            <div style="border-top: 1px solid #e5e7eb; padding-top: 30px; margin-bottom: 30px;">
                <h3 style="color: #002C76; font-size: 16px; margin: 0 0 20px; font-weight: 600;">Personal Information</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- First Name -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">First Name <span style="color: #dc2626;">*</span></label>
                        <input type="text" name="fname" value="{{ $user->fname }}" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('fname') border-color: #dc2626; @enderror">
                        @error('fname')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Last Name <span style="color: #dc2626;">*</span></label>
                        <input type="text" name="lname" value="{{ $user->lname }}" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('lname') border-color: #dc2626; @enderror">
                        @error('lname')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Email Address <span style="color: #dc2626;">*</span></label>
                        <input type="email" name="emailaddress" value="{{ $user->emailaddress }}" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('emailaddress') border-color: #dc2626; @enderror">
                        @error('emailaddress')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Mobile Number -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Mobile Number <span style="color: #dc2626;">*</span></label>
                        <input type="text" name="mobileno" value="{{ $user->mobileno }}" required maxlength="11" pattern="[0-9]{11}" inputmode="numeric" style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('mobileno') border-color: #dc2626; @enderror">
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
                                <i data-feather="home" style="width: 16px; height: 16px;"></i>
                            </div>
                            <select id="agencySelect" name="agency" required style="width: 100%; padding: 10px 44px 10px 40px; border-radius:8px; border: 1px solid #e6eef8; background: white; font-size: 14px; color: #111827; @error('agency') border-color: #dc2626; @enderror">
                                <option value="">Select Agency/LGU</option>
                                <option value="DILG" @selected($user->agency === 'DILG')>DILG</option>
                                <option value="LGU" @selected($user->agency === 'LGU')>LGU</option>
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
                                <i data-feather="briefcase" style="width: 16px; height: 16px;"></i>
                            </div>
                            <select id="positionSelect" name="position" required style="width: 100%; padding: 10px 44px 10px 40px; border-radius:8px; border: 1px solid #e6eef8; background: white; font-size: 14px; color: #111827; @error('position') border-color: #dc2626; @enderror">
                                <option value="{{ $user->position }}" selected>{{ $user->position }}</option>
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
                                <i data-feather="map" style="width: 16px; height: 16px;"></i>
                            </div>
                            <input type="text" name="region" value="{{ $user->region }}" required style="width: 100%; padding: 10px 44px 10px 40px; border-radius:8px; border: 1px solid #e6eef8; background: white; font-size: 14px; color: #111827; @error('region') border-color: #dc2626; @enderror">
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
                                <i data-feather="map-pin" style="width: 16px; height: 16px;"></i>
                            </div>
                            <select id="provinceSelect" name="province" required style="width: 100%; padding: 10px 44px 10px 40px; border-radius:8px; border: 1px solid #e6eef8; background: white; font-size: 14px; color: #111827; @error('province') border-color: #dc2626; @enderror">
                                <option value="{{ $user->province }}" selected>{{ $user->province }}</option>
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
                                <i data-feather="building" style="width: 16px; height: 16px;"></i>
                            </div>
                            <select id="officeSelect" name="office" style="width: 100%; padding: 10px 44px 10px 40px; border-radius:8px; border: 1px solid #e6eef8; background: white; font-size: 14px; color: #111827; @error('office') border-color: #dc2626; @enderror">
                                <option value="{{ $user->office }}" selected>{{ $user->office }}</option>
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
                        <input type="text" name="username" value="{{ $user->username }}" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('username') border-color: #dc2626; @enderror">
                        @error('username')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Role -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Role <span style="color: #dc2626;">*</span></label>
                        <select id="roleSelect" name="role" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('role') border-color: #dc2626; @enderror">
                            @foreach($roleOptions as $roleValue => $roleLabel)
                                <option value="{{ $roleValue }}" @selected($user->role === $roleValue)>{{ $roleLabel }}</option>
                            @endforeach
                        </select>
                        <p style="color: #64748b; font-size: 12px; margin-top: 6px;">Changing the role updates access scope only. Agency, province, and office stay as currently saved unless you edit them directly.</p>
                        @error('role')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Password <span style="color: #999; font-size: 12px;">(Leave blank to keep current)</span></label>
                        <div style="position: relative;">
                            <input type="password" name="password" id="password" style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('password') border-color: #dc2626; @enderror">
                            <i data-feather="eye" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; cursor: pointer; width: 18px; height: 18px;" onclick="togglePasswordVisibility('password')"></i>
                        </div>
                        @error('password')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Confirm Password</label>
                        <div style="position: relative;">
                            <input type="password" name="password_confirmation" id="passwordConfirmation" style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                            <i data-feather="eye" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; cursor: pointer; width: 18px; height: 18px;" onclick="togglePasswordVisibility('passwordConfirmation')"></i>
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Status <span style="color: #dc2626;">*</span></label>
                        <select name="status" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('status') border-color: #dc2626; @enderror">
                            <option value="active" @selected($user->status === 'active')>Active</option>
                            <option value="inactive" @selected($user->status === 'inactive')>Inactive</option>
                        </select>
                        @error('status')
                            <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
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

    <script src="https://unpkg.com/feather-icons"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            if(window.feather) feather.replace();

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

        // Update position dropdown
        agencySelect.addEventListener('change', function() {
            const selectedValue = this.value;
            const currentPosition = '{{ $user->position }}';
            positionSelect.innerHTML = '<option value="" disabled>Select Position</option>';
            if(positions[selectedValue]) {
                positions[selectedValue].forEach(function(position) {
                    const option = document.createElement('option');
                    option.value = position;
                    option.textContent = position;
                    if (position === currentPosition) {
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
            const currentProvince = '{{ $user->province }}';
            provinceSelect.innerHTML = '<option value="" disabled>Select Province</option>';
            
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

        // Update office dropdown based on province (for LGU only)
        function updateOfficeDropdown() {
            const selectedAgency = agencySelect.value;
            const selectedProvince = provinceSelect.value;
            const currentOffice = '{{ $user->office }}';
            
            officeSelect.innerHTML = '<option value="">Select Office (Optional)</option>';
            
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
        }

        agencySelect.addEventListener('change', updateOfficeDropdown);
        provinceSelect.addEventListener('change', updateOfficeDropdown);

        // Trigger change events to populate if data is pre-selected
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
        });
    </script>
@endsection
