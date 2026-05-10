<?php

namespace App\Filament\Admin\Resources\RecommendationLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RecommendationLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('weather_used')
                    ->label('Cuaca')
                    ->badge()
                    ->sortable(),

                TextColumn::make('weather_source')
                    ->label('Sumber Cuaca')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('total_candidates')
                    ->label('Kandidat')
                    ->sortable(),

                TextColumn::make('top_destination_name')
                    ->label('Top Destination')
                    ->limit(45)
                    ->placeholder('-'),

                TextColumn::make('response_time_ms')
                    ->label('Response')
                    ->suffix(' ms')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('-')
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

                SelectFilter::make('weather_source')
                    ->label('Sumber Cuaca')
                    ->options([
                        'manual' => 'Manual',
                        'bmkg' => 'BMKG',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
