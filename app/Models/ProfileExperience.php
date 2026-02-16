<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileExperience extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'position',
        'company',
        'start_date',
        'end_date',
        'location',
        'responsibilities',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
