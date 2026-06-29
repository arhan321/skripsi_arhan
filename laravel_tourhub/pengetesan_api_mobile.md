# Sample Pengetesan API - TourHub Bali

Dokumentasi ini dipakai untuk mengetes **API TourHub Bali** sebelum API dihubungkan ke aplikasi Flutter Mobile.

> Catatan:
> - File ini fokus untuk **pengetesan API Laravel + FastAPI ML**.
> - Dokumentasi khusus Flutter Mobile dipisahkan di file `README_MOBILE.md`.
> - Flutter hanya menjadi client yang mengonsumsi API dari backend Laravel.
> - Admin tetap dikelola dari Laravel/Filament, bukan dari Flutter.

---

## 1. Gambaran Sistem

Alur sistem TourHub Bali:

```text
Flutter Mobile
    ↓
Laravel REST API
    ↓
FastAPI ML Service
    ↓
Dataset Wisata Bali + Weather/BMKG Context
```

Peran masing-masing bagian:

| Bagian | Fungsi |
|---|---|
| Flutter Mobile | Menampilkan UI user, login/register, form rekomendasi, history, dan tombol Google Maps |
| Laravel API | Auth user, validasi request, proxy ke ML, menyimpan log/history rekomendasi |
| FastAPI ML | Mengolah rekomendasi wisata menggunakan CBF + CARS |
| Dataset CSV | Sumber data destinasi wisata Bali |
| BMKG/Weather | Konteks cuaca untuk rekomendasi |

---

## 2. Base URL API

Domain utama yang dipakai pada project TourHub Bali saat ini:

| Service | Domain |
|---|---|
| FastAPI ML | `https://machine_learning.djncloud.my.id` |
| Laravel Web/API | `https://prediksi.djncloud.my.id` |
| Laravel REST API untuk Flutter/Postman | `https://prediksi.djncloud.my.id/api` |

Ringkasan domain aktif:

```text
FAST API : https://machine_learning.djncloud.my.id
LARAVEL  : https://prediksi.djncloud.my.id
```

Untuk aplikasi Flutter, base URL yang dipakai tetap domain Laravel API dengan suffix `/api`:

```text
https://prediksi.djncloud.my.id/api
```

Untuk komunikasi Laravel ke service ML, gunakan domain FastAPI:

```env
TOURHUB_ML_BASE_URL=https://machine_learning.djncloud.my.id
```

Sesuaikan base URL dengan environment yang sedang dipakai.

### Production / Staging

Laravel domain:

```text
https://prediksi.djncloud.my.id
```

Laravel REST API base URL:

```text
https://prediksi.djncloud.my.id/api
```

### Local Laravel

Jika testing dari browser/Postman di laptop:

```text
http://127.0.0.1:8000/api
```

### Local dari Android Emulator

Jika Flutter berjalan di Android emulator dan Laravel berjalan di laptop:

```text
http://10.0.2.2:8000/api
```

### Local dari HP Fisik

Jika Flutter berjalan di HP asli satu jaringan dengan laptop/server:

```text
http://192.168.1.10:8000/api
```

---

## 3. Cek Route Laravel

Sebelum mengetes API, cek route yang tersedia:

```bash
php artisan route:list
```

Filter route API:

```bash
php artisan route:list --path=api
```

Jika nama endpoint di dokumentasi ini berbeda dengan project, ikuti hasil dari `php artisan route:list`.

---

## 4. Header Standar API

Untuk request JSON:

```http
Accept: application/json
Content-Type: application/json
```

Untuk endpoint yang membutuhkan login:

```http
Authorization: Bearer TOKEN_USER
Accept: application/json
Content-Type: application/json
```

Contoh variable token di terminal:

```bash
TOKEN="paste_token_login_di_sini"
BASE_URL="https://prediksi.djncloud.my.id/api"
```

---

## 5. Endpoint yang Disiapkan untuk Flutter

