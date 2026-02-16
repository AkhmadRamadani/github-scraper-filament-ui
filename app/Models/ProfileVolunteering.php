<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileVolunteering extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'organization',
        'role',
        'start_date',
        'end_date',
        'description',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
