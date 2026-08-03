from __future__ import annotations

from datetime import datetime
from typing import Any, Dict, List, Optional, Tuple
from zoneinfo import ZoneInfo

import requests


# ============================================================
# KONFIGURASI UTAMA BMKG
# ============================================================

# Endpoint resmi prakiraan cuaca publik BMKG.
BMKG_FORECAST_URL = "https://api.bmkg.go.id/publik/prakiraan-cuaca"

# Bali menggunakan zona waktu WITA atau UTC+8.
# Penggunaan timezone eksplisit mencegah perbedaan waktu apabila
# container atau server TourHub berjalan menggunakan timezone UTC.
BALI_TIMEZONE = ZoneInfo("Asia/Makassar")

# Daftar kata yang dianggap sebagai kondisi hujan.
# Kata bahasa Indonesia dan Inggris digunakan karena API BMKG
# menyediakan weather_desc dan weather_desc_en.
RAINY_TERMS = (
    "hujan",
    "gerimis",
    "lebat",
    "petir",
    "thunder",
    "rain",
    "shower",
    "storm",
)


# ============================================================
# FUNGSI BANTUAN UNTUK MERATAKAN DATA PRAKIRAAN
# ============================================================

def _flatten_forecasts(cuaca: Any) -> List[Dict[str, Any]]:
    """
    Meratakan struktur data prakiraan cuaca BMKG.

    Field `cuaca` dari API BMKG umumnya berbentuk nested list.
    Setiap list bagian dalam mewakili satu hari dan berisi
    beberapa slot prakiraan dengan interval sekitar tiga jam.

    Contoh struktur awal:

    [
        [
            {"local_datetime": "...", "weather_desc": "Cerah"},
            {"local_datetime": "...", "weather_desc": "Berawan"},
        ],
        [
            {"local_datetime": "...", "weather_desc": "Hujan Ringan"}
        ]
    ]

    Fungsi ini mengubahnya menjadi:

    [
        {"local_datetime": "...", "weather_desc": "Cerah"},
        {"local_datetime": "...", "weather_desc": "Berawan"},
        {"local_datetime": "...", "weather_desc": "Hujan Ringan"}
    ]

    Args:
        cuaca:
            Data field `cuaca` yang diperoleh dari API BMKG.

    Returns:
        List dictionary yang berisi seluruh slot prakiraan.
    """

    forecasts: List[Dict[str, Any]] = []

    # Jika data cuaca bukan list, berarti format data tidak sesuai.
    # Fungsi mengembalikan list kosong agar proses tidak error.
    if not isinstance(cuaca, list):
        return forecasts

    for item in cuaca:
        # Jika item merupakan nested list, masukkan seluruh dictionary
        # di dalamnya ke list forecasts.
        if isinstance(item, list):
            forecasts.extend(
                forecast
                for forecast in item
                if isinstance(forecast, dict)
            )

        # Jika item sudah langsung berbentuk dictionary,
        # masukkan satu data menggunakan append.
        elif isinstance(item, dict):
            forecasts.append(item)

    return forecasts


# ============================================================
# FUNGSI DETEKSI HUJAN
# ============================================================

def _is_rainy_desc(value: object) -> bool:
    """
    Menentukan apakah deskripsi cuaca termasuk kondisi hujan.

    Deteksi dilakukan dengan mencari kata-kata yang berhubungan
    dengan hujan pada weather_desc atau weather_desc_en.

    Contoh:
        "Hujan Ringan" -> True
        "Thunderstorm" -> True
        "Cerah Berawan" -> False

    Args:
        value:
            Deskripsi cuaca dari BMKG.

    Returns:
        True apabila kondisi tergolong hujan.
        False apabila kondisi tidak tergolong hujan.
    """

    # Jika value None, ubah menjadi string kosong.
    # Seluruh teks diubah menjadi huruf kecil agar pencarian
    # tidak sensitif terhadap kapitalisasi.
    text = str(value or "").strip().lower()

    return any(term in text for term in RAINY_TERMS)


# ============================================================
# FUNGSI PARSING TANGGAL BMKG
# ============================================================

