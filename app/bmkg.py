from __future__ import annotations

from datetime import datetime
from typing import Any, Dict, List

import requests

BMKG_FORECAST_URL = "https://api.bmkg.go.id/publik/prakiraan-cuaca"


def _flatten_forecasts(cuaca: Any) -> List[Dict[str, Any]]:
    """Flatten struktur forecast BMKG yang kadang berbentuk nested list.

    API prakiraan cuaca BMKG biasanya mengembalikan field `cuaca` dalam bentuk
    list per hari. Setiap hari berisi beberapa slot prakiraan sekitar interval
    3 jam. Fungsi ini meratakan semuanya menjadi list of dict.
    """
    forecasts: List[Dict[str, Any]] = []

    if isinstance(cuaca, list):
        for item in cuaca:
            if isinstance(item, list):
                forecasts.extend([x for x in item if isinstance(x, dict)])
            elif isinstance(item, dict):
                forecasts.append(item)

    return forecasts


def _is_rainy_desc(value: object) -> bool:
    """Deteksi deskripsi cuaca yang tergolong hujan."""
    text = str(value or "").lower()
    rainy_terms = [
        "hujan",
        "gerimis",
        "lebat",
        "petir",
        "thunder",
        "rain",
        "shower",
        "storm",
    ]
    return any(term in text for term in rainy_terms)


def _parse_local_datetime(value: object) -> datetime | None:
    """Parse local_datetime BMKG ke datetime naive.

    Format yang umum dari BMKG: YYYY-mm-dd HH:MM:SS.
    Jika format berubah, fungsi ini mengembalikan None agar tidak membuat API gagal.
    """
    if not value:
        return None

    text = str(value)
    for fmt in ("%Y-%m-%d %H:%M:%S", "%Y-%m-%dT%H:%M:%S"):
        try:
            return datetime.strptime(text, fmt)
        except ValueError:
            continue

    return None


def fetch_bmkg_weather(adm4: str, timeout: int = 10) -> Dict[str, Any]:
    """Ambil prakiraan cuaca BMKG berdasarkan kode ADM4.

    Perubahan versi ini:
    - Membaca prakiraan sekitar 3 hari ke depan atau maksimal 24 slot forecast.
    - Jika ada slot prakiraan yang mengandung hujan/gerimis/petir, cuaca sistem
      dikelompokkan menjadi `hujan` agar CARS otomatis memprioritaskan
      destinasi indoor atau mixed.
    - Jika data BMKG kosong, sistem mengembalikan fallback `cerah`, bukan error.
    """
    response = requests.get(
        BMKG_FORECAST_URL,
        params={"adm4": adm4},
        timeout=timeout,
    )
    response.raise_for_status()

    payload = response.json()
    data = payload.get("data", [])

    if not data:
        return {
            "weather_desc": "cerah",
            "weather_desc_en": "clear",
            "weather_group": "cerah",
            "rain_detected": False,
            "rain_slots_count": 0,
            "forecast_slots_checked": 0,
            "weather_source_note": "Data BMKG kosong, fallback cerah.",
        }

    forecasts = _flatten_forecasts(data[0].get("cuaca", []))

    if not forecasts:
        return {
            "weather_desc": "cerah",
            "weather_desc_en": "clear",
            "weather_group": "cerah",
            "rain_detected": False,
            "rain_slots_count": 0,
            "forecast_slots_checked": 0,
            "weather_source_note": "Field cuaca BMKG kosong, fallback cerah.",
        }

    now = datetime.now()
    future_forecasts: List[Dict[str, Any]] = []

    for forecast in forecasts:
        forecast_dt = _parse_local_datetime(forecast.get("local_datetime"))
        if forecast_dt is None:
            continue
        if forecast_dt >= now:
            future_forecasts.append(forecast)

    # BMKG biasanya menyediakan interval sekitar 3 jam.
    # 8 slot per hari x 3 hari = 24 slot.
    next_3_days = future_forecasts[:24] if future_forecasts else forecasts[:24]

    rainy_slots = [
        item
        for item in next_3_days
        if _is_rainy_desc(item.get("weather_desc"))
        or _is_rainy_desc(item.get("weather_desc_en"))
    ]

    if rainy_slots:
        selected = rainy_slots[0]
        return {
            "weather_desc": "hujan",
            "weather_desc_en": selected.get("weather_desc_en") or "rain",
            "weather_group": "hujan",
            "rain_detected": True,
            "rain_slots_count": len(rainy_slots),
            "forecast_slots_checked": len(next_3_days),
            "first_rain_datetime": selected.get("local_datetime"),
            "raw_selected": selected,
        }

    selected = next_3_days[0] if next_3_days else forecasts[0]
    weather_desc = selected.get("weather_desc") or "cerah"
    weather_desc_en = selected.get("weather_desc_en")

    return {
        "weather_desc": weather_desc,
        "weather_desc_en": weather_desc_en,
        "weather_group": "cerah",
        "rain_detected": False,
        "rain_slots_count": 0,
        "forecast_slots_checked": len(next_3_days),
        "temperature": selected.get("t"),
        "humidity": selected.get("hu"),
        "wind_speed": selected.get("ws"),
        "local_datetime": selected.get("local_datetime"),
        "raw_selected": selected,
    }
