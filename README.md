# TourHub Bali ML API

> README versi update: sudah ditambahkan hasil running notebook `01_eda_cbf_cars.ipynb`, bukti preprocessing, hasil TF-IDF, hasil rekomendasi CBF + CARS, serta panduan kernel Jupyter di VS Code.


README ini menjelaskan cara menjalankan dan menguji project **TourHub Bali ML API**, yaitu layanan rekomendasi destinasi wisata Bali menggunakan:

- **Content-Based Filtering (CBF)** untuk mencari destinasi yang sesuai dengan preferensi user.
- **Context-Aware Recommender System (CARS)** untuk menyesuaikan hasil rekomendasi berdasarkan konteks seperti cuaca, hari kunjungan, high season, dan potensi keramaian.
- **FastAPI** sebagai service machine learning/recommender system.
- **Docker Compose** agar API mudah dijalankan tanpa setup manual yang rumit.

---

## 1. Gambaran Singkat Project

Project ini adalah backend machine learning sederhana untuk skripsi TourHub Bali.

Alur sistem:

```text
Input preferensi user
        ↓
Content-Based Filtering
        ↓
Skor kemiripan destinasi
        ↓
Context-Aware Recommender System
        ↓
Penyesuaian skor berdasarkan cuaca, hari, high season, dan keramaian
        ↓
Top-N rekomendasi destinasi wisata Bali
```

Contoh sederhana:

```text
User memilih kategori: Alam
Cuaca: cerah
→ Sistem memprioritaskan destinasi outdoor seperti waterfall, beach, rice terrace.

User memilih kategori: Alam
Cuaca: hujan
→ Sistem dapat menaikkan destinasi indoor atau mixed sebagai alternatif yang lebih aman.
```

---

## 2. Struktur Folder

```text
tourhub-bali-ml/
├── app/
│   ├── __init__.py
│   ├── main.py              # File utama FastAPI dan endpoint API
│   ├── recommender.py       # Logic CBF + CARS
│   ├── bmkg.py              # Integrasi BMKG opsional
│   └── schemas.py           # Schema request dan response API
│
├── data/
│   └── bali_tourist_destination.csv
│
├── notebooks/
│   └── 01_eda_cbf_cars.ipynb
│
├── tests/
│   └── test_recommender.py
│
├── docker-compose.yml
├── Dockerfile
├── requirements.txt
└── README.md
```

---

## 3. Teknologi yang Digunakan

| Teknologi | Fungsi |
|---|---|
| Python | Bahasa utama untuk machine learning/recommender system |
| FastAPI | Membuat REST API untuk rekomendasi |
| Pandas | Membaca dan memproses dataset |
| Scikit-learn | TF-IDF Vectorizer dan Cosine Similarity |
| NumPy | Perhitungan numerik |
| Requests | Mengambil data BMKG secara opsional |
| Docker | Menjalankan aplikasi dalam container |
| Docker Compose | Menjalankan service FastAPI dengan konfigurasi sederhana |
| Jupyter Notebook | Eksplorasi dataset dan eksperimen awal |

---

## 4. Dataset yang Dipakai

Dataset utama:

```text
data/bali_tourist_destination.csv
```

Kolom penting yang digunakan:

| Kolom | Fungsi |
|---|---|
| id_tempat | ID destinasi |
| nama_tempat_wisata | Nama destinasi wisata |
| kategori | Kategori wisata, misalnya Alam, Budaya, Rekreasi, Umum |
| kecamatan | Lokasi kecamatan |
| kabupaten_kota | Lokasi kabupaten/kota |
| rating | Rating destinasi |
| jumlah_rating | Jumlah ulasan/rating |
| latitude | Latitude destinasi |
| longitude | Longitude destinasi |
| link_google_maps | Link Google Maps |
| link_gambar | Link gambar destinasi |

Kolom tambahan yang dibuat otomatis oleh sistem:

| Kolom | Fungsi |
|---|---|
| tipe_wisata | Indoor, outdoor, atau mixed |
| rating_score | Normalisasi rating 0 sampai 1 |
| popularity_score | Normalisasi popularitas berdasarkan jumlah_rating |
| feature_text | Gabungan teks destinasi untuk CBF |
| cbf_score | Skor kemiripan preferensi user dengan destinasi |
| context_multiplier | Pengali skor dari CARS |
| final_score | Skor akhir rekomendasi |
| alasan | Penjelasan kenapa destinasi direkomendasikan |

---

## 5. Konsep Algoritma

### 5.1 Content-Based Filtering atau CBF

CBF mencari destinasi berdasarkan kemiripan antara preferensi user dan fitur destinasi.

Fitur destinasi yang dipakai:

```text
nama_tempat_wisata + kategori + kecamatan + kabupaten_kota + tipe_wisata
```

Preferensi user akan digabung menjadi query teks, misalnya:

```text
alam kabupaten gianyar pantai sunset
```

Lalu sistem menghitung kemiripan menggunakan:

```text
TF-IDF Vectorizer + Cosine Similarity
```

Hasilnya berupa:

```text
cbf_score
```

Semakin besar `cbf_score`, semakin mirip destinasi tersebut dengan preferensi user.

