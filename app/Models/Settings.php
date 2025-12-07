<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory;
    
     protected $table = 'settings';
    
    protected $fillable = [
        'name',
        'logo',
        'email',
        'phone',
        'address',
    ];
    
    // public static function getSettings()
    // {
    //     return self::first() ?? self::create([
    //         'name' => 'Mon Entreprise',
    //     ]);
    // }
}
    
