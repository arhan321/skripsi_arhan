<?php

namespace App\Filament\Admin\Resources\TouristDestinations;

use App\Filament\Admin\Resources\TouristDestinations\Pages\CreateTouristDestination;
use App\Filament\Admin\Resources\TouristDestinations\Pages\EditTouristDestination;
use App\Filament\Admin\Resources\TouristDestinations\Pages\ListTouristDestinations;
use App\Filament\Admin\Resources\TouristDestinations\Schemas\TouristDestinationForm;
use App\Filament\Admin\Resources\TouristDestinations\Tables\TouristDestinationsTable;
use App\Models\TouristDestination;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TouristDestinationResource extends Resource
{
    protected static ?string $model = TouristDestination::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static string|UnitEnum|null $navigationGroup = 'TourHub';

    protected static ?string $navigationLabel = 'Data Wisata';

    protected static ?string $modelLabel = 'Destinasi Wisata';

    protected static ?string $pluralModelLabel = 'Data Wisata';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return TouristDestinationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TouristDestinationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTouristDestinations::route('/'),
            'create' => CreateTouristDestination::route('/create'),
            'edit' => EditTouristDestination::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canManageTouristDestinations();
    }

    public static function canViewAny(): bool
    {
        return static::canManageTouristDestinations();
    }

    public static function canView(Model $record): bool
    {
        return static::canManageTouristDestinations();
    }

    public static function canCreate(): bool
    {
        return static::canManageTouristDestinations();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canManageTouristDestinations();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canManageTouristDestinations();
    }

    public static function canDeleteAny(): bool
    {
        return static::canManageTouristDestinations();
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::canManageTouristDestinations();
    }

    public static function canForceDeleteAny(): bool
    {
        return static::canManageTouristDestinations();
    }

    public static function canRestore(Model $record): bool
    {
        return static::canManageTouristDestinations();
    }

    public static function canRestoreAny(): bool
    {
        return static::canManageTouristDestinations();
    }

    public static function canReplicate(Model $record): bool
    {
        return static::canManageTouristDestinations();
    }

    private static function canManageTouristDestinations(): bool
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