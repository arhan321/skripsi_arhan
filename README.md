# TourHub Bali ML API

Program awal untuk skripsi TourHub Bali: sistem rekomendasi destinasi wisata menggunakan **Content-Based Filtering (CBF)** dan **Context-Aware Recommender System (CARS)**.

## Isi project

```text
tourhub-bali-ml/
├── app/
│   ├── main.py              # Endpoint FastAPI
│   ├── recommender.py       # Logic CBF + CARS
│   ├── bmkg.py              # Client API BMKG opsional
│   └── schemas.py           # Request/response schema
├── data/
│   └── bali_tourist_destination.csv
├── notebooks/
│   └── 01_eda_cbf_cars.ipynb
├── tests/
│   └── test_recommender.py
├── docker-compose.yml
├── Dockerfile
├── requirements.txt
└── README.md
```

## Cara menjalankan dengan Docker Compose

```bash
docker compose up --build
```

Buka dokumentasi API:

```text
http://localhost:8000/docs
```

Cek API:

```bash
curl http://localhost:8000/health
```

## Endpoint utama

### 1. Metadata dataset

```bash
curl http://localhost:8000/metadata
```

### 2. List destinasi

```bash
curl "http://localhost:8000/destinations?limit=10&kategori=Alam"
```

### 3. Rekomendasi CBF + CARS manual weather

```bash
curl -X POST "http://localhost:8000/recommend" \
  -H "Content-Type: application/json" \
  -d '{
    "kategori_preferensi": ["Alam", "Budaya"],
    "kabupaten_kota": "Kabupaten Gianyar",
    "keywords": ["pantai", "sunset"],
    "min_rating": 4.2,
    "top_n": 10,
    "weather": "hujan",
    "visit_day": "weekend",
    "is_high_season": false
  }'
```

### 4. Rekomendasi dengan BMKG

BMKG memakai kode wilayah administrasi tingkat IV (`adm4`). Jadi API ini tidak bisa langsung memakai latitude/longitude. Untuk tahap awal, kirim `bmkg_adm4` dari frontend/backend.

```bash
curl -X POST "http://localhost:8000/recommend" \
  -H "Content-Type: application/json" \
  -d '{
    "kategori_preferensi": ["Alam"],
    "kabupaten_kota": "Kabupaten Gianyar",
    "top_n": 10,
    "use_bmkg": true,
    "bmkg_adm4": "31.71.03.1001",
    "visit_day": "weekend"
  }'
```

> Catatan: contoh `adm4` di atas adalah contoh dari dokumentasi BMKG, bukan kode wilayah Bali. Untuk skripsi Bali, kamu perlu membuat mapping kode adm4 Bali sesuai lokasi user/destinasi.

## Penjelasan algoritma singkat

### CBF

CBF menghitung kemiripan antara preferensi user dan fitur destinasi. Fitur yang dipakai:

- nama tempat wisata
- kategori
- kecamatan
- kabupaten/kota
- tipe wisata: indoor/outdoor/mixed

Teknik yang dipakai:

```text
TF-IDF Vectorizer + Cosine Similarity
```

### CARS

CARS di project ini adalah contextual post-filtering, yaitu skor rekomendasi dari CBF disesuaikan dengan konteks:

- cuaca: cerah / hujan / berawan
- hari kunjungan: weekday / weekend
- high season
- perkiraan keramaian dari `jumlah_rating` sebagai proxy popularitas

Contoh rule:

```text
Jika cuaca hujan dan destinasi outdoor → skor diturunkan
Jika cuaca hujan dan destinasi indoor → skor dinaikkan
Jika weekend/high season dan destinasi sangat populer → skor diturunkan sedikit karena diasumsikan ramai
```

Formula awal:

```text
base_score = (0.70 * cbf_score) + (0.20 * rating_score) + (0.10 * popularity_score)
final_score = base_score * context_multiplier
```

## Cara menjalankan tanpa Docker

```bash
python -m venv .venv
source .venv/bin/activate  # Mac/Linux
# .venv\Scripts\activate   # Windows
pip install -r requirements.txt
uvicorn app.main:app --reload
```

## Langkah berikutnya untuk skripsi

1. Validasi hasil rekomendasi: apakah Top-10 terasa masuk akal.
2. Tambahkan kolom manual `tipe_wisata` jika ingin lebih akurat daripada rule otomatis.
3. Tambahkan mapping kode BMKG `adm4` untuk lokasi Bali.
4. Buat kuesioner user untuk mengukur Precision@10.
5. Setelah endpoint stabil, hubungkan Laravel/Flutter ke endpoint `/recommend`.
