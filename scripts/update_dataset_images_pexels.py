#!/usr/bin/env python3
"""
Update kolom link_gambar dataset TourHub Bali memakai Pexels API.

Cara pakai dari root repo skripsi_arhan:
    python scripts/update_dataset_images_pexels.py \
      --input data/bali_tourist_destination.csv \
      --output data/bali_tourist_destination_pexels_api.csv

API key dibaca dari environment variable PEXELS_API_KEY atau file .env.
JANGAN hardcode API key ke file ini dan JANGAN commit .env ke GitHub.
"""

from __future__ import annotations

import argparse
import hashlib
import json
import os
import re
import shutil
import sys
import time
from dataclasses import dataclass
from pathlib import Path
from typing import Any, Dict, Iterable, List, Optional, Tuple

import pandas as pd
import requests
from dotenv import load_dotenv

PEXELS_SEARCH_URL = "https://api.pexels.com/v1/search"

REQUIRED_COLUMNS = [
    "id_tempat",
    "nama_tempat_wisata",
    "kategori",
    "kecamatan",
    "kabupaten_kota",
    "link_gambar",
]

OUTPUT_META_COLUMNS = [
    "pexels_photo_id",
    "pexels_photo_url",
    "pexels_photographer",
    "pexels_photographer_url",
    "pexels_alt",
    "pexels_query",
    "pexels_source",
]

CATEGORY_FALLBACK_QUERIES = {
    "alam": [
        "Bali beach landscape",
        "Bali waterfall",
        "Bali rice terrace",
        "Bali lake mountain",
        "Nusa Penida Bali beach",
        "Ubud rice field Bali",
    ],
    "budaya": [
        "Bali temple",
        "Balinese temple ceremony",
        "Pura Bali",
        "Tirta Empul Bali temple",
        "Balinese culture",
        "Bali traditional gate",
    ],
    "rekreasi": [
        "Bali tourist attraction",
        "Bali adventure tourism",
        "Bali waterpark",
        "Bali outdoor recreation",
        "Bali park tourism",
        "Bali family vacation",
    ],
    "umum": [
        "Bali city landmark",
        "Denpasar Bali landmark",
        "Bali traditional market",
        "Bali monument",
        "Bali street tourism",
        "Bali public park",
    ],
}

# Keyword → query tambahan. Ini membuat hasil lebih nyambung daripada sekadar kategori.
SUBTYPE_QUERY_RULES: List[Tuple[List[str], List[str]]] = [
    (["pantai", "beach", "nusa penida", "kelingking", "diamond"], ["{name} Bali beach", "Bali beach landscape", "Nusa Penida Bali"]),
    (["air terjun", "waterfall", "tegenungan", "gitgit", "sekumpul", "aleng"], ["{name} Bali waterfall", "Bali waterfall", "Bali nature waterfall"]),
    (["sawah", "rice", "campuhan", "subak", "terasering", "jatiluwih", "tegalalang"], ["{name} Bali rice terrace", "Ubud rice field Bali", "Bali rice terrace"]),
    (["gunung", "mount", "batur", "agung", "bukit", "hill"], ["{name} Bali mountain", "Mount Batur Bali", "Bali hill landscape"]),
    (["danau", "lake", "bedugul", "beratan", "tamblingan", "buyan"], ["{name} Bali lake", "Bali lake temple", "Bedugul Bali lake"]),
    (["pura", "temple", "tirta", "ulun", "lempuyang", "besakih", "tanah lot", "goa gajah"], ["{name} Bali temple", "Pura Bali", "Balinese temple"]),
    (["museum", "galeri", "gallery", "art", "seni"], ["{name} Bali museum", "Bali art museum", "Balinese art gallery"]),
    (["pasar", "market"], ["{name} Bali market", "Bali traditional market", "Denpasar traditional market"]),
    (["taman", "park", "garden", "kebun"], ["{name} Bali park", "Bali public park", "Bali garden tourism"]),
    (["zoo", "safari", "bird", "reptile", "monkey", "animal"], ["{name} Bali wildlife", "Bali zoo", "Bali monkey forest"]),
    (["rafting", "atv", "adventure", "water sport", "watersport", "snorkeling", "diving"], ["{name} Bali adventure", "Bali adventure tourism", "Bali water sport"]),
    (["monumen", "patung", "statue", "landmark"], ["{name} Bali monument", "Bali monument", "Denpasar Bali landmark"]),
]

