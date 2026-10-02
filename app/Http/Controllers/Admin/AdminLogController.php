<?php
// app/Http/Controllers/Admin/AdminLogController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use Illuminate\Http\Request;

class AdminLogController extends Controller
{
    /**
     * Get all admin logs
     */
    public function index(Request $request)
    {
        $query = AdminLog::with('user');

        // Filter by action
        if ($request->action) {
            $query->where('action', $request->action);
        }

        // Filter by table
        if ($request->table_name) {
            $query->where('table_name', $request->table_name);
        }

        // Filter by user
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('table_name', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('record_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function($query) use ($search) {
                      $query->where('full_name', 'like', "%{$search}%");
                  });
            });
        }

        // Sort
        $query->orderBy('created_at', 'desc');

        $logs = $query->paginate($request->per_page ?? 50)->withQueryString();

        if (!$request->wantsJson() && !$request->is('api/*')) {
            return \Inertia\Inertia::render('Admin/Logs/Index', [
                'logs' => $logs,
                'stats' => $this->getStatistics()->getData(true)['data'],
                'filters' => $request->only(['search', 'action', 'table_name', 'from_date', 'to_date']),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Get log detail
     */
    public function show($id)
    {
        $log = AdminLog::with('user')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $log
        ]);
    }

    /**
     * Get logs by table and record
     */
    public function getByRecord(Request $request)
    {
        $request->validate([
            'table_name' => 'required|string',
            'record_id' => 'required|integer',
        ]);

        $logs = AdminLog::where('table_name', $request->table_name)
            ->where('record_id', $request->record_id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Get logs statistics
     */
    public function getStatistics()
    {
        $stats = [
            'total_logs' => AdminLog::count(),
            'today_logs' => AdminLog::whereDate('created_at', today())->count(),
            'by_action' => AdminLog::selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->get()
                ->pluck('count', 'action'),
            'by_table' => AdminLog::selectRaw('table_name, COUNT(*) as count')
                ->groupBy('table_name')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            'top_users' => AdminLog::selectRaw('user_id, COUNT(*) as count')
                ->with('user:id,full_name')
                ->groupBy('user_id')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities(Request $request)
    {
        $limit = $request->limit ?? 20;

        $logs = AdminLog::with('user')
            ->latest()
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Clear old logs (older than X days)
     */
    public function clearOldLogs(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1',
        ]);

        $deletedCount = AdminLog::where('created_at', '<', now()->subDays($request->days))
            ->delete();

        if (!$request->wantsJson() && !$request->is('api/*')) {
            return back()->with('success', "Đã xóa {$deletedCount} log cũ");
        }

        return response()->json([
            'success' => true,
            'message' => "Đã xóa {$deletedCount} log cũ"
        ]);
    }

    /**
     * Export logs to CSV
     */
    public function export(Request $request)
    {
        $query = AdminLog::with('user:id,full_name');

        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $logs = $query->orderByDesc('created_at')->get();

        $csv = "Thoi gian,Nguoi thuc hien,Hanh dong,Bang,Ma ban ghi\n";
        foreach ($logs as $log) {
            $csv .= implode(',', [
                $log->created_at,
                str_replace(',', ' ', $log->user->full_name ?? 'N/A'),
                $log->action,
                $log->table_name,
                $log->record_id,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="admin-logs.csv"',
        ]);
    }
}