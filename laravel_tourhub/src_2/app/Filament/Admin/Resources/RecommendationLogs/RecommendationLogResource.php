<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\RecommendationLogs;

use App\Filament\Admin\Resources\RecommendationLogs\Pages\ListRecommendationLogs;
use App\Filament\Admin\Resources\RecommendationLogs\Pages\ViewRecommendationLog;
use App\Filament\Admin\Resources\RecommendationLogs\Schemas\RecommendationLogForm;
use App\Filament\Admin\Resources\RecommendationLogs\Tables\RecommendationLogsTable;
use App\Models\RecommendationLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

final class RecommendationLogResource extends Resource
{
    protected static ?string $model = RecommendationLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'TourHub';

    protected static ?string $navigationLabel = 'Log Rekomendasi';

    protected static ?string $modelLabel = 'Log Rekomendasi';

    protected static ?string $pluralModelLabel = 'Log Rekomendasi';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return RecommendationLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecommendationLogsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecommendationLogs::route('/'),
            'view' => ViewRecommendationLog::route('/{record}'),
        ];
    }
}
