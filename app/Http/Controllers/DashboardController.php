<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Policies\DashboardPolicy;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected $policy;

    public function __construct()
    {
        $this->policy = new DashboardPolicy;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Base stats for all users
        $stats = $this->getBaseStats($user);

        // Role-specific statistics
        if ($this->policy->viewManagerStats($user)) {
            $stats = array_merge($stats, $this->getManagerStats($user));
        }

        if ($this->policy->viewAdminStats($user)) {
            $stats = array_merge($stats, $this->getAdminStats($user));
        }

        // Dashboard data based on user role
        $dashboardData = [
            'stats' => $stats,
            'leadByStatus' => $this->getLeadsByStatus($user, $currentMonth, $currentYear),
            'taskByStatus' => $this->getTasksByStatus($user, $currentMonth, $currentYear),
            'appointmentByStatus' => $this->getAppointmentsByStatus($user, $currentMonth, $currentYear),
            'monthlyStats' => $this->getMonthlyStats($user),
            'recentActivities' => $this->getRecentActivities($user),
            'recentData' => $this->getRecentData($user),
            'userTaskStats' => $this->getUserTaskStats($user),
            'userAppointmentStats' => $this->getUserAppointmentStats($user),
            'invoiceStats' => $this->getInvoiceStats($user),
            'userRole' => $user->role,
        ];

        // Add team performance data for managers and admins
        if ($this->policy->viewTeamPerformance($user)) {
            $dashboardData['topPerformers'] = $this->getTopPerformers($currentMonth, $currentYear);
        }

        return Inertia::render('Dashboard', $dashboardData);
    }

    /**
     * Get base statistics available to all users
     */
    protected function getBaseStats(User $user): array
    {
        $policy = $this->policy;

        $leadsQuery = $policy->scopeDashboardData($user, new Lead);
        $clientsQuery = $policy->scopeDashboardData($user, new Client);
        $tasksQuery = $policy->scopeDashboardData($user, new Task);
        $appointmentsQuery = $policy->scopeDashboardData($user, new Appointment);
        $notesQuery = $policy->scopeDashboardData($user, new Note);
        $projectsQuery = $policy->scopeDashboardData($user, new Project);
        $documentsQuery = $policy->scopeDashboardData($user, new Document);

        return [
            'leads' => $leadsQuery->count(),
            'clients' => $clientsQuery->count(),
            'pending_tasks' => $tasksQuery->where('status', 'pending')->count(),
            'total_appointments' => $appointmentsQuery->count(),
            'upcoming_appointments' => $appointmentsQuery->where('start_time', '>', now())->count(),
            'notes' => $notesQuery->count(),
            'projects' => $projectsQuery->count(),
            'documents' => $documentsQuery->count(),
        ];
    }

    /**
     * Get manager-specific statistics
     */
    protected function getManagerStats(User $user): array
    {
        return [
            'invoices' => Invoice::count(),
            'total_invoice_amount' => Invoice::sum('amount'),
            'paid_invoice_amount' => Invoice::where('status', 'paid')->sum('amount'),
        ];
    }

    /**
     * Get admin-specific statistics
     */
    protected function getAdminStats(User $user): array
    {
        return [
            'users' => User::count(),
        ];
    }

    /**
     * Get leads by status with role-based scoping
     */
    protected function getLeadsByStatus(User $user, int $month, int $year)
    {
        $query = $this->policy->scopeDashboardData($user, new Lead)
            ->select('status', DB::raw('count(*) as total'))
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->groupBy('status');

        return $query->pluck('total', 'status');
    }

    /**
     * Get tasks by status with role-based scoping
     */
    protected function getTasksByStatus(User $user, int $month, int $year)
    {
        $query = $this->policy->scopeDashboardData($user, new Task)
            ->select('status', DB::raw('count(*) as total'))
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->groupBy('status');

        $totalTasks = $query->clone()->count();

        return $query->get()
            ->map(function ($item) use ($totalTasks) {
                return [
                    'status' => $item->status,
                    'label' => ucfirst(str_replace('_', ' ', $item->status)),
                    'count' => $item->total,
                    'percentage' => round(($item->total / max($totalTasks, 1)) * 100, 1),
                ];
            });
    }

    /**
     * Get appointments by status with role-based scoping
     */
    protected function getAppointmentsByStatus(User $user, int $month, int $year)
    {
        $query = $this->policy->scopeDashboardData($user, new Appointment)
            ->select('status', DB::raw('count(*) as total'))
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->groupBy('status');

        $totalAppointments = $query->clone()->count();

        return $query->get()
            ->map(function ($item) use ($totalAppointments) {
                return [
                    'status' => $item->status,
                    'label' => ucfirst(str_replace('_', ' ', $item->status)),
                    'count' => $item->total,
                    'percentage' => round(($item->total / max($totalAppointments, 1)) * 100, 1),
                ];
            });
    }

    /**
     * Get monthly statistics with role-based scoping
     */
    protected function getMonthlyStats(User $user)
    {
        $query = $this->policy->scopeDashboardData($user, new Lead)
            ->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as total')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month');

        $monthlyStats = $query->get();

        $formattedMonthlyStats = [];
        foreach ($monthlyStats as $stat) {
            $monthYear = Carbon::createFromDate($stat->year, $stat->month, 1)->format('M Y');
            $formattedMonthlyStats[$monthYear] = $stat->total;
        }

        return $formattedMonthlyStats;
    }

    /**
     * Get top performers (admin/manager only)
     */
    protected function getTopPerformers(int $month, int $year)
    {
        return DB::table('users')
            ->select(
                'users.name',
                DB::raw('COUNT(leads.id) as total_leads'),
                DB::raw('COUNT(clients.id) as converted_clients'),
                DB::raw('ROUND((COUNT(clients.id) / COUNT(leads.id)) * 100, 2) as conversion_rate')
            )
            ->leftJoin('leads', 'users.id', '=', 'leads.created_by')
            ->leftJoin('clients', 'leads.id', '=', 'clients.lead_id')
            ->whereMonth('leads.created_at', $month)
            ->whereYear('leads.created_at', $year)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('converted_clients')
            ->limit(5)
            ->get();
    }

    /**
     * Get recent activities with role-based scoping
     */
    protected function getRecentActivities(User $user)
    {
        $query = Activity::with('causer')
            ->latest();

        // Regular users only see their own activities
        if ($user->role === 'member') {
            $query->where('causer_id', $user->id);
        }

        return $query->take(5)
            ->get(['id', 'action', 'subject_type', 'subject_id', 'causer_id', 'created_at']);
    }

    /**
     * Get recent data for dashboard widgets
     */
    protected function getRecentData(User $user): array
    {
        $policy = $this->policy;
        $recentData = [
            'recent_leads' => $policy->scopeDashboardData($user, new Lead)
                ->with('creator')->latest()->take(5)->get(),
            'recent_tasks' => $policy->scopeDashboardData($user, new Task)
                ->with(['assignee', 'creator'])->latest()->take(5)->get(),
            'recent_appointments' => $policy->scopeDashboardData($user, new Appointment)
                ->with(['creator', 'attendees'])->latest()->take(5)->get(),
            'recent_clients' => $policy->scopeDashboardData($user, new Client)
                ->with('creator')->latest()->take(5)->get(),
            'recent_notes' => $policy->scopeDashboardData($user, new Note)
                ->with('user')->latest()->take(5)->get(),
            'recent_projects' => $policy->scopeDashboardData($user, new Project)
                ->with(['client', 'creator', 'members'])->latest()->take(5)->get(),
            'recent_documents' => $policy->scopeDashboardData($user, new Document)
                ->with(['uploader', 'documentable'])->latest()->take(5)->get(),
        ];

        // Add admin/manager specific recent data
        if ($this->policy->viewInvoiceStats($user)) {
            $recentData['recent_invoices'] = Invoice::with(['client', 'project', 'creator'])
                ->latest()
                ->take(5)
                ->get();
        }

        if ($this->policy->viewUserManagement($user)) {
            $recentData['recent_users'] = User::latest()->take(5)->get();
        }

        return $recentData;
    }

    /**
     * Get task statistics for current user
     */
    protected function getUserTaskStats(User $user): array
    {
        $query = $this->policy->scopeDashboardData($user, new Task)
            ->where('assigned_to', $user->id);

        return [
            'total_tasks' => $query->count(),
            'pending_tasks' => $query->where('status', 'pending')->count(),
            'in_progress_tasks' => $query->where('status', 'in_progress')->count(),
            'completed_tasks' => $query->where('status', 'completed')->count(),
        ];
    }

    /**
     * Get appointment statistics for current user
     */
    protected function getUserAppointmentStats(User $user): array
    {
        $query = $this->policy->scopeDashboardData($user, new Appointment);

        return [
            'total_appointments' => $query->count(),
            'scheduled_appointments' => $query->where('status', 'scheduled')->count(),
            'completed_appointments' => $query->where('status', 'completed')->count(),
            'cancelled_appointments' => $query->where('status', 'cancelled')->count(),
            'upcoming_appointments' => $query->where('start_time', '>', now())->count(),
        ];
    }

    /**
     * Get invoice statistics (admin/manager only)
     */
    protected function getInvoiceStats(User $user): array
    {
        if (! $this->policy->viewInvoiceStats($user)) {
            return [];
        }

        return [
            'total_invoices' => Invoice::count(),
            'draft_invoices' => Invoice::where('status', 'draft')->count(),
            'sent_invoices' => Invoice::where('status', 'sent')->count(),
            'partially_paid_invoices' => Invoice::where('status', 'partially_paid')->count(),
            'paid_invoices' => Invoice::where('status', 'paid')->count(),
            'cancelled_invoices' => Invoice::where('status', 'cancelled')->count(),
        ];
    }
}