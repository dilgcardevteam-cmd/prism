<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DILG-CAR Project Development and Management Unit</title>
    <link rel="icon" type="image/png" href="{{ asset('DILG-Logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('DILG-Logo.png') }}">

    @include('partials.google-sans-font')

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Toast CSS -->
    <style>
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        .toast {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: 1px solid #e0e0e0;
        }
        .toast.success {
            border-color: #28a745;
        }
        .toast.success .toast-header {
            background-color: #d4edda;
            color: #155724;
        }
        .toast.error {
            border-color: #dc3545;
        }
        .toast.error .toast-header {
            background-color: #f8d7da;
            color: #721c24;
        }
        .toast-countdown {
            display: block;
            margin-top: 8px;
            font-size: 12px;
            color: #4b5563;
        }
        .toast-progress-track {
            margin-top: 8px;
            width: 100%;
            height: 4px;
            border-radius: 999px;
            background: rgba(17, 24, 39, 0.15);
            overflow: hidden;
        }
        .toast-progress-bar {
            width: 100%;
            height: 100%;
        }
        .toast.success .toast-progress-bar {
            background: #16a34a;
        }
        .toast.error .toast-progress-bar {
            background: #dc2626;
        }
    </style>

    <style>
        body { font-family: var(--app-font-sans); background-image: url('/background.jpg'); background-size: cover; background-position: center; background-attachment: fixed; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; padding:20px 0; }
        .card { background:#fff; width:100%; max-width:640px; padding:28px; border-radius:10px; box-shadow:0 6px 24px rgba(10,10,10,0.08); }
        .card h2 { color:#002C76; margin:0 0 6px; font-size:20px; }
        .card p.subtitle { margin:0 0 18px; color:#6b7280; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; border-top:1px solid #e6eef8; padding-top:8px; }
        .col { display:flex; flex-direction:column; gap:12px; }
        .field { position:relative; }
        .input-wrapper { position: relative; }
        .field input, .field select { width:100%; box-sizing:border-box; padding:10px 44px 10px 40px; border-radius:8px; border:1px solid #e6eef8; background:#fff; font-size:14px; color:#111827; }
        .left-icon { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#9ca3af; display:flex; align-items:center; pointer-events:none }
        .left-icon svg { width:16px; height:16px; stroke:currentColor }
        .invalid { border-color:#dc2626 !important; box-shadow:0 0 0 4px rgba(220,38,38,0.06); }
        .error { color:#dc2626; font-size:13px; margin-top:6px; }
        .field input::placeholder { color:#9ca3af }
        .field label.visible { display:block; margin-bottom:6px; color:#374151; font-size:13px; font-weight:400 }
        .password-icon { position:absolute; right:12px; top:50%; transform:translateY(-50%); display:flex; gap:6px; color:#9ca3af; cursor:pointer }
        .password-icon svg { width:14px; height:14px; stroke:currentColor }
        .actions { margin-top:14px; display:flex; gap:12px; align-items:center; }
        .actions button { flex:1; padding:12px; border-radius:8px; border:none; background:#002C76; color:#fff; font-weight:600; cursor:pointer }
        .actions a.cancel { flex:1; text-align:center; padding:12px; border-radius:8px; border:1px solid #e6eef8; color:#374151; text-decoration:none; display:flex; align-items:center; justify-content:center; }
        .actions a.cancel i { margin-right:6px; }
        .signin-link { text-decoration: none; }
        .signin-link:hover { text-decoration: underline; }
        .full { grid-column:1 / -1; margin-top:0px; display:flex; flex-direction:column; gap:12px; }
        label.sr-only { position:absolute !important; width:1px !important; height:1px !important; padding:0 !important; margin:-1px !important; overflow:hidden !important; clip:rect(0,0,0,0) !important; white-space:nowrap !important; border:0 !important; }
        
        @media (max-width: 768px) {
            body { padding: 15px 0; min-height: auto; }
            .card { max-width: 100%; padding: 24px 20px; margin: 15px auto; width: calc(100% - 40px); }
            .grid { gap: 10px; }
            .field input, .field select { font-size: 13px; padding: 9px 40px 9px 36px; }
            .card h2 { font-size: 18px; }
            .card p.subtitle { font-size: 13px; }
        }

        @media (max-width: 480px) {
            body { padding: 10px 0; }
            .card { 
                padding: 18px 15px; 
                margin: 10px auto; 
                width: calc(100% - 30px);
                border-radius: 8px;
            }
            div[style*="display:flex; align-items:center; margin-bottom:6px"] {
                flex-direction: column;
                text-align: center;
                margin-bottom: 15px !important;
            }
            img[alt="DILG Logo"] { 
                margin-right: 0 !important; 
                margin-bottom: 12px !important;
                width: 50px !important;
                height: 50px !important;
            }
            .card h2 { font-size: 16px; }
            .card p.subtitle { font-size: 12px; }
            .grid { grid-template-columns: 1fr !important; gap: 10px; }
            .field input, .field select { 
                font-size: 13px; 
                padding: 9px 36px 9px 34px; 
            }
            .field label.visible { font-size: 12px; }
            .error { font-size: 11px; }
            .actions { 
                gap: 8px; 
                flex-direction: column;
            }
            .actions button, .actions a.cancel { 
                width: 100%; 
                padding: 10px;
                font-size: 13px;
            }
            h3 { font-size: 13px !important; margin: 0 0 8px !important; }
            .toast-container {
                top: 10px !important;
                right: 10px !important;
                left: 10px !important;
            }
            .toast {
                width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <div style="display:flex; align-items:center; margin-bottom:6px;">
            <img src="{{ asset('DILG-Logo.png') }}" alt="DILG Logo" style="width:60px; height:60px; margin-right:16px;">
            <div>
                <h2 style="margin:0 0 4px; color:#002C76; font-size:20px;">Create Account</h2>
                <p class="subtitle" style="margin:0; color:#6b7280;">Kindly fill out all the required information to complete your registration.</p>
            </div>
        </div>

            <form method="POST" action="{{ route('register') }}" id="registerForm">
                @csrf
                <div class="grid">
                    <div class="col">
                        <h3 style="margin:0 0 8px;color:#374151;font-size:14px;">Personal Information</h3>
                        <div class="field">
                            <div class="input-wrapper">
                                <div class="left-icon"><i data-feather="user"></i></div>
                                <label class="sr-only" for="fname">First Name</label>
                                <input id="fname" name="fname" type="text" placeholder="First Name" required>
                            </div>
                        </div>
                        <div class="field">
                            <div class="input-wrapper">
                                <div class="left-icon"><i data-feather="user"></i></div>
                                <label class="sr-only" for="lname">Last Name</label>
                                <input id="lname" name="lname" type="text" placeholder="Last Name" required>
                            </div>
                        </div>
                        <div class="field">
                            <div class="input-wrapper">
                                <div class="left-icon"><i data-feather="home"></i></div>
                                <label class="sr-only" for="agency">Agency/LGU</label>
                                <select id="agency" name="agency" required>
                                    <option value="" disabled selected>Select Agency/LGU</option>
                                    <option value="DILG">DILG</option>
                                    <option value="LGU">LGU</option>
                                </select>
                            </div>
                        </div>
                        <div class="field">
                            <div class="input-wrapper">
                                <div class="left-icon"><i data-feather="briefcase"></i></div>
                                <label class="sr-only" for="position">Position</label>
                                <select id="position" name="position" required>
                                    <option value="" disabled selected>Select Position</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <h3 style="margin:0 0 8px;color:#374151;font-size:14px;">Contact Information</h3>
                        <div class="field">
                            <div class="input-wrapper">
                                <div class="left-icon"><i data-feather="map"></i></div>
                                <label class="sr-only" for="region">Region</label>
                                <select id="region" name="region" required>
                                    <option value="" disabled selected>Select Region</option>
                                    @foreach(($locationOptions['regions'] ?? []) as $regionName)
                                        <option value="{{ $regionName }}">{{ $regionName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="field" id="province-field">
                            <div class="input-wrapper">
                                <div class="left-icon"><i data-feather="map-pin"></i></div>
                                <label class="sr-only" for="province">Province</label>
                                <select id="province" name="province" required>
                                    <option value="" disabled selected>Select Province</option>
                                </select>
                            </div>
                        </div>
                        <div class="field" id="office-field">
                            <div class="input-wrapper">
                                <div class="left-icon"><i data-feather="home"></i></div>
                                <label class="sr-only" for="office">Office</label>
                                <select id="office" name="office">
                                    <option value="" disabled selected>Select Office</option>
                                </select>
                            </div>
                        </div>
                        <div class="field">
                            <div class="input-wrapper">
                                <div class="left-icon"><i data-feather="mail"></i></div>
                                <label class="sr-only" for="emailaddress">Email</label>
                                <input id="emailaddress" name="emailaddress" type="email" placeholder="Email" required>
                            </div>
                        </div>
                        <div class="field">
                            <div class="input-wrapper">
                                <div class="left-icon"><i data-feather="phone"></i></div>
                                <label class="sr-only" for="mobileno">Mobile Number</label>
                                <input id="mobileno" name="mobileno" type="tel" placeholder="Mobile Number" pattern="^09\d{9}$" title="Mobile number must start with 09 and be 11 digits long" maxlength="11" required>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="full" style="margin-top: 10px;">
                    <h3 style="margin:0 0 8px;color:#374151;font-size:14px;">Account Credential</h3>
                    <div class="field">
                        <div class="input-wrapper">
                            <div class="left-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></div>
                            <label class="sr-only" for="username">Username</label>
                            <input id="username" name="username" type="text" placeholder="Username" required="">
                        </div>
                    </div>
                    <div class="field">
                        <div class="input-wrapper">
                            <div class="left-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-lock"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></div>
                            <label class="sr-only" for="password">Password</label>
                            <input id="password" name="password" type="password" placeholder="Password" required="">
                            <div class="password-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off" style="display: none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </div>
                        </div>
                    </div>
                    <div class="field">
                        <div class="input-wrapper">
                            <div class="left-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-lock"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></div>
                            <label class="sr-only" for="password_confirmation">Confirm Password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Confirm Password" required="">
                            <div class="password-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off" style="display: none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="actions">
                    <button type="submit" id="registerBtn">
                        Register
                    </button>
                </div>
            </form>

            <div class="actions">
                <a href="{{ route('login') }}" class="cancel">Already have an account? Sign in</a>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script src="https://unpkg.com/feather-icons"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            if(window.feather) feather.replace();

            function initPasswordToggle(wrapper){
                if(!wrapper) return;
                const eye = wrapper.querySelector('.feather-eye');
                const eyeoff = wrapper.querySelector('.feather-eye-off');
                const input = wrapper.parentElement.querySelector('input[type=password]');
                if(!input) return;
                if(!eye || !eyeoff){ setTimeout(()=>initPasswordToggle(wrapper),50); return; }
                eyeoff.style.display = 'none';
                eye.addEventListener('click', ()=>{ eye.style.display='none'; eyeoff.style.display='block'; input.type='text'; });
                eyeoff.addEventListener('click', ()=>{ eye.style.display='block'; eyeoff.style.display='none'; input.type='password'; });
            }
            document.querySelectorAll('.password-icon').forEach(initPasswordToggle);

            // Dynamic Position Dropdown
            const agencySelect = document.getElementById('agency');
            const positionSelect = document.getElementById('position');

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

            agencySelect.addEventListener('change', function(){
                const selectedValue = this.value;
                positionSelect.innerHTML = '<option value="" disabled selected>Select Position</option>';
                if(positions[selectedValue]){
                    positions[selectedValue].forEach(function(position){
                        const option = document.createElement('option');
                        option.value = position;
                        option.textContent = position;
                        positionSelect.appendChild(option);
                    });
                }
            });

            const locationOptions = @json($locationOptions);

            // Dynamic Province/Office Dropdowns based on Agency and Region
            const regionSelect = document.getElementById('region');
            const provinceSelect = document.getElementById('province');
            const officeSelect = document.getElementById('office');
            const provinceField = document.getElementById('province-field');
            const officeField = document.getElementById('office-field');
            const provinceLabel = document.querySelector('label[for="province"]');
            const officeLabel = document.querySelector('label[for="office"]');

            function resetSelect(select, placeholder){
                select.innerHTML = '';
                const option = document.createElement('option');
                option.value = '';
                option.disabled = true;
                option.selected = true;
                option.textContent = placeholder;
                select.appendChild(option);
            }

            function setSelectOptions(select, placeholder, items){
                resetSelect(select, placeholder);
                items.forEach(function(item){
                    const value = String(item || '').trim();
                    if(value === ''){
                        return;
                    }

                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = value;
                    select.appendChild(option);
                });
            }

            function getProvinceOptionsByRegion(regionName){
                return locationOptions.provincesByRegion?.[regionName] || [];
            }

            function getOfficeOptionsByProvince(provinceName){
                return locationOptions.officesByProvince?.[provinceName] || [];
            }

            function updateOfficeDropdown(){
                if(agencySelect.value !== 'LGU'){
                    officeSelect.required = false;
                    resetSelect(officeSelect, 'Select Office');
                    officeField.style.display = 'none';
                    return;
                }

                officeField.style.display = '';
                officeLabel.textContent = 'Office';
                officeSelect.required = true;

                const selectedProvince = provinceSelect.value;
                if(!selectedProvince){
                    resetSelect(officeSelect, 'Select Office');
                    return;
                }

                setSelectOptions(officeSelect, 'Select Office', getOfficeOptionsByProvince(selectedProvince));
            }

            function updateAgencyLocationFields(){
                const selectedAgency = agencySelect.value;
                const selectedRegion = regionSelect.value;
                const provinceOptions = getProvinceOptionsByRegion(selectedRegion);

                if(selectedAgency === 'DILG'){
                    provinceLabel.textContent = 'Office';
                    setSelectOptions(provinceSelect, 'Select Office', ['Regional Office', ...provinceOptions]);
                    officeSelect.required = false;
                    resetSelect(officeSelect, 'Select Office');
                    officeField.style.display = 'none';
                    return;
                }

                provinceLabel.textContent = 'Province';
                setSelectOptions(provinceSelect, 'Select Province', provinceOptions);

                if(selectedAgency === 'LGU'){
                    officeField.style.display = '';
                    updateOfficeDropdown();
                    return;
                }

                officeSelect.required = false;
                resetSelect(officeSelect, 'Select Office');
                officeField.style.display = 'none';
            }

            agencySelect.addEventListener('change', updateAgencyLocationFields);
            regionSelect.addEventListener('change', updateAgencyLocationFields);
            provinceSelect.addEventListener('change', updateOfficeDropdown);

            resetSelect(provinceSelect, 'Select Province');
            resetSelect(officeSelect, 'Select Office');
            officeField.style.display = 'none';
            updateAgencyLocationFields();

            // Validation for required fields
            const requiredFields = document.querySelectorAll('input[required], select[required]');
            const originalBorderColor = '#e6eef8';
            const errorBorderColor = '#dc2626';

            requiredFields.forEach(field => {
                field.addEventListener('blur', function() {
                    if (this.value.trim() === '') {
                        this.style.borderColor = errorBorderColor;
                    }
                });

                field.addEventListener('focus', function() {
                    this.style.borderColor = originalBorderColor;
                });
            });

            // Toast notification function
            function showToast(message, type = 'success', options = {}) {
                const toastContainer = document.getElementById('toastContainer');
                const toastId = 'toast-' + Date.now();
                const parsedDelay = Number(options.delay);
                const delay = Number.isFinite(parsedDelay) ? Math.max(1000, parsedDelay) : 5000;
                const shouldShowCountdown = options.showCountdown === true;
                const shouldShowProgress = options.showProgress === true;
                const countdownPrefix = typeof options.countdownPrefix === 'string' && options.countdownPrefix.trim() !== ''
                    ? options.countdownPrefix.trim()
                    : 'Closing in';
                const toastExpiresAt = Date.now() + delay;
                let countdownIntervalId = null;

                const toastElement = document.createElement('div');
                toastElement.id = toastId;
                toastElement.className = `toast ${type}`;
                toastElement.setAttribute('role', 'alert');
                toastElement.setAttribute('aria-live', 'assertive');
                toastElement.setAttribute('aria-atomic', 'true');

                const toastHeader = document.createElement('div');
                toastHeader.className = 'toast-header';

                const title = document.createElement('strong');
                title.className = 'me-auto';
                title.textContent = type === 'success' ? 'Success' : 'Error';

                const closeButton = document.createElement('button');
                closeButton.type = 'button';
                closeButton.className = 'btn-close';
                closeButton.setAttribute('data-bs-dismiss', 'toast');
                closeButton.setAttribute('aria-label', 'Close');

                toastHeader.append(title, closeButton);

                const toastBody = document.createElement('div');
                toastBody.className = 'toast-body';
                toastBody.style.whiteSpace = 'pre-line';
                const toastMessage = document.createElement('div');
                toastMessage.textContent = message ?? '';
                toastBody.appendChild(toastMessage);

                let countdownText = null;
                if (shouldShowCountdown) {
                    countdownText = document.createElement('small');
                    countdownText.className = 'toast-countdown';
                    toastBody.appendChild(countdownText);
                }

                let progressBar = null;
                if (shouldShowProgress) {
                    const progressTrack = document.createElement('div');
                    progressTrack.className = 'toast-progress-track';

                    progressBar = document.createElement('div');
                    progressBar.className = 'toast-progress-bar';
                    progressTrack.appendChild(progressBar);
                    toastBody.appendChild(progressTrack);
                }

                toastElement.append(toastHeader, toastBody);
                toastContainer.appendChild(toastElement);

                const bsToast = new bootstrap.Toast(toastElement, {
                    autohide: true,
                    delay
                });

                if (countdownText) {
                    const updateCountdown = () => {
                        const remainingSeconds = Math.max(0, Math.ceil((toastExpiresAt - Date.now()) / 1000));
                        countdownText.textContent = `${countdownPrefix} ${remainingSeconds}s`;
                    };

                    updateCountdown();
                    countdownIntervalId = setInterval(updateCountdown, 100);
                }

                if (progressBar) {
                    requestAnimationFrame(() => {
                        progressBar.style.transition = `width ${delay}ms linear`;
                        progressBar.style.width = '0%';
                    });
                }

                bsToast.show();

                // Remove toast from DOM after it's hidden
                toastElement.addEventListener('hidden.bs.toast', function() {
                    if (countdownIntervalId !== null) {
                        clearInterval(countdownIntervalId);
                    }
                    toastElement.remove();
                });
            }

            // AJAX form submission
            const registerForm = document.getElementById('registerForm');
            const registerBtn = document.getElementById('registerBtn');

            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                let isRedirecting = false;

                // Show loading state
                registerBtn.disabled = true;
                registerBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Registering...';

                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json().then(data => ({ status: response.status, data })))
                .then(({ status, data }) => {
                    if (status === 200 && data.success) {
                        const successDelay = 10000;
                        const redirectUrl = (typeof data.redirect === 'string' && data.redirect.trim() !== '')
                            ? data.redirect
                            : @json(route('login'));
                        isRedirecting = true;
                        showToast(data.message, 'success', {
                            delay: successDelay,
                            showCountdown: true,
                            showProgress: true,
                            countdownPrefix: 'Redirecting to login in'
                        });
                        // Redirect after toast countdown
                        setTimeout(() => {
                            window.location.href = redirectUrl;
                        }, successDelay);
                    } else {
                        // Handle validation errors
                        if (data.errors) {
                            let errorMessage = 'Please fix the following errors:\n';
                            for (const field in data.errors) {
                                errorMessage += `• ${data.errors[field][0]}\n`;
                            }
                            showToast(errorMessage, 'error');
                        } else if (data.message) {
                            showToast(data.message, 'error');
                        } else {
                            showToast('User registration failed.', 'error');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred. Please try again.', 'error');
                })
                .finally(() => {
                    if (!isRedirecting) {
                        // Reset button state
                        registerBtn.disabled = false;
                        registerBtn.innerHTML = 'Register';
                    }
                });
            });
        });
    </script>
</body>
</html>
