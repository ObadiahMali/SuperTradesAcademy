<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'intake_id',
        'amount',
        'currency',
        'amount_converted',
        'converted_currency',
        'method',
        'reference',
        'paid_at',
        'receipt_number',
        'verification_hash',
        'plan_key',
        'created_by',
        'student_name', // include if you store denormalized name
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'amount_converted' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    // Relation to student
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // Relation to intake
    public function intake()
    {
        return $this->belongsTo(Intake::class);
    }

    // Creator relation
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user()
    {
        return $this->creator();
    }

    // Accessor used in Blade: $pay->display_student_name
    public function getDisplayStudentNameAttribute()
    {
        return $this->student_name
            ?? $this->student?->name
            ?? null;
    }
}