from __future__ import annotations

import re
from dataclasses import dataclass
from pathlib import Path
from typing import Dict, Iterable, List, Optional, Tuple

import numpy as np
import pandas as pd
import requests
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity


def _safe_text(value: object) -> str:
    if pd.isna(value):
        return ""
    return str(value).strip()


def _normalize_text(value: object) -> str:
    text = _safe_text(value).lower()
    text = re.sub(r"[^a-z0-9A-ZÀ-ÿ\s]", " ", text)
    text = re.sub(r"\s+", " ", text).strip()
    return text


def infer_tipe_wisata(row: pd.Series) -> str:
    """Inferensi sederhana indoor/outdoor/mixed dari kategori dan nama destinasi."""

    existing = _normalize_text(row.get("tipe_wisata", ""))
    if existing in {"indoor", "outdoor", "mixed"}:
        return existing

    name = _normalize_text(row.get("nama_tempat_wisata", ""))
    kategori = _normalize_text(row.get("kategori", ""))

    indoor_keywords = [
        "museum",
        "galeri",
        "gallery",
        "mall",
        "plaza",
        "theater",
        "teater",
        "studio",
        "indoor",
        "art center",
    ]
    outdoor_keywords = [
        "pantai",
        "beach",
        "air terjun",
        "waterfall",
        "bukit",
        "hill",
        "gunung",
        "mount",
        "danau",
        "lake",
        "taman",
        "park",
        "sawah",
        "rice",
        "campuhan",
        "river",
        "rafting",
        "snorkeling",
        "diving",
        "zoo",
        "monkey forest",
        "forest",
        "pura",
        "temple",
        "goa",
        "cave",
    ]

    if any(keyword in name for keyword in indoor_keywords):
        return "indoor"
    if any(keyword in name for keyword in outdoor_keywords):
        return "outdoor"
    if kategori == "alam":
        return "outdoor"
    if kategori == "budaya":
        return "mixed"
    return "mixed"


def weather_group(weather: Optional[str]) -> str:
    """Kelompokkan deskripsi cuaca ke cerah/hujan/berawan/tidak_diketahui."""

    if not weather:
        return "tidak_diketahui"

    text = _normalize_text(weather)
    rainy_terms = ["hujan", "rain", "shower", "storm", "petir", "thunder", "lebat", "gerimis"]
    clear_terms = ["cerah", "clear", "sunny"]
    cloudy_terms = ["berawan", "cloud", "mendung", "overcast", "kabut", "fog"]

    if any(term in text for term in rainy_terms):
        return "hujan"
    if any(term in text for term in clear_terms):
        return "cerah"
    if any(term in text for term in cloudy_terms):
        return "berawan"
    return "tidak_diketahui"


def is_rainy_weather(weather: Optional[str]) -> bool:
    """Return True jika cuaca masuk kelompok hujan."""

    return weather_group(weather) == "hujan"


def compute_context_multiplier(
    tipe_wisata: str,
    popularity_score: float,
    weather: Optional[str] = None,
    visit_day: Optional[str] = None,
    is_high_season: bool = False,
) -> Tuple[float, List[str]]:
    """Hitung pengali konteks untuk CARS."""

    multiplier = 1.0
    reasons: List[str] = []
    w_group = weather_group(weather)
    tipe = (tipe_wisata or "mixed").lower()

    if w_group == "hujan":
        if tipe == "outdoor":
            multiplier *= 0.72
            reasons.append("cuaca hujan sehingga destinasi outdoor diberi penalti")
        elif tipe == "indoor":
            multiplier *= 1.12
            reasons.append("cuaca hujan sehingga destinasi indoor lebih diprioritaskan")
        else:
            multiplier *= 0.92
            reasons.append("cuaca hujan sehingga destinasi mixed sedikit diturunkan")
    elif w_group == "cerah":
        if tipe == "outdoor":
            multiplier *= 1.08
            reasons.append("cuaca cerah mendukung destinasi outdoor")
        elif tipe == "indoor":
            multiplier *= 0.96
            reasons.append("cuaca cerah membuat destinasi indoor sedikit kurang diprioritaskan")
    elif w_group == "berawan":
        if tipe == "outdoor":
            multiplier *= 1.02
            reasons.append("cuaca berawan masih cukup aman untuk destinasi outdoor")

    visit = _normalize_text(visit_day)
    if visit in {"weekend", "akhir pekan", "sabtu", "minggu"}:
        if popularity_score >= 0.75:
            multiplier *= 0.90
            reasons.append("weekend dan destinasi sangat populer sehingga diasumsikan lebih ramai")
        else:
            multiplier *= 1.03
            reasons.append("weekend dan destinasi tidak terlalu populer sehingga lebih nyaman")

    if is_high_season:
        if popularity_score >= 0.75:
            multiplier *= 0.88
            reasons.append("high season dan destinasi populer sehingga diberi penalti keramaian")
        else:
            multiplier *= 1.05
            reasons.append("high season sehingga destinasi alternatif sedikit diprioritaskan")

    return float(multiplier), reasons


