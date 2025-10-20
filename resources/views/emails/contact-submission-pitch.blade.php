<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thank you for your Project Pitch</title>
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
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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
            color: #11998e;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .highlight {
            background: linear-gradient(135deg, #e0f7f4 0%, #c3f4e8 100%);
            padding: 25px;
            border-radius: 8px;
            margin: 25px 0;
            border-left: 5px solid #11998e;
        }
        .highlight h3 {
            margin-top: 0;
            color: #11998e;
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
            color: #11998e;
            display: inline-block;
            min-width: 140px;
        }
        .process-box {
            background-color: #fff9e6;
            border: 2px solid #ffcc00;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .process-box h4 {
            margin-top: 0;
            color: #856404;
        }
        .process-box ol {
            margin: 10px 0 0 20px;
            padding: 0;
        }
        .process-box li {
            margin-bottom: 8px;
            color: #856404;
        }
        .footer {
            margin-top: 30px;
            padding: 25px 30px;
            background-color: #f8f9fa;
            text-align: center;
            color: #6c757d;
            border-top: 3px solid #11998e;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💡 Pitch Received!</h1>
            <p>We're excited to learn about your project idea</p>
        </div>

        <div class="content">
            <p class="greeting">Dear {{ $submission->name }},</p>

            <p>Thank you for pitching your project to Parallel Studio! We're always looking for exciting creative opportunities and innovative ideas to bring to life.</p>

            <div class="highlight">
                <h3>🎬 What Happens Next?</h3>
                <p>Our creative team will review your pitch carefully, evaluating the project scope, creative vision, and how it aligns with our expertise in visual storytelling and production.</p>
            </div>

            <div class="process-box">
                <h4>📅 Review Process:</h4>
                <ol>
                    <li><strong>Initial Review:</strong> 2-3 business days to assess your pitch</li>
                    <li><strong>Evaluation:</strong> Our team discusses feasibility and creative approach</li>
                    <li><strong>Response:</strong> We'll contact you to discuss next steps or schedule a meeting</li>
                    <li><strong>Collaboration:</strong> If aligned, we'll explore partnership opportunities</li>
                </ol>
            </div>

            <div class="submission-summary">
                <h4>Your Pitch Summary:</h4>
                <ul>
                    <li><strong>Submission Type:</strong> Project Pitch</li>
                    <li><strong>Project Title:</strong> {{ $submission->subject ?? 'Creative Project' }}</li>
                    <li><strong>Submitted:</strong> {{ $submission->created_at->format('F j, Y \a\t g:i A') }}</li>
                    <li><strong>Reference ID:</strong> #{{ $submission->id }}</li>
                    @if($submission->company)
                    <li><strong>Organization:</strong> {{ $submission->company }}</li>
                    @endif
                    @if($submission->phone)
                    <li><strong>Contact Phone:</strong> {{ $submission->phone }}</li>
                    @endif
                </ul>
            </div>

            <p><strong>Additional Materials:</strong> If you have supporting documents, mood boards, references, or other materials you'd like to share, feel free to reply to this email with attachments.</p>

            <p style="margin-top: 30px;">We're looking forward to exploring your creative vision and potentially collaborating on something amazing!</p>

            <p>Best regards,<br>
            <strong>The Parallel Studio Creative Team</strong></p>
        </div>

        <div class="footer">
            <p>
                <strong>Parallel Studio</strong><br>
                Email: projects@parallelstudio.asia<br>
                Website: www.parallelstudio.asia
            </p>
            <small>This is an automated confirmation. Please reply to this email if you need to provide additional information about your pitch.</small>
        </div>
    </div>
</body>
</html>
