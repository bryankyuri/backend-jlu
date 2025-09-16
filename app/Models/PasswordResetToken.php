<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PasswordResetToken extends Model
{
    protected $fillable = [
        'email',
        'token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Check if token has expired
     */
    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    /**
     * Create a new password reset token
     */
    public static function createToken($email)
    {
        // Delete any existing tokens for this email
        self::where('email', $email)->delete();

        // Generate a secure token
        $token = bin2hex(random_bytes(32));

        // Create new token (expires in 1 hour)
        $record = self::create([
            'email' => $email,
            'token' => hash('sha256', $token), // Store hashed version
            'expires_at' => Carbon::now()->addHour(),
        ]);

        // Return the plain token for email sending
        return ['record' => $record, 'plain_token' => $token];
    }

    /**
     * Verify token and get the record
     */
    public static function verifyToken($email, $token)
    {
        $hashedToken = hash('sha256', $token);
        
        $resetToken = self::where('email', $email)
            ->where('token', $hashedToken)
            ->first();

        if (!$resetToken || $resetToken->isExpired()) {
            return null;
        }

        return $resetToken;
    }
}
