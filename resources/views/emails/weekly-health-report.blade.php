<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Health Report</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="620" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="padding:24px 28px;background:linear-gradient(120deg,#1d4ed8,#1e3a8a);color:#ffffff;">
                            <h1 style="margin:0;font-size:24px;line-height:1.3;font-weight:800;">SilverCare Weekly Report</h1>
                            <p style="margin:8px 0 0;font-size:14px;line-height:1.5;opacity:0.9;">Automatic summary for your linked patient</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 28px;">
                            <p style="margin:0 0 12px;font-size:15px;line-height:1.6;">Hello {{ $caregiverUser->name }},</p>
                            <p style="margin:0 0 12px;font-size:15px;line-height:1.6;">
                                Attached is this week's health report for
                                <strong>{{ $elderlyProfile->user?->name ?? 'your patient' }}</strong>.
                            </p>
                            <p style="margin:0 0 12px;font-size:15px;line-height:1.6;">
                                The PDF includes vitals trends, medication adherence, and task completion insights so you can review weekly progress quickly.
                            </p>
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#475569;">
                                Generated on {{ now()->format('M j, Y \a\t g:i A') }}.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px 24px;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#64748b;">
                                You are receiving this because weekly auto-reports are enabled in SilverCare.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
