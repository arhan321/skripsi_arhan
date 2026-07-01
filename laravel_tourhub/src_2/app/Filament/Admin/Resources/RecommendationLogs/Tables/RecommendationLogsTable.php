<?php

namespace App\Filament\Admin\Resources\RecommendationLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecommendationLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->description(fn ($record): string => $record->created_at?->diffForHumans() ?? '-')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'success' => 'Success',
                        'failed' => 'Failed',
                        default => filled($state) ? ucfirst((string) $state) : 'Unknown',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (?string $state): string => match ($state) {
                        'success' => 'heroicon-o-check-circle',
                        'failed' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable(),

                TextColumn::make('weather_used')
                    ->label('Cuaca')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match (strtolower((string) $state)) {
                        'cerah' => 'Cerah',
                        'hujan' => 'Hujan',
                        'mendung' => 'Mendung',
                        'berawan' => 'Berawan',
                        'unknown' => 'Unknown',
                        default => filled($state) ? ucfirst((string) $state) : 'Unknown',
                    })
                    ->color(fn (?string $state): string => match (strtolower((string) $state)) {
                        'cerah' => 'info',
                        'hujan' => 'warning',
                        'mendung' => 'gray',
                        'berawan' => 'primary',
                        default => 'gray',
                    })
                    ->icon(fn (?string $state): string => match (strtolower((string) $state)) {
                        'cerah' => 'heroicon-o-sun',
                        'hujan' => 'heroicon-o-cloud',
                        'mendung' => 'heroicon-o-cloud',
                        'berawan' => 'heroicon-o-cloud',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable(),

                TextColumn::make('weather_source')
                    ->label('Sumber Cuaca')
                    ->badge()
                    ->limit(34)
                    ->tooltip(fn ($state): ?string => filled($state) ? (string) $state : null)
                    ->color(fn (?string $state): string => str_contains(strtolower((string) $state), 'bmkg') || str_contains(strtolower((string) $state), 'adm4') ? 'info' : 'gray')
                    ->icon(fn (?string $state): string => str_contains(strtolower((string) $state), 'bmkg') || str_contains(strtolower((string) $state), 'adm4') ? 'heroicon-o-signal' : 'heroicon-o-adjustments-horizontal')
                    ->toggleable(),

                TextColumn::make('total_candidates')
                    ->label('Kandidat')
                    ->alignCenter()
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => number_format((int) $state, 0, ',', '.')),

                TextColumn::make('top_destination_name')
                    ->label('Top Destination')
                    ->weight('bold')
                    ->limit(48)
                    ->placeholder('-')
                    ->tooltip(fn ($state): ?string => filled($state) ? (string) $state : null),

                TextColumn::make('response_time_ms')
                    ->label('Response')
                    ->formatStateUsing(fn ($state): string => filled($state) ? number_format((int) $state, 0, ',', '.') . ' ms' : '-')
                    ->color(fn ($state): string => match (true) {
                        blank($state) => 'gray',
                        (int) $state <= 800 => 'success',
                        (int) $state <= 2000 => 'warning',
                        default => 'danger',
                    })
                    ->icon(fn ($state): string => match (true) {
                        blank($state) => 'heroicon-o-minus-circle',
                        (int) $state <= 800 => 'heroicon-o-bolt',
                        (int) $state <= 2000 => 'heroicon-o-clock',
                        default => 'heroicon-o-exclamation-triangle',
                    })
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->weight('bold')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ]),

                SelectFilter::make('weather_used')
                    ->label('Cuaca')
                    ->options([
                        'cerah' => 'Cerah',
                        'hujan' => 'Hujan',
                        'mendung' => 'Mendung',
                        'berawan' => 'Berawan',
                        'unknown' => 'Unknown',
                    ]),

                SelectFilter::make('weather_source_group')
                    ->label('Sumber Cuaca')
                    ->options([
                        'bmkg' => 'BMKG / ADM4',
                        'manual' => 'Manual',
                        'unknown' => 'Unknown',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return match ($value) {
                            'bmkg' => $query->where(function (Builder $query): void {
                                $query
                                    ->where('weather_source', 'like', 'BMKG%')
                                    ->orWhere('weather_source', 'like', 'bmkg%')
                                    ->orWhere('weather_source', 'like', '%adm4=%');
                            }),
                            'manual' => $query->where(function (Builder $query): void {
                                $query
                                    ->where('weather_source', 'manual')
                                    ->orWhere('weather_source', 'like', 'Manual%');
                            }),
                            'unknown' => $query->where(function (Builder $query): void {
                                $query
                                    ->whereNull('weather_source')
                                    ->orWhere('weather_source', '');
                            }),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-o-eye'),

                DeleteAction::make()
                    ->label('Delete')
                    ->icon('heroicon-o-trash'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultSort('created_at', 'desc');
    }
}