---

### 5.2 Context-Aware Recommender System atau CARS

CARS digunakan untuk menyesuaikan rekomendasi berdasarkan konteks.

Konteks yang digunakan di project ini:

| Konteks | Contoh |
|---|---|
| Cuaca | cerah, hujan, berawan |
| Hari kunjungan | weekday, weekend |
| High season | true atau false |
| Potensi keramaian | dihitung dari popularity_score |

CARS di project ini menggunakan pendekatan **contextual post-filtering**, artinya:

```text
CBF menghitung skor awal → CARS menyesuaikan skor akhir
```

---

## 6. Formula Skor

Formula dasar:

```text
base_score = (0.70 * cbf_score) + (0.20 * rating_score) + (0.10 * popularity_score)
```

Formula akhir:

```text
final_score = base_score * context_multiplier
```

Keterangan:

| Field | Penjelasan |
|---|---|
| cbf_score | Skor kemiripan berdasarkan preferensi user |
| rating_score | Skor rating yang sudah dinormalisasi |
| popularity_score | Skor popularitas dari jumlah rating |
| context_multiplier | Pengali dari CARS |
| final_score | Skor akhir yang dipakai untuk ranking |

---

## 7. Rule CARS yang Digunakan

### 7.1 Rule Cuaca Hujan

| Tipe Destinasi | Pengaruh |
|---|---:|
| outdoor | dikalikan 0.72 |
| indoor | dikalikan 1.12 |
| mixed | dikalikan 0.92 |

Artinya, saat hujan:

```text
Destinasi outdoor diturunkan.
Destinasi indoor dinaikkan.
Destinasi mixed sedikit diturunkan.
```

---

### 7.2 Rule Cuaca Cerah

| Tipe Destinasi | Pengaruh |
|---|---:|
| outdoor | dikalikan 1.08 |
| indoor | dikalikan 0.96 |
| mixed | tetap |

Artinya, saat cuaca cerah:

```text
Destinasi outdoor lebih diprioritaskan.
```

---

### 7.3 Rule Cuaca Berawan

| Tipe Destinasi | Pengaruh |
|---|---:|
| outdoor | dikalikan 1.02 |

Artinya, saat berawan:

```text
Destinasi outdoor masih cukup aman, tetapi bonusnya kecil.
```

---

### 7.4 Rule Weekend

Jika `visit_day = weekend`:

| Kondisi | Pengaruh |
|---|---:|
| popularity_score >= 0.75 | dikalikan 0.90 |
| popularity_score < 0.75 | dikalikan 1.03 |

Artinya:

```text
Saat weekend, destinasi yang sangat populer diasumsikan lebih ramai.
Destinasi yang tidak terlalu populer sedikit diprioritaskan sebagai alternatif.
```

---

### 7.5 Rule High Season

Jika `is_high_season = true`:

| Kondisi | Pengaruh |
|---|---:|
| popularity_score >= 0.75 | dikalikan 0.88 |
| popularity_score < 0.75 | dikalikan 1.05 |

Artinya:

```text
Saat high season, destinasi populer diberi penalti keramaian.
Destinasi alternatif sedikit diprioritaskan.
```

---

## 8. Cara Menjalankan dengan Docker Compose

Pastikan Docker sudah terinstall.

Masuk ke folder project:

```bash
cd tourhub-bali-ml
```

Jalankan project:

```bash
docker compose up --build
```

Jika berhasil, akan muncul log seperti:

```text
Uvicorn running on http://0.0.0.0:8000
```

Buka dokumentasi API di browser:

```text
http://localhost:8000/docs
```

---

## 9. Cara Menjalankan Tanpa Docker

Gunakan cara ini jika ingin menjalankan langsung di laptop.

### 9.1 Buat virtual environment

Mac/Linux:

```bash
python -m venv .venv
source .venv/bin/activate
```

Windows PowerShell:

```powershell
python -m venv .venv
.\.venv\Scripts\Activate.ps1
```

Windows CMD:

```cmd
python -m venv .venv
.venv\Scripts\activate
```

### 9.2 Install dependency

```bash
pip install -r requirements.txt
```

### 9.3 Jalankan FastAPI

```bash
uvicorn app.main:app --reload
```

Buka:

```text
http://localhost:8000/docs
```

---

## 10. Endpoint API

### 10.1 Root Endpoint

```http
GET /
```

Fungsi:

```text
Mengecek apakah API hidup.
```

Contoh curl:

```bash
curl http://localhost:8000/
```

Contoh response:

```json
{
  "message": "TourHub Bali ML API is running",
  "docs": "/docs",
  "algorithm": "Content-Based Filtering + Context-Aware Recommender System"
}
```

---

### 10.2 Health Check

```http
GET /health
```

Fungsi:

```text
Mengecek status API dan metadata dataset.
```

Contoh curl:

```bash
curl http://localhost:8000/health
```

Tanda berhasil:

```json
{
  "status": "ok",
  "metadata": {
    "total_destinations": 1452
  }
}
```

Jumlah `total_destinations` bisa berubah jika dataset berubah.

---

### 10.3 Metadata Dataset

