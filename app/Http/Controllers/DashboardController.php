<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

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

        return Inertia::render('Dashboard', [
            'stats' => [
                'leads' => Lead::count(),
                'clients' => Client::count(),
                'pending_tasks' => Task::where('status', 'pending')->count(),
            ],
            'leadByStatus' => $leadByStatus,
            'taskByStatus' => $taskByStatus,
            'monthlyStats' => $formattedMonthlyStats,
            'topPerformers' => $topPerformers,
            'recentActivities' => $recentActivities,
        ]);
    }
}
