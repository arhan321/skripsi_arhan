<?php

namespace App\Filament\Admin\Resources\TouristDestinations\Pages;

use App\Filament\Admin\Resources\TouristDestinations\TouristDestinationResource;
use App\Models\TouristDestination;
use App\Services\TourHubMlService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

class ListTouristDestinations extends ListRecords
{
    protected static string $resource = TouristDestinationResource::class;

    /**
     * Custom HTML view untuk halaman manajemen data wisata.
     * Tabel Filament tetap ditampilkan di dalam Blade melalui {{ $this->table }}.
     */
    protected string $view = 'filament.admin.resources.tourist-destinations.pages.list';

    public function getTitle(): string
    {
        return 'Manajemen Data Wisata';
    }

    public function getHeading(): string
    {
        return 'Manajemen Data Wisata';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola master data destinasi wisata Bali yang digunakan sistem rekomendasi TourHub.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Destinasi Wisata')
                ->icon('heroicon-o-plus-circle')
                ->color('primary'),

            Action::make('reloadMlDataset')
                ->label('Reload Dataset FastAPI')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('info')
                ->requiresConfirmation()
                ->modalIcon('heroicon-o-arrow-path')
                ->modalHeading('Reload dataset FastAPI?')
                ->modalDescription('Aksi ini akan meminta FastAPI membaca ulang semua destinasi aktif dari database Laravel. Gunakan setelah menambah, mengedit, menonaktifkan, atau menghapus data wisata.')
                ->modalSubmitActionLabel('Ya, reload sekarang')
                ->action(function (): void {
                    try {
                        app(TourHubMlService::class)->reloadDataset();

                        Notification::make()
                            ->title('Dataset FastAPI berhasil di-reload')
                            ->body('Rekomendasi terbaru sudah memakai data wisata aktif dari database Laravel.')
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        report($e);

                        Notification::make()
                            ->title('Gagal reload dataset FastAPI')
                            ->body('Data Laravel tetap tersimpan. Cek koneksi FastAPI, TOURHUB_ML_BASE_URL, dan TOURHUB_ML_API_KEY.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function getCreateDestinationUrl(): string
    {
        return TouristDestinationResource::getUrl('create');
    }

    public function getEditDestinationUrl(TouristDestination $destination): string
    {
        return TouristDestinationResource::getUrl('edit', [
            'record' => $destination->getKey(),
        ]);
    }

    public function getInternalDatasetUrl(): string
    {
        return url('/api/internal/tourist-destinations');
    }

    public function getFastApiBaseUrl(): string
    {
        return rtrim((string) config('tourhub.ml_base_url', env('TOURHUB_ML_BASE_URL', '')), '/');
    }

    public function getDestinationStats(): array
    {
        try {
            $query = TouristDestination::query();

            $total = (clone $query)->count();
            $active = (clone $query)->where('is_active', true)->count();
            $inactive = max($total - $active, 0);
            $reviews = (int) (clone $query)->sum('jumlah_rating');
            $averageRating = (float) (clone $query)->where('rating', '>', 0)->avg('rating');
            $bestRating = (float) (clone $query)->max('rating');
            $cities = (clone $query)
                ->whereNotNull('kabupaten_kota')
                ->where('kabupaten_kota', '!=', '')
                ->distinct()
                ->count('kabupaten_kota');
            $subdistricts = (clone $query)
                ->whereNotNull('kecamatan')
                ->where('kecamatan', '!=', '')
                ->distinct()
                ->count('kecamatan');

            $withImages = (clone $query)->whereNotNull('link_gambar')->where('link_gambar', '!=', '')->count();
            $withMaps = (clone $query)->whereNotNull('link_google_maps')->where('link_google_maps', '!=', '')->count();
            $withCoordinates = (clone $query)->whereNotNull('latitude')->whereNotNull('longitude')->count();
            $validBaliCoordinates = (clone $query)
                ->whereBetween('latitude', [-8.90, -8.00])
                ->whereBetween('longitude', [114.40, 115.80])
                ->count();

            $zeroRating = (clone $query)->where(function (Builder $builder): void {
                $builder->whereNull('rating')->orWhere('rating', '<=', 0);
            })->count();

            $highRating = (clone $query)
                ->where('is_active', true)
                ->where('rating', '>=', 4.5)
                ->count();

            $readyForRecommendation = (clone $query)
                ->where('is_active', true)
                ->whereNotNull('nama_tempat_wisata')
                ->where('nama_tempat_wisata', '!=', '')
                ->whereNotNull('kategori')
                ->where('kategori', '!=', '')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('rating', '>', 0)
                ->count();

            $needsAttention = (clone $query)->where(function (Builder $builder): void {
                $builder
                    ->where('is_active', false)
                    ->orWhereNull('nama_tempat_wisata')
                    ->orWhere('nama_tempat_wisata', '')
                    ->orWhereNull('kategori')
                    ->orWhere('kategori', '')
                    ->orWhereNull('latitude')
                    ->orWhereNull('longitude')
                    ->orWhereNull('rating')
                    ->orWhere('rating', '<=', 0)
                    ->orWhereNull('link_gambar')
                    ->orWhere('link_gambar', '')
                    ->orWhereNull('link_google_maps')
                    ->orWhere('link_google_maps', '');
            })->count();

            $updatedToday = (clone $query)->whereDate('updated_at', now()->toDateString())->count();
            $latestUpdatedAt = (clone $query)->max('updated_at');
            $createdToday = (clone $query)->whereDate('created_at', now()->toDateString())->count();

            return [
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive,
                'reviews' => $reviews,
                'average_rating' => $averageRating,
                'best_rating' => $bestRating,
                'cities' => $cities,
                'subdistricts' => $subdistricts,
                'active_percentage' => $this->percentage($active, $total),
                'with_images' => $withImages,
                'with_maps' => $withMaps,
                'with_coordinates' => $withCoordinates,
                'valid_bali_coordinates' => $validBaliCoordinates,
                'missing_images' => max($total - $withImages, 0),
                'missing_maps' => max($total - $withMaps, 0),
                'missing_coordinates' => max($total - $withCoordinates, 0),
                'zero_rating' => $zeroRating,
                'high_rating' => $highRating,
                'ready_for_recommendation' => $readyForRecommendation,
                'ready_percentage' => $this->percentage($readyForRecommendation, $total),
                'image_percentage' => $this->percentage($withImages, $total),
                'maps_percentage' => $this->percentage($withMaps, $total),
                'coordinate_percentage' => $this->percentage($withCoordinates, $total),
                'valid_coordinate_percentage' => $this->percentage($validBaliCoordinates, $total),
                'needs_attention' => $needsAttention,
                'updated_today' => $updatedToday,
                'created_today' => $createdToday,
                'latest_updated_at' => $latestUpdatedAt,
            ];
        } catch (Throwable $e) {
            report($e);

            return $this->emptyStats();
        }
    }

    public function getCategorySummary(): Collection
    {
        try {
            return TouristDestination::query()
                ->selectRaw('kategori, COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_total, AVG(NULLIF(rating, 0)) as average_rating')
                ->whereNotNull('kategori')
                ->where('kategori', '!=', '')
                ->groupBy('kategori')
                ->orderByDesc('total')
                ->limit(6)
                ->get();
        } catch (Throwable $e) {
            report($e);

            return collect();
        }
    }

    public function getTypeSummary(): Collection
    {
        try {
            return TouristDestination::query()
                ->selectRaw('tipe_wisata, COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_total')
                ->whereNotNull('tipe_wisata')
                ->where('tipe_wisata', '!=', '')
                ->groupBy('tipe_wisata')
                ->orderByDesc('total')
                ->get();
        } catch (Throwable $e) {
            report($e);

            return collect();
        }
    }

    public function getLocationSummary(): Collection
    {
        try {
            return TouristDestination::query()
                ->selectRaw('kabupaten_kota, COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_total, AVG(NULLIF(rating, 0)) as average_rating, SUM(jumlah_rating) as reviews')
                ->whereNotNull('kabupaten_kota')
                ->where('kabupaten_kota', '!=', '')
                ->groupBy('kabupaten_kota')
                ->orderByDesc('total')
                ->limit(9)
                ->get();
        } catch (Throwable $e) {
            report($e);

            return collect();
        }
    }

    public function getFeaturedDestinations(): Collection
    {
        try {
            return TouristDestination::query()
                ->where('is_active', true)
                ->whereNotNull('link_gambar')
                ->where('link_gambar', '!=', '')
                ->orderByDesc('rating')
                ->orderByDesc('jumlah_rating')
                ->limit(5)
                ->get();
        } catch (Throwable $e) {
            report($e);

            return collect();
        }
    }

    public function getLatestDestinations(): Collection
    {
        try {
            return TouristDestination::query()
                ->latest('updated_at')
                ->limit(6)
                ->get();
        } catch (Throwable $e) {
            report($e);

            return collect();
        }
    }

    public function getDatasetQualityItems(): array
    {
        $stats = $this->getDestinationStats();

        return [
            [
                'label' => 'Siap Rekomendasi',
                'value' => $stats['ready_for_recommendation'],
                'percent' => $stats['ready_percentage'],
                'description' => 'Aktif, punya kategori, koordinat, dan rating.',
                'tone' => 'emerald',
            ],
            [
                'label' => 'Gambar Tersedia',
                'value' => $stats['with_images'],
                'percent' => $stats['image_percentage'],
                'description' => 'Data memiliki link gambar untuk tampilan mobile.',
                'tone' => 'blue',
            ],
            [
                'label' => 'Link Maps',
                'value' => $stats['with_maps'],
                'percent' => $stats['maps_percentage'],
                'description' => 'Data bisa diarahkan ke Google Maps.',
                'tone' => 'amber',
            ],
            [
                'label' => 'Koordinat Bali',
                'value' => $stats['valid_bali_coordinates'],
                'percent' => $stats['valid_coordinate_percentage'],
                'description' => 'Koordinat berada pada area Bali.',
                'tone' => 'cyan',
            ],
        ];
    }

    public function getIssueItems(): array
    {
        $stats = $this->getDestinationStats();

        return [
            [
                'label' => 'Perlu Dicek',
                'value' => $stats['needs_attention'],
                'description' => 'Data nonaktif atau memiliki atribut penting yang belum lengkap.',
                'tone' => 'rose',
            ],
            [
                'label' => 'Tanpa Gambar',
                'value' => $stats['missing_images'],
                'description' => 'Akan tampil tanpa thumbnail pada hasil rekomendasi.',
                'tone' => 'orange',
            ],
            [
                'label' => 'Tanpa Maps',
                'value' => $stats['missing_maps'],
                'description' => 'User tidak bisa langsung membuka lokasi destinasi.',
                'tone' => 'yellow',
            ],
            [
                'label' => 'Rating Kosong',
                'value' => $stats['zero_rating'],
                'description' => 'Rating kosong dapat menurunkan kualitas ranking.',
                'tone' => 'slate',
            ],
        ];
    }

    private function percentage(int|float $value, int|float $total): float
    {
        if ((float) $total <= 0) {
            return 0.0;
        }

        return round(((float) $value / (float) $total) * 100, 1);
    }

    private function emptyStats(): array
    {
        return [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'reviews' => 0,
            'average_rating' => 0,
            'best_rating' => 0,
            'cities' => 0,
            'subdistricts' => 0,
            'active_percentage' => 0,
            'with_images' => 0,
            'with_maps' => 0,
            'with_coordinates' => 0,
            'valid_bali_coordinates' => 0,
            'missing_images' => 0,
            'missing_maps' => 0,
            'missing_coordinates' => 0,
            'zero_rating' => 0,
            'high_rating' => 0,
            'ready_for_recommendation' => 0,
            'ready_percentage' => 0,
            'image_percentage' => 0,
            'maps_percentage' => 0,
            'coordinate_percentage' => 0,
            'valid_coordinate_percentage' => 0,
            'needs_attention' => 0,
            'updated_today' => 0,
            'created_today' => 0,
            'latest_updated_at' => null,
        ];
    }
}
