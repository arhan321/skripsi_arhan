from __future__ import annotations

# Digunakan untuk membaca environment variable,
# seperti API_SECRET_KEY dan lokasi dataset.
import os

# lru_cache digunakan untuk menyimpan objek recommender di memori
# agar dataset dan model tidak dimuat ulang pada setiap request.
from functools import lru_cache

# Path digunakan untuk mengelola alamat atau lokasi file dataset.
from pathlib import Path

# Type hint untuk memperjelas tipe data pada fungsi.
from typing import Any, Dict, Optional

# Import komponen utama FastAPI.
from fastapi import (
    Depends,
    FastAPI,
    HTTPException,
    Query,
    Security,
    status,
)

# APIKeyHeader digunakan untuk mengambil API key
# dari header request bernama X-API-Key.
from fastapi.security import APIKeyHeader

# Fungsi untuk mengambil prakiraan cuaca dari BMKG.
from app.bmkg import fetch_bmkg_weather

# Import konfigurasi dan class utama sistem rekomendasi TourHub.
from app.recommender import (
    RecommenderConfig,
    TourHubRecommender,
)

# Import schema validasi request dan response rekomendasi.
from app.schemas import (
    RecommendRequest,
    RecommendResponse,
)


# ============================================================
# INFORMASI DASAR APLIKASI
# ============================================================

# Judul yang akan ditampilkan pada dokumentasi Swagger.
APP_TITLE = "TourHub Bali FAST API"

# Deskripsi fungsi utama FastAPI TourHub.
APP_DESCRIPTION = (
    "FastAPI untuk rekomendasi destinasi wisata Bali "
    "menggunakan CBF + CARS."
)


# ============================================================
# KONFIGURASI KEAMANAN API
# ============================================================

# Mengambil secret key dari environment variable API_SECRET_KEY.
#
# Jika environment variable tersebut tidak tersedia,
# sistem menggunakan nilai default "123".
API_SECRET_KEY = os.getenv("API_SECRET_KEY", "123")

# Nama header yang digunakan untuk mengirim secret key.
API_KEY_HEADER_NAME = "X-API-Key"

# Membuat konfigurasi autentikasi API key.
#
# auto_error=False berarti FastAPI tidak langsung menghasilkan error
# ketika header kosong. Pemeriksaan akan dilakukan secara manual
# melalui fungsi verify_api_key().
api_key_header = APIKeyHeader(
    name=API_KEY_HEADER_NAME,
    auto_error=False,
    description="Masukkan secret key TourHub.",
)


def verify_api_key(
    api_key: Optional[str] = Security(api_key_header),
) -> str:
    """
    Memeriksa secret key dari header request.

    API key dapat dimasukkan melalui tombol Authorize pada Swagger
    atau dikirim langsung melalui header X-API-Key.

    Parameters:
        api_key:
            Secret key yang diperoleh dari header X-API-Key.

    Returns:
        Secret key apabila nilainya sesuai.

    Raises:
        HTTPException 401 apabila API key salah atau tidak diisi.
    """

    # Bandingkan API key dari request dengan key milik server.
    if api_key != API_SECRET_KEY:
        # Jika berbeda atau tidak diisi, hentikan request
        # dan kembalikan status 401 Unauthorized.
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail=(
                "Secret key salah atau belum diisi. "
                "Silakan klik tombol Authorize di Swagger "
                "dan masukkan secret key yang valid."
            ),
        )

    # Jika valid, kembalikan API key.
    return api_key


# ============================================================
# INISIALISASI APLIKASI FASTAPI
# ============================================================

app = FastAPI(
    # Judul aplikasi pada Swagger.
    title=APP_TITLE,

    # Deskripsi aplikasi pada Swagger.
    description=APP_DESCRIPTION,

    # Versi API yang sedang digunakan.
    version="0.2.0",

    # Dokumentasi Swagger tersedia pada /docs.
    docs_url="/docs",

    # Dokumentasi ReDoc dinonaktifkan.
    redoc_url=None,

    # File spesifikasi OpenAPI tersedia pada /openapi.json.
    openapi_url="/openapi.json",

    # verify_api_key dijadikan dependency global.
    #
    # Artinya seluruh endpoint di dalam aplikasi ini
    # harus melewati pemeriksaan API key.
    dependencies=[Depends(verify_api_key)],
)