def _parse_local_datetime(value: object) -> Optional[datetime]:
    """
    Mengubah local_datetime BMKG menjadi objek datetime timezone-aware.

    API BMKG biasanya mengirim format:
        2026-07-28 09:00:00

    Namun fungsi ini juga mendukung format:
        2026-07-28T09:00:00
        2026-07-28T09:00:00+08:00

    Jika tanggal tidak memiliki informasi timezone, sistem menganggap
    tanggal tersebut menggunakan zona waktu Bali atau WITA.

    Args:
        value:
            Teks tanggal yang diperoleh dari BMKG.

    Returns:
        Objek datetime dengan timezone Asia/Makassar.
        None apabila tanggal tidak dapat dibaca.
    """

    if not value:
        return None

    text = str(value).strip()

    # Mencoba parser ISO bawaan Python terlebih dahulu.
    # Metode ini dapat membaca format dengan "T" dan timezone offset.
    try:
        parsed = datetime.fromisoformat(text.replace("Z", "+00:00"))

        # Jika hasil parsing tidak memiliki timezone,
        # anggap tanggal berasal dari waktu lokal Bali.
        if parsed.tzinfo is None:
            return parsed.replace(tzinfo=BALI_TIMEZONE)

        # Jika tanggal sudah memiliki timezone, konversikan ke WITA.
        return parsed.astimezone(BALI_TIMEZONE)

    except ValueError:
        pass

    # Fallback untuk format BMKG yang umum digunakan.
    supported_formats = (
        "%Y-%m-%d %H:%M:%S",
        "%Y-%m-%dT%H:%M:%S",
    )

    for date_format in supported_formats:
        try:
            parsed = datetime.strptime(text, date_format)

            # Hasil strptime belum memiliki timezone.
            # Oleh karena itu, timezone Bali ditambahkan secara eksplisit.
            return parsed.replace(tzinfo=BALI_TIMEZONE)

        except ValueError:
            continue

    # Jika seluruh format gagal, kembalikan None.
    # Slot dengan tanggal tidak valid nantinya akan dilewati.
    return None


# ============================================================
# NORMALISASI WAKTU KUNJUNGAN
# ============================================================

def _normalize_visit_datetime(
    visit_datetime: datetime | str | None,
    now: datetime,
) -> Tuple[datetime, str]:
    """
    Menentukan waktu yang akan dijadikan acuan pemilihan prakiraan.

    Jika visit_datetime diberikan oleh pengguna, sistem menggunakan
    tanggal tersebut sebagai waktu target.

    Jika visit_datetime tidak diberikan atau formatnya tidak valid,
    sistem menggunakan waktu sekarang sebagai target.

    Args:
        visit_datetime:
            Tanggal dan waktu kunjungan dari pengguna.
            Dapat berupa objek datetime, string, atau None.

        now:
            Waktu sekarang dalam timezone Bali.

    Returns:
        Tuple yang berisi:
        - datetime target
        - keterangan sumber target
    """

    # Jika pengguna belum memilih waktu kunjungan,
    # gunakan waktu sekarang sebagai target.
    if visit_datetime is None:
        return now, "Waktu kunjungan tidak diberikan; menggunakan waktu sekarang."

    # Jika input sudah berupa datetime, normalisasikan timezone-nya.
    if isinstance(visit_datetime, datetime):
        parsed = visit_datetime

        if parsed.tzinfo is None:
            parsed = parsed.replace(tzinfo=BALI_TIMEZONE)
        else:
            parsed = parsed.astimezone(BALI_TIMEZONE)

    # Jika input berupa string, parse menggunakan fungsi yang sama
    # dengan waktu BMKG.
    else:
        parsed = _parse_local_datetime(visit_datetime)

        if parsed is None:
            return now, (
                "Format waktu kunjungan tidak valid; "
                "menggunakan waktu sekarang."
            )

    # Waktu kunjungan yang sudah lewat tidak digunakan karena
    # BMKG menyediakan data prakiraan, bukan data historis.
    if parsed < now:
        return now, (
            "Waktu kunjungan sudah lewat; "
            "menggunakan waktu sekarang."
        )

    return parsed, "Menggunakan waktu kunjungan yang diberikan pengguna."


# ============================================================
# RESPONSE FALLBACK
# ============================================================

