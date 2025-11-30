<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    // If your table is named "employees", you don’t need this.
    // If it’s something else, specify:
    // protected $table = 'your_table_name';

    // Add fillable fields if you want mass assignment:
    protected $fillable = [
        'name',
        'position',
        'email',
        // add other columns here
    ];
}