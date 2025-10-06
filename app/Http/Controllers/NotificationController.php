<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Notification;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = $user->role === 'admin'
            ? Notification::query()
            : $user->notifications();

        // Apply filters
        $query
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'unread') {
                    $q->whereNull('read_at');
                } elseif ($request->status === 'read') {
                    $q->whereNotNull('read_at');
                }
            })
            ->when($request->filled('type'), fn ($q) => $q->where('type', 'like', "%{$request->type}%"))
            ->latest();

        $notifications = $query->paginate(10);

        $mapped = $notifications->through(fn ($n) => [
            'id' => $n->id,
            'type' => class_basename($n->type),
            'message' => $n->data['message'] ?? 'No message',
            'url' => $n->data['url'] ?? null,
            'read_at' => $n->read_at,
            'created_at' => $n->created_at->toDateTimeString(),
        ]);

        // Build proper pagination links structure
        $links = [];
        $urlRange = $notifications->getUrlRange(1, $notifications->lastPage());
        
        // Previous page link
        $links[] = [
            'url' => $notifications->previousPageUrl(),
            'label' => '&laquo; Previous',
            'active' => false,
        ];

        // Page number links
        foreach ($urlRange as $page => $url) {
            $links[] = [
                'url' => $url,
                'label' => (string)$page,
                'active' => $page == $notifications->currentPage(),
            ];
        }

        // Next page link
        $links[] = [
            'url' => $notifications->nextPageUrl(),
            'label' => 'Next &raquo;',
            'active' => false,
        ];

        $users = $user->role === 'admin' ? User::select('id', 'name')->get() : [];

        return Inertia::render('notifications/Index', [
            'notifications' => [
                'data' => $mapped->items(),
                'meta' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'from' => $notifications->firstItem(),
                    'to' => $notifications->lastItem(),
                    'links' => $links,
                ],
            ],
            'filters' => $request->only(['status', 'type']),
            'users' => $users,
        ]);
    }

    public function markAsRead($id)
    {
        $user = auth()->user();
        
        if ($user->role === 'admin') {
            $notification = Notification::findOrFail($id);
        } else {
            $notification = $user->notifications()->findOrFail($id);
        }
        
        $notification->update(['read_at' => now()]);

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead()
    {
        $user = auth()->user();
        
        if ($user->role === 'admin') {
            Notification::whereNull('read_at')->update(['read_at' => now()]);
        } else {
            $user->unreadNotifications->markAsRead();
        }

        return redirect()->route('notifications.index')->with('success', 'All notifications marked as read.');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        
        if ($user->role === 'admin') {
            $notification = Notification::findOrFail($id);
        } else {
            $notification = $user->notifications()->findOrFail($id);
        }
        
        $notification->delete();

        return redirect()->route('notifications.index')->with('success', 'Notification deleted successfully.');
    }
}