# ============================================================
# PEMUATAN DATASET DAN SISTEM REKOMENDASI
# ============================================================

@lru_cache(maxsize=1)
def get_recommender() -> TourHubRecommender:
    """
    Membuat dan menyimpan satu objek TourHubRecommender.

    lru_cache(maxsize=1) membuat objek recommender disimpan di memori.
    Dengan demikian, dataset dan model rekomendasi tidak dimuat ulang
    pada setiap request.

    Returns:
        Objek TourHubRecommender yang siap menghasilkan rekomendasi.
    """

    # Menentukan sumber dataset.
    #
    # Nilai dibaca dari environment variable DATA_SOURCE.
    # Jika tidak tersedia, sumber default adalah CSV.
    #
    # strip() menghapus spasi di awal dan akhir.
    # lower() mengubah nilai menjadi huruf kecil.
    data_source = os.getenv(
        "DATA_SOURCE",
        "csv",
    ).strip().lower()

    # Menentukan lokasi file dataset CSV.
    #
    # Jika DATA_PATH tidak tersedia, sistem menggunakan
    # file data/bali_tourist_destination.csv.
    data_path = Path(
        os.getenv(
            "DATA_PATH",
            "data/bali_tourist_destination.csv",
        )
    )

    # Membentuk konfigurasi yang dibutuhkan oleh recommender.
    config = RecommenderConfig(
        # Lokasi dataset CSV.
        data_path=data_path,

        # Sumber dataset, misalnya csv atau laravel.
        data_source=data_source,

        # URL Laravel yang menyediakan dataset apabila
        # sumber data berasal dari Laravel.
        laravel_dataset_url=os.getenv(
            "LARAVEL_DATASET_URL"
        ),

        # Key internal untuk komunikasi FastAPI dengan Laravel.
        laravel_internal_key=os.getenv(
            "LARAVEL_INTERNAL_KEY"
        ),

        # Batas waktu pengambilan dataset dari Laravel.
        #
        # Environment variable menghasilkan string sehingga
        # harus diubah menjadi integer.
        request_timeout=int(
            os.getenv(
                "DATASET_REQUEST_TIMEOUT",
                "30",
            )
        ),
    )

    # Membuat objek recommender berdasarkan konfigurasi.
    #
    # Objek inilah yang akan membaca dataset dan menjalankan
    # proses rekomendasi CBF serta CARS.
    return TourHubRecommender(config)


# ============================================================
# ENDPOINT HALAMAN UTAMA
# ============================================================

@app.get("/")
def root() -> dict:
    """
    Menampilkan informasi bahwa FastAPI TourHub sedang berjalan.

    Returns:
        Informasi service, dokumentasi, autentikasi,
        dan algoritma yang digunakan.
    """

    return {
        # Pesan status API.
        "message": "TourHub Bali ML API is running",

        # Lokasi dokumentasi Swagger.
        "docs": "/docs",

        # Petunjuk autentikasi pada Swagger.
        "auth": (
            "Klik Authorize di Swagger "
            "lalu masukkan secret key."
        ),

        # Algoritma utama yang digunakan oleh TourHub.
        "algorithm": (
            "Content-Based Filtering + "
            "Context-Aware Recommender System"
        ),
    }


# ============================================================
# ENDPOINT PEMERIKSAAN STATUS API
# ============================================================

@app.get("/health")
def health() -> dict:
    """
    Memeriksa status FastAPI dan recommender.

    Endpoint ini juga menampilkan metadata dari sistem rekomendasi
    untuk memastikan dataset berhasil dimuat.
    """

    # Mengambil recommender dari cache.
    recommender = get_recommender()

    return {
        # Menandakan bahwa service berjalan dengan baik.
        "status": "ok",

        # Menampilkan metadata dataset dan recommender.
        "metadata": recommender.metadata(),
    }


# ============================================================
# ENDPOINT METADATA
# ============================================================

@app.get("/metadata")
def metadata() -> dict:
    """
    Mengembalikan metadata dari sistem rekomendasi.

    Metadata dapat berisi informasi mengenai dataset
    dan konfigurasi recommender yang sedang digunakan.
    """

    return get_recommender().metadata()


# ============================================================
# ENDPOINT RELOAD DATASET
# ============================================================

