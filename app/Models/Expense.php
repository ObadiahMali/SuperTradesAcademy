<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'notes',
        'vendor',
        'description',
        'category',
        'type',
        'spent_at',   // existing column in your DB
        'currency',
        'amount',
        'paid',
        'paid_at',
        'created_by',
    ];

    protected $casts = [
        'spent_at' => 'datetime',
        'paid_at'  => 'datetime',
        'paid'     => 'boolean',
        'amount'   => 'decimal:2',
    ];
}