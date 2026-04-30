<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffMember extends Model
{
    use HasFactory;

    const CATEGORIES = [
        'baby'      => 'Baby Hand',
        'u7'        => 'U7',
        'u9'        => 'U9',
        'u11_m'     => 'U11 Masculins',
        'u11_f'     => 'U11 Féminines',
        'u13_m'     => 'U13 Masculins',
        'u13_f'     => 'U13 Féminines',
        'u15_m'     => 'U15 Masculins',
        'u15_f'     => 'U15 Féminines',
        'u18_m'     => 'U18 Masculins',
        'u18_f'     => 'U18 Féminines',
        'seniors_m' => 'Seniors Masculins',
        'seniors_f' => 'Seniors Féminines',
        'loisirs'   => 'Loisirs',
    ];

    protected $fillable = [
        'name',
        'type',
        'photo',
        'bio',
        'categories',
    ];

    protected function casts(): array
    {
        return [
            'categories' => 'array',
        ];
    }
}