@app.post("/reload-dataset")
def reload_dataset() -> dict:
    """
    Menghapus cache dan membaca ulang dataset.

    Endpoint ini dapat dipanggil oleh Laravel setelah admin
    menambah, mengubah, atau menghapus data destinasi wisata
    melalui Filament.
    """

    # Menghapus objek recommender lama dari cache.
    get_recommender.cache_clear()

    # Membuat objek recommender kembali.
    #
    # Karena cache telah dihapus, proses ini akan membaca
    # dataset terbaru.
    recommender = get_recommender()

    return {
        # Status proses reload.
        "status": "reloaded",

        # Pesan bahwa dataset berhasil dimuat ulang.
        "message": (
            "Dataset rekomendasi berhasil dibaca ulang."
        ),

        # Metadata dataset setelah proses reload.
        "metadata": recommender.metadata(),
    }


# ============================================================
# ENDPOINT DAFTAR DESTINASI
# ============================================================

@app.get("/destinations")
def destinations(
    # Jumlah destinasi yang ditampilkan.
    #
    # default=20 : nilai awal adalah 20.
    # ge=1       : nilai minimal adalah 1.
    # le=100     : nilai maksimal adalah 100.
    limit: int = Query(
        default=20,
        ge=1,
        le=100,
    ),

    # Filter kategori destinasi.
    # Parameter ini bersifat opsional.
    kategori: Optional[str] = Query(
        default=None
    ),

    # Filter kabupaten atau kota.
    # Parameter ini juga bersifat opsional.
    kabupaten_kota: Optional[str] = Query(
        default=None
    ),
) -> dict:
    """
    Mengambil daftar destinasi wisata.

    Daftar destinasi dapat dibatasi berdasarkan kategori,
    kabupaten/kota, dan jumlah data yang ingin ditampilkan.
    """

    # Memanggil fungsi list_destinations pada recommender.
    data = get_recommender().list_destinations(
        limit=limit,
        kategori=kategori,
        kabupaten_kota=kabupaten_kota,
    )

    return {
        # Jumlah destinasi yang dikembalikan.
        "total": len(data),

        # Daftar destinasi.
        "data": data,
    }


# ============================================================
# PENENTUAN KONTEKS CUACA
# ============================================================

