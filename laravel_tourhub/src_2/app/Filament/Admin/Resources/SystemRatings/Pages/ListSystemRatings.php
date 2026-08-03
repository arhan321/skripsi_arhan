<?php

namespace App\Filament\Admin\Resources\SystemRatings\Pages;

use App\Filament\Admin\Resources\SystemRatings\SystemRatingResource;
use App\Models\SystemRating;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListSystemRatings extends ListRecords
{
    protected static string $resource = SystemRatingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshRatings')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function (): void {
                    $this->dispatch('$refresh');
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'Rating Sistem TourHub';
    }

    public function getSubheading(): ?string
    {
        $stats = $this->getSystemRatingStats();

        return 'Total ' . number_format($stats['total'], 0, ',', '.')
            . ' user sudah memberi rating, rata-rata ' . number_format($stats['average'], 1, ',', '.')
            . '/5, dan ' . number_format($stats['satisfied_rate'], 1, ',', '.')
            . '% user memberi nilai membantu.';
    }

    public function getSystemRatingStats(): array
    {
        $query = SystemRating::query();

        $total = (clone $query)->count();
        $average = (float) ((clone $query)->avg('rating') ?? 0);
        $satisfied = (clone $query)->where('rating', '>=', 4)->count();
        $low = (clone $query)->where('rating', '<=', 2)->count();
        $withComment = (clone $query)
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->count();

        return [
            'total' => $total,
            'average' => round($average, 2),
            'satisfied' => $satisfied,
            'low' => $low,
            'with_comment' => $withComment,
            'satisfied_rate' => $total > 0 ? round(($satisfied / $total) * 100, 1) : 0,
            'low_rate' => $total > 0 ? round(($low / $total) * 100, 1) : 0,
        ];
    }
}
