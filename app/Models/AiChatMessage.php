<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role',
        'message',
        'action_type',
        'action_data',
    ];

    protected $casts = [
        'action_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
