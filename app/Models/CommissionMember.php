<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'commission_id',
        'name',
        'role',
        'bio',
        'photo',
        'sort_order',
    ];

    public function commission(): BelongsTo
    {
        return $this->belongsTo(Commission::class);
    }
}
