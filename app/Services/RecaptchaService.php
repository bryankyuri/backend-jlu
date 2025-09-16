<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RecaptchaService
{
    private $secretKey;

    public function __construct()
    {
        $this->secretKey = config('captcha.secret');
    }

    /**
     * Verify reCAPTCHA token
     */
    public function verify($token, $ip = null)
    {
        if (empty($token)) {
            return false;
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $this->secretKey,
            'response' => $token,
            'remoteip' => $ip,
        ]);

        $result = $response->json();

        return $result['success'] ?? false;
    }

    /**
     * Validate reCAPTCHA and return error message if invalid
     */
    public function validate($token, $ip = null)
    {
        if (!$this->verify($token, $ip)) {
            return 'reCAPTCHA verification failed. Please try again.';
        }

        return null;
    }
}