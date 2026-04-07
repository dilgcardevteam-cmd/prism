<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DILG-CAR Project Development and Management Unit</title>
    <link rel="icon" type="image/png" href="/DILG-Logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    @include('partials.google-sans-font')
    <style>
        body {
            font-family: var(--app-font-sans);
            background-color: #f4f4f4;
            background-image: url('/background.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: white;
            padding: 28px 24px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 420px;
        }
        .logo {
            display: block;
            margin: 0 auto 12px;
            width: 110px;
            height: auto;
        }

        h2 {
            text-align: center;
            margin: 2px 0 4px;
            color: #002C76;
            font-size: 18px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        h3 {
            text-align: center;
            font-weight: normal;
            font-size: 14px;
            margin: 2px 0 18px;
        }
        form {
            display: flex;
            flex-direction: column;
        }

        .field {
            position: relative;
            margin-bottom: 14px;
        }
        .field input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid #e6eef8;
            background: #fff;
            font-size: 14px;
            color: #111827;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .invalid { border-color:#dc2626 !important; box-shadow:0 0 0 4px rgba(220,38,38,0.06); }
        .error { color:#dc2626; font-size:13px; margin-top:6px; }
        .field input::placeholder { color: #9ca3af; }

        .field.email-field {
            position: relative;
        }
        .field.email-field::after {
            content: "\f0e0";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
        }
        .field.email-field input {
            padding-left: 40px;
        }

        .field.otp-field .otp-inputs {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 14px;
        }

        .field.otp-field .otp-input {
            width: 40px;
            height: 40px;
            text-align: center;
            border: 1px solid #e6eef8;
            border-radius: 4px;
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            background: #fff;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            box-sizing: border-box;
        }

        .field.otp-field .otp-input:focus {
            border-color: #002C76;
            box-shadow: 0 0 0 2px rgba(0, 44, 118, 0.1);
            outline: none;
        }

        .field.otp-field .otp-input::placeholder {
            color: #9ca3af;
        }

        button {
            padding: 12px 14px;
            background-color: #002C76;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-weight: 600;
        }
        button:hover {
            background-color: #001f59;
        }

        .back-link {
            text-align: center;
            margin-top: 12px;
            font-size: 13px;
        }
        .back-link a {
            color: #002C76;
            font-weight: 600;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }

        .toast {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #10b981;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            font-size: 14px;
            font-weight: 500;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease-in-out;
        }

        .toast.show {
            opacity: 1;
            visibility: visible;
            animation: slideDown 0.3s ease-out;
        }

        .toast.fade-out {
            animation: fadeOut 0.5s ease-out forwards;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-20px);
            }
        }

        .timer {
            text-align: center;
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 14px;
        }

        /* Responsive tweaks */
        @media (max-width: 480px) {
            .login-container {
                padding: 20px;
            }
            .logo { width: 80px; margin-bottom: 10px; }
            h2 { font-size: 16px; }
            h3 { font-size: 13px; margin-bottom: 14px; }
            input[type="email"] { font-size: 14px; padding: 10px 12px; }
            button { padding: 10px; }
        }

        @media (min-width: 769px) {
            .login-container { max-width: 480px; padding: 32px 28px; }
            h2 { font-size: 20px; }
            h3 { font-size: 15px; }
            input[type="email"] { font-size: 15px; padding: 12px 14px; }
            button { padding: 12px 14px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        @php
            $showOtp = ($showOtp ?? false) || session()->has('otp_email');
            $emailValue = session('otp_email', old('email'));
        @endphp
        <img src="/DILG-Logo.png" alt="DILG Logo" style="display: block; margin: 0 auto 8px; width: 100px;">
        <h2>Project Development and Management Unit</h2>
        <h3>Reporting, Inspection and Monitoring System (PRISM)</h3>
        @if($errors->any())
            <div class="error">
                @foreach($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif
        @if($showOtp)
            <h2 style="margin-top: 18px;">OTP Verification</h2>
            <p style="text-align: center; font-size: 13px; color: #6b7280; margin: 0 0 12px;">
                Enter the 6-digit code sent to your email.
            </p>
            <form action="{{ route('forgot-password.verify-otp') }}" method="POST">
                @csrf
                <div class="field otp-field">
                    <div class="otp-inputs">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                    </div>
                    <input type="hidden" id="otp" name="otp" required>
                    <input type="hidden" name="email" value="{{ $emailValue }}">
                </div>
                <div id="timer" class="timer">Time remaining: 05:00</div>
                <button type="submit">Verify OTP</button>
            </form>
            <form id="resendOtpForm" action="{{ route('forgot-password.send-otp') }}" method="POST" style="display: none;">
                @csrf
                <input type="hidden" name="email" value="{{ $emailValue }}">
            </form>

        @else
            <h2>Recovery Email Address</h2>

            <form action="{{ route('forgot-password.send-otp') }}" method="POST">
                @csrf
                <div class="field email-field">
                    <input type="email" id="email" name="email" placeholder="Enter your registered email" value="{{ old('email') }}" required>
                </div>
                <button type="submit">Send OTP</button>
            </form>
        @endif

        <div class="back-link">
            <a href="/login">← Back to Login</a>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <script>
        // Toast notification function
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast'; // Reset classes
            toast.classList.add('show');

            // Change color based on type
            if (type === 'error') {
                toast.style.backgroundColor = '#dc2626'; // Red for errors
            } else {
                toast.style.backgroundColor = '#10b981'; // Green for success
            }

            setTimeout(() => {
                toast.classList.add('fade-out');
                setTimeout(() => {
                    toast.classList.remove('show', 'fade-out');
                }, 500);
            }, type === 'error' ? 4000 : 2000); // Show longer for errors
        }

        // Check if timer element exists (only after OTP is sent)
        const timerElement = document.getElementById('timer');
        const resendForm = document.getElementById('resendOtpForm');
        if (timerElement) {
            let timeLeft = 5 * 60; // 5 minutes in seconds
            let countdown;

            function startCountdown() {
                countdown = setInterval(() => {
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    timerElement.textContent = `Time remaining: ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    timerElement.style.color = '#6b7280'; // Reset color

                    if (timeLeft <= 0) {
                        clearInterval(countdown);
                        timerElement.textContent = 'Resend OTP';
                        timerElement.style.color = '#002C76';
                        timerElement.style.cursor = 'pointer';
                        timerElement.onclick = function() {
                            if (resendForm) {
                                resendForm.submit();
                            } else {
                                showToast('OTP sent to your email. Please check your inbox.');
                                timeLeft = 5 * 60; // Reset to 5 minutes
                                startCountdown();
                            }
                        };
                        timerElement.onmouseover = function() {
                            timerElement.style.textDecoration = 'underline';
                        };
                        timerElement.onmouseout = function() {
                            timerElement.style.textDecoration = 'none';
                        };
                    }
                    timeLeft--;
                }, 1000);
            }

            startCountdown();
        }

        // OTP input handling
        document.addEventListener('DOMContentLoaded', function() {
            const otpInputs = document.querySelectorAll('.otp-input');
            const otpHidden = document.getElementById('otp');

            function updateHiddenInput() {
                const otpValue = Array.from(otpInputs).map(input => input.value).join('');
                otpHidden.value = otpValue;
            }

            otpInputs.forEach((input, index) => {
                input.addEventListener('input', function(e) {
                    // Allow only numbers
                    this.value = this.value.replace(/[^0-9]/g, '');

                    if (this.value.length === 1 && index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }

                    updateHiddenInput();
                });

                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value === '' && index > 0) {
                        otpInputs[index - 1].focus();
                    }
                });

                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const paste = (e.clipboardData || window.clipboardData).getData('text');
                    const pasteNumbers = paste.replace(/[^0-9]/g, '').slice(0, 6);

                    pasteNumbers.split('').forEach((char, i) => {
                        if (otpInputs[index + i]) {
                            otpInputs[index + i].value = char;
                        }
                    });

                    updateHiddenInput();

                    // Focus on the next empty input or the last one
                    const nextIndex = Math.min(index + pasteNumbers.length, otpInputs.length - 1);
                    otpInputs[nextIndex].focus();
                });
            });

            // Trigger toast on page load if success or error session exists
            @if(session("success"))
                showToast('{{ session("success") }}', 'success');
            @endif

            @if($errors->any())
                @php
                    $errorMessages = $errors->all();
                    $firstError = reset($errorMessages);
                @endphp
                showToast('{{ $firstError }}', 'error');
            @endif
        });
    </script>
</body>
</html>
