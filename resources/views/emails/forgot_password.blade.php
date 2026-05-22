<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Glimpse Password</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #0F0A1A;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #FFFFFF;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .card {
            background-color: #1C132E;
            border: 1px solid #9D4EDD;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(157, 78, 221, 0.15);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 32px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #FFFFFF;
            text-shadow: 0 0 15px rgba(157, 78, 221, 0.6);
            margin: 0;
        }
        .logo span {
            color: #FF4DAD;
        }
        h1 {
            font-size: 24px;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 16px;
            color: #FFFFFF;
            text-align: center;
        }
        p {
            font-size: 16px;
            line-height: 1.6;
            color: #C3B4D6;
            margin-top: 0;
            margin-bottom: 24px;
        }
        .code-box {
            background-color: #0F0A1A;
            border: 2px dashed #9D4EDD;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        .code-text {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 6px;
            color: #9D4EDD;
            margin: 0;
        }
        .footer {
            margin-top: 40px;
            border-top: 1px solid #2D2045;
            padding-top: 24px;
            text-align: center;
        }
        .signature {
            font-size: 14px;
            color: #8C7B9E;
            line-height: 1.6;
            margin: 0;
        }
        .signature strong {
            color: #FF4DAD;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h2 class="logo">Glimpse<span>.</span></h2>
            </div>
            <h1>Password Reset Request</h1>
            <p>Hello, {{ $name }}!</p>
            <p>We received a request to reset the password for your Glimpse account. Please enter the password reset code below in the app to set up a new password:</p>
            
            <div class="code-box">
                <div class="code-text">{{ $otp }}</div>
            </div>
            
            <p style="font-size: 14px; color: #8C7B9E; text-align: center;">This code is valid for 15 minutes. If you did not request a password reset, please ignore this email and your password will remain unchanged.</p>
            
            <div class="footer">
                <p class="signature">
                    Lovingly crafted for your connection,<br>
                    <strong>Lovinpeace</strong>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