```http
GET /metadata
```

Fungsi:

```text
Melihat jumlah destinasi, daftar kategori, daftar kabupaten/kota, dan jumlah tipe wisata.
```

Contoh curl:

```bash
curl http://localhost:8000/metadata
```

---

### 10.4 List Destinasi

```http
GET /destinations
```

Parameter query:

| Parameter | Wajib | Contoh | Penjelasan |
|---|---|---|---|
| limit | Tidak | 10 | Jumlah data yang ditampilkan |
| kategori | Tidak | Alam | Filter kategori |
| kabupaten_kota | Tidak | Kabupaten Gianyar | Filter kabupaten/kota |

Contoh curl:

```bash
curl "http://localhost:8000/destinations?limit=10&kategori=Alam"
```

Contoh dengan kabupaten/kota:

```bash
curl "http://localhost:8000/destinations?limit=10&kategori=Alam&kabupaten_kota=Kabupaten%20Gianyar"
```

---

### 10.5 Endpoint Rekomendasi

```http
POST /recommend
```

Fungsi:

```text
Menghasilkan rekomendasi Top-N destinasi wisata berdasarkan CBF + CARS.
```

---

## 11. Parameter Request `/recommend`

| Parameter | Tipe | Wajib | Contoh | Penjelasan |
|---|---|---|---|---|
| kategori_preferensi | array string | Tidak | ["Alam"] | Kategori yang disukai user |
| kabupaten_kota | string/null | Tidak | "Kabupaten Gianyar" | Preferensi/filter kabupaten/kota |
| kecamatan | string/null | Tidak | "Ubud" | Preferensi/filter kecamatan |
| keywords | array string | Tidak | ["pantai", "sunset"] | Kata kunci tambahan |
| min_rating | number/null | Tidak | 4.2 | Rating minimal |
| top_n | integer | Tidak | 10 | Jumlah rekomendasi |
| weather | string/null | Tidak | "hujan" | Cuaca manual jika tidak pakai BMKG |
| visit_day | string/null | Tidak | "weekend" | weekday atau weekend |
| is_high_season | boolean | Tidak | false | Apakah sedang high season |
| use_bmkg | boolean | Tidak | false | Ambil cuaca dari BMKG atau tidak |
| bmkg_adm4 | string/null | Jika use_bmkg=true | "kode_adm4" | Kode wilayah BMKG |

---

## 12. Sample Parameter Pengetesan

Bagian ini bisa langsung kamu pakai di Swagger UI, Postman, Insomnia, atau curl.

Buka Swagger:

```text
http://localhost:8000/docs
```

Pilih endpoint:

```text
POST /recommend
```

Klik:

```text
Try it out
```

Lalu masukkan salah satu JSON di bawah ini.

---

### Test 1 — Wisata Alam, Cuaca Cerah, Weekday

Tujuan test:

```text
Membuktikan bahwa saat cuaca cerah, destinasi outdoor/alam naik.
```

Sample JSON:

```json
{
  "kategori_preferensi": ["Alam"],
  "kabupaten_kota": "Kabupaten Gianyar",
  "kecamatan": null,
  "keywords": [],
  "min_rating": 4.2,
  "top_n": 10,
  "weather": "cerah",
  "visit_day": "weekday",
  "is_high_season": false,
  "use_bmkg": false,
  "bmkg_adm4": null
}
```

Ekspektasi hasil:

```text
Destinasi outdoor seperti waterfall, beach, rice terrace, pura outdoor, atau wisata alam seharusnya banyak muncul di posisi atas.
```

Tanda CARS berhasil:

```text
weather_group = cerah
context_multiplier destinasi outdoor biasanya 1.08
alasan berisi: cuaca cerah mendukung destinasi outdoor
```

Contoh curl:

```bash
curl -X POST "http://localhost:8000/recommend" \
  -H "Content-Type: application/json" \
  -d '{
    "kategori_preferensi": ["Alam"],
    "kabupaten_kota": "Kabupaten Gianyar",
    "kecamatan": null,
    "keywords": [],
    "min_rating": 4.2,
    "top_n": 10,
    "weather": "cerah",
    "visit_day": "weekday",
    "is_high_season": false,
    "use_bmkg": false,
    "bmkg_adm4": null
  }'
```

---

### Test 2 — Wisata Alam, Cuaca Hujan, Weekday

Tujuan test:

```text
Membuktikan bahwa saat cuaca hujan, destinasi indoor lebih diprioritaskan sebagai alternatif.
```

Sample JSON:

```json
{
  "kategori_preferensi": ["Alam"],
  "kabupaten_kota": "Kabupaten Gianyar",
  "kecamatan": null,
  "keywords": [],
  "min_rating": 4.2,
  "top_n": 10,
  "weather": "hujan",
  "visit_day": "weekday",
  "is_high_season": false,
  "use_bmkg": false,
  "bmkg_adm4": null
}
```

Ekspektasi hasil:

```text
Destinasi indoor seperti museum bisa naik ke posisi atas.
Destinasi outdoor bisa turun karena cuaca hujan.
```

Tanda CARS berhasil:

