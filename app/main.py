from __future__ import annotations

import os
from functools import lru_cache
from pathlib import Path
from typing import Any, Dict, Optional

from fastapi import Depends, FastAPI, HTTPException, Query, Security, status
from fastapi.security import APIKeyHeader

from app.bmkg import fetch_bmkg_weather
from app.recommender import RecommenderConfig, TourHubRecommender
from app.schemas import RecommendRequest, RecommendResponse

APP_TITLE = "TourHub Bali FAST API"
APP_DESCRIPTION = "FastAPI untuk rekomendasi destinasi wisata Bali menggunakan CBF + CARS."

API_SECRET_KEY = os.getenv("API_SECRET_KEY", "123")
API_KEY_HEADER_NAME = "X-API-Key"

api_key_header = APIKeyHeader(
    name=API_KEY_HEADER_NAME,
    auto_error=False,
    description="Masukkan secret key TourHub.",
)


def verify_api_key(api_key: Optional[str] = Security(api_key_header)) -> str:
    """Mengecek secret key dari tombol Authorize Swagger atau dari header request."""

    if api_key != API_SECRET_KEY:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Secret key salah atau belum diisi. Silakan klik tombol Authorize di Swagger dan masukkan secret key yang valid.",
        )

    return api_key


app = FastAPI(
    title=APP_TITLE,
    description=APP_DESCRIPTION,
    version="0.2.0",
    docs_url="/docs",
    redoc_url=None,
    openapi_url="/openapi.json",
    dependencies=[Depends(verify_api_key)],
)


@lru_cache(maxsize=1)
def get_recommender() -> TourHubRecommender:
    data_source = os.getenv("DATA_SOURCE", "csv").strip().lower()
    data_path = Path(os.getenv("DATA_PATH", "data/bali_tourist_destination.csv"))

    config = RecommenderConfig(
        data_path=data_path,
        data_source=data_source,
        laravel_dataset_url=os.getenv("LARAVEL_DATASET_URL"),
        laravel_internal_key=os.getenv("LARAVEL_INTERNAL_KEY"),
        request_timeout=int(os.getenv("DATASET_REQUEST_TIMEOUT", "30")),
    )

    return TourHubRecommender(config)


@app.get("/")
def root() -> dict:
    return {
        "message": "TourHub Bali ML API is running",
        "docs": "/docs",
        "auth": "Klik Authorize di Swagger lalu masukkan secret key.",
        "algorithm": "Content-Based Filtering + Context-Aware Recommender System",
    }


@app.get("/health")
def health() -> dict:
    recommender = get_recommender()

    return {
        "status": "ok",
        "metadata": recommender.metadata(),
    }


@app.get("/metadata")
def metadata() -> dict:
    return get_recommender().metadata()


@app.post("/reload-dataset")
def reload_dataset() -> dict:
    """Clear cache dan baca ulang dataset.

    Endpoint ini dipanggil Laravel setelah admin menambah, mengubah, atau
    menghapus data destinasi wisata dari Filament.
    """

    get_recommender.cache_clear()
    recommender = get_recommender()

    return {
        "status": "reloaded",
        "message": "Dataset rekomendasi berhasil dibaca ulang.",
        "metadata": recommender.metadata(),
    }


@app.get("/destinations")
def destinations(
    limit: int = Query(default=20, ge=1, le=100),
    kategori: Optional[str] = Query(default=None),
    kabupaten_kota: Optional[str] = Query(default=None),
) -> dict:
    data = get_recommender().list_destinations(
        limit=limit,
        kategori=kategori,
        kabupaten_kota=kabupaten_kota,
    )

    return {
        "total": len(data),
        "data": data,
    }


def _resolve_weather_context(payload: RecommendRequest) -> tuple[str, str, Dict[str, Any]]:
    """Tentukan cuaca yang dipakai CARS."""

    weather_used = payload.weather or "cerah"
    weather_source = "manual" if payload.weather else "default_cerah"
    bmkg_context: Dict[str, Any] = {
        "rain_detected": False,
        "fallback_weather": "cerah",
    }

    if payload.use_bmkg:
        if not payload.bmkg_adm4:
            return (
                "cerah",
                "default_cerah_no_adm4",
                {
                    **bmkg_context,
                    "message": "use_bmkg=True tetapi bmkg_adm4 kosong, fallback ke cerah.",
                },
            )

        try:
            bmkg_result = fetch_bmkg_weather(payload.bmkg_adm4)
            weather_used = (
                bmkg_result.get("weather_group")
                or bmkg_result.get("weather_desc")
                or bmkg_result.get("weather_desc_en")
                or "cerah"
            )
            weather_source = f"BMKG adm4={payload.bmkg_adm4}"
            bmkg_context = {
                "rain_detected": bool(bmkg_result.get("rain_detected", False)),
                "rain_slots_count": bmkg_result.get("rain_slots_count", 0),
                "forecast_slots_checked": bmkg_result.get("forecast_slots_checked", 0),
                "first_rain_datetime": bmkg_result.get("first_rain_datetime"),
                "weather_desc": bmkg_result.get("weather_desc"),
                "weather_desc_en": bmkg_result.get("weather_desc_en"),
                "temperature": bmkg_result.get("temperature"),
                "humidity": bmkg_result.get("humidity"),
                "wind_speed": bmkg_result.get("wind_speed"),
                "local_datetime": bmkg_result.get("local_datetime"),
            }
        except Exception:  # noqa: BLE001 - fallback sengaja agar rekomendasi tetap berjalan
            weather_used = "cerah"
            weather_source = "default_cerah_bmkg_error"
            bmkg_context = {
                **bmkg_context,
                "message": "Gagal mengambil data BMKG, sistem memakai fallback cerah.",
            }

    return str(weather_used), weather_source, bmkg_context


@app.post("/recommend", response_model=RecommendResponse)
def recommend(payload: RecommendRequest) -> RecommendResponse:
    recommender = get_recommender()
    weather_used, weather_source, bmkg_context = _resolve_weather_context(payload)

    result, meta = recommender.recommend(
        kategori_preferensi=payload.kategori_preferensi,
        kabupaten_kota=payload.kabupaten_kota,
        kecamatan=payload.kecamatan,
        keywords=payload.keywords,
        min_rating=payload.min_rating,
        top_n=payload.top_n,
        weather=weather_used,
        visit_day=payload.visit_day,
        is_high_season=payload.is_high_season,
        strict_weather_filter=True,
    )

    if result.empty and meta.get("strict_weather_filter_applied"):
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
            strict_weather_filter=False,
        )
        fallback_meta["fallback_reason"] = (
            "Strict weather filter menghasilkan 0 kandidat. "
            "Sistem menampilkan rekomendasi alternatif dengan penalti CARS untuk destinasi outdoor."
        )
        fallback_meta["strict_weather_filter_fallback_used"] = True
        meta = fallback_meta
    else:
        meta["strict_weather_filter_fallback_used"] = False

    recommendations = result.round(
        {
            "cbf_score": 6,
            "rating_score": 6,
            "popularity_score": 6,
            "context_multiplier": 6,
            "final_score": 6,
        }
    ).to_dict(orient="records")

    return RecommendResponse(
        query={
            **payload.model_dump(),
            **meta,
            "bmkg_context": bmkg_context,
        },
        weather_source=weather_source,
        weather_used=weather_used,
        total_candidates=len(recommendations),
        recommendations=recommendations,
    )
