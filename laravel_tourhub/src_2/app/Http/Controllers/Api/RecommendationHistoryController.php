<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\RecommendationLog;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

final class RecommendationHistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = RecommendationLog::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    public function show(Request $request, RecommendationLog $recommendationLog): JsonResponse
    {
        abort_if(
            (int) $recommendationLog->user_id !== (int) $request->user()->id,
            403,
            'Kamu tidak punya akses ke riwayat ini.'
        );

        return response()->json([
            'success' => true,
            'data' => $recommendationLog,
        ]);
    }
}