```text
weather_group = hujan
context_multiplier indoor biasanya 1.12
context_multiplier outdoor biasanya 0.72
context_multiplier mixed biasanya 0.92
```

Contoh curl:

```bash
curl -X POST "http://localhost:8000/recommend" \
  -H "Content-Type: application/json" \
  -d '{
    "kategori_preferensi": ["Alam"],
    "kabupaten_kota": "Kabupaten Gianyar",
    "kecamatan": null,
    "keywords": [],
    "min_rating": 4.2,
    "top_n": 10,
    "weather": "hujan",
    "visit_day": "weekday",
    "is_high_season": false,
    "use_bmkg": false,
    "bmkg_adm4": null
  }'
```

---

### Test 3 — Alam + Budaya, Keyword Pantai Sunset, Hujan, Weekend

Tujuan test:

```text
Menguji kombinasi preferensi kategori, keyword, cuaca hujan, dan weekend.
```

Sample JSON:

```json
{
  "kategori_preferensi": ["Alam", "Budaya"],
  "kabupaten_kota": "Kabupaten Gianyar",
  "kecamatan": null,
  "keywords": ["pantai", "sunset"],
  "min_rating": 4.2,
  "top_n": 10,
  "weather": "hujan",
  "visit_day": "weekend",
  "is_high_season": false,
  "use_bmkg": false,
  "bmkg_adm4": null
}
```

Ekspektasi hasil:

```text
Karena cuaca hujan, destinasi indoor bisa lebih naik walaupun user memasukkan keyword pantai/sunset.
Saat weekend, destinasi yang tidak terlalu populer bisa mendapat sedikit bonus kenyamanan.
```

Tanda berhasil:

```text
weather_source = manual
weather_used = hujan
alasan menjelaskan pengaruh hujan dan/atau weekend
```

Contoh curl:

```bash
curl -X POST "http://localhost:8000/recommend" \
  -H "Content-Type: application/json" \
  -d '{
    "kategori_preferensi": ["Alam", "Budaya"],
    "kabupaten_kota": "Kabupaten Gianyar",
    "kecamatan": null,
    "keywords": ["pantai", "sunset"],
    "min_rating": 4.2,
    "top_n": 10,
    "weather": "hujan",
    "visit_day": "weekend",
    "is_high_season": false,
    "use_bmkg": false,
    "bmkg_adm4": null
  }'
```

---

### Test 4 — Alam, Cerah, Weekend, High Season

Tujuan test:

```text
Menguji penalti keramaian saat weekend dan high season.
```

Sample JSON:

```json
{
  "kategori_preferensi": ["Alam"],
  "kabupaten_kota": "Kabupaten Gianyar",
  "kecamatan": null,
  "keywords": [],
  "min_rating": 4.2,
  "top_n": 10,
  "weather": "cerah",
  "visit_day": "weekend",
  "is_high_season": true,
  "use_bmkg": false,
  "bmkg_adm4": null
}
```

Ekspektasi hasil:

```text
Outdoor tetap terbantu karena cuaca cerah.
Destinasi yang sangat populer bisa sedikit turun karena weekend + high season.
Destinasi alternatif bisa naik.
```

Tanda berhasil:

```text
alasan bisa berisi penalti weekend/high season untuk destinasi populer
context_multiplier berubah sesuai gabungan rule
```

---

### Test 5 — Budaya, Hujan, Ubud

Tujuan test:

```text
Menguji rekomendasi budaya saat hujan di area Ubud/Gianyar.
```

Sample JSON:

```json
{
  "kategori_preferensi": ["Budaya"],
  "kabupaten_kota": "Kabupaten Gianyar",
  "kecamatan": "Ubud",
  "keywords": ["museum", "galeri", "seni"],
  "min_rating": 4.0,
  "top_n": 10,
  "weather": "hujan",
  "visit_day": "weekday",
  "is_high_season": false,
  "use_bmkg": false,
  "bmkg_adm4": null
}
```

Ekspektasi hasil:

```text
Museum, galeri, dan destinasi budaya indoor/mixed lebih berpeluang muncul.
```

---

### Test 6 — Rekreasi, Cerah, Semua Kabupaten/Kota

Tujuan test:

```text
Menguji rekomendasi tanpa filter kabupaten/kota.
```

Sample JSON:

```json
{
  "kategori_preferensi": ["Rekreasi"],
  "kabupaten_kota": null,
  "kecamatan": null,
  "keywords": ["family", "park", "zoo"],
  "min_rating": 4.0,
  "top_n": 10,
  "weather": "cerah",
  "visit_day": "weekday",
  "is_high_season": false,
  "use_bmkg": false,
  "bmkg_adm4": null
}
```

Ekspektasi hasil:

```text
Sistem mencari rekomendasi dari seluruh Bali, bukan hanya satu kabupaten/kota.
```

---

### Test 7 — CBF Saja Tanpa Cuaca

Tujuan test:

```text
Melihat hasil rekomendasi ketika konteks cuaca tidak diberikan.
```

Sample JSON:

