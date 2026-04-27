<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opening NADAI Document</title>
</head>
<body style="margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; background: #f3f4f6; font-family: Arial, Helvetica, sans-serif; color: #0f172a;">
    <div style="width: min(100%, 520px); background: #ffffff; border: 1px solid #d1d5db; border-radius: 16px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08); padding: 28px;">
        <div style="font-size: 20px; font-weight: 700; color: #123b84; margin-bottom: 10px;">Preparing your NADAI document</div>
        <p style="margin: 0 0 14px; font-size: 14px; line-height: 1.55; color: #475569;">
            The download for <strong>{{ $document->original_filename }}</strong> should start automatically.
            You will then be redirected to the NADAI page for <strong>{{ $officeName }}</strong>.
        </p>
        <p style="margin: 0 0 18px; font-size: 13px; line-height: 1.5; color: #64748b;">
            If nothing happens, use the buttons below.
        </p>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="{{ $downloadUrl }}" style="display: inline-flex; align-items: center; justify-content: center; padding: 10px 16px; background: #0f766e; color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: 700;">
                Download document
            </a>
            <a href="{{ $redirectUrl }}" style="display: inline-flex; align-items: center; justify-content: center; padding: 10px 16px; background: #123b84; color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: 700;">
                Go to NADAI page
            </a>
        </div>
    </div>

    <iframe id="nadai-download-frame" title="NADAI document download" style="display: none;"></iframe>

    <script>
        (function () {
            const downloadUrl = @json($downloadUrl);
            const redirectUrl = @json($redirectUrl);
            const downloadFrame = document.getElementById('nadai-download-frame');

            if (downloadFrame) {
                downloadFrame.src = downloadUrl;
            }

            window.setTimeout(function () {
                window.location.replace(redirectUrl);
            }, 1200);
        })();
    </script>
</body>
</html>
