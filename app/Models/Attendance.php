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

    // Additional methods or relationships can be added here
    
}