```json
{
  "kategori_preferensi": ["Alam"],
  "kabupaten_kota": "Kabupaten Gianyar",
  "kecamatan": null,
  "keywords": ["waterfall"],
  "min_rating": 4.2,
  "top_n": 10,
  "weather": null,
  "visit_day": null,
  "is_high_season": false,
  "use_bmkg": false,
  "bmkg_adm4": null
}
```

Ekspektasi hasil:

```text
Ranking lebih banyak dipengaruhi oleh CBF, rating, dan popularitas.
weather_group = tidak_diketahui
```

---

### Test 8 — BMKG Aktif

Tujuan test:

```text
Menguji pengambilan cuaca dari BMKG.
```

Sample JSON:

```json
{
  "kategori_preferensi": ["Alam"],
  "kabupaten_kota": "Kabupaten Gianyar",
  "kecamatan": null,
  "keywords": [],
  "min_rating": 4.2,
  "top_n": 10,
  "weather": null,
  "visit_day": "weekend",
  "is_high_season": false,
  "use_bmkg": true,
  "bmkg_adm4": "ISI_DENGAN_KODE_ADM4_BALI"
}
```

Catatan penting:

```text
bmkg_adm4 harus diisi dengan kode wilayah administrasi BMKG.
Kode adm4 bukan latitude/longitude.
Untuk skripsi Bali, perlu dibuat mapping kode adm4 Bali sesuai lokasi user atau destinasi.
```

Jika `use_bmkg = true` tapi `bmkg_adm4 = null`, API akan error:

```json
{
  "detail": "bmkg_adm4 wajib diisi jika use_bmkg=True"
}
```

Contoh curl:

```bash
curl -X POST "http://localhost:8000/recommend" \
  -H "Content-Type: application/json" \
  -d '{
    "kategori_preferensi": ["Alam"],
    "kabupaten_kota": "Kabupaten Gianyar",
    "kecamatan": null,
    "keywords": [],
    "min_rating": 4.2,
    "top_n": 10,
    "weather": null,
    "visit_day": "weekend",
    "is_high_season": false,
    "use_bmkg": true,
    "bmkg_adm4": "ISI_DENGAN_KODE_ADM4_BALI"
  }'
```

---

## 13. Cara Membaca Response `/recommend`

Contoh struktur response:

```json
{
  "query": {
    "kategori_preferensi": ["Alam"],
    "kabupaten_kota": "Kabupaten Gianyar",
    "weather": "cerah",
    "user_query": "alam kabupaten gianyar",
    "weather_group": "cerah",
    "total_after_filter": 10
  },
  "weather_source": "manual",
  "weather_used": "cerah",
  "total_candidates": 10,
  "recommendations": [
    {
      "id_tempat": "BL0302020",
      "nama_tempat_wisata": "Tukad cepung waterfall",
      "kategori": "Alam",
      "tipe_wisata": "outdoor",
      "rating": 4.6,
      "jumlah_rating": 4942,
      "cbf_score": 0.352519,
      "rating_score": 0.92,
      "popularity_score": 0.737829,
      "context_multiplier": 1.08,
      "final_score": 0.54491,
      "alasan": "cocok dengan fitur/preferensi user; cuaca cerah mendukung destinasi outdoor"
    }
  ]
}
```

Penjelasan field penting:

| Field | Penjelasan |
|---|---|
| query | Input user yang diterima API, plus metadata tambahan |
| weather_source | Sumber cuaca, manual atau BMKG |
| weather_used | Cuaca yang dipakai oleh CARS |
| total_candidates | Jumlah rekomendasi yang dikembalikan |
| recommendations | Daftar destinasi hasil rekomendasi |
| cbf_score | Skor kemiripan dari CBF |
| context_multiplier | Pengali dari CARS |
| final_score | Skor akhir untuk sorting |
| alasan | Penjelasan rekomendasi |

---

## 14. Tanda Program Sudah Berhasil

Program dianggap berhasil secara teknis jika:

```text
1. docker compose up --build berjalan tanpa error.
2. http://localhost:8000/docs bisa dibuka.
3. GET /health menghasilkan status ok.
4. POST /recommend menghasilkan array recommendations.
5. Setiap item rekomendasi memiliki cbf_score, context_multiplier, final_score, dan alasan.
6. Hasil berubah ketika weather diganti dari cerah ke hujan.
```

Contoh perubahan yang benar:

| Skenario | Hasil yang Diharapkan |
|---|---|
| Alam + cerah | outdoor/alam naik |
| Alam + hujan | indoor/mixed bisa naik sebagai alternatif |
| Weekend/high season | destinasi sangat populer bisa turun sedikit |
| Tanpa cuaca | ranking lebih banyak dipengaruhi CBF, rating, popularitas |

---


---

## 15. Hasil Running Notebook `01_eda_cbf_cars.ipynb`

Bagian ini adalah catatan hasil running notebook yang sudah berhasil dijalankan. Bagian ini bisa dipakai sebagai bukti awal bahwa proses eksplorasi data, preprocessing, CBF, dan CARS berjalan dengan benar.

### 15.1 Status Running Notebook

