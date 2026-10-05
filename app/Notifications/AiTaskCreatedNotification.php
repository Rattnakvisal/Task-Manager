<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AiTaskCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Task $task) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'ai_task_created',
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'priority' => $this->task->priority,
            'category' => $this->task->category,
            'due_date' => $this->task->due_date?->format('M d, Y'),
            'task_url' => route('tasks.show', $this->task, false),
            'source' => 'nova',
        ];
    }
}
