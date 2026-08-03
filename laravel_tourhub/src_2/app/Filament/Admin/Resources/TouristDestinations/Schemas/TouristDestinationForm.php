<?php

namespace App\Filament\Admin\Resources\TouristDestinations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TouristDestinationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Utama')
                    ->description('Data utama destinasi wisata yang akan digunakan oleh sistem rekomendasi.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('id_tempat')
                                    ->label('ID Tempat')
                                    ->required()
                                    ->maxLength(100)
                                    ->unique(ignoreRecord: true),

                                TextInput::make('nama_tempat_wisata')
                                    ->label('Nama Tempat Wisata')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                Select::make('kategori')
                                    ->label('Kategori')
                                    ->required()
                                    ->native(false)
                                    ->searchable()
                                    ->options([
                                        'Alam' => 'Alam',
                                        'Budaya' => 'Budaya',
                                        'Rekreasi' => 'Rekreasi',
                                        'Umum' => 'Umum',
                                    ]),

                                Select::make('tipe_wisata')
                                    ->label('Tipe Wisata')
                                    ->required()
                                    ->native(false)
                                    ->options([
                                        'indoor' => 'Indoor',
                                        'outdoor' => 'Outdoor',
                                        'mixed' => 'Mixed',
                                    ]),

                                Textarea::make('deskripsi')
                                    ->label('Deskripsi')
                                    ->rows(4)
                                    ->maxLength(3000)
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Lokasi')
                    ->description('Lokasi destinasi digunakan untuk filter kecamatan/kabupaten dan link peta.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('kabupaten_kota')
                                    ->label('Kabupaten/Kota')
                                    ->maxLength(100)
                                    ->datalist([
                                        'Kabupaten Badung',
                                        'Kabupaten Bangli',
                                        'Kabupaten Buleleng',
                                        'Kabupaten Gianyar',
                                        'Kabupaten Jembrana',
                                        'Kabupaten Karangasem',
                                        'Kabupaten Klungkung',
                                        'Kabupaten Tabanan',
                                        'Kota Denpasar',
                                    ]),

                                TextInput::make('kecamatan')
                                    ->label('Kecamatan')
                                    ->maxLength(100),

                                TextInput::make('latitude')
                                    ->label('Latitude')
                                    ->numeric()
                                    ->minValue(-90)
                                    ->maxValue(90)
                                    ->step('0.0000001')
                                    ->required(),

                                TextInput::make('longitude')
                                    ->label('Longitude')
                                    ->numeric()
                                    ->minValue(-180)
                                    ->maxValue(180)
                                    ->step('0.0000001')
                                    ->required(),
                            ]),
                    ]),

                Section::make('Rating, Link, dan Status')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('rating')
                                    ->label('Rating')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->step('0.01')
                                    ->required()
                                    ->default(0),

                                TextInput::make('jumlah_rating')
                                    ->label('Jumlah Rating')
                                    ->numeric()
                                    ->minValue(0)
                                    ->integer()
                                    ->required()
                                    ->default(0),

                                TextInput::make('link_google_maps')
                                    ->label('Link Google Maps')
                                    ->url()
                                    ->maxLength(2048)
                                    ->columnSpanFull(),

                                TextInput::make('link_gambar')
                                    ->label('Link Gambar')
                                    ->url()
                                    ->maxLength(2048)
                                    ->columnSpanFull(),

                                Toggle::make('is_active')
                                    ->label('Aktif digunakan untuk rekomendasi')
                                    ->helperText('Jika dimatikan, destinasi tidak akan dikirim ke FastAPI.')
                                    ->default(true)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
