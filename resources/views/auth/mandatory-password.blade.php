<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change password - PRISM</title>
    @include('partials.google-sans-font')
    <style>
        body { font-family: var(--app-font-sans); background: linear-gradient(315deg, #001a40 0%, #002c6c 50%, #0052cc 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        main { width: 100%; max-width: 440px; padding: 30px 28px; border-radius: 12px; background: white; box-shadow: 0 18px 40px rgba(0,0,0,.18); box-sizing: border-box; }
        h1 { margin: 0 0 10px; color: #002c76; font-size: 22px; text-align: center; }
        p { color: #475569; font-size: 14px; line-height: 1.6; }
        label { display: block; margin: 18px 0 7px; color: #374151; font-size: 13px; }
        input { width: 100%; box-sizing: border-box; padding: 12px; border: 1px solid #d5deeb; border-radius: 8px; font-size: 15px; }
        button { width: 100%; margin-top: 20px; padding: 12px; border: 0; border-radius: 8px; background: #002c76; color: white; font-weight: 600; cursor: pointer; }
        .error { margin-top: 6px; color: #dc2626; font-size: 13px; }
        .rules { color: #64748b; font-size: 12px; }
    </style>
</head>
<body>
    <main>
        <img src="{{ asset('DILG-Logo.png') }}" alt="DILG Logo" style="display:block;width:76px;margin:0 auto 16px;">
        <h1>Change your password</h1>
        <p>Your account requires a password update before you can use PRISM. After choosing a new password, you must verify a code sent to your registered email.</p>
        <p class="rules">Use at least 8 characters with uppercase and lowercase letters, a number, and a symbol.</p>
        @if ($errors->any())
            <div class="error" role="alert">{{ $errors->first() }}</div>
        @endif
        <form action="{{ route('password.mandatory.update') }}" method="POST">
            @csrf
            <label for="new_password">New password</label>
            <input id="new_password" name="new_password" type="password" autocomplete="new-password" required>
            <label for="new_password_confirmation">Confirm new password</label>
            <input id="new_password_confirmation" name="new_password_confirmation" type="password" autocomplete="new-password" required>
            @error('new_password') <div class="error">{{ $message }}</div> @enderror
            <button type="submit">Continue to email verification</button>
        </form>
    </main>
</body>
</html>
