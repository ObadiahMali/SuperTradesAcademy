<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Intake extends Model
{
    protected $fillable = ['name', 'start_date', 'end_date', 'active'];
protected $casts = [
    'start_date' => 'date',
];
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}