<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\RecommendationLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

final class UserDashboardController extends Controller
{
    public function index(): View
    {
        $userId = Auth::id();

        $logs = RecommendationLog::query()
            ->where('user_id', $userId)
            ->latest()
            ->paginate(10);

        $totalRecommendations = RecommendationLog::query()
            ->where('user_id', $userId)
            ->count();

        $successRecommendations = RecommendationLog::query()
            ->where('user_id', $userId)
            ->where('status', 'success')
            ->count();

        $failedRecommendations = RecommendationLog::query()
            ->where('user_id', $userId)
            ->where('status', 'failed')
            ->count();

        $latestSuccess = RecommendationLog::query()
            ->where('user_id', $userId)
            ->where('status', 'success')
            ->latest()
            ->first();

        return view('user.dashboard', [
            'logs' => $logs,
            'totalRecommendations' => $totalRecommendations,
            'successRecommendations' => $successRecommendations,
            'failedRecommendations' => $failedRecommendations,
            'latestSuccess' => $latestSuccess,
        ]);
    }

    public function show(RecommendationLog $recommendationLog): View
    {
        abort_if($recommendationLog->user_id !== Auth::id(), 403);

        return view('user.recommendation-history.show', [
            'log' => $recommendationLog,
        ]);
    }
}
