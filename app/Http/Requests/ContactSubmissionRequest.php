<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'form_type' => 'required|in:career,pitch,produce',
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string|min:10|max:5000',
            'recaptcha_token' => 'required|string',
        ];

        // Dynamic rules based on form type
        if ($this->form_type === 'career') {
            $rules['subject'] = 'required|string|max:255';
            $rules['portfolio_link'] = 'nullable|url|max:2000';
        }

        if ($this->form_type === 'pitch') {
            $rules['document_link'] = 'nullable|url|max:2000';
        }

        if ($this->form_type === 'produce') {
            $rules['company_name'] = 'required|string|min:2|max:255';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'form_type.required' => 'Form type is required.',
            'form_type.in' => 'Invalid form type selected.',
            'name.required' => 'Name is required.',
            'name.min' => 'Name must be at least 2 characters.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'message.required' => 'Message is required.',
            'message.min' => 'Message must be at least 10 characters.',
            'subject.required' => 'Subject is required for career applications.',
            'company_name.required' => 'Company name is required for produce inquiries.',
            'portfolio_link.url' => 'Portfolio link must be a valid URL.',
            'document_link.url' => 'Document link must be a valid URL.',
            'recaptcha_token.required' => 'reCAPTCHA verification is required.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'form_type' => 'form type',
            'portfolio_link' => 'portfolio link',
            'document_link' => 'document link',
            'company_name' => 'company name',
            'recaptcha_token' => 'reCAPTCHA token',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize input data
        $this->merge([
            'name' => strip_tags(trim($this->name ?? '')),
            'email' => strtolower(trim($this->email ?? '')),
            'message' => strip_tags(trim($this->message ?? '')),
            'subject' => strip_tags(trim($this->subject ?? '')),
            'company_name' => strip_tags(trim($this->company_name ?? '')),
        ]);
    }
}
