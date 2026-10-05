<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'occupation',
        'experience_level',
        'learning_interests',
        'work_skills',
        'assistance_areas',
        'other_needs',
    ];

    protected function casts(): array
    {
        return [
            'learning_interests' => 'array',
            'work_skills' => 'array',
            'assistance_areas' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
