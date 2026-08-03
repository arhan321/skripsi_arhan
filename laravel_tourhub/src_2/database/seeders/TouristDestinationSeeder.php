<?php

namespace Database\Seeders;

use App\Models\TouristDestination;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class TouristDestinationSeeder extends Seeder
{
    public function run(): void
    {
        $path = (string) config('tourhub.dataset_csv_path');

        if (! file_exists($path)) {
            throw new RuntimeException("File dataset tidak ditemukan: {$path}. Atur TOURHUB_DATASET_CSV_PATH di .env atau salin file CSV ke path tersebut.");
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("File dataset tidak bisa dibuka: {$path}");
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);
            throw new RuntimeException("Header CSV tidak ditemukan: {$path}");
        }

        $header = array_map(fn ($column): string => trim((string) $column), $header);
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($header)) {
                continue;
            }

            $csv = array_combine($header, $row);

            if (! is_array($csv)) {
                continue;
            }

            $name = $this->value($csv, 'nama_tempat_wisata');
            $latitude = $this->floatValue($this->value($csv, 'latitude'));
            $longitude = $this->floatValue($this->value($csv, 'longitude'));

            if ($name === '' || $latitude === null || $longitude === null) {
                continue;
            }

            $idTempat = $this->value($csv, 'id_tempat');

            if ($idTempat === '') {
                $idTempat = 'DST-'.Str::upper(Str::slug($name.'-'.$latitude.'-'.$longitude, '-'));
            }

            $kategori = $this->normalizeKategori($this->value($csv, 'kategori'));
            $tipeWisata = $this->value($csv, 'tipe_wisata') ?: $this->inferTipeWisata($name, $kategori);

            $rows[] = [
                'id_tempat' => $idTempat,
                'nama_tempat_wisata' => $name,
                'kategori' => $kategori,
                'tipe_wisata' => $tipeWisata,
                'kecamatan' => $this->nullableValue($csv, 'kecamatan'),
                'kabupaten_kota' => $this->nullableValue($csv, 'kabupaten_kota'),
                'rating' => $this->floatValue($this->value($csv, 'rating')) ?? 0,
                'jumlah_rating' => $this->integerValue($this->value($csv, 'jumlah_rating')),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'link_google_maps' => $this->nullableValue($csv, 'link_google_maps'),
                'link_gambar' => $this->nullableValue($csv, 'link_gambar'),
                'deskripsi' => $this->nullableValue($csv, 'deskripsi'),
                'is_active' => true,
            ];
        }

        fclose($handle);

        DB::transaction(function () use ($rows): void {
            TouristDestination::withoutEvents(function () use ($rows): void {
                foreach ($rows as $row) {
                    TouristDestination::query()->updateOrCreate(
                        ['id_tempat' => $row['id_tempat']],
                        $row
                    );
                }
            });
        });
    }

    private function value(array $row, string $key): string
    {
        return trim((string) ($row[$key] ?? ''));
    }

    private function nullableValue(array $row, string $key): ?string
    {
        $value = $this->value($row, $key);

        return $value !== '' ? $value : null;
    }

    private function floatValue(string $value): ?float
    {
        if ($value === '') {
            return null;
        }

        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? (float) $value : null;
    }

    private function integerValue(string $value): int
    {
        if ($value === '') {
            return 0;
        }

        $value = preg_replace('/[^0-9]/', '', $value) ?: '0';

        return (int) $value;
    }

    private function normalizeKategori(string $kategori): string
    {
        $kategori = Str::title(Str::lower(trim($kategori)));

        return in_array($kategori, ['Alam', 'Budaya', 'Rekreasi', 'Umum'], true)
            ? $kategori
            : 'Umum';
    }

    private function inferTipeWisata(string $name, string $kategori): string
    {
        $text = Str::of($name)->lower()->ascii()->value();
        $kategori = Str::of($kategori)->lower()->ascii()->value();

        $indoorKeywords = [
            'museum', 'galeri', 'gallery', 'mall', 'plaza', 'theater', 'teater', 'studio', 'indoor', 'art center',
        ];

        $outdoorKeywords = [
            'pantai', 'beach', 'air terjun', 'waterfall', 'bukit', 'hill', 'gunung', 'mount', 'danau', 'lake',
            'taman', 'park', 'sawah', 'rice', 'campuhan', 'river', 'rafting', 'snorkeling', 'diving', 'zoo',
            'monkey forest', 'forest', 'pura', 'temple', 'goa', 'cave',
        ];

        foreach ($indoorKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'indoor';
            }
        }

        foreach ($outdoorKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'outdoor';
            }
        }

        if ($kategori === 'alam') {
            return 'outdoor';
        }

        if ($kategori === 'budaya') {
            return 'mixed';
        }

        return 'mixed';
    }
}
