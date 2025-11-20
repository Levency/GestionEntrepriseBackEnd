<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_id',
        'check_in',
        'check_out',
        'status',
        'notes',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function isAbsent()
    {
        return $this->status === 'absent';
    }

    public function isPresent()
    {
        return $this->status === 'present';
    }

    public function isLate()
    {
        return $this->status === 'late';
    }

    // Additional methods or relationships can be added here
    
}
