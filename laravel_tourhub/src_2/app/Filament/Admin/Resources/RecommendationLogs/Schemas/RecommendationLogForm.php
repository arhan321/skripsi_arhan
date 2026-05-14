<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\RecommendationLogs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class RecommendationLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ringkasan Rekomendasi')
                    ->description('Informasi utama hasil request Laravel ke FastAPI ML.')
                    ->schema([
                        TextInput::make('status')
                            ->label('Status')
                            ->disabled(),

                        TextInput::make('weather_source')
                            ->label('Sumber Cuaca')
                            ->disabled(),

                        TextInput::make('weather_used')
                            ->label('Cuaca Dipakai')
                            ->disabled(),

                        TextInput::make('total_candidates')
                            ->label('Total Kandidat')
                            ->disabled(),

                        TextInput::make('response_time_ms')
                            ->label('Response Time')
                            ->suffix('ms')
                            ->disabled(),
                    ])
                    ->columns(5),

                Section::make('Request Payload')
                    ->description('Parameter yang dikirim dari Laravel ke FastAPI.')
                    ->schema([
                        Textarea::make('request_payload')
                            ->label('Request JSON')
                            ->formatStateUsing(fn ($state) => is_array($state)
                                ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                : $state)
                            ->rows(14)
                            ->disabled()
                            ->columnSpanFull(),
                    ]),

                Section::make('Response Payload')
                    ->description('Response lengkap dari FastAPI ML.')
                    ->schema([
                        Textarea::make('response_payload')
                            ->label('Response JSON')
                            ->formatStateUsing(fn ($state) => is_array($state)
                                ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                : $state)
                            ->rows(20)
                            ->disabled()
                            ->columnSpanFull(),
                    ]),

                Section::make('Error')
                    ->description('Akan terisi jika request ke ML API gagal.')
                    ->schema([
                        Textarea::make('error_message')
                            ->label('Error Message')
                            ->rows(5)
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
