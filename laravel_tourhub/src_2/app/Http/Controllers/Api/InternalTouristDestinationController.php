<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TouristDestination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InternalTouristDestinationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureInternalAccess($request);

        $activeOnly = $request->boolean('active', true);
        $includeDeleted = $request->boolean('include_deleted', false);
        $limit = min(max((int) $request->integer('limit', 5000), 1), 10000);

        $query = TouristDestination::query()
            ->when($includeDeleted, fn ($query) => $query->withTrashed())
            ->when($activeOnly, fn ($query) => $query->active())
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderByDesc('rating')
            ->orderByDesc('jumlah_rating')
            ->limit($limit);

        $destinations = $query->get()->map(fn (TouristDestination $destination): array => $destination->toMlPayload())->values();

        return response()->json([
            'success' => true,
            'source' => 'laravel_database',
            'total' => $destinations->count(),
            'data' => $destinations,
        ]);
    }

    private function ensureInternalAccess(Request $request): void
    {
        $expectedKey = (string) config('tourhub.internal_api_key');
        $givenKey = (string) $request->header('X-TourHub-Internal-Key', '');

        if ($expectedKey === '' || ! hash_equals($expectedKey, $givenKey)) {
            Log::warning('Unauthorized internal tourist destination access.', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            abort(response()->json([
                'success' => false,
                'message' => 'Unauthorized internal request.',
            ], 401));
        }
    }
}
