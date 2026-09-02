@php
    $severity = $alert->severity;
    $accent = match ($severity) {
        'emergency' => '#b91c1c',
        'critical'  => '#be123c',
        'warning'   => '#b45309',
        default     => '#1d4ed8',
    };
    $label = match ($severity) {
        'emergency' => 'Emergency',
        'critical'  => 'Critical',
        'warning'   => 'Warning',
        default     => 'Notice',
    };
    $isUrgent = in_array($severity, ['emergency', 'critical'], true);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $label }} alert for {{ $patientName }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,sans-serif;color:#0f172a;">
    {{-- Preheader: what most clients show next to the subject in the inbox list. --}}
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        {{ $label }} alert for {{ $patientName }} — {{ \Illuminate\Support\Str::limit(strip_tags($alert->message), 120) }}
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="620" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="padding:22px 28px;background:{{ $accent }};color:#ffffff;">
                            <p style="margin:0 0 6px;font-size:12px;letter-spacing:1.4px;text-transform:uppercase;font-weight:700;opacity:0.92;">
                                SilverCare &middot; {{ $label }} alert
                            </p>
                            <h1 style="margin:0;font-size:22px;line-height:1.3;font-weight:800;">{{ $alert->title }}</h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 28px 8px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 18px;">
                                <tr>
                                    <td style="padding:6px 0;font-size:13px;color:#64748b;width:110px;">Patient</td>
                                    <td style="padding:6px 0;font-size:15px;font-weight:700;">{{ $patientName }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;font-size:13px;color:#64748b;">Severity</td>
                                    <td style="padding:6px 0;font-size:15px;font-weight:700;color:{{ $accent }};">{{ strtoupper($severity) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;font-size:13px;color:#64748b;">Detected</td>
                                    <td style="padding:6px 0;font-size:15px;">{{ $alert->created_at?->format('M j, Y \a\t g:i A') ?? now()->format('M j, Y \a\t g:i A') }}</td>
                                </tr>
                            </table>

                            <p style="margin:0 0 18px;font-size:15px;line-height:1.65;">{{ $alert->message }}</p>

                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 20px;">
                                <tr>
                                    <td style="border-radius:10px;background:{{ $accent }};">
                                        <a href="{{ $dashboardUrl }}"
                                           style="display:inline-block;padding:13px 24px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">
                                            Review &amp; acknowledge
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 18px;font-size:13px;line-height:1.6;color:#475569;">
                                This alert stays open until a caregiver acknowledges it. If it is not acknowledged in time, SilverCare will send it again.
                            </p>
                        </td>
                    </tr>

                    @if ($isUrgent)
                        <tr>
                            <td style="padding:0 28px 24px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;">
                                    <tr>
                                        <td style="padding:14px 16px;font-size:13px;line-height:1.6;color:#7f1d1d;">
                                            {{ $disclaimer }}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:16px 28px 24px;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#94a3b8;">
                                Sent by SilverCare because you are the linked caregiver for {{ $patientName }}.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
