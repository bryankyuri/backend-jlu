<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thank you for contacting Parallel Studio</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #000;
            color: #fff;
            padding: 30px 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        .content {
            padding: 20px;
        }
        .highlight {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #000;
        }
        .submission-summary {
            background-color: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Thank You!</h1>
        <p>We've received your {{ ucfirst($submission->form_type) }} submission</p>
    </div>

    <div class="content">
        <p>Dear {{ $submission->name }},</p>

        <p>Thank you for reaching out to Parallel Studio. We have successfully received your submission and will get back to you soon.</p>

        <div class="highlight">
            <h3>What happens next?</h3>
            <p>Our team will review your submission carefully and respond within 1-2 business days. We appreciate your interest in working with us.</p>
        </div>

        <div class="submission-summary">
            <h4>Your Submission Summary:</h4>
            <ul>
                <li><strong>Form Type:</strong> {{ ucfirst($submission->form_type) }}</li>
                <li><strong>Submitted:</strong> {{ $submission->created_at->format('F j, Y \a\t g:i A') }}</li>
                <li><strong>Reference ID:</strong> #{{ $submission->id }}</li>
            </ul>
        </div>

        <p>If you have any urgent questions or need to add additional information, please reply to this email or contact us directly.</p>

        <p>Best regards,<br>
        <strong>The Parallel Studio Team</strong></p>
    </div>

    <div class="footer">
        <p>
            <strong>Parallel Studio</strong><br>
            Email: contact@parallelstudio.asia<br>
        </p>
        <p><small>This is an automated confirmation email. Please do not reply to this message unless you have additional information to provide.</small></p>
    </div>
</body>
</html>