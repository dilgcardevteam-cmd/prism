<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Received Notice of Authority to Debit Account Issued</title>
</head>
@php
    $emailLogoPath = public_path('email-dilg-logo.png');
    $emailLogoSrc = file_exists($emailLogoPath)
        ? (isset($message) ? $message->embed($emailLogoPath) : asset('email-dilg-logo.png'))
        : asset('DILG-Logo.png');
    $programDisplayMap = [
        'SBDP' => 'Support to the Barangay Development Program',
        'FALGU' => 'Financial Assistance to Local Government Unit',
    ];
@endphp
<body style="margin: 0; padding: 8px; background: #f3f4f6; font-family: Arial, Helvetica, sans-serif; color: #0f172a;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background: #f3f4f6;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="680" style="width: 680px; max-width: 680px; background: #ffffff; border: 1px solid #b6becb; border-radius: 12px;">
                    <tr>
                        <td style="background: #123b84; padding: 12px 14px; border-radius: 12px 12px 0 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td style="width: 58px; vertical-align: middle;">
                                        <img src="{{ $emailLogoSrc }}" alt="DILG Logo" width="50" height="50" style="display: block; width: 50px; height: 50px; border-radius: 50%; background: #ffffff;">
                                    </td>
                                    <td style="width: 12px; vertical-align: middle;">
                                        <div style="width: 3px; height: 52px; background: #ffffff; border-radius: 999px; margin: 0 auto;"></div>
                                    </td>
                                    <td style="padding-left: 12px; vertical-align: middle; color: #ffffff;">
                                        <div style="font-size: 11px; line-height: 1.25; font-weight: 700; text-transform: uppercase;">Department of the Interior and Local Government</div>
                                        <div style="font-size: 10px; line-height: 1.3;">Cordillera Administrative Region</div>
                                        <div style="font-size: 10px; line-height: 1.3; text-transform: uppercase;">Project Development and Management Unit (PDMU)</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px 8px;">
                            <div style="font-size: 17px; line-height: 1.2; font-weight: 700; color: #4b5563; text-align: center;">
                                Received Notice of Authority to Debit Account Issued
                            </div>
                            <div style="height: 2px; background: #123b84; margin-top: 6px;"></div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 16px 8px; font-size: 13px; line-height: 1.5; color: #4b5563;">
                            <p style="margin: 0 0 10px;">Good Day {{ trim(($recipient->fname ?? '') . ' ' . ($recipient->lname ?? '')) ?: ($recipient->username ?? 'User') }},</p>
                            <p style="margin: 0;">{{ $senderName }} uploaded a Notice of Authority to Debit Account Issued for <span style="font-weight: 700; color: #334155;">{{ $officeName }}</span>, {{ $province }}.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 16px 8px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background: #eaf2ff; border: 1px solid #c7dcff; border-radius: 10px;">
                                <tr>
                                    <td style="padding: 10px 14px; font-size: 12px; line-height: 1.5; color: #334155;">
                                        <div style="margin-bottom: 8px;">
                                            <div style="font-weight: 700; color: #0f172a;">Province:</div>
                                            <div style="padding-top: 1px;">{{ $document->province ?: '—' }}</div>
                                        </div>
                                        <div style="margin-bottom: 8px;">
                                            <div style="font-weight: 700; color: #0f172a;">Municipality:</div>
                                            <div style="padding-top: 1px;">{{ $document->municipality ?: '—' }}</div>
                                        </div>
                                        <div style="margin-bottom: 8px;">
                                            <div style="font-weight: 700; color: #0f172a;">Barangay:</div>
                                            <div style="padding-top: 1px;">{{ $document->barangay ?: '—' }}</div>
                                        </div>
                                        <div style="margin-bottom: 8px;">
                                            <div style="font-weight: 700; color: #0f172a;">Funding Year:</div>
                                            <div style="padding-top: 1px;">{{ $document->funding_year ?: '—' }}</div>
                                        </div>
                                        <div style="margin-bottom: 8px;">
                                            <div style="font-weight: 700; color: #0f172a;">Program:</div>
                                            @php
                                                $programValue = trim((string) ($document->program ?? ''));
                                            @endphp
                                            <div style="padding-top: 1px; word-break: break-word; overflow-wrap: anywhere;">{{ $programValue !== '' ? ($programDisplayMap[$programValue] ?? $programValue) : '—' }}</div>
                                        </div>
                                        <div style="margin-bottom: 8px;">
                                            <div style="font-weight: 700; color: #0f172a;">Project Title:</div>
                                            <div style="padding-top: 1px; word-break: break-word; overflow-wrap: anywhere;">{{ $document->project_title }}</div>
                                        </div>
                                        <div style="margin-bottom: 8px;">
                                            <div style="font-weight: 700; color: #0f172a;">Date of NADAI:</div>
                                            <div style="padding-top: 1px;">{{ $document->nadai_date?->format('F d, Y') ?? '—' }}</div>
                                        </div>
                                        <div>
                                            <div style="font-weight: 700; color: #0f172a;">Document:</div>
                                            <div style="padding-top: 1px; font-size: 11px; line-height: 1.45; word-break: break-word; overflow-wrap: anywhere;">{{ $document->original_filename }}</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 4px 16px 8px;">
                            <a href="{{ $actionUrl }}" style="display: inline-block; padding: 9px 18px; background: #123b84; color: #ffffff; text-decoration: none; border-radius: 9px; font-size: 13px; font-weight: 700;">
                                View NADAI
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 16px 14px; font-size: 10px; line-height: 1.4; color: #6b7280;">
                            This is an automated email from PRISM. Please do not reply directly to this message.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
