<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_name',
        'email',
        'password',
        'employee_id',
        'role',
        'is_active',  
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function depenses()
    {
        return $this->hasMany(Depense::class);
    }

    public function stockMouvements()
    {
        return $this->hasMany(StockMouvement::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }
    public function isManager()
    {
        return $this->role === 'manager';
    }
    public function isCashier()
    {
        return $this->role === 'cashier';
    }
    public function isEmployee()
    {
        return $this->role === 'employee';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
}
