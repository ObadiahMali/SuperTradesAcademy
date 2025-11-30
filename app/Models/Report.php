<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    // If your table name is not "reports", set it:
    // protected $table = 'your_reports_table';

    protected $fillable = [
        'title',
        'type',
        'generated_by',
        'data',      // json or text
        'created_at',
        'updated_at',
    ];

    // If you store JSON payloads:
    // protected $casts = ['data' => 'array'];
}