def _fallback_weather(
    note: str,
    target_datetime: Optional[datetime] = None,
) -> Dict[str, Any]:
    """
    Membuat response fallback apabila API atau data BMKG bermasalah.

    Untuk menjaga kompatibilitas dengan algoritma CARS yang hanya
    menerima kelompok `cerah` dan `hujan`, fallback tetap menggunakan
    kelompok `cerah`.

    Field weather_is_fallback digunakan untuk menjelaskan bahwa kondisi
    tersebut bukan hasil prakiraan aktual dari BMKG.

    Args:
        note:
            Penjelasan penyebab fallback.

        target_datetime:
            Waktu target yang sedang digunakan.

    Returns:
        Dictionary konteks cuaca fallback.
    """

    return {
        "weather_desc": "cerah",
        "weather_desc_en": "clear",
        "weather_group": "cerah",

        # Menandakan bahwa tidak ada hujan yang terdeteksi.
        "rain_detected": False,
        "rain_slots_count": 0,

        # Tidak ada slot valid yang berhasil digunakan.
        "forecast_slots_checked": 0,

        # Field tambahan agar sistem mengetahui bahwa hasil ini fallback.
        "weather_is_fallback": True,
        "weather_source": "fallback",

        # Waktu target tetap disertakan untuk kebutuhan log/debugging.
        "target_datetime": (
            target_datetime.isoformat()
            if target_datetime is not None
            else None
        ),

        "selected_forecast_datetime": None,
        "time_difference_minutes": None,

        # Informasi cuaca tambahan tidak tersedia saat fallback.
        "temperature": None,
        "humidity": None,
        "wind_speed": None,

        # Alasan penggunaan fallback.
        "weather_source_note": note,

        # Tidak ada data mentah BMKG yang dipilih.
        "raw_selected": None,
    }


# ============================================================
# MEMILIH SLOT PRAKIRAAN
# ============================================================

def _select_forecast(
    parsed_forecasts: List[Tuple[datetime, Dict[str, Any]]],
    target_datetime: datetime,
    now: datetime,
    visit_datetime_provided: bool,
) -> Tuple[datetime, Dict[str, Any], str]:
    """
    Memilih slot prakiraan BMKG yang paling relevan.

    Jika waktu kunjungan diberikan:
        Pilih slot yang waktunya paling dekat dengan waktu kunjungan.

    Jika waktu kunjungan tidak diberikan:
        Pilih slot masa depan terdekat dari waktu sekarang.

    Pendekatan ini memperbaiki kode sebelumnya yang langsung menganggap
    kondisi hujan jika terdapat satu slot hujan di antara 24 slot atau
    sekitar tiga hari prakiraan.

    Args:
        parsed_forecasts:
            List tuple berisi datetime dan data prakiraan.

        target_datetime:
            Waktu yang ingin dicocokkan.

        now:
            Waktu sekarang di Bali.

        visit_datetime_provided:
            Menandakan apakah pengguna memberikan waktu kunjungan.

    Returns:
        Tuple:
        - datetime slot terpilih
        - dictionary prakiraan terpilih
        - metode pemilihan slot
    """

    if visit_datetime_provided:
        # Pilih slot dengan selisih waktu absolut terkecil dari
        # waktu kunjungan pengguna.
        selected_datetime, selected_forecast = min(
            parsed_forecasts,
            key=lambda item: abs(
                (item[0] - target_datetime).total_seconds()
            ),
        )

        return (
            selected_datetime,
            selected_forecast,
            "nearest_to_visit_datetime",
        )

    # Jika waktu kunjungan tidak diberikan, cari seluruh prakiraan
    # yang waktunya sama atau lebih besar dari waktu sekarang.
    future_forecasts = [
        item
        for item in parsed_forecasts
        if item[0] >= now
    ]

    if future_forecasts:
        # Karena data sudah diurutkan, elemen pertama merupakan
        # prakiraan masa depan yang paling dekat.
        selected_datetime, selected_forecast = future_forecasts[0]

        return (
            selected_datetime,
            selected_forecast,
            "nearest_upcoming_forecast",
        )

    # Jika semua slot sudah lewat, pilih slot dengan jarak waktu
    # paling dekat sebagai fallback terakhir.
    selected_datetime, selected_forecast = min(
        parsed_forecasts,
        key=lambda item: abs((item[0] - now).total_seconds()),
    )

    return (
        selected_datetime,
        selected_forecast,
        "nearest_available_forecast",
    )


# ============================================================
# FUNGSI UTAMA INTEGRASI BMKG
# ============================================================

