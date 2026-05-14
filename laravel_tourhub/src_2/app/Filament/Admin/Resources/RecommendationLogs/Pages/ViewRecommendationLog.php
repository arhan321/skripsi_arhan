<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\RecommendationLogs\Pages;

use App\Filament\Admin\Resources\RecommendationLogs\RecommendationLogResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewRecommendationLog extends ViewRecord
{
    protected static string $resource = RecommendationLogResource::class;
}