def _resolve_weather_context(
    payload: RecommendRequest,
) -> tuple[str, str, Dict[str, Any]]:
    """
    Menentukan kondisi cuaca yang digunakan oleh CARS.

    Cuaca dapat berasal dari:
    1. Input manual pengguna.
    2. Data prakiraan cuaca BMKG.
    3. Nilai default cerah.

    Parameters:
        payload:
            Data permintaan rekomendasi dari pengguna.

    Returns:
        Tuple yang terdiri atas:
        - Cuaca yang digunakan.
        - Sumber informasi cuaca.
        - Detail konteks BMKG.
    """

    # Jika payload.weather tersedia, gunakan cuaca tersebut.
    # Jika tidak tersedia, gunakan cuaca default "cerah".
    weather_used = payload.weather or "cerah"

    # Menentukan sumber cuaca.
    #
    # Jika pengguna mengirimkan cuaca, sumbernya adalah manual.
    # Jika tidak, sumbernya adalah default_cerah.
    weather_source = (
        "manual"
        if payload.weather
        else "default_cerah"
    )

    # Membentuk konteks BMKG awal.
    #
    # Nilai awal menunjukkan belum ditemukan hujan
    # dan fallback yang digunakan adalah cerah.
    bmkg_context: Dict[str, Any] = {
        "rain_detected": False,
        "fallback_weather": "cerah",
    }

    # Bagian ini dijalankan apabila pengguna memilih
    # untuk menggunakan data BMKG.
    if payload.use_bmkg:
        # Kode ADM4 diperlukan untuk mengambil data cuaca
        # berdasarkan wilayah administratif tingkat desa/kelurahan.
        if not payload.bmkg_adm4:
            return (
                # Cuaca fallback.
                "cerah",

                # Informasi alasan penggunaan fallback.
                "default_cerah_no_adm4",

                # Context tambahan untuk response.
                {
                    **bmkg_context,
                    "message": (
                        "use_bmkg=True tetapi bmkg_adm4 kosong, "
                        "fallback ke cerah."
                    ),
                },
            )

        try:
            # Mengambil prakiraan cuaca BMKG berdasarkan kode ADM4.
            bmkg_result = fetch_bmkg_weather(
                payload.bmkg_adm4
            )

            # Menentukan cuaca yang digunakan oleh CARS.
            #
            # Prioritas nilai:
            # 1. weather_group
            # 2. weather_desc
            # 3. weather_desc_en
            # 4. fallback cerah
            weather_used = (
                bmkg_result.get("weather_group")
                or bmkg_result.get("weather_desc")
                or bmkg_result.get("weather_desc_en")
                or "cerah"
            )

            # Menyimpan informasi bahwa cuaca berasal dari BMKG.
            weather_source = (
                f"BMKG adm4={payload.bmkg_adm4}"
            )

            # Mengambil informasi yang diperlukan dari hasil BMKG.
            bmkg_context = {
                # Menandakan apakah prakiraan hujan ditemukan.
                "rain_detected": bool(
                    bmkg_result.get(
                        "rain_detected",
                        False,
                    )
                ),

                # Jumlah slot prakiraan yang mengandung hujan.
                "rain_slots_count": bmkg_result.get(
                    "rain_slots_count",
                    0,
                ),

                # Jumlah slot prakiraan yang diperiksa.
                "forecast_slots_checked": bmkg_result.get(
                    "forecast_slots_checked",
                    0,
                ),

                # Waktu hujan pertama yang ditemukan.
                "first_rain_datetime": bmkg_result.get(
                    "first_rain_datetime"
                ),

                # Deskripsi cuaca dalam bahasa Indonesia.
                "weather_desc": bmkg_result.get(
                    "weather_desc"
                ),

                # Deskripsi cuaca dalam bahasa Inggris.
                "weather_desc_en": bmkg_result.get(
                    "weather_desc_en"
                ),

                # Suhu udara dari BMKG.
                "temperature": bmkg_result.get(
                    "temperature"
                ),

                # Kelembapan udara dari BMKG.
                "humidity": bmkg_result.get(
                    "humidity"
                ),

                # Kecepatan angin dari BMKG.
                "wind_speed": bmkg_result.get(
                    "wind_speed"
                ),

                # Waktu lokal prakiraan yang dipilih.
                "local_datetime": bmkg_result.get(
                    "local_datetime"
                ),
            }

        except Exception:
            # Apabila pengambilan data BMKG gagal,
            # proses rekomendasi tidak dihentikan.
            #
            # Sistem tetap melanjutkan proses menggunakan
            # kondisi cuaca fallback "cerah".
            weather_used = "cerah"

            # Menandakan bahwa fallback dipakai karena BMKG gagal.
            weather_source = "default_cerah_bmkg_error"

            # Menambahkan keterangan kegagalan BMKG.
            bmkg_context = {
                **bmkg_context,
                "message": (
                    "Gagal mengambil data BMKG, "
                    "sistem memakai fallback cerah."
                ),
            }

    # Mengembalikan cuaca, sumber cuaca, dan detail BMKG.
    return (
        str(weather_used),
        weather_source,
        bmkg_context,
    )


# ============================================================
# ENDPOINT UTAMA REKOMENDASI
# ============================================================

