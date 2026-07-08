<?php

namespace App\Filament\Admin\Resources\SystemRatings;

use App\Filament\Admin\Resources\SystemRatings\Pages\ListSystemRatings;
use App\Filament\Admin\Resources\SystemRatings\Pages\ViewSystemRating;
use App\Filament\Admin\Resources\SystemRatings\Schemas\SystemRatingForm;
use App\Filament\Admin\Resources\SystemRatings\Tables\SystemRatingsTable;
use App\Models\SystemRating;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SystemRatingResource extends Resource
{
    protected static ?string $model = SystemRating::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Rating Sistem';

    protected static ?string $modelLabel = 'Rating Sistem';

    protected static ?string $pluralModelLabel = 'Rating Sistem';

    protected static string|UnitEnum|null $navigationGroup = 'Monitoring TourHub';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'rating';

    public static function form(Schema $schema): Schema
    {
        return SystemRatingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SystemRatingsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'user:id,name,email',
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSystemRatings::route('/'),
            'view' => ViewSystemRating::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canManageSystemRatings()) {
            return null;
        }

        try {
            $count = static::getModel()::query()->count();
        } catch (\Throwable) {
            return null;
        }

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canManageSystemRatings();
    }

    public static function canViewAny(): bool
    {
        return static::canManageSystemRatings();
    }

    public static function canView(Model $record): bool
    {
        return static::canManageSystemRatings();
    }

    public static function canCreate(): bool
    {
        // Rating sistem harus dibuat oleh user dari halaman web/mobile,
        // bukan dibuat manual oleh admin agar data feedback tetap natural.
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        // Admin hanya memantau feedback. Rating tetap milik user.
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return static::canManageSystemRatings();
    }

    public static function canDeleteAny(): bool
    {
        return static::canManageSystemRatings();
    }

    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }

    public static function canRestore(Model $record): bool
    {
        return false;
    }

    public static function canRestoreAny(): bool
    {
        return false;
    }

    public static function canReplicate(Model $record): bool
    {
        return false;
    }

    private static function canManageSystemRatings(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (! method_exists($user, 'hasRole')) {
            return false;
        }

        return $user->hasRole('super_admin');
    }
}