def fetch_bmkg_weather(
    adm4: str,
    timeout: int = 10,
    visit_datetime: datetime | str | None = None,
) -> Dict[str, Any]:
    """
    Mengambil prakiraan cuaca BMKG berdasarkan kode ADM4.

    Proses utama:
    1. Validasi kode ADM4.
    2. Menentukan waktu target.
    3. Mengirim request ke API BMKG.
    4. Meratakan nested list prakiraan.
    5. Mengubah local_datetime menjadi datetime.
    6. Memilih satu slot yang paling dekat dengan waktu target.
    7. Menentukan kelompok cuaca hujan atau cerah.
    8. Mengembalikan konteks untuk digunakan oleh CARS.

    Berbeda dari kode sebelumnya, fungsi ini TIDAK langsung menganggap
    seluruh kondisi sebagai hujan hanya karena terdapat hujan pada salah
    satu slot dalam tiga hari ke depan.

    Args:
        adm4:
            Kode wilayah administratif tingkat desa atau kelurahan
            yang digunakan oleh API BMKG.

        timeout:
            Batas maksimal waktu tunggu request dalam detik.

        visit_datetime:
            Waktu kunjungan pengguna. Parameter ini bersifat opsional.

            Contoh:
                "2026-07-29 12:00:00"

            Jika tidak diberikan, sistem menggunakan prakiraan masa
            depan terdekat dari waktu sekarang.

    Returns:
        Dictionary konteks cuaca yang digunakan oleh CARS.
    """

    # Waktu saat fungsi dijalankan dalam zona waktu Bali.
    now = datetime.now(BALI_TIMEZONE)

    # Membersihkan kode ADM4 dari spasi.
    adm4 = str(adm4 or "").strip()

    # ADM4 wajib tersedia karena digunakan sebagai parameter API BMKG.
    if not adm4:
        return _fallback_weather(
            note="Kode ADM4 kosong; prakiraan BMKG tidak dapat diambil.",
            target_datetime=now,
        )

    # Menentukan waktu yang dijadikan acuan pemilihan prakiraan.
    target_datetime, target_note = _normalize_visit_datetime(
        visit_datetime=visit_datetime,
        now=now,
    )

    # Digunakan untuk menentukan cara memilih slot prakiraan.
    visit_datetime_provided = visit_datetime is not None

    try:
        # Mengirim request GET ke API prakiraan cuaca BMKG.
        response = requests.get(
            BMKG_FORECAST_URL,
            params={"adm4": adm4},
            timeout=timeout,
        )

        # Menghasilkan exception jika status HTTP bukan 2xx.
        response.raise_for_status()

        # Mengubah response JSON menjadi dictionary Python.
        payload = response.json()

    except requests.Timeout:
        return _fallback_weather(
            note=(
                "API BMKG melebihi batas waktu respons; "
                "menggunakan fallback cerah."
            ),
            target_datetime=target_datetime,
        )

    except requests.RequestException:
        return _fallback_weather(
            note=(
                "API BMKG tidak dapat dihubungi; "
                "menggunakan fallback cerah."
            ),
            target_datetime=target_datetime,
        )

    except ValueError:
        return _fallback_weather(
            note=(
                "Respons API BMKG bukan JSON yang valid; "
                "menggunakan fallback cerah."
            ),
            target_datetime=target_datetime,
        )

    # Mengambil field data dari respons BMKG.
    # Jika field tidak tersedia, gunakan list kosong.
    data = payload.get("data", [])

    if not isinstance(data, list) or not data:
        return _fallback_weather(
            note="Data BMKG kosong; menggunakan fallback cerah.",
            target_datetime=target_datetime,
        )

    # Mengambil field cuaca dari data wilayah pertama,
    # kemudian meratakan nested list prakiraan.
    forecasts = _flatten_forecasts(
        data[0].get("cuaca", [])
        if isinstance(data[0], dict)
        else []
    )

    if not forecasts:
        return _fallback_weather(
            note=(
                "Field cuaca BMKG kosong; "
                "menggunakan fallback cerah."
            ),
            target_datetime=target_datetime,
        )

    # List ini menyimpan tuple:
    # (waktu prakiraan yang sudah diparsing, data prakiraan asli)
    parsed_forecasts: List[Tuple[datetime, Dict[str, Any]]] = []

    for forecast in forecasts:
        forecast_datetime = _parse_local_datetime(
            forecast.get("local_datetime")
        )

        # Slot tanpa waktu valid tidak digunakan dalam pemilihan.
        if forecast_datetime is None:
            continue

        parsed_forecasts.append(
            (forecast_datetime, forecast)
        )

    # Apabila tidak ada tanggal yang dapat diparsing, sistem masih
    # mencoba menggunakan slot pertama berdasarkan deskripsi cuacanya.
    if not parsed_forecasts:
        selected = forecasts[0]

        weather_desc = (
            selected.get("weather_desc")
            or "Tidak diketahui"
        )
        weather_desc_en = selected.get("weather_desc_en")

        is_rainy = (
            _is_rainy_desc(weather_desc)
            or _is_rainy_desc(weather_desc_en)
        )

        return {
            "weather_desc": weather_desc,
            "weather_desc_en": weather_desc_en,
            "weather_group": "hujan" if is_rainy else "cerah",
            "rain_detected": is_rainy,
            "rain_slots_count": 1 if is_rainy else 0,
            "forecast_slots_checked": len(forecasts),
            "weather_is_fallback": False,
            "weather_source": "bmkg",
            "target_datetime": target_datetime.isoformat(),
            "selected_forecast_datetime": None,
            "selection_mode": "first_slot_without_valid_datetime",
            "time_difference_minutes": None,
            "temperature": selected.get("t"),
            "humidity": selected.get("hu"),
            "wind_speed": selected.get("ws"),
            "weather_source_note": (
                "Waktu slot BMKG tidak dapat diparsing; "
                "menggunakan slot prakiraan pertama."
            ),
            "raw_selected": selected,
        }

    # Mengurutkan slot prakiraan dari waktu paling awal.
    parsed_forecasts.sort(key=lambda item: item[0])

    # Memilih slot prakiraan yang paling relevan dengan waktu target.
    (
        selected_datetime,
        selected,
        selection_mode,
    ) = _select_forecast(
        parsed_forecasts=parsed_forecasts,
        target_datetime=target_datetime,
        now=now,
        visit_datetime_provided=visit_datetime_provided,
    )

    # Mengambil deskripsi cuaca dari slot terpilih.
    weather_desc = (
        selected.get("weather_desc")
        or "Tidak diketahui"
    )
    weather_desc_en = selected.get("weather_desc_en")

    # Kondisi dianggap hujan jika deskripsi Indonesia atau
    # deskripsi Inggris mengandung kata yang berkaitan dengan hujan.
    is_rainy = (
        _is_rainy_desc(weather_desc)
        or _is_rainy_desc(weather_desc_en)
    )

    # TourHub saat ini menggunakan dua kelompok:
    # - hujan
    # - cerah/non-hujan
    #
    # Oleh karena itu, kondisi berawan tetap masuk kelompok cerah.
    weather_group = "hujan" if is_rainy else "cerah"

    # Menghitung selisih antara slot terpilih dan waktu target.
    time_difference_minutes = round(
        abs(
            (selected_datetime - target_datetime).total_seconds()
        ) / 60,
        2,
    )

    return {
        # Deskripsi asli prakiraan BMKG.
        "weather_desc": weather_desc,
        "weather_desc_en": weather_desc_en,

        # Kelompok sederhana yang digunakan algoritma CARS.
        "weather_group": weather_group,

        # Informasi pendeteksian hujan.
        "rain_detected": is_rainy,
        "rain_slots_count": 1 if is_rainy else 0,

        # Jumlah slot valid yang diperiksa.
        "forecast_slots_checked": len(parsed_forecasts),

        # Menandakan bahwa data benar-benar berasal dari BMKG.
        "weather_is_fallback": False,
        "weather_source": "bmkg",

        # Informasi waktu target dan waktu prakiraan yang dipilih.
        "target_datetime": target_datetime.isoformat(),
        "selected_forecast_datetime": selected_datetime.isoformat(),
        "selection_mode": selection_mode,
        "time_difference_minutes": time_difference_minutes,

        # Informasi cuaca tambahan dari BMKG.
        "temperature": selected.get("t"),
        "humidity": selected.get("hu"),
        "wind_speed": selected.get("ws"),

        # Field kompatibilitas dengan kode sebelumnya.
        "local_datetime": selected.get("local_datetime"),
        "first_rain_datetime": (
            selected.get("local_datetime")
            if is_rainy
            else None
        ),

        # Catatan mengenai cara waktu target ditentukan.
        "weather_source_note": target_note,

        # Data asli slot terpilih untuk kebutuhan debugging.
        "raw_selected": selected,
    }