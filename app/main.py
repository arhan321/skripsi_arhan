from __future__ import annotations

import os
from functools import lru_cache
from pathlib import Path
from typing import Optional

from fastapi import FastAPI, HTTPException, Query

from app.bmkg import fetch_bmkg_weather
from app.recommender import RecommenderConfig, TourHubRecommender
from app.schemas import RecommendRequest, RecommendResponse

APP_TITLE = "TourHub Bali ML API"
APP_DESCRIPTION = "FastAPI untuk rekomendasi destinasi wisata Bali menggunakan CBF + CARS."

app = FastAPI(title=APP_TITLE, description=APP_DESCRIPTION, version="0.1.0")


@lru_cache(maxsize=1)
def get_recommender() -> TourHubRecommender:
    data_path = Path(os.getenv("DATA_PATH", "data/bali_tourist_destination.csv"))
    return TourHubRecommender(RecommenderConfig(data_path=data_path))


@app.get("/")
def root() -> dict:
    return {
        "message": "TourHub Bali ML API is running",
        "docs": "/docs",
        "algorithm": "Content-Based Filtering + Context-Aware Recommender System",
    }


@app.get("/health")
def health() -> dict:
    recommender = get_recommender()
    return {"status": "ok", "metadata": recommender.metadata()}


@app.get("/metadata")
def metadata() -> dict:
    return get_recommender().metadata()


@app.get("/destinations")
def destinations(
    limit: int = Query(default=20, ge=1, le=100),
    kategori: Optional[str] = Query(default=None),
    kabupaten_kota: Optional[str] = Query(default=None),
) -> dict:
    data = get_recommender().list_destinations(limit=limit, kategori=kategori, kabupaten_kota=kabupaten_kota)
    return {"total": len(data), "data": data}


@app.post("/recommend", response_model=RecommendResponse)
def recommend(payload: RecommendRequest) -> RecommendResponse:
    recommender = get_recommender()

    weather_used = payload.weather
    weather_source = "manual"

    if payload.use_bmkg:
        if not payload.bmkg_adm4:
            raise HTTPException(status_code=400, detail="bmkg_adm4 wajib diisi jika use_bmkg=True")
        try:
            bmkg_result = fetch_bmkg_weather(payload.bmkg_adm4)
            weather_used = bmkg_result.get("weather_desc") or bmkg_result.get("weather_desc_en")
            weather_source = f"BMKG adm4={payload.bmkg_adm4}"
        except Exception as exc:  # noqa: BLE001 - sengaja agar API tetap informatif untuk debugging skripsi
            raise HTTPException(status_code=502, detail=f"Gagal mengambil data BMKG: {exc}") from exc

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
    )

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
        query={**payload.model_dump(), **meta},
        weather_source=weather_source,
        weather_used=weather_used,
        total_candidates=len(recommendations),
        recommendations=recommendations,
    )
