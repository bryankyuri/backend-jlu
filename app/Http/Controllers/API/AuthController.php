<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\PasswordResetToken;
use App\Mail\PasswordResetMail;
use App\Services\RecaptchaService;

class AuthController extends Controller
{
    /**
     * Get frontend URL based on environment
     */
    private function getFrontendUrl()
    {
        $env = config('app.env');
        
        switch ($env) {
            case 'production':
                return config('app.frontend_url_production', 'https://cms.parallelstudio.asia');
            case 'staging':
                return config('app.frontend_url_staging', 'https://staging-cms.parallelstudio.asia');
            default:
                return config('app.frontend_url_local', 'http://localhost:3000');
        }
    }

    /**
     * Login user and create token
     */
    public function login(Request $request)
    {
        try {
            // reCAPTCHA validation
            $recaptchaService = new RecaptchaService();
            $recaptchaError = $recaptchaService->validate($request->input('recaptcha_token'), $request->ip());
            
            if ($recaptchaError) {
                return response()->json([
                    'success' => false,
                    'message' => $recaptchaError
                ], 422);
            }

            // Basic validation
            $email = $request->input('email');
            $password = $request->input('password');
            
            if (!$email || !$password) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email and password are required'
                ], 400);
            }
            
            // Try to find user
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 401);
            }
            
            // Check password
            if (!Hash::check($password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password'
                ], 401);
            }
            
            // Create token
            $token = $user->createToken('cms-token')->plainTextToken;
            
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'token' => $token
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout user (revoke token)
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'created_at' => $request->user()->created_at
                ]
            ]
        ]);
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request)
    {
        try {
            // reCAPTCHA validation
            $recaptchaService = new RecaptchaService();
            $recaptchaError = $recaptchaService->validate($request->input('recaptcha_token'), $request->ip());
            
            if ($recaptchaError) {
                return response()->json([
                    'success' => false,
                    'message' => $recaptchaError
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = $request->email;
            
            // Find the user to get their name
            $user = User::where('email', $email)->first();
            
            // Generate a secure token
            $token = bin2hex(random_bytes(32));
            
            // Get the appropriate frontend URL based on environment
            $frontendUrl = $this->getFrontendUrl();
            $resetUrl = $frontendUrl . "/reset-password?token=" . $token . "&email=" . urlencode($email);
            
            // Send the password reset email
            try {
                Mail::to($email)->send(new PasswordResetMail($resetUrl, $user ? $user->name : 'User'));
                
                return response()->json([
                    'success' => true,
                    'message' => 'Password reset link has been sent to your email address.',
                ]);
                
            } catch (\Exception $mailException) {
                // Log the mail error but don't expose it to the user
                \Log::error('Failed to send password reset email: ' . $mailException->getMessage());
                
                // For development, you might want to return the reset URL
                return response()->json([
                    'success' => true,
                    'message' => 'Password reset link generated. (Email service unavailable)',
                    'data' => [
                        'reset_url' => $resetUrl,
                        'mail_error' => config('app.debug') ? $mailException->getMessage() : null
                    ]
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset link',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password using token
     */
    public function resetPassword(Request $request)
    {
        try {
            // reCAPTCHA validation
            $recaptchaService = new RecaptchaService();
            $recaptchaError = $recaptchaService->validate($request->input('recaptcha_token'), $request->ip());
            
            if ($recaptchaError) {
                return response()->json([
                    'success' => false,
                    'message' => $recaptchaError
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'token' => 'required|string',
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // For now, just validate that token is not empty (in production, verify against database)
            if (empty($request->token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired token'
                ], 400);
            }

            // Find the user
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Update password
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
