<?php

namespace App\Mail;

use App\Models\ContactSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactSubmissionConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public ContactSubmission $submission;

    /**
     * Create a new message instance.
     */
    public function __construct(ContactSubmission $submission)
    {
        $this->submission = $submission;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Customize subject based on form type
        $subjects = [
            'career' => 'Thank you for your Career Application - Parallel Studio',
            'pitch' => 'Thank you for your Project Pitch - Parallel Studio',
            'produce' => 'Thank you for your Production Inquiry - Parallel Studio',
        ];

        $subject = $subjects[$this->submission->form_type] ?? 'Thank you for contacting Parallel Studio';

        return new Envelope(
            subject: $subject,
            from: config('mail.from.address', 'noreply@parallelstudio.asia'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Select template based on form type
        $templates = [
            'career' => 'emails.contact-submission-career',
            'pitch' => 'emails.contact-submission-pitch',
            'produce' => 'emails.contact-submission-produce',
        ];

        $view = $templates[$this->submission->form_type] ?? 'emails.contact-submission-confirmation';

        return new Content(
            view: $view,
            with: [
                'submission' => $this->submission,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
