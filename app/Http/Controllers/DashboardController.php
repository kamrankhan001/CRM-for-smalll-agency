<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Task;
use App\Models\Note;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'leads' => Lead::count(),
                'clients' => Client::count(),
                'pending_tasks' => Task::where('status', 'pending')->count(),
            ],
            'recent_notes' => Note::with('user')
                ->latest()
                ->take(5)
                ->get(['id', 'content', 'noteable_type', 'created_at', 'user_id']),
        ]);
    }
}
