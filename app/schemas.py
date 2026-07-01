from typing import List, Optional

from pydantic import BaseModel, Field


class RecommendRequest(BaseModel):
    """Payload untuk endpoint rekomendasi TourHub."""

    kategori_preferensi: List[str] = Field(
        default_factory=list,
        examples=[["Alam", "Budaya"]],
        description="Kategori wisata yang disukai user. Contoh: Alam, Budaya, Rekreasi, Umum.",
    )
    kabupaten_kota: Optional[str] = Field(
        default=None,
        examples=["Kabupaten Gianyar"],
        description="Filter/preferensi lokasi tingkat kabupaten/kota.",
    )
    kecamatan: Optional[str] = Field(
        default=None,
        examples=["Ubud"],
        description="Filter/preferensi lokasi tingkat kecamatan.",
    )
    keywords: List[str] = Field(
        default_factory=list,
        examples=[["pantai", "sunset"]],
        description="Kata kunci tambahan preferensi user.",
    )
    min_rating: Optional[float] = Field(
        default=None,
        ge=0,
        le=5,
        examples=[4.2],
        description="Rating minimal destinasi yang ingin ditampilkan.",
    )
    top_n: int = Field(default=10, ge=1, le=50)

    # Konteks CARS
    weather: Optional[str] = Field(
        default=None,
        examples=["hujan"],
        description="Cuaca manual jika tidak memakai BMKG. Contoh: cerah, berawan, hujan.",
    )
    visit_day: Optional[str] = Field(
        default=None,
        examples=["weekend"],
        description="weekday atau weekend.",
    )
    is_high_season: bool = Field(
        default=False,
        description="True jika periode liburan/high season.",
    )
    use_bmkg: bool = Field(
        default=False,
        description="True untuk mengambil cuaca dari BMKG memakai bmkg_adm4.",
    )
    bmkg_adm4: Optional[str] = Field(
        default=None,
        examples=["31.71.03.1001"],
        description="Kode wilayah administrasi tingkat IV BMKG. Isi jika use_bmkg=True.",
    )


class DestinationResponse(BaseModel):
    id_tempat: str
    nama_tempat_wisata: str
    kategori: str
    tipe_wisata: str
    kecamatan: str
    kabupaten_kota: str
    rating: float
    jumlah_rating: int
    latitude: float
    longitude: float
    link_google_maps: Optional[str] = None
    link_gambar: Optional[str] = None
    deskripsi: Optional[str] = None
    cbf_score: float
    rating_score: float
    popularity_score: float
    context_multiplier: float
    final_score: float
    alasan: str


class RecommendResponse(BaseModel):
    query: dict
    weather_source: str
    weather_used: Optional[str]
    total_candidates: int
    recommendations: List[DestinationResponse]
