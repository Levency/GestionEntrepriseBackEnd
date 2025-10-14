<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'position',
        'department_id',
        'salary',
        'status'
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }
    public function department()
    {
        return $this->belongsTo(Departement::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
