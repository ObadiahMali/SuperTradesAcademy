<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'key',
        'label',
        'price',
        'currency',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];
}