STOPWORDS = {
    "bali", "wisata", "tempat", "area", "lokasi", "parkir", "parking", "point", "spot", "view", "photo",
    "kabupaten", "kota", "kecamatan", "barat", "timur", "utara", "selatan", "tengah", "dan", "the", "of",
    "1", "2", "3", "4", "5", "i", "ii", "iii", "iv", "v",
}


@dataclass
class PexelsPhoto:
    id: str
    image_url: str
    photo_url: str
    photographer: str
    photographer_url: str
    alt: str
    query: str
    source: str = "pexels_api"

    @classmethod
    def from_api(cls, raw: Dict[str, Any], query: str, image_size: str) -> "PexelsPhoto":
        src = raw.get("src") or {}
        image_url = (
            src.get(image_size)
            or src.get("landscape")
            or src.get("large2x")
            or src.get("large")
            or src.get("medium")
            or src.get("original")
            or ""
        )
        return cls(
            id=str(raw.get("id", "")),
            image_url=image_url,
            photo_url=str(raw.get("url", "")),
            photographer=str(raw.get("photographer", "")),
            photographer_url=str(raw.get("photographer_url", "")),
            alt=str(raw.get("alt", "")),
            query=query,
        )

    def as_cache_dict(self) -> Dict[str, Any]:
        return {
            "id": self.id,
            "image_url": self.image_url,
            "photo_url": self.photo_url,
            "photographer": self.photographer,
            "photographer_url": self.photographer_url,
            "alt": self.alt,
            "query": self.query,
            "source": self.source,
        }

    @classmethod
    def from_cache_dict(cls, data: Dict[str, Any]) -> "PexelsPhoto":
        return cls(
            id=str(data.get("id", "")),
            image_url=str(data.get("image_url", "")),
            photo_url=str(data.get("photo_url", "")),
            photographer=str(data.get("photographer", "")),
            photographer_url=str(data.get("photographer_url", "")),
            alt=str(data.get("alt", "")),
            query=str(data.get("query", "")),
            source=str(data.get("source", "pexels_api")),
        )


def normalize_text(value: object) -> str:
    text = "" if pd.isna(value) else str(value)
    text = text.lower()
    text = re.sub(r"https?://\S+", " ", text)
    text = re.sub(r"[._+/\\-]+", " ", text)
    text = re.sub(r"[()\[\]{}]", " ", text)
    text = re.sub(r"[^a-z0-9à-ÿ\s]", " ", text)
    text = re.sub(r"\s+", " ", text).strip()
    return text


def clean_place_name(name: object) -> str:
    text = normalize_text(name)
    tokens = [t for t in text.split() if t not in STOPWORDS]
    cleaned = " ".join(tokens).strip()
    return cleaned or text or "wisata bali"


def canonical_category(category: object) -> str:
    text = normalize_text(category)
    if "alam" in text:
        return "alam"
    if "budaya" in text:
        return "budaya"
    if "rekreasi" in text:
        return "rekreasi"
    return "umum"


def row_key(row: pd.Series) -> str:
    if str(row.get("id_tempat", "")).strip():
        return str(row.get("id_tempat", "")).strip()
    raw = "|".join(
        str(row.get(col, "")) for col in ["nama_tempat_wisata", "latitude", "longitude", "kecamatan"]
    )
    return hashlib.sha1(raw.encode("utf-8")).hexdigest()


def dedupe(items: Iterable[str]) -> List[str]:
    seen = set()
    out: List[str] = []
    for item in items:
        item = re.sub(r"\s+", " ", str(item)).strip()
        if item and item.lower() not in seen:
            seen.add(item.lower())
            out.append(item)
    return out


