<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

final class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recommendation_log_id',
        'destination_key',
        'destination_id',
        'destination_name',
        'category',
        'tourism_type',
        'subdistrict',
        'city',
        'rating',
        'review_count',
        'latitude',
        'longitude',
        'google_maps_url',
        'image_url',
        'reason',
        'snapshot',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'review_count' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'snapshot' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recommendationLog(): BelongsTo
    {
        return $this->belongsTo(RecommendationLog::class);
    }

    /**
     * Membuat key unik destinasi.
     *
     * Kenapa tidak wajib pakai id destinasi?
     * Karena hasil rekomendasi berasal dari response ML/FastAPI yang kadang hanya berbentuk snapshot JSON.
     * Jadi key dibuat dari id jika ada, jika tidak ada memakai nama + lokasi + koordinat.
     *
     * @param array<string, mixed> $destination
     */
    public static function makeDestinationKey(array $destination): string
    {
        $destination = self::normalizeDestinationSnapshot($destination);

        $destinationId = self::stringOrNull(
            data_get($destination, 'id_tempat')
            ?? data_get($destination, 'id_wisata')
            ?? data_get($destination, 'id')
            ?? data_get($destination, 'destination_id')
        );

        if ($destinationId !== null) {
            return sha1('id:'.$destinationId);
        }

        $name = self::stringOrNull(
            data_get($destination, 'nama_tempat_wisata')
            ?? data_get($destination, 'nama_wisata')
            ?? data_get($destination, 'destination_name')
            ?? data_get($destination, 'name')
        );

        $subdistrict = self::stringOrNull(
            data_get($destination, 'kecamatan')
            ?? data_get($destination, 'subdistrict')
        );

        $city = self::stringOrNull(
            data_get($destination, 'kabupaten_kota')
            ?? data_get($destination, 'kabupaten')
            ?? data_get($destination, 'city')
        );

        $latitude = self::stringOrNull(data_get($destination, 'latitude') ?? data_get($destination, 'lat'));
        $longitude = self::stringOrNull(data_get($destination, 'longitude') ?? data_get($destination, 'lng') ?? data_get($destination, 'lon'));

        return sha1(mb_strtolower(implode('|', [
            $name,
            $subdistrict,
            $city,
            $latitude,
            $longitude,
        ])));
    }

    /**
     * @param array<string, mixed> $destination
     * @return array<string, mixed>
     */
    public static function normalizeDestinationSnapshot(array $destination): array
    {
        $allowedKeys = [
            'id',
            'id_tempat',
            'id_wisata',
            'destination_id',
            'nama_tempat_wisata',
            'nama_wisata',
            'destination_name',
            'name',
            'kategori',
            'category',
            'tipe_wisata',
            'tourism_type',
            'kecamatan',
            'subdistrict',
            'kabupaten_kota',
            'kabupaten',
            'city',
            'rating',
            'jumlah_rating',
            'jumlah_ulasan',
            'jumlah_review',
            'review_count',
            'user_ratings_total',
            'latitude',
            'lat',
            'longitude',
            'lng',
            'lon',
            'link_google_maps',
            'google_maps_url',
            'maps_url',
            'url_google_maps',
            'link_gambar',
            'image_url',
            'photo_url',
            'gambar',
            'image',
            'alasan',
            'alasan_rekomendasi',
            'recommendation_reason',
            'reason',
            'final_score',
            'cbf_score',
            'rating_score',
            'popularity_score',
            'context_multiplier',
            'status_kesesuaian',
            'status_kunjungan',
            'weather_used',
            'weather_source',
        ];

        $snapshot = Arr::only($destination, $allowedKeys);

        foreach ($snapshot as $key => $value) {
            if (is_string($value)) {
                $snapshot[$key] = trim($value);
            }
        }

        return $snapshot;
    }

    /**
     * @param array<string, mixed> $destination
     * @return array<string, mixed>
     */
    public static function fromRecommendationItem(array $destination): array
    {
        $snapshot = self::normalizeDestinationSnapshot($destination);

        return [
            'destination_key' => self::makeDestinationKey($snapshot),

            'destination_id' => self::stringOrNull(
                data_get($snapshot, 'id_tempat')
                ?? data_get($snapshot, 'id_wisata')
                ?? data_get($snapshot, 'id')
                ?? data_get($snapshot, 'destination_id')
            ),

            'destination_name' => self::stringOrNull(
                data_get($snapshot, 'nama_tempat_wisata')
                ?? data_get($snapshot, 'nama_wisata')
                ?? data_get($snapshot, 'destination_name')
                ?? data_get($snapshot, 'name')
            ) ?? 'Destinasi Wisata',

            'category' => self::stringOrNull(
                data_get($snapshot, 'kategori')
                ?? data_get($snapshot, 'category')
            ),

            'tourism_type' => self::stringOrNull(
                data_get($snapshot, 'tipe_wisata')
                ?? data_get($snapshot, 'tourism_type')
            ),

            'subdistrict' => self::stringOrNull(
                data_get($snapshot, 'kecamatan')
                ?? data_get($snapshot, 'subdistrict')
            ),

            'city' => self::stringOrNull(
                data_get($snapshot, 'kabupaten_kota')
                ?? data_get($snapshot, 'kabupaten')
                ?? data_get($snapshot, 'city')
            ),

            'rating' => self::floatOrNull(data_get($snapshot, 'rating')),

            'review_count' => self::intOrNull(
                data_get($snapshot, 'jumlah_rating')
                ?? data_get($snapshot, 'jumlah_ulasan')
                ?? data_get($snapshot, 'jumlah_review')
                ?? data_get($snapshot, 'review_count')
                ?? data_get($snapshot, 'user_ratings_total')
            ),

            'latitude' => self::floatOrNull(data_get($snapshot, 'latitude') ?? data_get($snapshot, 'lat')),
            'longitude' => self::floatOrNull(data_get($snapshot, 'longitude') ?? data_get($snapshot, 'lng') ?? data_get($snapshot, 'lon')),

            'google_maps_url' => self::stringOrNull(
                data_get($snapshot, 'link_google_maps')
                ?? data_get($snapshot, 'google_maps_url')
                ?? data_get($snapshot, 'maps_url')
                ?? data_get($snapshot, 'url_google_maps')
            ),

            'image_url' => self::stringOrNull(
                data_get($snapshot, 'link_gambar')
                ?? data_get($snapshot, 'image_url')
                ?? data_get($snapshot, 'photo_url')
                ?? data_get($snapshot, 'gambar')
                ?? data_get($snapshot, 'image')
            ),

            'reason' => self::stringOrNull(
                data_get($snapshot, 'alasan')
                ?? data_get($snapshot, 'alasan_rekomendasi')
                ?? data_get($snapshot, 'recommendation_reason')
                ?? data_get($snapshot, 'reason')
            ),

            'snapshot' => $snapshot,
        ];
    }

    /**
     * @param array<string, mixed> $destination
     */
    public static function isWishedByUser(?int $userId, array $destination): bool
    {
        if (! $userId || $destination === []) {
            return false;
        }

        return self::query()
            ->where('user_id', $userId)
            ->where('destination_key', self::makeDestinationKey($destination))
            ->exists();
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private static function floatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private static function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