| Tahap | Status | Hasil/Bukti |
|---|---|---|
| Load dataset | Berhasil | Dataset `bali_tourist_destination.csv` berhasil dibaca |
| Ukuran dataset awal | Berhasil | Shape awal: `(1452, 11)` |
| Cek kategori | Berhasil | Kategori muncul: `Umum`, `Alam`, `Budaya`, `Rekreasi` |
| Cek kabupaten/kota | Berhasil | Ada 9 kabupaten/kota, termasuk `Kabupaten Gianyar`, `Kabupaten Badung`, `Kabupaten Tabanan`, dan lainnya |
| Preprocessing | Berhasil | Data dibersihkan dan duplikat nama destinasi dikurangi |
| Ukuran dataset setelah preprocessing | Berhasil | Data bersih menjadi `1447` destinasi |
| Feature engineering | Berhasil | Kolom seperti `tipe_wisata`, `rating_score`, `popularity_score`, dan `feature_text` berhasil dibuat |
| TF-IDF Vectorizer | Berhasil | Matrix TF-IDF terbentuk dengan ukuran `(1447, 5630)` |
| CBF | Berhasil | `cbf_score` berhasil dihitung menggunakan cosine similarity |
| CARS | Berhasil | `context_multiplier` berhasil dibuat berdasarkan cuaca, weekend, dan high season |
| Output rekomendasi | Berhasil | Top-N rekomendasi berhasil muncul |
| Simpan dataset bersih | Berhasil | File `../data/cleaned_bali_tourist_destination.csv` berhasil dibuat |

---

### 15.2 Distribusi Kategori Dataset

Hasil pengecekan kategori pada dataset:

| Kategori | Jumlah Data |
|---|---:|
| Umum | 577 |
| Alam | 529 |
| Budaya | 182 |
| Rekreasi | 164 |

---

### 15.3 Distribusi Kabupaten/Kota Dataset

Hasil pengecekan kabupaten/kota pada dataset:

| Kabupaten/Kota | Jumlah Data |
|---|---:|
| Kabupaten Badung | 261 |
| Kabupaten Gianyar | 215 |
| Kabupaten Tabanan | 170 |
| Kabupaten Buleleng | 169 |
| Kabupaten Karangasem | 158 |
| Kabupaten Klungkung | 136 |
| Kota Denpasar | 130 |
| Kabupaten Bangli | 110 |
| Kabupaten Jembrana | 103 |

---

### 15.4 Contoh Parameter yang Dijalankan di Notebook

Contoh fungsi yang berhasil dijalankan:

```python
recommend_destinations(
    kategori_preferensi=['Alam', 'Budaya'],
    kabupaten_kota='Kabupaten Gianyar',
    keywords=['pantai', 'sunset'],
    min_rating=4.2,
    top_n=10,
    weather='hujan',
    visit_day='weekend',
    is_high_season=False
)
```

Contoh ini menguji gabungan:

```text
- Preferensi kategori: Alam dan Budaya
- Lokasi: Kabupaten Gianyar
- Keyword tambahan: pantai dan sunset
- Rating minimal: 4.2
- Cuaca: hujan
- Hari kunjungan: weekend
- High season: false
```

---

### 15.5 Contoh Hasil Rekomendasi dari Notebook

Pada skenario `Alam + Budaya + Gianyar + pantai/sunset + hujan + weekend`, hasil rekomendasi yang muncul di posisi atas adalah destinasi indoor/budaya, seperti:

| No | Nama Destinasi | Kategori | Tipe Wisata | Alasan Utama |
|---:|---|---|---|---|
| 1 | Museum Puri Lukisan | Budaya | Indoor | Cuaca hujan membuat destinasi indoor lebih diprioritaskan |
| 2 | The Blanco Renaissance Museum | Budaya | Indoor | Cocok sebagai alternatif indoor saat hujan |
| 3 | Museum Seni Agung Rai | Budaya | Indoor | Rating baik dan sesuai konteks hujan |
| 4 | Ada Garuda Museum | Budaya | Indoor | Indoor sehingga mendapat bonus CARS saat hujan |
| 5 | Istana Kepresidenan Tampaksiring Bali | Budaya | Mixed | Masih relevan, tetapi sedikit lebih rendah dari indoor |

Hasil ini benar karena CARS bekerja sebagai penyesuaian konteks. Saat cuaca `hujan`, sistem menaikkan destinasi `indoor` dan menurunkan destinasi `outdoor`.

---

### 15.6 Kesimpulan Hasil Running Notebook

Kesimpulan hasil running notebook:

```text
Notebook berhasil dijalankan.
Dataset berhasil dibaca.
Preprocessing berhasil dilakukan.
TF-IDF untuk CBF berhasil dibuat.
CARS berhasil menyesuaikan rekomendasi berdasarkan cuaca.
Output Top-N rekomendasi berhasil muncul.
Dataset bersih berhasil disimpan.
```

Status keseluruhan:

```text
Notebook: BERHASIL
CBF: BERHASIL
CARS manual: BERHASIL
Dataset bersih: BERHASIL
Siap lanjut ke FastAPI: YA
BMKG: BELUM dibuktikan dari notebook ini
```

Catatan penting:

```text
Pada notebook ini, cuaca masih diinput manual melalui parameter weather.
Artinya, CARS sudah terbukti berjalan, tetapi integrasi BMKG belum termasuk dalam pembuktian notebook ini.
```