Endpoint yang ideal untuk aplikasi Flutter:

| Fitur | Method | Endpoint |
|---|---:|---|
| Register | POST | `/api/register` |
| Login | POST | `/api/login` |
| Profile user | GET | `/api/user` atau `/api/me` |
| Logout | POST | `/api/logout` |
| ML Health | GET | `/api/tourhub/ml-health` |
| Rekomendasi | POST | `/api/tourhub/rekomendasi` |
| History rekomendasi | GET | `/api/tourhub/recommendation-histories` |
| Detail history | GET | `/api/tourhub/recommendation-histories/{id}` |

Jika project memakai endpoint berbeda, sesuaikan di Flutter pada file API service.

---

## 6. Pengetesan Health Laravel API

### Tujuan

Memastikan Laravel API bisa diakses.

### Request

```bash
curl -X GET "$BASE_URL/health" \
  -H "Accept: application/json"
```

Jika belum ada endpoint `/health`, tes endpoint API lain yang tersedia di `php artisan route:list`.

### Hasil yang Diharapkan

```json
{
  "status": "ok"
}
```

---

## 7. Pengetesan Health ML FastAPI dari Laravel

### Tujuan

Memastikan Laravel bisa menghubungi service ML FastAPI.

### Endpoint

```text
GET /api/tourhub/ml-health
```

### Request

```bash
curl -X GET "$BASE_URL/tourhub/ml-health" \
  -H "Accept: application/json"
```

### Test Langsung ke FastAPI

Jika ingin memastikan domain FastAPI bisa diakses langsung, gunakan domain berikut:

```text
https://machine_learning.djncloud.my.id
```

Contoh test langsung ke FastAPI:

```bash
curl -X GET "https://machine_learning.djncloud.my.id" \
  -H "Accept: application/json"
```

Jika FastAPI memiliki endpoint health khusus seperti `/health`, maka test juga:

```bash
curl -X GET "https://machine_learning.djncloud.my.id/health" \
  -H "Accept: application/json"
```

### Hasil yang Diharapkan

```json
{
  "status": "ok",
  "ml_service": "connected"
}
```

Jika gagal, cek:

- FastAPI ML sudah running.
- URL ML di `.env` Laravel benar.
- Config `config/tourhub.php` benar.
- Laravel bisa menjangkau host/port FastAPI.
- Jika memakai Docker, nama service Docker sudah benar.
- Log Laravel tidak menunjukkan `connection refused`.

Contoh `.env` Laravel untuk production/staging:

```env
TOURHUB_ML_BASE_URL=https://machine_learning.djncloud.my.id
```

Jika Laravel dan FastAPI berada dalam Docker network yang sama, bisa juga memakai nama service internal Docker:

```env
TOURHUB_ML_BASE_URL=http://tourhub-ml:8001
```

Namun untuk domain yang kamu berikan saat ini, gunakan:

```env
TOURHUB_ML_BASE_URL=https://machine_learning.djncloud.my.id
```

Setelah mengubah `.env`, jalankan:

```bash
php artisan optimize:clear
```

---

## 8. Pengetesan Register User

### Endpoint

```text
POST /api/register
```

### Request

```bash
curl -X POST "$BASE_URL/register" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "User TourHub",
    "email": "user_tourhub@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Hasil yang Diharapkan

```json
{
  "message": "Register berhasil",
  "user": {
    "id": 1,
    "name": "User TourHub",
    "email": "user_tourhub@example.com"
  },
  "token": "TOKEN_USER"
}
```

Catatan:

- Jika register tidak langsung mengembalikan token, lanjutkan ke endpoint login.
- Jika email sudah pernah dipakai, API harus mengembalikan status `422`.
- Error validasi harus dalam format JSON agar Flutter mudah membacanya.

---

## 9. Pengetesan Login User

### Endpoint

```text
POST /api/login
```

### Request

```bash
curl -X POST "$BASE_URL/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user_tourhub@example.com",
    "password": "password123"
  }'
