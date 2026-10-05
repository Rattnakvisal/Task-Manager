<?php

namespace App\Http\Controllers;

use App\Notifications\AiTaskCreatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class TaskAlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()
            ->where('type', AiTaskCreatedNotification::class)
            ->select('notifications.*')
            ->selectRaw('SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) OVER () AS unread_total')
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'unread_count' => (int) ($notifications->first()?->unread_total ?? 0),
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

        return response()->json(['success' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()
            ->where('type', AiTaskCreatedNotification::class)
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
