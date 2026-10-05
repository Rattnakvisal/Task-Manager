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
        $unreadCount = $request->user()->unreadNotifications()->count();
        $notifications = $request->user()->notifications()
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
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
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'unread_count' => 0,
        ]);
    }
}