```

### Hasil yang Diharapkan

```json
{
  "message": "Login berhasil",
  "user": {
    "id": 1,
    "name": "User TourHub",
    "email": "user_tourhub@example.com"
  },
  "token": "TOKEN_USER"
}
```

Simpan token dari response login.

Contoh:

```bash
TOKEN="TOKEN_USER_DARI_RESPONSE_LOGIN"
```

---

## 10. Pengetesan Profile User

### Endpoint

```text
GET /api/user
```

atau:

```text
GET /api/me
```

### Request

```bash
curl -X GET "$BASE_URL/user" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### Hasil yang Diharapkan

```json
{
  "id": 1,
  "name": "User TourHub",
  "email": "user_tourhub@example.com"
}
```

Jika response `Unauthenticated`, cek:

- Token benar.
- Header `Authorization` sudah memakai format `Bearer`.
- User/token belum logout.
- Guard API Laravel benar.
- Endpoint profile memang berada di middleware auth.

---

## 11. Pengetesan Rekomendasi Wisata

### Endpoint

```text
POST /api/tourhub/rekomendasi
```

Endpoint ini dipakai Flutter untuk meminta rekomendasi wisata.

---

### 11.1 Payload Rekomendasi Tanpa BMKG

```json
{
  "kategori_preferensi": ["Alam", "Budaya"],
  "kabupaten_kota": "Badung",
  "kecamatan": "Kuta",
  "keywords": ["pantai", "sunset"],
  "min_rating": 4,
  "top_n": 10,
  "weather": "cerah",
  "visit_day": "weekend",
  "is_high_season": false,
  "use_bmkg": false,
  "bmkg_adm4": null
}
```

### Request

```bash
curl -X POST "$BASE_URL/tourhub/rekomendasi" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "kategori_preferensi": ["Alam", "Budaya"],
    "kabupaten_kota": "Badung",
    "kecamatan": "Kuta",
    "keywords": ["pantai", "sunset"],
    "min_rating": 4,
    "top_n": 10,
    "weather": "cerah",
    "visit_day": "weekend",
    "is_high_season": false,
    "use_bmkg": false,
    "bmkg_adm4": null
  }'
```

---

### 11.2 Payload Rekomendasi Dengan BMKG

```json
{
  "kategori_preferensi": ["Alam"],
  "kabupaten_kota": "Gianyar",
  "kecamatan": "Ubud",
  "keywords": ["alam", "sawah", "budaya"],
  "min_rating": 4,
  "top_n": 10,
  "weather": "unknown",
  "visit_day": "weekday",
  "is_high_season": false,
  "use_bmkg": true,
  "bmkg_adm4": "51.04.xx.xxxx"
}
```

### Request

```bash
curl -X POST "$BASE_URL/tourhub/rekomendasi" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "kategori_preferensi": ["Alam"],
    "kabupaten_kota": "Gianyar",
    "kecamatan": "Ubud",
    "keywords": ["alam", "sawah", "budaya"],
    "min_rating": 4,
    "top_n": 10,
    "weather": "unknown",
    "visit_day": "weekday",
    "is_high_season": false,
    "use_bmkg": true,
    "bmkg_adm4": "51.04.xx.xxxx"
  }'
```

Catatan:

- `bmkg_adm4` wajib jika `use_bmkg = true`.
- User Flutter tidak perlu mengetik ADM4 manual.
- ADM4 sebaiknya ditentukan otomatis dari mapping `kabupaten_kota` + `kecamatan`.

---

## 12. Penjelasan Field Payload Rekomendasi

