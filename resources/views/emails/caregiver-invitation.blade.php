<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Montserrat', Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #000080 0%, #4169E1 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
            font-weight: 900;
        }
        .content {
            padding: 40px 30px;
        }
        .content h2 {
            color: #000080;
            font-size: 20px;
            margin-bottom: 20px;
            font-weight: 700;
        }
        .content p {
            color: #333333;
            line-height: 1.6;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 16px 40px;
            background-color: #000080;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 800;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(0,0,128,0.3);
            transition: all 0.3s ease;
        }
        .button:hover {
            background-color: #000066;
            box-shadow: 0 6px 16px rgba(0,0,128,0.4);
        }
        .info-box {
            background-color: #f0f4ff;
            border-left: 4px solid #000080;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .footer {
            background-color: #f9f9f9;
            padding: 20px 30px;
            text-align: center;
            color: #666666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('assets/icons/silvercare.png') }}" alt="SilverCare" style="width: 60px; height: 60px; margin-bottom: 15px;">
            <h1>SilverCare</h1>
        </div>
        
        <div class="content">
            <h2>Hello {{ $caregiverUser->name }}!</h2>
            
            <p>You've been invited to be a caregiver for <strong>{{ $elderlyUser->name }}</strong> on SilverCare.</p>
            
            <div class="info-box">
                <p style="margin: 0;"><strong>Elderly User:</strong> {{ $elderlyUser->name }}</p>
                <p style="margin: 5px 0 0 0;"><strong>Your Role:</strong> {{ $caregiverUser->profile->relationship }}</p>
            </div>
            
            <p>To get started, please set your password by clicking the button below. This link will expire in 7 days.</p>
            
            <div class="button-container">
                <a href="{{ $setPasswordUrl }}" class="button">SET MY PASSWORD</a>
            </div>
            
            <p style="font-size: 14px; color: #666;">If the button doesn't work, copy and paste this link into your browser:</p>
            <p style="font-size: 12px; color: #999; word-break: break-all;">{{ $setPasswordUrl }}</p>
            
            <p>Once you set your password, you'll be able to:</p>
            <ul style="color: #333; line-height: 1.8;">
                <li>Monitor {{ $elderlyUser->name }}'s medication schedule</li>
                <li>Track health metrics and vitals</li>
                <li>Receive important notifications</li>
                <li>Manage daily checklists and appointments</li>
            </ul>
            
            <p>If you didn't expect this invitation, you can safely ignore this email.</p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} SilverCare. All rights reserved.</p>
            <p style="margin: 5px 0 0 0;">This invitation was sent because {{ $elderlyUser->name }} added you as their caregiver.</p>
        </div>
    </div>
</body>
</html>