@dataclass
class RecommenderConfig:
    data_path: Path
    data_source: str = "csv"  # csv atau laravel
    laravel_dataset_url: Optional[str] = None
    laravel_internal_key: Optional[str] = None
    request_timeout: int = 30
    min_df: int = 1
    ngram_range: Tuple[int, int] = (1, 2)


class TourHubRecommender:
    """Content-Based Filtering + Context-Aware Recommender System untuk destinasi Bali."""

    def __init__(self, config: RecommenderConfig):
        self.config = config
        self.df = self._load_and_clean_data(config)
        self.vectorizer = TfidfVectorizer(min_df=config.min_df, ngram_range=config.ngram_range)
        self.destination_matrix = self.vectorizer.fit_transform(self.df["feature_text"])

    @staticmethod
    def _load_raw_data(config: RecommenderConfig) -> pd.DataFrame:
        source = (config.data_source or "csv").lower().strip()

        if source == "laravel":
            if not config.laravel_dataset_url:
                raise ValueError("DATA_SOURCE=laravel tetapi LARAVEL_DATASET_URL belum diisi.")

            headers = {"Accept": "application/json"}
            if config.laravel_internal_key:
                headers["X-TourHub-Internal-Key"] = config.laravel_internal_key

            response = requests.get(
                config.laravel_dataset_url,
                headers=headers,
                params={"active": "1", "limit": "10000"},
                timeout=config.request_timeout,
            )
            response.raise_for_status()
            payload = response.json()

            if isinstance(payload, dict):
                rows = payload.get("data", [])
            else:
                rows = payload

            if not isinstance(rows, list):
                raise ValueError("Format dataset dari Laravel tidak valid. Field data harus berupa list.")

            return pd.DataFrame(rows)

        if not config.data_path.exists():
            raise FileNotFoundError(f"Dataset tidak ditemukan: {config.data_path}")

        return pd.read_csv(config.data_path)

    @staticmethod
    def _load_and_clean_data(config: RecommenderConfig) -> pd.DataFrame:
        df = TourHubRecommender._load_raw_data(config)

        required_columns = [
            "id_tempat",
            "nama_tempat_wisata",
            "kategori",
            "kecamatan",
            "kabupaten_kota",
            "rating",
            "jumlah_rating",
            "latitude",
            "longitude",
        ]
        missing = [col for col in required_columns if col not in df.columns]
        if missing:
            raise ValueError(f"Kolom wajib tidak ada di dataset: {missing}")

        df = df.copy()

        for col in ["id_tempat", "nama_tempat_wisata", "kategori", "kecamatan", "kabupaten_kota"]:
            df[col] = df[col].fillna("").astype(str).str.strip()

        if "is_active" in df.columns:
            df = df[df["is_active"].fillna(True).astype(bool)]

        df["rating"] = pd.to_numeric(df["rating"], errors="coerce").fillna(0.0)
        df["jumlah_rating"] = pd.to_numeric(df["jumlah_rating"], errors="coerce").fillna(0).astype(int)
        df["latitude"] = pd.to_numeric(df["latitude"], errors="coerce")
        df["longitude"] = pd.to_numeric(df["longitude"], errors="coerce")

        df = df.drop_duplicates(subset=["nama_tempat_wisata", "latitude", "longitude"], keep="first")
        df = df.dropna(subset=["latitude", "longitude"])

        if "tipe_wisata" not in df.columns:
            df["tipe_wisata"] = ""
        df["tipe_wisata"] = df.apply(infer_tipe_wisata, axis=1)

        optional_cols = ["link_google_maps", "link_gambar", "deskripsi"]
        for col in optional_cols:
            if col not in df.columns:
                df[col] = None

        for col in optional_cols:
            df[col] = df[col].where(df[col].notna(), None)

        if df.empty:
            raise ValueError("Dataset kosong setelah proses cleaning.")

        max_rating = float(df["rating"].max()) if float(df["rating"].max()) > 0 else 5.0
        df["rating_score"] = (df["rating"] / max_rating).clip(0, 1)

        log_reviews = np.log1p(df["jumlah_rating"].astype(float))
        max_log_reviews = float(log_reviews.max()) if float(log_reviews.max()) > 0 else 1.0
        df["popularity_score"] = (log_reviews / max_log_reviews).clip(0, 1)

        df["feature_text"] = (
            df["nama_tempat_wisata"].map(_normalize_text)
            + " "
            + df["kategori"].map(_normalize_text)
            + " "
            + df["kecamatan"].map(_normalize_text)
            + " "
            + df["kabupaten_kota"].map(_normalize_text)
            + " "
            + df["tipe_wisata"].map(_normalize_text)
            + " "
            + df["deskripsi"].map(_normalize_text)
        )

        return df.reset_index(drop=True)

    def metadata(self) -> Dict[str, object]:
        return {
            "data_source": self.config.data_source,
            "total_destinations": int(len(self.df)),
            "categories": sorted(self.df["kategori"].dropna().unique().tolist()),
            "kabupaten_kota": sorted(self.df["kabupaten_kota"].dropna().unique().tolist()),
            "tipe_wisata": self.df["tipe_wisata"].value_counts().to_dict(),
        }

    def list_destinations(
        self,
        limit: int = 20,
        kategori: Optional[str] = None,
        kabupaten_kota: Optional[str] = None,
    ) -> List[Dict[str, object]]:
        data = self.df.copy()

        if kategori:
            data = data[data["kategori"].str.lower() == kategori.lower()]
        if kabupaten_kota:
            data = data[data["kabupaten_kota"].str.lower() == kabupaten_kota.lower()]

        columns = [
            "id_tempat",
            "nama_tempat_wisata",
            "kategori",
            "tipe_wisata",
            "kecamatan",
            "kabupaten_kota",
            "rating",
            "jumlah_rating",
            "latitude",
            "longitude",
            "link_google_maps",
            "link_gambar",
            "deskripsi",
        ]

        return (
            data.sort_values(["rating", "jumlah_rating"], ascending=False)[columns]
            .head(limit)
            .replace({np.nan: None})
            .to_dict(orient="records")
        )

    @staticmethod
    def _build_user_query(
        kategori_preferensi: Iterable[str],
        kabupaten_kota: Optional[str],
        kecamatan: Optional[str],
        keywords: Iterable[str],
    ) -> str:
        parts: List[str] = []
        parts.extend([_normalize_text(x) for x in kategori_preferensi if x])
        if kabupaten_kota:
            parts.append(_normalize_text(kabupaten_kota))
        if kecamatan:
            parts.append(_normalize_text(kecamatan))
        parts.extend([_normalize_text(x) for x in keywords if x])

        return " ".join([p for p in parts if p]) or "wisata bali"

    @staticmethod
    def _build_reason(row: pd.Series) -> str:
        reasons = [
            f"Destinasi memiliki kesesuaian preferensi dengan skor CBF {float(row['cbf_score']):.3f}",
            f"rating {float(row['rating']):.2f}",
            f"jumlah rating {int(row['jumlah_rating'])}",
        ]

        context_reasons = row.get("context_reasons", [])
        if isinstance(context_reasons, list) and context_reasons:
            reasons.extend(context_reasons)

        return "; ".join(reasons) + "."

    def recommend(
        self,
        kategori_preferensi: Optional[List[str]] = None,
        kabupaten_kota: Optional[str] = None,
        kecamatan: Optional[str] = None,
        keywords: Optional[List[str]] = None,
        min_rating: Optional[float] = None,
        top_n: int = 10,
        weather: Optional[str] = None,
        visit_day: Optional[str] = None,
        is_high_season: bool = False,
        strict_weather_filter: bool = True,
    ) -> Tuple[pd.DataFrame, Dict[str, object]]:
        kategori_preferensi = kategori_preferensi or []
        keywords = keywords or []

        user_query = self._build_user_query(kategori_preferensi, kabupaten_kota, kecamatan, keywords)
        user_vector = self.vectorizer.transform([user_query])
        cbf_scores = cosine_similarity(user_vector, self.destination_matrix).flatten()

        result = self.df.copy()
        result["cbf_score"] = cbf_scores

        if kabupaten_kota:
            result = result[result["kabupaten_kota"].str.lower() == kabupaten_kota.lower()]
        if kecamatan:
            result = result[result["kecamatan"].str.lower() == kecamatan.lower()]
        if min_rating is not None:
            result = result[result["rating"] >= float(min_rating)]

        total_after_basic_filter = int(len(result))
        strict_weather_filter_applied = False
        strict_weather_filter_removed_outdoor = 0
        strict_weather_filter_reason: Optional[str] = None

        if strict_weather_filter and is_rainy_weather(weather):
            before_weather_filter = int(len(result))
            result = result[result["tipe_wisata"].fillna("").astype(str).str.lower().ne("outdoor")].copy()
            after_weather_filter = int(len(result))
            strict_weather_filter_applied = True
            strict_weather_filter_removed_outdoor = before_weather_filter - after_weather_filter
            strict_weather_filter_reason = (
                "Cuaca terdeteksi hujan, sehingga destinasi outdoor tidak ditampilkan "
                "demi kenyamanan dan keamanan wisatawan."
            )

        if result.empty:
            message = "Tidak ada kandidat setelah filter."
            if strict_weather_filter_applied:
                message = (
                    "Tidak ada kandidat non-outdoor setelah strict weather filter. "
                    "Coba pilih lokasi/kategori lain, turunkan min_rating, atau matikan strict_weather_filter."
                )

            return result, {
                "user_query": user_query,
                "weather_group": weather_group(weather),
                "total_after_basic_filter": total_after_basic_filter,
                "total_after_filter": 0,
                "total_returned": 0,
                "strict_weather_filter": bool(strict_weather_filter),
                "strict_weather_filter_applied": strict_weather_filter_applied,
                "strict_weather_filter_removed_outdoor": strict_weather_filter_removed_outdoor,
                "strict_weather_filter_reason": strict_weather_filter_reason,
                "message": message,
            }

        context_values = result.apply(
            lambda row: compute_context_multiplier(
                tipe_wisata=row["tipe_wisata"],
                popularity_score=float(row["popularity_score"]),
                weather=weather,
                visit_day=visit_day,
                is_high_season=is_high_season,
            ),
            axis=1,
        )

        result["context_multiplier"] = [x[0] for x in context_values]
        result["context_reasons"] = [x[1] for x in context_values]

        result["base_score"] = (
            (0.70 * result["cbf_score"])
            + (0.20 * result["rating_score"])
            + (0.10 * result["popularity_score"])
        )
        result["final_score"] = result["base_score"] * result["context_multiplier"]

        result = result.sort_values("final_score", ascending=False).head(top_n).copy()
        result["alasan"] = result.apply(self._build_reason, axis=1)

        output_columns = [
            "id_tempat",
            "nama_tempat_wisata",
            "kategori",
            "tipe_wisata",
            "kecamatan",
            "kabupaten_kota",
            "rating",
            "jumlah_rating",
            "latitude",
            "longitude",
            "link_google_maps",
            "link_gambar",
            "deskripsi",
            "cbf_score",
            "rating_score",
            "popularity_score",
            "context_multiplier",
            "final_score",
            "alasan",
        ]

        return result[output_columns].replace({np.nan: None}), {
            "user_query": user_query,
            "weather_group": weather_group(weather),
            "total_after_basic_filter": total_after_basic_filter,
            "total_after_filter": int(len(result)),
            "total_returned": int(len(result)),
            "strict_weather_filter": bool(strict_weather_filter),
            "strict_weather_filter_applied": strict_weather_filter_applied,
            "strict_weather_filter_removed_outdoor": strict_weather_filter_removed_outdoor,
            "strict_weather_filter_reason": strict_weather_filter_reason,
        }