def build_queries(row: pd.Series) -> List[str]:
    name = clean_place_name(row.get("nama_tempat_wisata", ""))
    kecamatan = clean_place_name(row.get("kecamatan", ""))
    kabupaten = clean_place_name(row.get("kabupaten_kota", ""))
    kategori = canonical_category(row.get("kategori", ""))

    queries: List[str] = []
    # Query spesifik lokasi dulu.
    if name and name != "wisata bali":
        queries.extend([
            f"{name} Bali",
            f"{name} {kecamatan} Bali",
            f"{name} {kabupaten} Bali",
        ])

    joined = f"{name} {kategori}"
    for keywords, templates in SUBTYPE_QUERY_RULES:
        if any(k in joined for k in keywords):
            for template in templates:
                queries.append(template.format(name=name, kecamatan=kecamatan, kabupaten=kabupaten))

    # Fallback kategori agar tidak kosong walaupun nama tempat terlalu lokal/aneh.
    queries.extend(CATEGORY_FALLBACK_QUERIES.get(kategori, CATEGORY_FALLBACK_QUERIES["umum"]))
    return dedupe(queries)


def important_terms(row: pd.Series) -> List[str]:
    terms: List[str] = []
    for col in ["nama_tempat_wisata", "kategori", "kecamatan", "kabupaten_kota"]:
        terms.extend(clean_place_name(row.get(col, "")).split())
    terms.extend(["bali", "indonesia"])
    return [t for t in dedupe(terms) if len(t) >= 3 and t not in STOPWORDS]


def score_photo(photo: PexelsPhoto, row: pd.Series, used_photo_ids: set[str]) -> float:
    text = normalize_text(f"{photo.alt} {photo.photo_url} {photo.photographer}")
    terms = important_terms(row)
    score = 0.0

    for term in terms:
        if term in text:
            score += 1.0

    kategori = canonical_category(row.get("kategori", ""))
    if "bali" in text:
        score += 4.0
    if "indonesia" in text:
        score += 2.0

    if kategori == "alam" and any(x in text for x in ["beach", "waterfall", "nature", "rice", "mountain", "lake", "sea", "ocean"]):
        score += 3.0
    elif kategori == "budaya" and any(x in text for x in ["temple", "pura", "culture", "ceremony", "traditional"]):
        score += 3.0
    elif kategori == "rekreasi" and any(x in text for x in ["tourism", "vacation", "park", "adventure", "water", "family"]):
        score += 2.5
    elif kategori == "umum" and any(x in text for x in ["market", "city", "street", "monument", "landmark", "park"]):
        score += 2.0

    # Penalti keras supaya satu foto tidak dipakai berulang jika masih ada pilihan lain.
    if photo.id in used_photo_ids:
        score -= 100.0

    # URL kosong tidak valid.
    if not photo.image_url:
        score -= 1000.0

    return score


def load_cache(path: Path) -> Dict[str, Any]:
    if not path.exists():
        return {"version": 1, "destinations": {}, "queries": {}}
    with path.open("r", encoding="utf-8") as f:
        data = json.load(f)
    data.setdefault("version", 1)
    data.setdefault("destinations", {})
    data.setdefault("queries", {})
    return data


