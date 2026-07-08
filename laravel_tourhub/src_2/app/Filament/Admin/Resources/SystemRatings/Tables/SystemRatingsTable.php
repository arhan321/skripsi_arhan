<?php

namespace App\Filament\Admin\Resources\SystemRatings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SystemRatingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu Rating')
                    ->dateTime('d M Y H:i')
                    ->description(fn ($record): string => $record->created_at?->diffForHumans() ?? '-')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Nama User')
                    ->weight('bold')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label('Email User')
                    ->placeholder('-')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('rating')
                    ->label('Rating Sistem')
                    ->formatStateUsing(fn ($state): string => self::formatRating((int) $state))
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        (int) $state >= 5 => 'success',
                        (int) $state >= 4 => 'info',
                        (int) $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->icon(fn ($state): string => match (true) {
                        (int) $state >= 4 => 'heroicon-o-star',
                        (int) $state >= 3 => 'heroicon-o-face-smile',
                        default => 'heroicon-o-face-frown',
                    })
                    ->sortable(),

                TextColumn::make('sentiment')
                    ->label('Keterangan')
                    ->state(fn ($record): string => self::resolveSentiment((int) $record->rating))
                    ->badge()
                    ->color(fn ($record): string => match (true) {
                        (int) $record->rating >= 5 => 'success',
                        (int) $record->rating >= 4 => 'info',
                        (int) $record->rating >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->toggleable(),

                TextColumn::make('comment')
                    ->label('Komentar User')
                    ->limit(70)
                    ->placeholder('Tidak ada komentar')
                    ->tooltip(fn ($state): ?string => filled($state) ? (string) $state : null)
                    ->searchable(),

                TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? self::formatText((string) $state) : '-')
                    ->color(fn (?string $state): string => match (strtolower((string) $state)) {
                        'web' => 'info',
                        'mobile', 'flutter' => 'success',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('source')
                    ->label('Sumber Halaman')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? self::formatText((string) $state) : '-')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('rating')
                    ->label('Rating')
                    ->options([
                        5 => '5 - Sangat membantu',
                        4 => '4 - Membantu',
                        3 => '3 - Cukup membantu',
                        2 => '2 - Kurang membantu',
                        1 => '1 - Tidak membantu',
                    ]),

                SelectFilter::make('platform')
                    ->label('Platform')
                    ->options([
                        'web' => 'Web',
                        'mobile' => 'Mobile',
                        'flutter' => 'Flutter',
                    ]),

                Filter::make('has_comment')
                    ->label('Ada Komentar')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('comment')
                        ->where('comment', '!=', '')),

                Filter::make('low_rating')
                    ->label('Rating Rendah')
                    ->query(fn (Builder $query): Builder => $query->where('rating', '<=', 2)),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Detail')
                    ->icon('heroicon-o-eye'),

                DeleteAction::make()
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultSort('created_at', 'desc');
    }

    private static function formatRating(int $rating): string
    {
        if ($rating <= 0) {
            return '-';
        }

        $rating = max(1, min(5, $rating));

        return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) . ' ' . $rating . '/5';
    }

    private static function resolveSentiment(int $rating): string
    {
        return match (true) {
            $rating >= 5 => 'Sangat Membantu',
            $rating >= 4 => 'Membantu',
            $rating >= 3 => 'Cukup Membantu',
            $rating >= 2 => 'Kurang Membantu',
            $rating >= 1 => 'Tidak Membantu',
            default => '-',
        };
    }

    private static function formatText(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '-';
        }

        return ucwords(str_replace('_', ' ', $value));
    }
}
