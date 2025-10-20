<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thank you for your Career Application</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            color: #667eea;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .highlight {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 25px;
            border-radius: 8px;
            margin: 25px 0;
            border-left: 5px solid #667eea;
        }
        .highlight h3 {
            margin-top: 0;
            color: #667eea;
            font-size: 20px;
        }
        .submission-summary {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
            border: 2px solid #e9ecef;
        }
        .submission-summary h4 {
            margin-top: 0;
            color: #495057;
            font-size: 16px;
        }
        .submission-summary ul {
            list-style: none;
            padding: 0;
            margin: 15px 0 0 0;
        }
        .submission-summary li {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .submission-summary li:last-child {
            border-bottom: none;
        }
        .submission-summary strong {
            color: #667eea;
            display: inline-block;
            min-width: 140px;
        }
        .timeline {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .timeline h4 {
            margin-top: 0;
            color: #856404;
        }
        .timeline ol {
            margin: 10px 0 0 20px;
            padding: 0;
        }
        .timeline li {
            margin-bottom: 8px;
            color: #856404;
        }
        .footer {
            margin-top: 30px;
            padding: 25px 30px;
            background-color: #f8f9fa;
            text-align: center;
            color: #6c757d;
            border-top: 3px solid #667eea;
        }
        .footer strong {
            color: #495057;
        }
        .footer small {
            display: block;
            margin-top: 15px;
            font-size: 12px;
            color: #868e96;
        }
        .cta-button {
            display: inline-block;
            background-color: #667eea;
            color: #fff;
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            margin: 20px 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Application Received!</h1>
            <p>Your career application is now under review</p>
        </div>

        <div class="content">
            <p class="greeting">Dear {{ $submission->name }},</p>

            <p>Thank you for your interest in joining the Parallel Studio team! We're excited to review your career application and learn more about your talents and experience.</p>

            <div class="highlight">
                <h3>📋 What's Next?</h3>
                <p>Our HR team will carefully review your application, including your portfolio and qualifications. We'll evaluate how your skills align with our current opportunities and company culture.</p>
            </div>

            <div class="timeline">
                <h4>⏱️ Application Timeline:</h4>
                <ol>
                    <li><strong>Review:</strong> 2-3 business days for initial screening</li>
                    <li><strong>Response:</strong> We'll contact qualified candidates for interviews</li>
                    <li><strong>Process:</strong> Selected candidates will proceed to next steps</li>
                </ol>
            </div>

            <div class="submission-summary">
                <h4>Your Application Summary:</h4>
                <ul>
                    <li><strong>Application Type:</strong> Career Opportunity</li>
                    <li><strong>Position Interest:</strong> {{ $submission->subject ?? 'General Application' }}</li>
                    <li><strong>Submitted:</strong> {{ $submission->created_at->format('F j, Y \a\t g:i A') }}</li>
                    <li><strong>Reference ID:</strong> #{{ $submission->id }}</li>
                    @if($submission->phone)
                    <li><strong>Contact Phone:</strong> {{ $submission->phone }}</li>
                    @endif
                </ul>
            </div>

            <p><strong>Important:</strong> Please ensure your email is set up to receive messages from us, and check your spam folder periodically. If you need to update your application or add additional materials, feel free to reply to this email.</p>

            <p style="margin-top: 30px;">We appreciate your interest in Parallel Studio and wish you the best in your application process!</p>

            <p>Best regards,<br>
            <strong>Parallel Studio HR Team</strong></p>
        </div>

        <div class="footer">
            <p>
                <strong>Parallel Studio</strong><br>
                Email: hr@parallelstudio.asia<br>
                Website: www.parallelstudio.asia
            </p>
            <small>This is an automated confirmation. Please reply to this email if you need to provide additional information about your application.</small>
        </div>
    </div>
</body>
</html>
