<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'intake_id',
        'first_name',
        'last_name',
        'phone',
        'phone_country_code',
        'phone_full',
        'phone_display',
        'phone_dial',
        'email',
        'plan_key',
        'course_fee',
        'currency',
        'address_line1',
        'address_line2',
        'city',
        'region',
        'postal_code',
        'country',
        'email_verification_token',
        'email_verification_sent_at',
        'email_verified_at',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'course_fee' => 'decimal:2',
        'email_verification_sent_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    /**
     * Student belongs to an intake.
     */
    public function intake(): BelongsTo
    {
        return $this->belongsTo(Intake::class);
    }

    /**
     * Student has many payments.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Accessor for full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    /**
     * Mutator to keep phone_full in sync if phone parts are set directly.
     */
    public function setPhoneFullAttribute($value): void
    {
        $this->attributes['phone_full'] = $value;
    }

    /**
     * Helper to build phone_full on demand.
     * Returns E.164-like string (e.g. +256712345678) if possible.
     */
    public function buildPhoneFull(): string
    {
        $dial = $this->phone_dial ?? $this->phone_country_code ?? '';
        $phone = $this->phone ?? '';

        // Ensure dial starts with plus for display if it doesn't already
        if ($dial && !str_starts_with((string) $dial, '+')) {
            $dial = '+' . $dial;
        }

        if ($dial && $phone) {
            // return concatenated E.164-like value without spaces
            return preg_replace('/\s+/', '', $dial . $phone);
        }

        return $this->phone_full ?? ($phone ?: '');
    }
}