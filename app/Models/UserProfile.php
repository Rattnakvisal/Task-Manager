<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'headline',
        'bio',
        'city',
        'country',
        'profile_sections',
        'work_status',
        'job_title',
        'company',
        'industry',
        'study_status',
        'education_level',
        'institution',
        'field_of_study',
        'skills',
        'interests',
        'website',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'interests' => 'array',
            'profile_sections' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
