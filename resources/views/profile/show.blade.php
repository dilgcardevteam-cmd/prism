@extends('layouts.dashboard')

@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('content')
    <div class="content-header">
        <h1>My Profile</h1>
        <p>View and update your profile information</p>
    </div>

    @if (session('success'))
        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <form action="{{ route('profile.update') }}" method="POST" id="profileForm">
        @csrf
        @method('PUT')

        <!-- Profile Card -->
        <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
            <!-- Header Section -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;">
                <div style="display: flex; align-items: center;">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #002C76 0%, #003d99 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 32px; margin-right: 20px;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h2 style="margin: 0 0 4px; color: #002C76; font-size: 20px;">{{ Auth::user()->fname }} {{ Auth::user()->lname }}</h2>
                        <p style="margin: 0; color: #6b7280; font-size: 14px;">{{ Auth::user()->username }}</p>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button type="submit" style="padding: 10px 20px; background-color: #002C76; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s ease; white-space: nowrap;">
                        <i class="fas fa-save" style="margin-right: 8px;"></i> Save Changes
                    </button>
                    <a href="{{ route('dashboard') }}" style="padding: 10px 20px; background-color: #e5e7eb; color: #374151; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease; white-space: nowrap;">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>

            <!-- Editable Fields -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; border-top: 1px solid #e5e7eb; padding-top: 30px;">
                <!-- First Name -->
                <div>
                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">First Name</label>
                    <input type="text" name="fname" value="{{ Auth::user()->fname }}" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('fname') border-color: #dc2626; @enderror">
                    @error('fname')
                        <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Last Name -->
                <div>
                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Last Name</label>
                    <input type="text" name="lname" value="{{ Auth::user()->lname }}" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('lname') border-color: #dc2626; @enderror">
                    @error('lname')
                        <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Position -->
                <div>
                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Position</label>
                    <select id="positionSelect" name="position" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('position') border-color: #dc2626; @enderror">
                        <option value="{{ Auth::user()->position }}" selected>{{ Auth::user()->position }}</option>
                    </select>
                    @error('position')
                        <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Mobile Number -->
                <div>
                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Mobile Number</label>
                    <input type="text" name="mobileno" value="{{ Auth::user()->mobileno }}" required maxlength="11" pattern="[0-9]{11}" inputmode="numeric" style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('mobileno') border-color: #dc2626; @enderror">
                    @error('mobileno')
                        <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Read-Only Fields Section -->
            <div style="border-top: 1px solid #e5e7eb; padding-top: 30px; margin-bottom: 30px;">
                <h3 style="color: #002C76; font-size: 16px; margin: 0 0 20px; font-weight: 600;">Organization Information (Cannot be changed)</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- Agency/LGU -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #6b7280; font-weight: 500; font-size: 14px;">Agency/LGU</label>
                        <input type="text" value="{{ Auth::user()->agency }}" disabled style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; background-color: #f9fafb; color: #6b7280; cursor: not-allowed;">
                    </div>

                    <!-- Region -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #6b7280; font-weight: 500; font-size: 14px;">Region</label>
                        <input type="text" value="{{ Auth::user()->region }}" disabled style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; background-color: #f9fafb; color: #6b7280; cursor: not-allowed;">
                    </div>

                    <!-- Province -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #6b7280; font-weight: 500; font-size: 14px;">Province</label>
                        <input type="text" value="{{ Auth::user()->province }}" disabled style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; background-color: #f9fafb; color: #6b7280; cursor: not-allowed;">
                    </div>

                    <!-- Office -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #6b7280; font-weight: 500; font-size: 14px;">Office</label>
                        <input type="text" value="{{ Auth::user()->office ?? 'N/A' }}" disabled style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; background-color: #f9fafb; color: #6b7280; cursor: not-allowed;">
                    </div>
                </div>
            </div>

            <!-- Account Information Section -->
            <div style="border-top: 1px solid #e5e7eb; padding-top: 30px; margin-bottom: 30px;">
                <h3 style="color: #002C76; font-size: 16px; margin: 0 0 20px; font-weight: 600;">Account Information (Cannot be changed)</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- Email Address -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #6b7280; font-weight: 500; font-size: 14px;">Email Address</label>
                        <input type="email" value="{{ Auth::user()->emailaddress }}" disabled style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; background-color: #f9fafb; color: #6b7280; cursor: not-allowed;">
                    </div>

                    <!-- Username -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; color: #6b7280; font-weight: 500; font-size: 14px;">Username</label>
                        <input type="text" value="{{ Auth::user()->username }}" disabled style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; background-color: #f9fafb; color: #6b7280; cursor: not-allowed;">
                    </div>
                </div>
            </div>
        </div>
    </form>

    <style>
        input[disabled] {
            opacity: 0.7;
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

        input[type="text"], input[type="email"] {
            transition: all 0.3s ease;
        }

        input[type="text"]:focus, input[type="email"]:focus {
            outline: none;
            border-color: #002C76;
            box-shadow: 0 0 0 3px rgba(0, 44, 118, 0.1);
        }

        @media (max-width: 768px) {
            .content-header h1 {
                font-size: 24px;
            }

            .content-header p {
                font-size: 13px;
            }

            div[style*="grid-template-columns: 1fr 1fr"] {
                grid-template-columns: 1fr !important;
            }

            div[style*="display: flex; align-items: center"] {
                flex-direction: column;
                text-align: center;
            }

            button, a[style*="flex"] {
                font-size: 13px !important;
            }
        }

        @media (max-width: 480px) {
            .content-header h1 {
                font-size: 20px;
                margin-bottom: 6px;
            }

            .content-header p {
                font-size: 12px;
            }

            div[style*="padding: 30px"] {
                padding: 20px !important;
            }

            div[style*="gap: 20px"] {
                gap: 15px !important;
            }

            div[style*="display: flex; align-items: center; margin-bottom: 30px"] {
                margin-bottom: 20px !important;
            }

            div[style*="width: 80px; height: 80px"] {
                width: 60px !important;
                height: 60px !important;
                font-size: 24px !important;
                margin-right: 12px !important;
            }

            h2[style*="color: #002C76"] {
                font-size: 16px !important;
            }

            p[style*="color: #6b7280"] {
                font-size: 12px !important;
            }

            h3[style*="color: #002C76"] {
                font-size: 14px !important;
                margin-bottom: 15px !important;
            }

            label {
                font-size: 12px !important;
            }

            input[type="text"], input[type="email"] {
                padding: 10px !important;
                font-size: 13px !important;
            }

            button, a[style*="flex"] {
                padding: 10px !important;
                font-size: 12px !important;
            }

            div[style*="display: flex; gap: 15px"] {
                flex-direction: column;
                gap: 10px !important;
            }

            a[style*="flex: 1"] {
                flex: 1 !important;
            }
        }
    </style>

    <script>
        // Position dropdown based on agency
        const positions = @json($positions);
        const userAgency = "{{ Auth::user()->agency }}";
        const userPosition = "{{ Auth::user()->position }}";
        const positionSelect = document.getElementById('positionSelect');

        // Function to populate position dropdown
        function populatePositions() {
            positionSelect.innerHTML = '<option value="" disabled>Select Position</option>';
            
            if (positions[userAgency]) {
                positions[userAgency].forEach(function(position) {
                    const option = document.createElement('option');
                    option.value = position;
                    option.textContent = position;
                    if (position === userPosition) {
                        option.selected = true;
                    }
                    positionSelect.appendChild(option);
                });
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            populatePositions();
        });
    </script>
@endsection
