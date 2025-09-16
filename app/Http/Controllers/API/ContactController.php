<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Store a new contact message
     */
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'message' => 'required|string|max:2000',
            'subject' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        
        try {
            // In real implementation, you'd save to database and send email
            // For now, we'll just simulate success
            
            // Save to database (uncomment when you have a contacts table)
            // $contact = Contact::create($data);
            
            // Send notification email (configure mail settings in .env)
            // Mail::to('info@parallelstudio.asia')->send(new ContactFormMail($data));
            
            // For now, just log the data
            \Log::info('Contact form submission', $data);
            
            return response()->json([
                'success' => true,
                'message' => 'Thank you for your message! We will get back to you soon.',
                'data' => [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'submitted_at' => now()->toISOString()
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Contact form error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Sorry, there was an error processing your message. Please try again.',
            ], 500);
        }
    }
}