---

### 15.7 Screenshot yang Disarankan untuk Dokumentasi Skripsi

Untuk bukti pada laporan skripsi, ambil screenshot bagian-bagian berikut:

| No | Bagian Notebook | Fungsi di Skripsi |
|---:|---|---|
| 1 | Output `Shape: (1452, 11)` | Bukti dataset berhasil dibaca |
| 2 | Output `value_counts()` kategori | Bukti eksplorasi dataset |
| 3 | Output `value_counts()` kabupaten/kota | Bukti distribusi lokasi destinasi |
| 4 | Output matrix TF-IDF `(1447, 5630)` | Bukti feature extraction CBF berhasil |
| 5 | Output tabel rekomendasi | Bukti CBF + CARS berhasil menghasilkan rekomendasi |
| 6 | Output save CSV | Bukti preprocessing menghasilkan dataset bersih |

---

### 15.8 Cara Menjalankan Notebook di VS Code

Jika ingin menjalankan notebook dari awal:

```bash
cd tourhub-bali-ml
python3 -m venv .venv
source .venv/bin/activate
pip install --upgrade pip
pip install -r requirements.txt
pip install ipykernel
python -m ipykernel install --user --name tourhub-bali --display-name "TourHub Bali ML"
```

Setelah itu buka file:

```text
notebooks/01_eda_cbf_cars.ipynb
```

Lalu pilih kernel:

```text
TourHub Bali ML
```

Jalankan semua cell dari atas ke bawah.

---

### 15.9 Jika Muncul Error `ipykernel`

Jika VS Code menampilkan pesan seperti:

```text
Running cells with 'tourhub' requires the ipykernel package.
```

Jalankan perintah berikut:

```bash
source .venv/bin/activate
pip install ipykernel
python -m ipykernel install --user --name tourhub-bali --display-name "TourHub Bali ML"
```

Lalu di VS Code pilih ulang kernel:

```text
TourHub Bali ML
```

---

## 16. Troubleshooting

### 16.1 Port 8000 Sudah Dipakai

Error kemungkinan:

```text
address already in use
```

Solusi 1: matikan container lama.

```bash
docker compose down
```

Lalu jalankan lagi:

```bash
docker compose up --build
```

Solusi 2: ubah port di `docker-compose.yml`:

```yaml
ports:
  - "8001:8000"
```

Lalu buka:

```text
http://localhost:8001/docs
```

---

### 16.2 Dataset Tidak Ditemukan

Error kemungkinan:

```text
Dataset tidak ditemukan
```

Pastikan file ini ada:

```text
data/bali_tourist_destination.csv
```

Pastikan juga environment di Docker Compose benar:

```yaml
environment:
  DATA_PATH: /app/data/bali_tourist_destination.csv
```

---

### 16.3 Error Saat `use_bmkg = true`

Jika response:

```json
{
  "detail": "bmkg_adm4 wajib diisi jika use_bmkg=True"
}
```

Solusi:

```text
Isi bmkg_adm4 dengan kode wilayah BMKG.
Atau ubah use_bmkg menjadi false dan pakai weather manual.
```

Untuk pengujian awal skripsi, paling aman:

```json
{
  "use_bmkg": false,
  "weather": "hujan"
}
```

---

### 16.4 Hasil Rekomendasi Kosong

Penyebab umum:

```text
Filter terlalu ketat.
```

Contoh filter terlalu ketat:

```json
{
  "kabupaten_kota": "Kabupaten Gianyar",
  "kecamatan": "Nama Kecamatan Salah",
  "min_rating": 5.0
}
```

Solusi:

```text
Turunkan min_rating.
Kosongkan kecamatan.
Kosongkan kabupaten_kota.
```

Contoh lebih aman:

```json
{
  "kategori_preferensi": ["Alam"],
  "kabupaten_kota": "Kabupaten Gianyar",
  "kecamatan": null,
  "min_rating": 4.0,
  "top_n": 10,
  "weather": "cerah",
  "visit_day": "weekday",
  "is_high_season": false,
  "use_bmkg": false,
  "bmkg_adm4": null
}
```

---

## 17. Catatan Penting untuk Skripsi

### 17.1 Kategori di Project Ini Bersifat Soft Preference

Di project ini, kategori user tidak selalu menjadi filter keras.

Artinya:

```text
Jika user memilih Alam, sistem tetap bisa menampilkan Budaya/Indoor saat hujan.
```

Alasannya:

```text
Agar CARS bisa memberikan alternatif yang lebih sesuai dengan kondisi nyata.
```

Penjelasan untuk skripsi:

```text
Kategori preferensi digunakan sebagai bagian dari representasi fitur pada Content-Based Filtering. Namun, sistem tetap memungkinkan eksplorasi destinasi lintas kategori agar CARS dapat menyesuaikan rekomendasi dengan konteks, misalnya mengutamakan destinasi indoor saat cuaca hujan.
```

Jika ingin kategori wajib dipatuhi, perlu dibuat mode tambahan:

```text
strict_category = true
```

Namun pada versi awal ini belum digunakan.

