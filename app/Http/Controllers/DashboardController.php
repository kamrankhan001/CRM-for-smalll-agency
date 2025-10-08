<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Client;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $user = Auth::user();

        // Leads by Status - Current Month Only
        $leadByStatus = Lead::select('status', DB::raw('count(*) as total'))
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->groupBy('status')
            ->pluck('total', 'status');

        // Tasks by Status - Current Month Only
        $taskByStatus = Task::select('status', DB::raw('count(*) as total'))
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'label' => ucfirst(str_replace('_', ' ', $item->status)),
                    'count' => $item->total,
                    'percentage' => round(($item->total / max(Task::count(), 1)) * 100, 1),
                ];
            });

        // Monthly Growth - Last 12 Months
        $monthlyStats = Lead::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as total')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Format monthly stats with month-year labels
        $formattedMonthlyStats = [];
        foreach ($monthlyStats as $stat) {
            $monthYear = Carbon::createFromDate($stat->year, $stat->month, 1)->format('M Y');
            $formattedMonthlyStats[$monthYear] = $stat->total;
        }

        // Top Performers - Current Month with client conversion rate
        $topPerformers = DB::table('users')
            ->select(
                'users.name',
                DB::raw('COUNT(leads.id) as total_leads'),
                DB::raw('COUNT(clients.id) as converted_clients'),
                DB::raw('ROUND((COUNT(clients.id) / COUNT(leads.id)) * 100, 2) as conversion_rate')
            )
            ->leftJoin('leads', 'users.id', '=', 'leads.created_by')
            ->leftJoin('clients', 'leads.id', '=', 'clients.lead_id')
            ->whereMonth('leads.created_at', $currentMonth)
            ->whereYear('leads.created_at', $currentYear)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('converted_clients')
            ->limit(5)
            ->get();

        $recentActivities = Activity::with('causer')
            ->latest()
            ->take(5)
            ->get(['id', 'action', 'subject_type', 'subject_id', 'causer_id', 'created_at']);

        // Base stats for all users
        $stats = [
            'leads' => Lead::count(),
            'clients' => Client::count(),
            'pending_tasks' => Task::where('status', 'pending')->count(),
            'notes' => Note::count(),
            'projects' => Project::count(),
            'documents' => Document::count(),
        ];

        // Add admin/manager specific stats
        if ($user->role === 'admin' || $user->role === 'manager') {
            $stats['invoices'] = Invoice::count();
            $stats['total_invoice_amount'] = Invoice::sum('amount');
            $stats['paid_invoice_amount'] = Invoice::where('status', 'paid')->sum('amount');
            
            if ($user->role === 'admin') {
                $stats['users'] = User::count();
            }
        }

        // Recent data for dashboard widgets
        $recentData = [
            'recent_leads' => Lead::with('creator')->latest()->take(5)->get(),
            'recent_tasks' => Task::with(['assignee', 'creator'])->latest()->take(5)->get(),
            'recent_clients' => Client::with('creator')->latest()->take(5)->get(),
            'recent_notes' => Note::with('user')->latest()->take(5)->get(), // Changed from 'creator' to 'user'
            'recent_projects' => Project::with(['client', 'creator'])->latest()->take(5)->get(),
            'recent_documents' => Document::with(['uploader', 'documentable'])->latest()->take(5)->get(),
        ];

        // Add admin/manager specific recent data
        if ($user->role === 'admin' || $user->role === 'manager') {
            $recentData['recent_invoices'] = Invoice::with(['client', 'project', 'creator'])
                ->latest()
                ->take(5)
                ->get();
            
            if ($user->role === 'admin') {
                $recentData['recent_users'] = User::latest()->take(5)->get();
            }
        }

        // Task statistics for current user
        $userTaskStats = [
            'total_tasks' => Task::where('assigned_to', $user->id)->count(),
            'pending_tasks' => Task::where('assigned_to', $user->id)->where('status', 'pending')->count(),
            'in_progress_tasks' => Task::where('assigned_to', $user->id)->where('status', 'in_progress')->count(),
            'completed_tasks' => Task::where('assigned_to', $user->id)->where('status', 'completed')->count(),
        ];

        // Invoice statistics for admin/manager
        $invoiceStats = [];
        if ($user->role === 'admin' || $user->role === 'manager') {
            $invoiceStats = [
                'total_invoices' => Invoice::count(),
                'draft_invoices' => Invoice::where('status', 'draft')->count(),
                'sent_invoices' => Invoice::where('status', 'sent')->count(),
                'partially_paid_invoices' => Invoice::where('status', 'partially_paid')->count(),
                'paid_invoices' => Invoice::where('status', 'paid')->count(),
                'cancelled_invoices' => Invoice::where('status', 'cancelled')->count(),
            ];
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'leadByStatus' => $leadByStatus,
            'taskByStatus' => $taskByStatus,
            'monthlyStats' => $formattedMonthlyStats,
            'topPerformers' => $topPerformers,
            'recentActivities' => $recentActivities,
            'recentData' => $recentData,
            'userTaskStats' => $userTaskStats,
            'invoiceStats' => $invoiceStats,
            'userRole' => $user->role,
        ]);
    }
}