def save_cache(path: Path, cache: Dict[str, Any]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    tmp = path.with_suffix(path.suffix + ".tmp")
    with tmp.open("w", encoding="utf-8") as f:
        json.dump(cache, f, indent=2, ensure_ascii=False)
    tmp.replace(path)


def search_pexels(
    session: requests.Session,
    api_key: str,
    query: str,
    *,
    per_page: int,
    orientation: str,
    locale: str,
    image_size: str,
    timeout: int,
) -> List[PexelsPhoto]:
    params: Dict[str, Any] = {
        "query": query,
        "per_page": per_page,
        "page": 1,
        "orientation": orientation,
        "locale": locale,
    }
    headers = {"Authorization": api_key}
    response = session.get(PEXELS_SEARCH_URL, headers=headers, params=params, timeout=timeout)

    if response.status_code == 401:
        raise RuntimeError("Pexels API key salah/kosong. Cek PEXELS_API_KEY di .env.")
    if response.status_code == 429:
        raise RuntimeError("RATE_LIMIT_429: Pexels membatasi request. Jalankan ulang nanti; cache sudah menyimpan progress.")
    if response.status_code >= 400:
        raise RuntimeError(f"Pexels API error HTTP {response.status_code}: {response.text[:300]}")

    payload = response.json()
    photos = payload.get("photos") or []
    return [PexelsPhoto.from_api(raw, query=query, image_size=image_size) for raw in photos]


def choose_photo(photos: List[PexelsPhoto], row: pd.Series, used_photo_ids: set[str]) -> Optional[PexelsPhoto]:
    valid = [p for p in photos if p.image_url and p.id]
    if not valid:
        return None
    return max(valid, key=lambda photo: score_photo(photo, row, used_photo_ids))


def apply_photo_to_df(df: pd.DataFrame, idx: int, photo: PexelsPhoto) -> None:
    df.at[idx, "link_gambar"] = photo.image_url
    df.at[idx, "pexels_photo_id"] = photo.id
    df.at[idx, "pexels_photo_url"] = photo.photo_url
    df.at[idx, "pexels_photographer"] = photo.photographer
    df.at[idx, "pexels_photographer_url"] = photo.photographer_url
    df.at[idx, "pexels_alt"] = photo.alt
    df.at[idx, "pexels_query"] = photo.query
    df.at[idx, "pexels_source"] = photo.source


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Update gambar dataset TourHub Bali memakai Pexels API.")
    parser.add_argument("--input", default="data/bali_tourist_destination.csv", help="Path CSV input.")
    parser.add_argument("--output", default="data/bali_tourist_destination_pexels_api.csv", help="Path CSV output.")
    parser.add_argument("--cache", default="data/pexels_image_cache.json", help="Path file cache JSON.")
    parser.add_argument("--limit", type=int, default=0, help="Batasi jumlah baris untuk testing. 0 = semua.")
    parser.add_argument("--start", type=int, default=0, help="Mulai dari index baris tertentu untuk resume manual.")
    parser.add_argument("--only-empty", action="store_true", help="Hanya isi baris yang link_gambar-nya kosong.")
    parser.add_argument("--ignore-cache", action="store_true", help="Abaikan cache destinasi, tapi query cache tetap dipakai.")
    parser.add_argument("--in-place", action="store_true", help="Overwrite file input. Backup otomatis dibuat.")
    parser.add_argument("--dry-run", action="store_true", help="Tidak panggil API, hanya tampilkan query yang akan dipakai.")
    parser.add_argument("--per-page", type=int, default=15, help="Jumlah foto per query. Maks 80, disarankan 10-20.")
    parser.add_argument("--orientation", default="landscape", choices=["landscape", "portrait", "square"], help="Orientasi gambar.")
    parser.add_argument("--locale", default="id-ID", help="Locale pencarian Pexels, contoh id-ID atau en-US.")
    parser.add_argument("--image-size", default="landscape", choices=["original", "large2x", "large", "medium", "small", "portrait", "landscape", "tiny"], help="Ukuran URL gambar dari Pexels.")
    parser.add_argument("--sleep", type=float, default=0.4, help="Jeda antar request API agar aman dari rate limit.")
    parser.add_argument("--timeout", type=int, default=30, help="Timeout request API.")
    return parser.parse_args()


def main() -> int:
    load_dotenv()
    args = parse_args()

    input_path = Path(args.input)
    output_path = Path(args.output)
    cache_path = Path(args.cache)

    if args.in_place:
        output_path = input_path

    if not input_path.exists():
        print(f"ERROR: File input tidak ditemukan: {input_path}", file=sys.stderr)
        return 1

    df = pd.read_csv(input_path)
    missing = [col for col in REQUIRED_COLUMNS if col not in df.columns]
    if missing:
        print(f"ERROR: Kolom wajib tidak ada di CSV: {missing}", file=sys.stderr)
        return 1

    for col in OUTPUT_META_COLUMNS:
        if col not in df.columns:
            df[col] = ""

    if args.dry_run:
        max_rows = args.limit or min(10, len(df))
        for idx, row in df.iloc[args.start: args.start + max_rows].iterrows():
            print(f"\n[{idx}] {row.get('nama_tempat_wisata')} | {row.get('kategori')} | {row.get('kecamatan')}")
            for q in build_queries(row)[:8]:
                print(f"  - {q}")
        return 0

    api_key = os.getenv("PEXELS_API_KEY", "").strip()
    if not api_key:
        print("ERROR: PEXELS_API_KEY belum diisi. Buat file .env atau export environment variable.", file=sys.stderr)
        return 1

    cache = load_cache(cache_path)
    used_photo_ids: set[str] = set(str(x).strip() for x in df.get("pexels_photo_id", pd.Series(dtype=str)).dropna().astype(str) if str(x).strip())
    session = requests.Session()

    processed = 0
    updated = 0
    skipped = 0
    failed = 0
    end = len(df) if args.limit == 0 else min(len(df), args.start + args.limit)

    try:
        for idx, row in df.iloc[args.start:end].iterrows():
            processed += 1
            key = row_key(row)
            existing_image = str(row.get("link_gambar", "")).strip()

            if args.only_empty and existing_image:
                skipped += 1
                continue

            if not args.ignore_cache and key in cache["destinations"]:
                photo = PexelsPhoto.from_cache_dict(cache["destinations"][key])
                apply_photo_to_df(df, idx, photo)
                used_photo_ids.add(photo.id)
                updated += 1
                print(f"[{idx}] CACHE OK: {row.get('nama_tempat_wisata')} -> {photo.id}")
                continue

            selected: Optional[PexelsPhoto] = None
            queries = build_queries(row)

            for query in queries:
                if query in cache["queries"]:
                    photos = [PexelsPhoto.from_cache_dict(p) for p in cache["queries"][query]]
                else:
                    photos = search_pexels(
                        session,
                        api_key,
                        query,
                        per_page=max(1, min(args.per_page, 80)),
                        orientation=args.orientation,
                        locale=args.locale,
                        image_size=args.image_size,
                        timeout=args.timeout,
                    )
                    cache["queries"][query] = [p.as_cache_dict() for p in photos]
                    save_cache(cache_path, cache)
                    time.sleep(max(0.0, args.sleep))

                if photos:
                    selected = choose_photo(photos, row, used_photo_ids)
                    if selected:
                        break

            if selected:
                apply_photo_to_df(df, idx, selected)
                cache["destinations"][key] = selected.as_cache_dict()
                used_photo_ids.add(selected.id)
                updated += 1
                print(f"[{idx}] OK: {row.get('nama_tempat_wisata')} -> {selected.id} | query='{selected.query}'")
            else:
                failed += 1
                print(f"[{idx}] GAGAL: tidak ada foto untuk {row.get('nama_tempat_wisata')}")

            if processed % 25 == 0:
                save_cache(cache_path, cache)
                output_path.parent.mkdir(parents=True, exist_ok=True)
                df.to_csv(output_path, index=False)
                print(f"--- checkpoint: {processed} diproses, {updated} update, output tersimpan sementara ---")

    except KeyboardInterrupt:
        print("\nDihentikan manual. Menyimpan progress sebelum keluar...")
    except RuntimeError as exc:
        print(f"\nERROR: {exc}", file=sys.stderr)
        print("Progress akan disimpan. Jalankan command yang sama nanti untuk resume dari cache.")
    finally:
        save_cache(cache_path, cache)
        if args.in_place and input_path.exists():
            backup = input_path.with_suffix(input_path.suffix + f".backup_{int(time.time())}")
            shutil.copy2(input_path, backup)
            print(f"Backup file lama dibuat: {backup}")
        output_path.parent.mkdir(parents=True, exist_ok=True)
        df.to_csv(output_path, index=False)

    unique_images = int(df["link_gambar"].nunique(dropna=True))
    print("\nSelesai.")
    print(f"Input          : {input_path}")
    print(f"Output         : {output_path}")
    print(f"Cache          : {cache_path}")
    print(f"Diproses       : {processed}")
    print(f"Updated/cache  : {updated}")
    print(f"Skipped        : {skipped}")
    print(f"Failed         : {failed}")
    print(f"Unique image   : {unique_images}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
