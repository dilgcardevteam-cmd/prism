@extends('layouts.dashboard')

@section('title', 'Change Password')
@section('page-title', 'Change Password')

@section('content')
    <div class="content-header">
        <h1>Change Password</h1>
        <p>Update your password to keep your account secure</p>
    </div>

    @if (session('success'))
        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <form action="{{ route('password.update') }}" method="POST" id="changePasswordForm">
        @csrf
        @method('PUT')

        <!-- Password Card -->
        <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
            <!-- Header Section -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;">
                <div style="display: flex; align-items: center;">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 32px; margin-right: 20px;">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div>
                        <h2 style="margin: 0 0 4px; color: #002C76; font-size: 20px;">Secure Your Account</h2>
                        <p style="margin: 0; color: #6b7280; font-size: 14px;">Update your password regularly for better security</p>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button type="submit" form="changePasswordForm" style="padding: 10px 20px; background-color: #dc2626; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease; white-space: nowrap;">
                        <i class="fas fa-lock" style="margin-right: 0;"></i> Update
                    </button>
                    <a href="{{ route('profile.show') }}" style="padding: 10px 20px; background-color: #e5e7eb; color: #374151; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease; white-space: nowrap;">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <!-- Password Fields -->
            <div style="border-top: 1px solid #e5e7eb; padding-top: 30px;">
                <!-- Current Password -->
                <div style="margin-bottom: 25px;">
                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Current Password</label>
                    <div style="position: relative;">
                        <input type="password" name="current_password" id="currentPassword" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('current_password') border-color: #dc2626; @enderror">
                        <i class="fas fa-eye" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; cursor: pointer;" onclick="togglePasswordVisibility('currentPassword')"></i>
                    </div>
                    @error('current_password')
                        <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- New Password -->
                <div style="margin-bottom: 25px;">
                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">New Password</label>
                    <div style="position: relative;">
                        <input type="password" name="new_password" id="newPassword" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; @error('new_password') border-color: #dc2626; @enderror">
                        <i class="fas fa-eye" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; cursor: pointer;" onclick="togglePasswordVisibility('newPassword')"></i>
                    </div>
                    <p style="color: #6b7280; font-size: 12px; margin-top: 4px;">Password must be at least 8 characters long</p>
                    @error('new_password')
                        <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div style="margin-bottom: 25px;">
                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500; font-size: 14px;">Confirm New Password</label>
                    <div style="position: relative;">
                        <input type="password" name="new_password_confirmation" id="confirmPassword" required style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                        <i class="fas fa-eye" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; cursor: pointer;" onclick="togglePasswordVisibility('confirmPassword')"></i>
                    </div>
                    @error('new_password_confirmation')
                        <p style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Requirements -->
                <div style="background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                    <p style="color: #374151; font-weight: 600; font-size: 14px; margin: 0 0 10px;">Password Requirements:</p>
                    <ul style="margin: 0; padding-left: 20px; color: #6b7280; font-size: 13px;">
                        <li>Minimum 8 characters</li>
                        <li>Mix of uppercase and lowercase letters</li>
                        <li>Include numbers and special characters for better security</li>
                    </ul>
                </div>
            </div>
        </div>
    </form>

    <style>
        input[type="password"], input[type="text"], input[type="email"] {
            transition: all 0.3s ease;
        }

        input[type="password"]:focus, input[type="text"]:focus, input[type="email"]:focus {
            border-color: #002C76;
            box-shadow: 0 0 0 3px rgba(0, 44, 118, 0.1);
            outline: none;
        }

        button:hover {
            background-color: #b91c1c !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
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

        @media (max-width: 768px) {
            .content-header h1 {
                font-size: 18px !important;
                margin-bottom: 8px !important;
            }

            .content-header p {
                font-size: 12px !important;
            }

            div[style*="background: white"] {
                padding: 20px !important;
                border-radius: 8px !important;
            }

            div[style*="width: 80px"] {
                width: 60px !important;
                height: 60px !important;
                font-size: 24px !important;
                margin-right: 15px !important;
            }

            h2[style*="color: #002C76"] {
                font-size: 16px !important;
            }

            p[style*="color: #6b7280"] {
                font-size: 12px !important;
            }

            label {
                font-size: 13px !important;
            }

            input[type="password"], input[type="text"], input[type="email"] {
                padding: 10px !important;
                font-size: 13px !important;
            }

            button {
                padding: 10px !important;
                font-size: 13px !important;
            }

            div[style*="display: flex; align-items: center; justify-content: space-between"] {
                flex-direction: column;
                align-items: flex-start !important;
            }

            div[style*="display: flex; gap: 10px; align-items: center"] {
                width: 100%;
                margin-top: 15px;
            }

            a[style*="flex: 1"] {
                flex: 1 !important;
                width: 100%;
            }

            button[style*="width: 100%"] {
                width: 100% !important;
            }

            div[style*="background-color: #f3f4f6"] {
                font-size: 12px !important;
            }

            ul {
                font-size: 12px !important;
            }
        }

        @media (max-width: 480px) {
            div[style*="background: white"] {
                padding: 15px !important;
            }

            h2[style*="color: #002C76"] {
                font-size: 14px !important;
            }

            input[type="password"], input[type="text"], input[type="email"] {
                padding: 10px !important;
                font-size: 12px !important;
            }

            button, a {
                padding: 10px !important;
                font-size: 12px !important;
            }

            div[style*="width: 80px"] {
                width: 50px !important;
                height: 50px !important;
                font-size: 20px !important;
                margin-right: 12px !important;
            }

            div[style*="margin-bottom: 25px"] {
                margin-bottom: 20px !important;
            }
        }
    </style>

    <script>
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

        // Check if passwords match
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }

            // Confirmation before saving changes
            const confirmChange = confirm('Are you sure you want to change your password? You will need to login again with your new password.');
            if (!confirmChange) {
                e.preventDefault();
                return false;
            }
        });
    </script>
@endsection