| Field | Tipe | Wajib | Keterangan |
|---|---:|---:|---|
| `kategori_preferensi` | array | Ya | Pilihan: `Alam`, `Budaya`, `Rekreasi`, `Umum` |
| `kabupaten_kota` | string | Ya | Kabupaten/kota di Bali |
| `kecamatan` | string | Ya | Kecamatan dari kabupaten/kota yang dipilih |
| `keywords` | array | Tidak | Kata kunci minat user |
| `min_rating` | number | Tidak | Nilai 0 sampai 5 |
| `top_n` | number | Tidak | Jumlah hasil, 1 sampai 50 |
| `weather` | string | Ya | `cerah`, `hujan`, `mendung`, `berawan`, `unknown` |
| `visit_day` | string | Ya | `weekday` atau `weekend` |
| `is_high_season` | boolean | Ya | Apakah sedang musim liburan |
| `use_bmkg` | boolean | Ya | Apakah cuaca diambil dari BMKG |
| `bmkg_adm4` | string/null | Kondisional | Wajib jika `use_bmkg = true` |

---

## 13. Hasil Response Rekomendasi yang Diharapkan

Struktur response minimal yang aman untuk Flutter:

```json
{
  "success": true,
  "weather_source": "manual",
  "weather_used": "cerah",
  "total_candidates": 25,
  "response_time_ms": 350,
  "data": [
    {
      "nama": "Pantai Kuta",
      "kategori": "Alam",
      "kabupaten_kota": "Badung",
      "kecamatan": "Kuta",
      "rating": 4.6,
      "deskripsi": "Destinasi wisata pantai di Bali.",
      "link_gambar": "https://example.com/pantai-kuta.jpg",
      "link_maps": "https://www.google.com/maps/search/?api=1&query=Pantai%20Kuta%20Bali",
      "final_score": 0.92
    }
  ]
}
```

Field yang penting untuk Flutter:

| Field | Fungsi di Flutter |
|---|---|
| `nama` | Judul destinasi |
| `kategori` | Badge kategori |
| `kabupaten_kota` | Lokasi utama |
| `kecamatan` | Lokasi detail |
| `rating` | Rating destinasi |
| `deskripsi` | Deskripsi destinasi |
| `link_gambar` | Gambar destinasi |
| `link_maps` | Tombol buka Google Maps |
| `final_score` | Highlight ranking rekomendasi |

---

## 14. Pengetesan Filter Cuaca Hujan

### Tujuan

Memastikan saat cuaca `hujan`, destinasi outdoor dapat difilter oleh ML sesuai konfigurasi `strict_weather_filter`.

### Request

```bash
curl -X POST "$BASE_URL/tourhub/rekomendasi" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "kategori_preferensi": ["Alam"],
    "kabupaten_kota": "Badung",
    "kecamatan": "Kuta",
    "keywords": ["pantai"],
    "min_rating": 0,
    "top_n": 10,
    "weather": "hujan",
    "visit_day": "weekend",
    "is_high_season": false,
    "use_bmkg": false,
    "bmkg_adm4": null
  }'
```

### Hasil yang Diharapkan

- API tetap mengembalikan response JSON.
- Destinasi outdoor berkurang atau tidak muncul sesuai rule ML.
- Jika kandidat kosong, response tetap rapi.
- Flutter tidak crash ketika `data` kosong.

Contoh response kosong yang aman:

```json
{
  "success": true,
  "message": "Tidak ada destinasi yang sesuai dengan kondisi saat ini.",
  "weather_used": "hujan",
  "total_candidates": 0,
  "data": []
}
```

---

## 15. Pengetesan Field Link Gambar

### Tujuan

Memastikan setiap destinasi memiliki link gambar yang bisa dipakai Flutter.

Field yang disarankan:

```json
{
  "link_gambar": "https://example.com/destinasi.jpg"
}
```

Validasi:

- Link tidak kosong.
- Link menggunakan `https://`.
- Link bisa dibuka dari browser.
- Flutter bisa membaca menggunakan `Image.network`.
- Jika gambar gagal, Flutter menampilkan placeholder.

Contoh test manual:

