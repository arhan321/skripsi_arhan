<?php

namespace App\Filament\Admin\Resources\SystemRatings\Schemas;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SystemRatingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ringkasan Rating Sistem')
                    ->description('Feedback user terhadap kualitas sistem rekomendasi TourHub secara keseluruhan. Rating ini satu kali per user dan bukan rating destinasi wisata.')
                    ->schema([
                        TextInput::make('user.name')
                            ->label('Nama User')
                            ->formatStateUsing(fn ($record): string => $record?->user?->name ?? '-')
                            ->disabled(),

                        TextInput::make('user.email')
                            ->label('Email User')
                            ->formatStateUsing(fn ($record): string => $record?->user?->email ?? '-')
                            ->disabled(),

                        TextInput::make('rating')
                            ->label('Rating Sistem')
                            ->formatStateUsing(fn ($state): string => filled($state) ? self::formatRating((int) $state) : '-')
                            ->disabled(),

                        TextInput::make('rating_label')
                            ->label('Keterangan Rating')
                            ->formatStateUsing(fn ($record): string => self::resolveSentiment((int) ($record?->rating ?? 0)))
                            ->disabled(),

                        TextInput::make('platform')
                            ->label('Platform')
                            ->formatStateUsing(fn ($state): string => filled($state) ? self::formatText((string) $state) : '-')
                            ->disabled(),

                        TextInput::make('source')
                            ->label('Sumber Halaman')
                            ->formatStateUsing(fn ($state): string => filled($state) ? self::formatText((string) $state) : '-')
                            ->disabled(),

                        TextInput::make('rated_at')
                            ->label('Waktu Rating')
                            ->formatStateUsing(fn ($state): string => self::formatDateTime($state))
                            ->disabled(),

                        TextInput::make('created_at')
                            ->label('Data Dibuat')
                            ->formatStateUsing(fn ($state): string => self::formatDateTime($state))
                            ->disabled(),

                        TextInput::make('updated_at')
                            ->label('Terakhir Diperbarui')
                            ->formatStateUsing(fn ($state): string => self::formatDateTime($state))
                            ->disabled(),
                    ])
                    ->columns(3),

                Section::make('Komentar / Masukan User')
                    ->description('Bagian ini menampilkan komentar opsional dari user tentang kualitas sistem TourHub. Tidak ada snapshot rekomendasi karena rating ini menilai sistem secara umum.')
                    ->schema([
                        Textarea::make('comment')
                            ->label('Komentar User')
                            ->rows(8)
                            ->placeholder('User tidak memberikan komentar.')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function formatRating(int $rating): string
    {
        if ($rating <= 0) {
            return '-';
        }

        $rating = max(1, min(5, $rating));

        return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) . ' (' . $rating . '/5)';
    }

    private static function resolveSentiment(int $rating): string
    {
        return match (true) {
            $rating >= 5 => 'Sangat membantu',
            $rating >= 4 => 'Membantu',
            $rating >= 3 => 'Cukup membantu',
            $rating >= 2 => 'Kurang membantu',
            $rating >= 1 => 'Tidak membantu',
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

    /**
     * Filament kadang mengirim state datetime sebagai Carbon, string, atau null.
     * Method ini dibuat aman supaya tidak muncul error saat detail rating dibuka.
     */
    private static function formatDateTime(mixed $state): string
    {
        if (blank($state)) {
            return '-';
        }

        if ($state instanceof CarbonInterface) {
            return $state->format('d M Y H:i');
        }

        try {
            return Carbon::parse((string) $state)->format('d M Y H:i');
        } catch (\Throwable) {
            return (string) $state;
        }
    }
}
