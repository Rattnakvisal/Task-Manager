<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ai_request_id',
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'end_date',
        'category',
        'subtasks',
        'tags',
        'is_pinned',
    ];

    protected $casts = [
        'due_date' => 'date',
        'end_date' => 'date',
        'subtasks' => 'array',
        'tags' => 'array',
        'is_pinned' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString());
    }

    public function getSubtasksCountAttribute(): int
    {
        return is_array($this->subtasks) ? count($this->subtasks) : 0;
    }

    public function getCompletedSubtasksCountAttribute(): int
    {
        if (! is_array($this->subtasks)) {
            return 0;
        }

        return count(array_filter($this->subtasks, fn ($st) => ! empty($st['completed'])));
    }

    public function getSubtasksProgressAttribute(): int
    {
        $total = $this->subtasks_count;
        if ($total === 0) {
            return 0;
        }

        return (int) round(($this->completed_subtasks_count / $total) * 100);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'completed' && $this->due_date && $this->due_date->isPast() && ! $this->due_date->isToday();
    }
}