@app.post(
    "/recommend",
    response_model=RecommendResponse,
)
def recommend(
    payload: RecommendRequest,
) -> RecommendResponse:
    """
    Menghasilkan rekomendasi destinasi wisata Bali.

    Proses rekomendasi menggunakan:
    1. Content-Based Filtering untuk mengukur kesesuaian
       destinasi dengan preferensi pengguna.
    2. Context-Aware Recommender System untuk menyesuaikan
       hasil berdasarkan cuaca, hari kunjungan, dan musim.
    """

    # Mengambil objek sistem rekomendasi.
    recommender = get_recommender()

    # Menentukan cuaca yang akan digunakan oleh CARS.
    weather_used, weather_source, bmkg_context = (
        _resolve_weather_context(payload)
    )

    # ========================================================
    # PROSES REKOMENDASI DENGAN STRICT WEATHER FILTER
    # ========================================================

    result, meta = recommender.recommend(
        # Kategori wisata yang diminati pengguna.
        kategori_preferensi=payload.kategori_preferensi,

        # Kabupaten atau kota pilihan pengguna.
        kabupaten_kota=payload.kabupaten_kota,

        # Kecamatan pilihan pengguna.
        kecamatan=payload.kecamatan,

        # Kata kunci tambahan dari pengguna.
        keywords=payload.keywords,

        # Rating minimal destinasi.
        min_rating=payload.min_rating,

        # Jumlah rekomendasi yang diminta.
        top_n=payload.top_n,

        # Kondisi cuaca yang telah ditentukan.
        weather=weather_used,

        # Jenis hari kunjungan, misalnya weekday atau weekend.
        visit_day=payload.visit_day,

        # Menandakan apakah kunjungan dilakukan
        # pada musim ramai atau high season.
        is_high_season=payload.is_high_season,

        # Filter cuaca ketat diaktifkan.
        #
        # Destinasi yang tidak sesuai dengan kondisi cuaca
        # dapat dikeluarkan dari kandidat rekomendasi.
        strict_weather_filter=True,
    )

    # ========================================================
    # FALLBACK STRICT WEATHER FILTER
    # ========================================================

    # Periksa apakah hasil rekomendasi kosong dan filter cuaca
    # ketat memang diterapkan oleh recommender.
    if (
        result.empty
        and meta.get("strict_weather_filter_applied")
    ):
        # Jalankan rekomendasi kembali tanpa filter cuaca ketat.
        #
        # Destinasi yang kurang sesuai dengan kondisi cuaca
        # masih dapat muncul, tetapi nilainya disesuaikan
        # menggunakan context multiplier CARS.
        result, fallback_meta = recommender.recommend(
            kategori_preferensi=payload.kategori_preferensi,
            kabupaten_kota=payload.kabupaten_kota,
            kecamatan=payload.kecamatan,
            keywords=payload.keywords,
            min_rating=payload.min_rating,
            top_n=payload.top_n,
            weather=weather_used,
            visit_day=payload.visit_day,
            is_high_season=payload.is_high_season,

            # Filter cuaca ketat dinonaktifkan pada proses fallback.
            strict_weather_filter=False,
        )

        # Menyimpan alasan penggunaan fallback.
        fallback_meta["fallback_reason"] = (
            "Strict weather filter menghasilkan 0 kandidat. "
            "Sistem menampilkan rekomendasi alternatif "
            "dengan penalti CARS untuk destinasi outdoor."
        )

        # Menandakan bahwa fallback digunakan.
        fallback_meta[
            "strict_weather_filter_fallback_used"
        ] = True

        # Metadata hasil fallback menggantikan metadata sebelumnya.
        meta = fallback_meta

    else:
        # Menandakan bahwa fallback tidak digunakan.
        meta[
            "strict_weather_filter_fallback_used"
        ] = False

    # ========================================================
    # PEMBULATAN DAN KONVERSI HASIL
    # ========================================================

    # Membulatkan skor menjadi enam angka di belakang koma.
    #
    # Setelah itu, DataFrame diubah menjadi list of dictionaries
    # agar dapat dikirim sebagai JSON.
    recommendations = result.round(
        {
            # Skor kesesuaian dari Content-Based Filtering.
            "cbf_score": 6,

            # Skor hasil normalisasi rating destinasi.
            "rating_score": 6,

            # Skor popularitas destinasi.
            "popularity_score": 6,

            # Pengali berdasarkan konteks CARS.
            "context_multiplier": 6,

            # Skor akhir rekomendasi setelah seluruh komponen
            # skor dan konteks diperhitungkan.
            "final_score": 6,
        }
    ).to_dict(
        orient="records"
    )

    # ========================================================
    # RESPONSE REKOMENDASI
    # ========================================================

    return RecommendResponse(
        # query berisi input pengguna, metadata proses rekomendasi,
        # serta informasi cuaca dari BMKG.
        query={
            # Memasukkan seluruh data input pengguna.
            **payload.model_dump(),

            # Memasukkan metadata hasil perhitungan rekomendasi.
            **meta,

            # Memasukkan detail konteks BMKG.
            "bmkg_context": bmkg_context,
        },

        # Menjelaskan asal informasi cuaca.
        weather_source=weather_source,

        # Cuaca yang digunakan dalam proses CARS.
        weather_used=weather_used,

        # Jumlah rekomendasi yang dihasilkan.
        total_candidates=len(recommendations),

        # Daftar hasil rekomendasi akhir.
        recommendations=recommendations,
    )