```bash
curl -I "https://example.com/destinasi.jpg"
```

Hasil yang bagus:

```text
HTTP/2 200
content-type: image/jpeg
```

---

## 16. Pengetesan Field Google Maps

### Tujuan

Memastikan API mengirim link Google Maps yang bisa dibuka dari Flutter.

Field yang disarankan:

```json
{
  "link_maps": "https://www.google.com/maps/search/?api=1&query=Pantai%20Kuta%20Bali"
}
```

Jika punya latitude/longitude:

```json
{
  "link_maps": "https://www.google.com/maps/search/?api=1&query=-8.718492,115.168632"
}
```

Validasi:

- Field `link_maps` tidak `null`.
- URL memakai `https://`.
- Link bisa dibuka di browser.
- Flutter bisa membuka link memakai `url_launcher`.

Catatan penting:

- Gunakan satu nama field secara konsisten.
- Disarankan memakai `link_maps`, agar Flutter tidak perlu mengecek banyak nama field.
- Jika dataset punya nama field berbeda, normalisasi di Laravel sebelum dikirim ke Flutter.

---

## 17. Pengetesan History Rekomendasi

### Endpoint

```text
GET /api/tourhub/recommendation-histories
```

Alternatif jika project memakai nama lain:

```text
GET /api/tourhub/rekomendasi/history
```

### Request

```bash
curl -X GET "$BASE_URL/tourhub/recommendation-histories" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### Hasil yang Diharapkan

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "weather_source": "manual",
      "weather_used": "cerah",
      "total_candidates": 25,
      "response_time_ms": 350,
      "status": "success",
      "created_at": "2026-05-19T10:00:00.000000Z"
    }
  ]
}
```

Validasi penting:

- User hanya melihat history miliknya sendiri.
- History user lain tidak muncul.
- Data terbaru muncul paling atas.
- Response aman jika history kosong.
- Pagination berjalan jika data banyak.

Contoh response history kosong:

```json
{
  "success": true,
  "data": []
}
```

---

## 18. Pengetesan Detail History

### Endpoint

```text
GET /api/tourhub/recommendation-histories/{id}
```

Alternatif jika project memakai nama lain:

```text
GET /api/tourhub/rekomendasi/history/{id}
```

### Request

```bash
curl -X GET "$BASE_URL/tourhub/recommendation-histories/1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### Hasil yang Diharapkan

```json
{
  "success": true,
  "data": {
    "id": 1,
    "weather_source": "manual",
    "weather_used": "cerah",
    "total_candidates": 25,
    "response_time_ms": 350,
    "status": "success",
    "error_message": null,
    "request_payload": {
      "kategori_preferensi": ["Alam"],
      "kabupaten_kota": "Badung",
      "kecamatan": "Kuta"
    },
    "response_payload": {
      "data": [
        {
          "nama": "Pantai Kuta",
          "rating": 4.6,
          "link_gambar": "https://example.com/pantai-kuta.jpg",
          "link_maps": "https://www.google.com/maps/search/?api=1&query=Pantai%20Kuta"
        }
      ]
    },
    "created_at": "2026-05-19T10:00:00.000000Z"
  }
}
```

Validasi penting:

- Detail hanya bisa dibuka oleh pemilik history.
- Jika ID tidak ditemukan, response `404`.
- Jika history milik user lain, response `403` atau `404`.
- `request_payload` dan `response_payload` harus bisa dibaca Flutter.
- Jika `response_payload.data` kosong, Flutter tetap aman.

---

## 19. Pengetesan Logout

### Endpoint

```text
POST /api/logout
```

### Request

```bash
curl -X POST "$BASE_URL/logout" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### Hasil yang Diharapkan

```json
{
  "message": "Logout berhasil"
}
```

Setelah logout:

- Token lama tidak bisa dipakai lagi.
- Endpoint profile/rekomendasi/history mengembalikan `Unauthenticated`.

