<?php

namespace App\Filament\Admin\Resources\RecommendationLogs\Pages;

use App\Filament\Admin\Resources\RecommendationLogs\RecommendationLogResource;
use App\Models\RecommendationLog;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ListRecommendationLogs extends ListRecords
{
    protected static string $resource = RecommendationLogResource::class;

    protected string $view = 'filament.admin.resources.recommendation-logs.pages.list-recommendation-logs-luxury';

    public function getTitle(): string | Htmlable
    {
        return 'Log Rekomendasi';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshLogs')
                ->label('Refresh Log')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function (): void {
                    $this->dispatch('$refresh');
                }),

            Action::make('testRecommendation')
                ->label('Test Rekomendasi')
                ->icon('heroicon-o-sparkles')
                ->color('info')
                ->url(route('tourhub.recommendation.index'))
                ->openUrlInNewTab(),
        ];
    }

    public function getRecommendationDashboardStats(): array
    {
        $baseQuery = RecommendationLog::query();

        $total = (clone $baseQuery)->count();
        $success = (clone $baseQuery)->where('status', 'success')->count();
        $failed = (clone $baseQuery)->where('status', 'failed')->count();
        $today = (clone $baseQuery)->whereDate('created_at', today())->count();
        $todaySuccess = (clone $baseQuery)->where('status', 'success')->whereDate('created_at', today())->count();
        $todayFailed = (clone $baseQuery)->where('status', 'failed')->whereDate('created_at', today())->count();
        $thisWeek = (clone $baseQuery)->where('created_at', '>=', now()->startOfWeek())->count();
        $thisMonth = (clone $baseQuery)->where('created_at', '>=', now()->startOfMonth())->count();

        $avgResponse = (clone $baseQuery)->whereNotNull('response_time_ms')->avg('response_time_ms');
        $fastestResponse = (clone $baseQuery)->whereNotNull('response_time_ms')->min('response_time_ms');
        $slowestResponse = (clone $baseQuery)->whereNotNull('response_time_ms')->max('response_time_ms');

        $bmkgCount = (clone $baseQuery)
            ->where(function ($query): void {
                $query
                    ->where('weather_source', 'like', 'BMKG%')
                    ->orWhere('weather_source', 'like', 'bmkg%')
                    ->orWhere('weather_source', 'like', '%adm4=%');
            })
            ->count();

        $manualCount = (clone $baseQuery)
            ->where(function ($query): void {
                $query
                    ->where('weather_source', 'manual')
                    ->orWhere('weather_source', 'like', 'Manual%');
            })
            ->count();

        $lastLog = (clone $baseQuery)->latest('created_at')->first();
        $lastSuccess = (clone $baseQuery)->where('status', 'success')->latest('created_at')->first();
        $lastFailed = (clone $baseQuery)->where('status', 'failed')->latest('created_at')->first();

        $successRate = $total > 0 ? round(($success / $total) * 100, 1) : 0;
        $failedRate = $total > 0 ? round(($failed / $total) * 100, 1) : 0;
        $bmkgRate = $total > 0 ? round(($bmkgCount / $total) * 100, 1) : 0;
        $todaySuccessRate = $today > 0 ? round(($todaySuccess / $today) * 100, 1) : 0;

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'today' => $today,
            'today_success' => $todaySuccess,
            'today_failed' => $todayFailed,
            'this_week' => $thisWeek,
            'this_month' => $thisMonth,
            'success_rate' => $successRate,
            'failed_rate' => $failedRate,
            'today_success_rate' => $todaySuccessRate,
            'bmkg_count' => $bmkgCount,
            'manual_count' => $manualCount,
            'bmkg_rate' => $bmkgRate,
            'avg_response' => (int) round((float) $avgResponse),
            'fastest_response' => (int) ($fastestResponse ?? 0),
            'slowest_response' => (int) ($slowestResponse ?? 0),
            'last_log_at' => $lastLog?->created_at,
            'last_success_at' => $lastSuccess?->created_at,
            'last_failed_at' => $lastFailed?->created_at,
            'last_top_destination' => $lastSuccess?->top_destination_name,
            'health_label' => $this->resolveHealthLabel($successRate, $failedRate, (int) round((float) $avgResponse)),
            'health_tone' => $this->resolveHealthTone($successRate, $failedRate, (int) round((float) $avgResponse)),
        ];
    }

    public function getRecommendationQualityItems(): array
    {
        $stats = $this->getRecommendationDashboardStats();

        return [
            [
                'label' => 'Success Rate',
                'value' => $stats['success_rate'],
                'suffix' => '%',
                'description' => 'Persentase request rekomendasi yang berhasil diproses oleh pipeline Laravel dan FastAPI.',
                'tone' => $stats['success_rate'] >= 90 ? 'emerald' : ($stats['success_rate'] >= 75 ? 'blue' : 'rose'),
                'percent' => $stats['success_rate'],
            ],
            [
                'label' => 'BMKG Coverage',
                'value' => $stats['bmkg_rate'],
                'suffix' => '%',
                'description' => 'Porsi rekomendasi yang memakai konteks cuaca dari BMKG atau ADM4 wilayah Bali.',
                'tone' => $stats['bmkg_rate'] >= 70 ? 'cyan' : ($stats['bmkg_rate'] >= 40 ? 'amber' : 'slate'),
                'percent' => $stats['bmkg_rate'],
            ],
            [
                'label' => 'Avg Response',
                'value' => $stats['avg_response'],
                'suffix' => ' ms',
                'description' => 'Rata-rata waktu respons rekomendasi. Makin rendah makin nyaman untuk aplikasi mobile.',
                'tone' => $stats['avg_response'] <= 800 ? 'emerald' : ($stats['avg_response'] <= 2000 ? 'amber' : 'rose'),
                'percent' => $this->responsePercent($stats['avg_response']),
            ],
            [
                'label' => 'Today Success',
                'value' => $stats['today_success_rate'],
                'suffix' => '%',
                'description' => 'Kesehatan request rekomendasi khusus hari ini untuk memantau demo dan penggunaan terbaru.',
                'tone' => $stats['today_success_rate'] >= 90 ? 'emerald' : ($stats['today_success_rate'] >= 70 ? 'blue' : 'orange'),
                'percent' => $stats['today_success_rate'],
            ],
        ];
    }

    public function getWeatherDistribution(): Collection
    {
        $rows = RecommendationLog::query()
            ->select(['weather_used'])
            ->latest('created_at')
            ->limit(600)
            ->get()
            ->groupBy(fn (RecommendationLog $log): string => filled($log->weather_used) ? strtolower((string) $log->weather_used) : 'unknown')
            ->map(fn (Collection $items, string $weather): array => [
                'weather' => $weather,
                'label' => $this->weatherLabel($weather),
                'total' => $items->count(),
                'success' => $items->where('status', 'success')->count(),
                'failed' => $items->where('status', 'failed')->count(),
                'tone' => $this->weatherTone($weather),
            ])
            ->sortByDesc('total')
            ->values();

        return $rows;
    }

    public function getSourceDistribution(): Collection
    {
        return RecommendationLog::query()
            ->select(['weather_source', 'status'])
            ->latest('created_at')
            ->limit(600)
            ->get()
            ->groupBy(function (RecommendationLog $log): string {
                $source = trim((string) $log->weather_source);

                if ($source === '') {
                    return 'Unknown';
                }

                if (str_contains(strtolower($source), 'bmkg') || str_contains(strtolower($source), 'adm4')) {
                    return $source;
                }

                return ucfirst($source);
            })
            ->map(fn (Collection $items, string $source): array => [
                'source' => $source,
                'total' => $items->count(),
                'success' => $items->where('status', 'success')->count(),
                'failed' => $items->where('status', 'failed')->count(),
            ])
            ->sortByDesc('total')
            ->take(6)
            ->values();
    }

    public function getTopDestinationDistribution(): Collection
    {
        return RecommendationLog::query()
            ->where('status', 'success')
            ->latest('created_at')
            ->limit(300)
            ->get()
            ->map(fn (RecommendationLog $log): ?string => $log->top_destination_name)
            ->filter(fn (?string $name): bool => filled($name))
            ->map(fn (string $name): string => trim($name))
            ->countBy()
            ->map(fn (int $total, string $name): array => [
                'name' => $name,
                'total' => $total,
            ])
            ->sortByDesc('total')
            ->take(8)
            ->values();
    }

    public function getRecentUsersDistribution(): Collection
    {
        return RecommendationLog::query()
            ->with('user:id,name,email')
            ->latest('created_at')
            ->limit(250)
            ->get()
            ->groupBy(fn (RecommendationLog $log): string => $log->user?->name ?: 'Guest / Unknown')
            ->map(fn (Collection $items, string $name): array => [
                'name' => $name,
                'total' => $items->count(),
                'success' => $items->where('status', 'success')->count(),
                'failed' => $items->where('status', 'failed')->count(),
                'last_at' => $items->sortByDesc('created_at')->first()?->created_at,
            ])
            ->sortByDesc('total')
            ->take(7)
            ->values();
    }

    public function getHourlyRecommendationTrend(): array
    {
        $start = now()->subHours(11)->startOfHour();

        $logs = RecommendationLog::query()
            ->select(['created_at', 'status', 'response_time_ms'])
            ->where('created_at', '>=', $start)
            ->get();

        $maxTotal = 1;
        $items = [];

        for ($index = 0; $index < 12; $index++) {
            $hour = $start->copy()->addHours($index);
            $nextHour = $hour->copy()->addHour();

            $hourItems = $logs->filter(function (RecommendationLog $log) use ($hour, $nextHour): bool {
                $createdAt = $log->created_at instanceof Carbon
                    ? $log->created_at
                    : Carbon::parse($log->created_at);

                return $createdAt->greaterThanOrEqualTo($hour) && $createdAt->lessThan($nextHour);
            });

            $total = $hourItems->count();
            $success = $hourItems->where('status', 'success')->count();
            $failed = $hourItems->where('status', 'failed')->count();
            $averageResponse = (int) round((float) $hourItems->whereNotNull('response_time_ms')->avg('response_time_ms'));

            $maxTotal = max($maxTotal, $total);

            $items[] = [
                'label' => $hour->format('H:00'),
                'total' => $total,
                'success' => $success,
                'failed' => $failed,
                'average_response' => $averageResponse,
            ];
        }

        return [
            'items' => collect($items)->map(function (array $item) use ($maxTotal): array {
                $item['height'] = $maxTotal > 0 ? max(8, round(($item['total'] / $maxTotal) * 100, 1)) : 8;
                $item['success_height'] = $item['total'] > 0 ? round(($item['success'] / $item['total']) * 100, 1) : 0;
                $item['failed_height'] = $item['total'] > 0 ? round(($item['failed'] / $item['total']) * 100, 1) : 0;

                return $item;
            })->all(),
            'max_total' => $maxTotal,
        ];
    }

    public function getLatestRecommendationPreview(): Collection
    {
        return RecommendationLog::query()
            ->with('user:id,name,email')
            ->latest('created_at')
            ->limit(8)
            ->get();
    }

    protected function resolveHealthLabel(float $successRate, float $failedRate, int $avgResponse): string
    {
        if ($successRate >= 90 && $avgResponse <= 1500) {
            return 'Pipeline Sehat';
        }

        if ($successRate >= 75 && $failedRate <= 20) {
            return 'Stabil';
        }

        if ($successRate <= 50 || $failedRate >= 35) {
            return 'Perlu Dicek';
        }

        return 'Monitoring';
    }

    protected function resolveHealthTone(float $successRate, float $failedRate, int $avgResponse): string
    {
        if ($successRate >= 90 && $avgResponse <= 1500) {
            return 'emerald';
        }

        if ($successRate >= 75 && $failedRate <= 20) {
            return 'blue';
        }

        if ($successRate <= 50 || $failedRate >= 35) {
            return 'rose';
        }

        return 'amber';
    }

    protected function responsePercent(int $response): float
    {
        if ($response <= 0) {
            return 0;
        }

        if ($response <= 500) {
            return 100;
        }

        if ($response >= 5000) {
            return 12;
        }

        return round(100 - (($response - 500) / 4500 * 88), 1);
    }

    protected function weatherLabel(string $weather): string
    {
        return match (strtolower($weather)) {
            'cerah' => 'Cerah',
            'hujan' => 'Hujan',
            'mendung' => 'Mendung',
            'berawan' => 'Berawan',
            'unknown' => 'Unknown',
            default => ucfirst($weather),
        };
    }

    protected function weatherTone(string $weather): string
    {
        return match (strtolower($weather)) {
            'cerah' => 'blue',
            'hujan' => 'cyan',
            'mendung' => 'slate',
            'berawan' => 'amber',
            default => 'purple',
        };
    }
}
