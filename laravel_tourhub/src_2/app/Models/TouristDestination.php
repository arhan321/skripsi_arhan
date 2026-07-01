<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TouristDestination extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'id_tempat',
        'nama_tempat_wisata',
        'kategori',
        'tipe_wisata',
        'kecamatan',
        'kabupaten_kota',
        'rating',
        'jumlah_rating',
        'latitude',
        'longitude',
        'link_google_maps',
        'link_gambar',
        'deskripsi',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'float',
            'jumlah_rating' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByKategori(Builder $query, ?string $kategori): Builder
    {
        return $query->when(
            filled($kategori),
            fn (Builder $query): Builder => $query->where('kategori', $kategori)
        );
    }

    public function scopeByKabupatenKota(Builder $query, ?string $kabupatenKota): Builder
    {
        return $query->when(
            filled($kabupatenKota),
            fn (Builder $query): Builder => $query->where('kabupaten_kota', $kabupatenKota)
        );
    }

    public function toMlPayload(): array
    {
        return [
            'id_tempat' => (string) $this->id_tempat,
            'nama_tempat_wisata' => (string) $this->nama_tempat_wisata,
            'kategori' => (string) $this->kategori,
            'tipe_wisata' => $this->tipe_wisata,
            'kecamatan' => $this->kecamatan,
            'kabupaten_kota' => $this->kabupaten_kota,
            'rating' => (float) $this->rating,
            'jumlah_rating' => (int) $this->jumlah_rating,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'link_google_maps' => $this->link_google_maps,
            'link_gambar' => $this->link_gambar,
            'deskripsi' => $this->deskripsi,
            'is_active' => (bool) $this->is_active,
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