---

## 20. Pengetesan Error Validasi

### 20.1 Kategori Kosong

Request:

```bash
curl -X POST "$BASE_URL/tourhub/rekomendasi" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "kategori_preferensi": [],
    "kabupaten_kota": "Badung",
    "kecamatan": "Kuta",
    "keywords": [],
    "min_rating": 4,
    "top_n": 10,
    "weather": "cerah",
    "visit_day": "weekend",
    "is_high_season": false,
    "use_bmkg": false,
    "bmkg_adm4": null
  }'
```

Hasil yang diharapkan:

```json
{
  "message": "The kategori preferensi field is required.",
  "errors": {
    "kategori_preferensi": [
      "Kategori preferensi wajib diisi."
    ]
  }
}
```

---

### 20.2 Cuaca Tidak Valid

Contoh payload salah:

```json
{
  "weather": "badai"
}
```

Hasil yang diharapkan:

- Status HTTP `422`.
- Response JSON berisi error validasi.
- Pesan menjelaskan pilihan cuaca yang benar.

---

### 20.3 Top N Terlalu Besar

Contoh payload salah:

```json
{
  "top_n": 100
}
```

Hasil yang diharapkan:

- Status HTTP `422`.
- Maksimal `top_n` adalah 50.

---

### 20.4 BMKG Aktif Tapi ADM4 Kosong

Contoh payload salah:

```json
{
  "use_bmkg": true,
  "bmkg_adm4": null
}
```

Hasil yang diharapkan:

- Status HTTP `422`.
- Pesan menjelaskan bahwa `bmkg_adm4` wajib jika BMKG aktif.

---

## 21. Pengetesan dari Postman

Buat collection Postman dengan urutan:

```text
1. Health Laravel
2. Health ML
3. Register
4. Login
5. Profile
6. Rekomendasi tanpa BMKG
7. Rekomendasi dengan BMKG
8. Rekomendasi cuaca hujan
9. History list
10. History detail
11. Logout
```

Environment variable yang disarankan:

| Variable | Contoh |
|---|---|
| `base_url` | `https://prediksi.djncloud.my.id/api` |
| `token` | Token dari login |
| `history_id` | ID history dari response list |

