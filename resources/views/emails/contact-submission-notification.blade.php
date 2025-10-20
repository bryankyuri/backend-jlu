<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Contact Form Submission</title>
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
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
        }
        .form-details {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .field {
            margin: 10px 0;
        }
        .label {
            font-weight: bold;
            color: #555;
        }
        .value {
            margin-top: 5px;
        }
        .message-content {
            background-color: #f0f0f0;
            padding: 15px;
            border-left: 4px solid #000;
            margin: 10px 0;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>New Contact Form Submission</h1>
    </div>

    <div class="content">
        <p>A new <strong>{{ ucfirst($submission->form_type) }}</strong> form submission has been received on your website.</p>

        <div class="form-details">
            <div class="field">
                <div class="label">Form Type:</div>
                <div class="value">{{ ucfirst($submission->form_type) }}</div>
            </div>

            <div class="field">
                <div class="label">Name:</div>
                <div class="value">{{ $submission->name }}</div>
            </div>

            <div class="field">
                <div class="label">Email:</div>
                <div class="value">{{ $submission->email }}</div>
            </div>

            @if($submission->subject)
            <div class="field">
                <div class="label">Subject:</div>
                <div class="value">{{ $submission->subject }}</div>
            </div>
            @endif

            @if($submission->company_name)
            <div class="field">
                <div class="label">Company Name:</div>
                <div class="value">{{ $submission->company_name }}</div>
            </div>
            @endif

            @if($submission->portfolio_link)
            <div class="field">
                <div class="label">Portfolio Link:</div>
                <div class="value"><a href="{{ $submission->portfolio_link }}">{{ $submission->portfolio_link }}</a></div>
            </div>
            @endif

            @if($submission->document_link)
            <div class="field">
                <div class="label">Document Link:</div>
                <div class="value"><a href="{{ $submission->document_link }}">{{ $submission->document_link }}</a></div>
            </div>
            @endif

            <div class="field">
                <div class="label">Message:</div>
                <div class="message-content">{{ $submission->message }}</div>
            </div>
        </div>

        <div style="margin-top: 20px; padding: 15px; background-color: #e9e9e9; border-radius: 5px;">
            <strong>Submission Details:</strong><br>
            <small>
                Submitted: {{ $submission->created_at->format('F j, Y \a\t g:i A') }}<br>
                IP Address: {{ $submission->ip_address }}<br>
                Submission ID: #{{ $submission->id }}
            </small>
        </div>
    </div>

    <div class="footer">
        <p>This email was automatically generated from your Parallel Studio website contact form.</p>
    </div>
</body>
</html>