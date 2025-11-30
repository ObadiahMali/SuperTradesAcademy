<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
  'intake_id','first_name','last_name','phone','email','plan_key',
  'course_fee','currency',
  'address_line1','address_line2','city','region','postal_code','country',
  'email_verification_token','email_verification_sent_at','email_verified_at'
];

protected $dates = ['email_verification_sent_at','email_verified_at'];

    protected $casts = [
        'course_fee' => 'decimal:2',
    ];

    /**
     * Student belongs to an intake.
     */
    public function intake()
    {
        return $this->belongsTo(Intake::class);
    }

    /**
     * Student has many payments.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Accessor for full name.
     */
    public function getFullNameAttribute()
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    /**
     * Mutator to keep phone_full in sync if phone_country/phone_dial/phone change.
     * Optionally call $student->save() after changing phone parts.
     */
    public function setPhoneFullAttribute($value)
    {
        $this->attributes['phone_full'] = $value;
    }

    /**
     * Helper to build phone_full on demand.
     */
    public function buildPhoneFull(): string
    {
        $dial = $this->phone_dial ?? '';
        $phone = $this->phone ?? '';
        if ($dial && $phone) {
            return "{$dial}{$phone}";
        }
        return $this->phone_full ?? ($phone ?: '');
    }
}