Header global:

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{token}}
```

---

## 22. Checklist Pengetesan API

| No | Pengujian | Status |
|---|---|---|
| 1 | Laravel API bisa diakses | Belum/Sudah |
| 2 | ML health bisa diakses dari Laravel | Belum/Sudah |
| 3 | Register berhasil | Belum/Sudah |
| 4 | Login berhasil dan token diterima | Belum/Sudah |
| 5 | Profile user berhasil | Belum/Sudah |
| 6 | Rekomendasi tanpa BMKG berhasil | Belum/Sudah |
| 7 | Rekomendasi dengan BMKG berhasil | Belum/Sudah |
| 8 | Filter cuaca hujan berjalan | Belum/Sudah |
| 9 | Response memiliki `link_gambar` | Belum/Sudah |
| 10 | Response memiliki `link_maps` | Belum/Sudah |
| 11 | History list tampil | Belum/Sudah |
| 12 | Detail history tampil | Belum/Sudah |
| 13 | User tidak bisa akses history user lain | Belum/Sudah |
| 14 | Logout berhasil | Belum/Sudah |
| 15 | Token logout tidak bisa dipakai lagi | Belum/Sudah |
| 16 | Error validasi tampil rapi | Belum/Sudah |
| 17 | Response kosong tetap aman untuk Flutter | Belum/Sudah |

---

## 23. Troubleshooting API

### 23.1 Error `Unauthenticated`

Penyebab umum:

- Token tidak dikirim.
- Format header salah.
- Token sudah logout/expired.
- Endpoint berada di middleware auth.
- Guard API belum sesuai.

Pastikan header:

```http
Authorization: Bearer TOKEN_USER
```

---

### 23.2 Error `Connection refused` ke FastAPI ML

Penyebab umum:

- Service FastAPI belum running.
- Port ML salah.
- URL ML di `.env` salah.
- Docker network Laravel dan FastAPI belum satu jaringan.
- Firewall memblokir koneksi.

Cek log Laravel:

```bash
tail -f storage/logs/laravel.log
```

Jika pakai Docker:

```bash
docker exec -it nama_container_php tail -f /var/www/html/storage/logs/laravel.log
```

---

### 23.3 Response Rekomendasi Kosong

Kemungkinan penyebab:

- Filter terlalu ketat.
- `min_rating` terlalu tinggi.
- Cuaca `hujan` memfilter destinasi outdoor.
- Kategori/kecamatan tidak punya kandidat.
- Dataset belum lengkap.

Solusi:

- Turunkan `min_rating`.
- Tambahkan kategori lain.
- Ubah kecamatan/kabupaten.
- Set `top_n` lebih besar.
- Cek dataset CSV.

---

### 23.4 Gambar Tidak Muncul di Flutter

Cek field API:

```json
{
  "link_gambar": "https://example.com/image.jpg"
}
```

Pastikan:

- URL gambar aktif.
- URL menggunakan HTTPS.
- Tidak diblokir hotlink.
- Flutter memiliki placeholder saat gambar gagal load.

---

### 23.5 Google Maps Tidak Bisa Dibuka

Cek field API:

```json
{
  "link_maps": "https://www.google.com/maps/search/?api=1&query=Pantai%20Kuta%20Bali"
}
```

Pastikan:

- Field tidak `null`.
- Format URL valid.
- URL memakai `https://`.
- Flutter sudah memakai `url_launcher`.
- AndroidManifest Flutter sudah memiliki `queries`.

---

### 23.6 Error 500 dari Laravel

Cek log:

```bash
tail -f storage/logs/laravel.log
```

Bersihkan cache config:

```bash
php artisan optimize:clear
```

Jika route/config baru ditambahkan:

```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

---

## 24. Catatan untuk Integrasi Flutter

Agar Flutter mudah membaca response, usahakan backend konsisten pada nama field berikut:

```json
{
  "nama": "Pantai Kuta",
  "kategori": "Alam",
  "kabupaten_kota": "Badung",
  "kecamatan": "Kuta",
  "rating": 4.6,
  "deskripsi": "Destinasi wisata pantai di Bali.",
  "link_gambar": "https://example.com/pantai-kuta.jpg",
  "link_maps": "https://www.google.com/maps/search/?api=1&query=Pantai%20Kuta%20Bali",
  "final_score": 0.92
}
```

Rekomendasi response wrapper:

```json
{
  "success": true,
  "message": "Rekomendasi berhasil dibuat.",
  "weather_source": "manual",
  "weather_used": "cerah",
  "total_candidates": 25,
  "response_time_ms": 350,
  "data": []
}
```

Hal yang perlu dijaga:

- Jangan mengubah nama field terlalu sering.
- Jangan mengirim HTML error ke Flutter.
- Error harus JSON.
- Jika gagal, kirim `message` yang mudah dibaca user.
- Jika data kosong, kirim `data: []`, bukan `null`.

---

## 25. Kesimpulan

File ini adalah panduan pengetesan **API TourHub Bali**.

Urutan testing yang disarankan:

```text
1. Pastikan FastAPI ML hidup
2. Pastikan Laravel API hidup
3. Test health Laravel
4. Test health ML
5. Test register
6. Test login
7. Test profile
8. Test rekomendasi tanpa BMKG
9. Test rekomendasi dengan BMKG
10. Test cuaca hujan
11. Test history
12. Test detail history
13. Test logout
14. Integrasikan ke Flutter
```

Jika semua API di file ini sudah berhasil, maka Flutter Mobile bisa mengambil data dengan lebih stabil dan minim error.
