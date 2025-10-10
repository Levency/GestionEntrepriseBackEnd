<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_id',
        'period', // e.g., "2024-01" for January 2024
        'gross_salary',
        'net_salary',
        'tax',
        'discount',
        'status',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

}
