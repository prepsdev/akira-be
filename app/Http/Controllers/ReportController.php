<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Activitylog\Models\Activity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function getUserActivityStats(): JsonResponse
    {
        $userStats = Activity::select('causer_id', 'causer_type')
            ->selectRaw('COUNT(*) as total_actions')
            ->selectRaw('SUM(CASE WHEN properties->>"$.action" = "approved" THEN 1 ELSE 0 END) as total_approved')
            ->selectRaw('SUM(CASE WHEN properties->>"$.action" = "rejected" THEN 1 ELSE 0 END) as total_rejected')
            ->whereNotNull('causer_id')
            ->groupBy('causer_id', 'causer_type')
            ->get()
            ->map(function ($stat) {
                $user = User::find($stat->causer_id);
                return [
                    'user_id' => $stat->causer_id,
                    'user_name' => $user ? $user->name : 'Unknown',
                    'username' => $user ? $user->username : 'unknown',
                    'total_actions' => $stat->total_actions,
                    'total_approved' => $stat->total_approved,
                    'total_rejected' => $stat->total_rejected,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $userStats
        ]);
    }

    public function getTodayStats(): JsonResponse
    {
        $today = Carbon::today();

        $todayStats = Activity::whereDate('created_at', $today)
            ->selectRaw('COUNT(*) as total_actions')
            ->selectRaw('SUM(CASE WHEN properties->>"$.action" = "approved" THEN 1 ELSE 0 END) as total_approved')
            ->selectRaw('SUM(CASE WHEN properties->>"$.action" = "rejected" THEN 1 ELSE 0 END) as total_rejected')
            ->first();

        $userTodayStats = Activity::whereDate('created_at', $today)
            ->select('causer_id', 'causer_type')
            ->selectRaw('COUNT(*) as total_actions')
            ->selectRaw('SUM(CASE WHEN properties->>"$.action" = "approved" THEN 1 ELSE 0 END) as total_approved')
            ->selectRaw('SUM(CASE WHEN properties->>"$.action" = "rejected" THEN 1 ELSE 0 END) as total_rejected')
            ->whereNotNull('causer_id')
            ->groupBy('causer_id', 'causer_type')
            ->get()
            ->map(function ($stat) {
                $user = User::find($stat->causer_id);
                return [
                    'user_id' => $stat->causer_id,
                    'user_name' => $user ? $user->name : 'Unknown',
                    'username' => $user ? $user->username : 'unknown',
                    'total_actions' => $stat->total_actions,
                    'total_approved' => $stat->total_approved,
                    'total_rejected' => $stat->total_rejected,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total' => [
                    'total_actions' => $todayStats->total_actions ?? 0,
                    'total_approved' => $todayStats->total_approved ?? 0,
                    'total_rejected' => $todayStats->total_rejected ?? 0,
                ],
                'per_user' => $userTodayStats
            ]
        ]);
    }

    public function getRecentActivity(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);

        $recentActivities = Activity::with('causer')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'action' => $activity->properties['action'] ?? null,
                    'document_id' => $activity->properties['document_id'] ?? null,
                    'document_name' => $activity->properties['document_name'] ?? null,
                    'category' => $activity->properties['category'] ?? null,
                    'user_name' => $activity->causer ? $activity->causer->name : 'System',
                    'username' => $activity->causer ? $activity->causer->username : 'system',
                    'created_at' => $activity->created_at->format('Y-m-d H:i:s'),
                    'time_ago' => $activity->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $recentActivities
        ]);
    }

    public function getActivityByDateRange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();

        $stats = Activity::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as total_actions')
            ->selectRaw('SUM(CASE WHEN properties->>"$.action" = "approved" THEN 1 ELSE 0 END) as total_approved')
            ->selectRaw('SUM(CASE WHEN properties->>"$.action" = "rejected" THEN 1 ELSE 0 END) as total_rejected')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
