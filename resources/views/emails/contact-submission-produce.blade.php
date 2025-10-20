<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thank you for your Production Inquiry</title>
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
            background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
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
            color: #ee0979;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .highlight {
            background: linear-gradient(135deg, #ffe5f0 0%, #ffd4e5 100%);
            padding: 25px;
            border-radius: 8px;
            margin: 25px 0;
            border-left: 5px solid #ee0979;
        }
        .highlight h3 {
            margin-top: 0;
            color: #ee0979;
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
            color: #ee0979;
            display: inline-block;
            min-width: 140px;
        }
        .services-box {
            background-color: #e7f3ff;
            border: 2px solid #2196f3;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .services-box h4 {
            margin-top: 0;
            color: #1565c0;
        }
        .services-box ul {
            margin: 10px 0 0 20px;
            padding: 0;
            list-style: disc;
        }
        .services-box li {
            margin-bottom: 6px;
            color: #1565c0;
        }
        .process-timeline {
            background-color: #fff9e6;
            border: 2px solid #ffcc00;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .process-timeline h4 {
            margin-top: 0;
            color: #856404;
        }
        .process-timeline ol {
            margin: 10px 0 0 20px;
            padding: 0;
        }
        .process-timeline li {
            margin-bottom: 8px;
            color: #856404;
        }
        .footer {
            margin-top: 30px;
            padding: 25px 30px;
            background-color: #f8f9fa;
            text-align: center;
            color: #6c757d;
            border-top: 3px solid #ee0979;
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
            <h1>🎥 Production Inquiry Received!</h1>
            <p>Let's bring your vision to life together</p>
        </div>

        <div class="content">
            <p class="greeting">Dear {{ $submission->name }},</p>

            <p>Thank you for reaching out to Parallel Studio for your production needs! We're excited to learn about your project and explore how we can help bring your creative vision to reality.</p>

            <div class="highlight">
                <h3>🎬 Next Steps</h3>
                <p>Our production team will review your requirements and reach out to discuss project details, timelines, budget considerations, and creative approach. We'll work with you to develop a comprehensive production plan.</p>
            </div>

            <div class="services-box">
                <h4>🌟 Our Production Services Include:</h4>
                <ul>
                    <li>Pre-production planning and creative development</li>
                    <li>Full production crew and equipment</li>
                    <li>Post-production and editing</li>
                    <li>Visual effects and motion graphics</li>
                    <li>Color grading and sound design</li>
                    <li>Final delivery in multiple formats</li>
                </ul>
            </div>

            <div class="process-timeline">
                <h4>📋 Production Process Timeline:</h4>
                <ol>
                    <li><strong>Initial Consultation:</strong> 1-2 business days to schedule a discovery call</li>
                    <li><strong>Proposal:</strong> We'll provide a detailed quote and production timeline</li>
                    <li><strong>Planning:</strong> Collaborative pre-production and creative development</li>
                    <li><strong>Production:</strong> Filming and content creation</li>
                    <li><strong>Post-Production:</strong> Editing, effects, and final delivery</li>
                </ol>
            </div>

            <div class="submission-summary">
                <h4>Your Inquiry Summary:</h4>
                <ul>
                    <li><strong>Inquiry Type:</strong> Production Services</li>
                    <li><strong>Project:</strong> {{ $submission->subject ?? 'Production Request' }}</li>
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

            <p><strong>Prepare for Our Call:</strong> To make our initial consultation productive, consider having ready: project objectives, target audience, timeline expectations, budget range, and any reference materials or inspiration.</p>

            <p style="margin-top: 30px;">We're looking forward to partnering with you on this production and creating something exceptional together!</p>

            <p>Best regards,<br>
            <strong>The Parallel Studio Production Team</strong></p>
        </div>

        <div class="footer">
            <p>
                <strong>Parallel Studio</strong><br>
                Email: production@parallelstudio.asia<br>
                Website: www.parallelstudio.asia
            </p>
            <small>This is an automated confirmation. Please reply to this email if you need to provide additional information about your production needs.</small>
        </div>
    </div>
</body>
</html>