---

### 17.2 BMKG Bukan Model Machine Learning

BMKG di project ini hanya sebagai sumber data cuaca.

```text
BMKG → memberi data cuaca
CARS → menggunakan cuaca tersebut untuk menyesuaikan rekomendasi
```

Jadi CARS tidak memprediksi cuaca sendiri.

---

### 17.3 Jumlah Rating Dipakai sebagai Proxy Popularitas

`jumlah_rating` digunakan untuk memperkirakan popularitas destinasi.

Semakin banyak jumlah rating, destinasi diasumsikan semakin populer.

Pada weekend/high season, destinasi yang sangat populer bisa diberi penalti kecil karena diasumsikan lebih ramai.

---

## 18. Narasi Singkat untuk Bab Implementasi

Kamu bisa menggunakan penjelasan berikut sebagai bahan awal untuk bab implementasi:

```text
Sistem rekomendasi TourHub Bali dikembangkan menggunakan FastAPI sebagai layanan machine learning. Dataset destinasi wisata Bali diproses menggunakan Pandas, kemudian setiap destinasi direpresentasikan ke dalam bentuk teks gabungan yang terdiri dari nama destinasi, kategori, kecamatan, kabupaten/kota, dan tipe wisata. Representasi teks tersebut diubah menjadi vektor menggunakan TF-IDF Vectorizer. Preferensi pengguna juga dibentuk menjadi query teks, kemudian dihitung tingkat kemiripannya terhadap setiap destinasi menggunakan Cosine Similarity. Nilai kemiripan tersebut menjadi skor Content-Based Filtering.

Setelah skor CBF diperoleh, sistem menerapkan Context-Aware Recommender System dengan pendekatan contextual post-filtering. Konteks yang digunakan meliputi kondisi cuaca, hari kunjungan, high season, dan popularitas destinasi. Setiap konteks menghasilkan context_multiplier yang digunakan untuk menaikkan atau menurunkan skor rekomendasi. Skor akhir dihitung dari kombinasi cbf_score, rating_score, popularity_score, dan context_multiplier. Hasil akhir kemudian diurutkan berdasarkan final_score untuk menghasilkan Top-N rekomendasi destinasi wisata.
```

---

## 19. Checklist Pengujian Manual

Gunakan checklist ini saat demo ke dosen atau saat mencatat hasil pengujian.

| No | Pengujian | Status |
|---:|---|---|
| 1 | Docker berhasil dijalankan | Belum/Sudah |
| 2 | Swagger `/docs` bisa dibuka | Belum/Sudah |
| 3 | `/health` menghasilkan status ok | Belum/Sudah |
| 4 | `/metadata` menampilkan kategori dan lokasi | Belum/Sudah |
| 5 | `/destinations` menampilkan daftar destinasi | Belum/Sudah |
| 6 | `/recommend` dengan cuaca cerah menghasilkan rekomendasi outdoor | Belum/Sudah |
| 7 | `/recommend` dengan cuaca hujan menaikkan indoor/mixed | Belum/Sudah |
| 8 | Weekend/high season memengaruhi context_multiplier | Belum/Sudah |
| 9 | Response memiliki `alasan` rekomendasi | Belum/Sudah |
| 10 | BMKG manual adm4 dites | Belum/Sudah |

---

## 20. Rencana Pengembangan Selanjutnya

Beberapa pengembangan yang disarankan:

```text
1. Tambahkan kolom deskripsi destinasi agar CBF lebih akurat.
2. Tambahkan kolom aktivitas, misalnya hiking, beach, museum, temple, family trip.
3. Buat mapping kode adm4 BMKG untuk wilayah Bali.
4. Tambahkan mode strict_category jika user hanya ingin kategori tertentu.
5. Tambahkan evaluasi Precision@10 dari hasil kuesioner user.
6. Integrasikan endpoint /recommend ke Laravel atau Flutter.
7. Simpan history preferensi user agar rekomendasi bisa lebih personal.
```

---

## 21. Ringkasan untuk Diri Sendiri

Inti project ini:

```text
CBF = mencari destinasi yang mirip dengan preferensi user.
CARS = menyesuaikan hasil rekomendasi berdasarkan kondisi nyata.
FastAPI = membungkus logic rekomendasi agar bisa dipanggil frontend/backend.
Docker Compose = menjalankan API dengan mudah.
```

Kalau ditanya dosen:

```text
Sistem ini tidak memprediksi cuaca. Sistem mengambil konteks cuaca, baik secara manual maupun dari BMKG, lalu menggunakan cuaca tersebut untuk menyesuaikan rekomendasi destinasi wisata.
```

Kalau ditanya kenapa museum muncul saat user memilih alam:

```text
Karena kategori diperlakukan sebagai preferensi lunak. Saat cuaca hujan, CARS dapat memprioritaskan destinasi indoor sebagai alternatif yang lebih sesuai dengan kondisi perjalanan.
```

Kalau ditanya output akhirnya apa:

```text
Output akhirnya adalah Top-N rekomendasi destinasi wisata Bali yang sudah diurutkan berdasarkan final_score dan dilengkapi alasan rekomendasi.
```
