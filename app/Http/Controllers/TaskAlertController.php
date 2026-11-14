<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

class TaskAlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $unreadCount = $request->user()->unreadNotifications()->count();
        $unreadReminderCount = $this->reminderTasks($request)
            ->where(function ($query) {
                $query->whereNull('reminder_read_at')
                    ->orWhereColumn('reminder_read_at', '<', 'updated_at');
            })
            ->count();
        $notifications = $request->user()->notifications()
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
            'unread_reminder_count' => $unreadReminderCount,
            'notifications' => $notifications->map(fn (DatabaseNotification $notification) => [
                'notification_id' => $notification->id,
                ...$notification->data,
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at?->toIso8601String(),
                'time_ago' => $notification->created_at?->diffForHumans(),
            ])->values(),
        ]);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        $alert = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $alert->markAsRead();

        return response()->json([
            'success' => true,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        DB::transaction(function () use ($request) {
            $request->user()->unreadNotifications()->update(['read_at' => now()]);
            $this->reminderTasks($request)->update(['reminder_read_at' => now()]);
        });

        return response()->json([
            'success' => true,
            'unread_count' => 0,
            'unread_reminder_count' => 0,
        ]);
    }

    public function markReminderRead(Request $request, string $task): JsonResponse
    {
        $updated = $this->reminderTasks($request)
            ->whereKey($task)
            ->update(['reminder_read_at' => now()]);

        abort_unless($updated === 1, 404);

        return response()->json([
            'success' => true,
            'unread_reminder_count' => $this->reminderTasks($request)
                ->where(function ($query) {
                    $query->whereNull('reminder_read_at')
                        ->orWhereColumn('reminder_read_at', '<', 'updated_at');
                })
                ->count(),
        ]);
    }

    private function reminderTasks(Request $request)
    {
        return $request->user()->tasks()
            ->whereNotNull('end_date')
            ->where('status', '!=', 'completed')
            ->where(function ($query) {
                $query->whereDate('end_date', now()->toDateString())
                    ->orWhereDate('end_date', now()->addDay()->toDateString());
            });
    }
}
