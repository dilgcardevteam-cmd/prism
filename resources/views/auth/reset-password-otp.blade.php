<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DILG-CAR Project Development and Management Unit</title>
    <link rel="icon" type="image/png" href="/DILG-Logo.png">
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
        h2 {
            text-align: center;
            margin: 6px 0 4px;
            color: #002C76;
            font-size: 18px;
        }
        .subtitle {
            text-align: center;
            font-weight: normal;
            font-size: 13px;
            margin: 2px 0 18px;
            color: #6b7280;
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
        .error { color:#dc2626; font-size:13px; margin: 0 0 10px; text-align: center; }
        .field input::placeholder { color: #9ca3af; }
        .hint {
            font-size: 12px;
            color: #6b7280;
            margin: 0 0 12px;
            text-align: center;
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
        button:hover { background-color: #001f59; }
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
        .back-link a:hover { text-decoration: underline; }
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
        .toast.fade-out { animation: fadeOut 0.5s ease-out forwards; }
        @keyframes slideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-20px); }
        }
        @media (max-width: 480px) {
            .login-container { padding: 20px; }
            h2 { font-size: 16px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="/DILG-Logo.png" alt="DILG Logo" style="display: block; margin: 0 auto 8px; width: 100px;">
        <h2>Reset Password</h2>
        <p class="subtitle">Set a new password for {{ $email }}</p>

        @if($errors->any())
            <div class="error">
                @foreach($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif

        <form action="{{ route('forgot-password.reset-submit') }}" method="POST">
            @csrf
            <div class="field">
                <input type="password" name="password" placeholder="New password" required>
            </div>
            <div class="field">
                <input type="password" name="password_confirmation" placeholder="Confirm new password" required>
            </div>
            <p class="hint">Password must be at least 8 characters long.</p>
            <button type="submit">Reset Password</button>
        </form>

        <div class="back-link">
            <a href="/login">← Back to Login</a>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast';
            toast.classList.add('show');
            toast.style.backgroundColor = type === 'error' ? '#dc2626' : '#10b981';
            setTimeout(() => {
                toast.classList.add('fade-out');
                setTimeout(() => {
                    toast.classList.remove('show', 'fade-out');
                }, 500);
            }, type === 'error' ? 4000 : 2000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                showToast('{{ session('success') }}', 'success');
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
