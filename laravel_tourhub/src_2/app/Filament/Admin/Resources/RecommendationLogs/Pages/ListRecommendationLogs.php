<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\RecommendationLogs\Pages;

use App\Filament\Admin\Resources\RecommendationLogs\RecommendationLogResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

final class ListRecommendationLogs extends ListRecords
{
    protected static string $resource = RecommendationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_rekomendasi')
                ->label('Test Rekomendasi')
                ->icon('heroicon-o-sparkles')
                ->url(route('tourhub.recommendation.index'))
                ->openUrlInNewTab(),
        ];
    }
}
