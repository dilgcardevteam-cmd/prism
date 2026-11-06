<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm password change - PRISM</title>
    @include('partials.google-sans-font')
    <style>
        body { font-family: var(--app-font-sans); background: linear-gradient(315deg, #001a40 0%, #002c6c 50%, #0052cc 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        main { width: 100%; max-width: 420px; padding: 30px 28px; border-radius: 12px; background: white; box-shadow: 0 18px 40px rgba(0,0,0,.18); box-sizing: border-box; }
        h1 { margin: 0 0 10px; color: #002c76; font-size: 22px; text-align: center; }
        p { color: #475569; font-size: 14px; line-height: 1.6; text-align: center; }
        label { display: block; margin: 22px 0 7px; color: #374151; font-size: 13px; }
        input { width: 100%; box-sizing: border-box; padding: 13px; border: 1px solid #d5deeb; border-radius: 8px; font-size: 20px; letter-spacing: 8px; text-align: center; }
        button { width: 100%; margin-top: 16px; padding: 12px; border: 0; border-radius: 8px; background: #002c76; color: white; font-weight: 600; cursor: pointer; }
        .error { margin-top: 6px; color: #dc2626; font-size: 13px; }
        .notice { margin: 12px 0; color: #047857; font-size: 13px; text-align: center; }
        .resend { display: block; margin: 12px auto 0; padding: 8px; border: 0; background: transparent; color: #002c76; cursor: pointer; }
        .resend:disabled { color: #64748b; cursor: not-allowed; }
    </style>
</head>
<body>
    <main>
        <img src="{{ asset('DILG-Logo.png') }}" alt="DILG Logo" style="display:block;width:76px;margin:0 auto 16px;">
        <h1>Confirm your password change</h1>
        <p>Enter the six-digit code being sent to <strong>{{ $emailAddress }}</strong>. You can use the code for five minutes.</p>
        @if (session('otp_notice'))
            <div class="notice" role="status">{{ session('otp_notice') }}</div>
        @endif
        @if ($errors->any())
            <div class="error" role="alert">{{ $errors->first() }}</div>
        @endif
        <form action="{{ route('password.mandatory.otp.verify') }}" method="POST">
            @csrf
            <label for="otp">Email verification code</label>
            <input id="otp" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus>
            <button type="submit">Verify and continue</button>
        </form>
        <form action="{{ route('password.mandatory.otp.resend') }}" method="POST">
            @csrf
            <button type="submit" class="resend" id="resendOtpButton" data-available-at="{{ $canResendAt }}">Resend code</button>
        </form>
    </main>
    <script>
        const resendButton = document.getElementById('resendOtpButton');
        const resendAt = Number(resendButton.dataset.availableAt) * 1000;

        function updateResendButton() {
            const secondsRemaining = Math.max(0, Math.ceil((resendAt - Date.now()) / 1000));
            resendButton.disabled = secondsRemaining > 0;
            resendButton.textContent = secondsRemaining > 0
                ? `Resend code in ${Math.floor(secondsRemaining / 60)}:${String(secondsRemaining % 60).padStart(2, '0')}`
                : 'Resend code';
            if (secondsRemaining > 0) window.setTimeout(updateResendButton, 1000);
        }

        updateResendButton();
    </script>
</body>
</html>
