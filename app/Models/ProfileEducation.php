<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileEducation extends Model
{
    use HasFactory;

    protected $table = 'profile_education';

    protected $fillable = [
        'profile_id',
        'institution',
        'degree',
        'start_date',
        'end_date',
        'location',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
