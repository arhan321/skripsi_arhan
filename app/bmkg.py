from __future__ import annotations

from datetime import datetime
from typing import Any, Dict, List, Optional

import requests

BMKG_FORECAST_URL = "https://api.bmkg.go.id/publik/prakiraan-cuaca"


def _flatten_forecasts(cuaca: Any) -> List[Dict[str, Any]]:
    """BMKG mengembalikan cuaca sebagai list yang kadang nested. Fungsi ini meratakan list tersebut."""
    forecasts: List[Dict[str, Any]] = []
    if isinstance(cuaca, list):
        for item in cuaca:
            if isinstance(item, list):
                forecasts.extend([x for x in item if isinstance(x, dict)])
            elif isinstance(item, dict):
                forecasts.append(item)
    return forecasts


def fetch_bmkg_weather(adm4: str, timeout: int = 10) -> Dict[str, Any]:
    """Ambil prakiraan cuaca BMKG berdasarkan kode wilayah tingkat IV (adm4).

    Catatan:
    - API BMKG membutuhkan kode wilayah adm4, bukan latitude/longitude langsung.
    - Untuk tahap awal skripsi, adm4 bisa dikirim dari frontend/backend sebagai konteks lokasi user.
    """
    response = requests.get(BMKG_FORECAST_URL, params={"adm4": adm4}, timeout=timeout)
    response.raise_for_status()
    payload = response.json()

    data = payload.get("data", [])
    if not data:
        return {
            "weather_desc": None,
            "weather_desc_en": None,
            "raw": payload,
            "message": "Data BMKG kosong untuk adm4 tersebut.",
        }

    forecasts = _flatten_forecasts(data[0].get("cuaca", []))
    if not forecasts:
        return {
            "weather_desc": None,
            "weather_desc_en": None,
            "raw": payload,
            "message": "Field cuaca BMKG tidak ditemukan atau format tidak sesuai.",
        }

    now = datetime.now()
    selected = forecasts[0]
    for forecast in forecasts:
        local_dt = forecast.get("local_datetime")
        if not local_dt:
            continue
        try:
            forecast_dt = datetime.strptime(local_dt, "%Y-%m-%d %H:%M:%S")
            if forecast_dt >= now:
                selected = forecast
                break
        except ValueError:
            continue

    return {
        "weather_desc": selected.get("weather_desc"),
        "weather_desc_en": selected.get("weather_desc_en"),
        "temperature": selected.get("t"),
        "humidity": selected.get("hu"),
        "wind_speed": selected.get("ws"),
        "local_datetime": selected.get("local_datetime"),
        "raw_selected": selected,
    }
