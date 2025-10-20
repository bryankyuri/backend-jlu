<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_type',
        'name',
        'email',
        'message',
        'subject',
        'portfolio_link',
        'document_link',
        'company_name',
        'recaptcha_token',
        'recaptcha_score',
        'ip_address',
        'user_agent',
        'status',
        'admin_notes',
        'responded_at',
        'responded_by',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'recaptcha_score' => 'decimal:2',
    ];

    const FORM_TYPES = [
        'career' => 'Career',
        'pitch' => 'Pitch',
        'produce' => 'Produce',
    ];

    const STATUSES = [
        'pending' => 'Pending',
        'reviewed' => 'Reviewed',
        'responded' => 'Responded',
        'archived' => 'Archived',
    ];

    // Accessors
    public function getFormTypeDisplayAttribute()
    {
        return self::FORM_TYPES[$this->form_type] ?? $this->form_type;
    }

    public function getStatusDisplayAttribute()
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    // Scopes
    public function scopeByFormType($query, $formType)
    {
        return $query->where('form_type', $formType);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
