<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactSubmissionRequest;
use App\Models\ContactSubmission;
use App\Services\RecaptchaService;
use App\Mail\ContactSubmissionNotification;
use App\Mail\ContactSubmissionConfirmation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class ContactSubmissionController extends Controller
{
    private RecaptchaService $recaptchaService;

    public function __construct(RecaptchaService $recaptchaService)
    {
        $this->recaptchaService = $recaptchaService;
    }

    /**
     * Store a new contact submission
     */
    public function store(ContactSubmissionRequest $request): JsonResponse
    {
        try {
            // Rate limiting
            $key = 'contact-submission:' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 5)) { // 5 attempts per hour
                $seconds = RateLimiter::availableIn($key);
                return response()->json([
                    'success' => false,
                    'message' => "Too many attempts. Please try again in {$seconds} seconds.",
                ], 429);
            }

            RateLimiter::hit($key, 3600); // 1 hour window

            // Verify reCAPTCHA
            $recaptchaResult = $this->recaptchaService->validate(
                $request->recaptcha_token,
                $request->ip()
            );

            if ($recaptchaResult !== null) {
                return response()->json([
                    'success' => false,
                    'message' => $recaptchaResult,
                ], 422);
            }

            // Prepare submission data
            $submissionData = [
                'form_type' => $request->form_type,
                'name' => $request->name,
                'email' => $request->email,
                'message' => $request->message,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'recaptcha_token' => $request->recaptcha_token,
                'status' => 'pending',
            ];

            // Add form-specific fields
            if ($request->form_type === 'career') {
                $submissionData['subject'] = $request->subject;
                $submissionData['portfolio_link'] = $request->portfolio_link;
            } elseif ($request->form_type === 'pitch') {
                $submissionData['document_link'] = $request->document_link;
            } elseif ($request->form_type === 'produce') {
                $submissionData['company_name'] = $request->company_name;
            }

            // Create submission record
            $submission = ContactSubmission::create($submissionData);

            // Send emails asynchronously (in background if queue is configured)
            try {
                // Send notification to admin
                Mail::to(config('mail.contact_email', 'contact@parallelstudio.asia'))
                    ->send(new ContactSubmissionNotification($submission));

                // Send confirmation to user
                Mail::to($submission->email)
                    ->send(new ContactSubmissionConfirmation($submission));

                Log::info('Contact submission emails sent successfully', [
                    'submission_id' => $submission->id,
                    'form_type' => $submission->form_type,
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to send contact submission emails', [
                    'submission_id' => $submission->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't fail the request if email sending fails
            }

            // Clear rate limiter on successful submission
            RateLimiter::clear($key);

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your submission! We will get back to you soon.',
                'submission_id' => $submission->id,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Contact submission failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['recaptcha_token']),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again later.',
            ], 500);
        }
    }

    /**
     * Get all contact submissions (for admin)
     */
    public function index(Request $request): JsonResponse
    {
        $query = ContactSubmission::query()->recent();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('message', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('subject', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('company_name', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filter by form type
        if ($request->has('form_type') && $request->form_type) {
            $query->byFormType($request->form_type);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->byStatus($request->status);
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort field
        $allowedSortFields = ['created_at', 'name', 'email', 'form_type', 'status', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = min($request->get('per_page', 20), 100); // Max 100 items per page
        $submissions = $query->paginate($perPage);

        // Add statistics
        $stats = [
            'total' => ContactSubmission::count(),
            'pending' => ContactSubmission::byStatus('pending')->count(),
            'reviewed' => ContactSubmission::byStatus('reviewed')->count(),
            'responded' => ContactSubmission::byStatus('responded')->count(),
            'archived' => ContactSubmission::byStatus('archived')->count(),
            'by_form_type' => [
                'career' => ContactSubmission::byFormType('career')->count(),
                'pitch' => ContactSubmission::byFormType('pitch')->count(),
                'produce' => ContactSubmission::byFormType('produce')->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $submissions->items(),
            'pagination' => [
                'current_page' => $submissions->currentPage(),
                'last_page' => $submissions->lastPage(),
                'per_page' => $submissions->perPage(),
                'total' => $submissions->total(),
                'from' => $submissions->firstItem(),
                'to' => $submissions->lastItem(),
                'has_more_pages' => $submissions->hasMorePages(),
            ],
            'stats' => $stats,
            'filters' => [
                'search' => $request->search,
                'form_type' => $request->form_type,
                'status' => $request->status,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
                'per_page' => $perPage,
            ]
        ]);
    }

    /**
     * Get a specific contact submission (for admin)
     */
    public function show(ContactSubmission $submission): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $submission,
        ]);
    }

    /**
     * Update submission status (for admin)
     */
    public function update(Request $request, ContactSubmission $submission): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,reviewed,responded,archived',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $submission->update([
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
            'responded_at' => $request->status === 'responded' ? now() : $submission->responded_at,
            'responded_by' => $request->status === 'responded' ? auth()->id() : $submission->responded_by,
        ]);

        Log::info('Contact submission updated', [
            'submission_id' => $submission->id,
            'updated_by' => auth()->id(),
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Submission updated successfully.',
            'data' => $submission->fresh(),
        ]);
    }

    /**
     * Delete a submission (for admin)
     */
    public function destroy(ContactSubmission $submission): JsonResponse
    {
        Log::info('Contact submission deleted', [
            'submission_id' => $submission->id,
            'deleted_by' => auth()->id(),
        ]);

        $submission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Submission deleted successfully.',
        ]);
    }

    /**
     * Bulk actions for submissions (for admin)
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:delete,update_status,archive',
            'submission_ids' => 'required|array|min:1',
            'submission_ids.*' => 'exists:contact_submissions,id',
            'status' => 'required_if:action,update_status|in:pending,reviewed,responded,archived',
        ]);

        $submissions = ContactSubmission::whereIn('id', $request->submission_ids);
        $count = $submissions->count();

        switch ($request->action) {
            case 'delete':
                $submissions->delete();
                $message = "Successfully deleted {$count} submissions.";
                break;
                
            case 'update_status':
                $submissions->update([
                    'status' => $request->status,
                    'responded_at' => $request->status === 'responded' ? now() : null,
                    'responded_by' => $request->status === 'responded' ? auth()->id() : null,
                ]);
                $message = "Successfully updated status for {$count} submissions.";
                break;
                
            case 'archive':
                $submissions->update(['status' => 'archived']);
                $message = "Successfully archived {$count} submissions.";
                break;
        }

        Log::info('Bulk action performed on contact submissions', [
            'action' => $request->action,
            'count' => $count,
            'performed_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }
}
