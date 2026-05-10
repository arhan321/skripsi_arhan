# README Sample Pengujian Rekomendasi TourHub Bali

Dokumen ini berisi contoh isian parameter untuk mengetes fitur rekomendasi wisata **TourHub Bali** pada halaman web Laravel.

Halaman pengujian:

```text
https://prediksi.djncloud.my.id/tourhub/rekomendasi
```

Panel user:

```text
https://prediksi.djncloud.my.id/user/dashboard
```

FastAPI ML:

```text
https://machine_learning.djncloud.my.id
```

---

## 1. Tujuan Pengujian

Pengujian ini bertujuan untuk memastikan bahwa:

1. User dapat melakukan pencarian rekomendasi wisata.
2. Laravel berhasil mengirim parameter ke FastAPI ML.
3. FastAPI menjalankan rekomendasi **CBF + CARS**.
4. Data rekomendasi berhasil disimpan sebagai riwayat per user.
5. User dapat melihat riwayat rekomendasi di dashboard.
6. Sistem dapat memakai BMKG secara otomatis tanpa user mengisi kode ADM4.

---

## 2. Alur Pengujian

Ikuti alur berikut:

```text
1. Login / Register user
2. Masuk ke /user/dashboard
3. Klik Cari Rekomendasi Baru
4. Isi form rekomendasi
5. Klik Cari Rekomendasi
6. Lihat hasil rekomendasi
7. Kembali ke dashboard
8. Pastikan riwayat rekomendasi muncul
9. Klik Detail untuk melihat request-response rekomendasi
```

---

## 3. Setting Default Aman untuk Semua Test

Untuk pengujian awal di semua daerah, gunakan setting default berikut:

```text
Kategori Preferensi : Alam, Budaya, Rekreasi, Umum
Keywords            : kosong
Min Rating          : 0
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

> Catatan: Untuk test awal, sebaiknya `Keywords` dikosongkan dulu agar hasil rekomendasi tidak menjadi 0 karena filter terlalu spesifik.

---

## 4. Sample Pengujian Per Kabupaten/Kota

## 4.1 Kabupaten Gianyar

### Sample Aman

```text
Kategori Preferensi : Alam, Budaya, Rekreasi, Umum
Kabupaten/Kota      : Kabupaten Gianyar
Kecamatan           : Ubud
Keywords            : kosong
Min Rating          : 0
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

### Sample Lebih Spesifik

```text
Kategori Preferensi : Alam, Budaya
Kabupaten/Kota      : Kabupaten Gianyar
Kecamatan           : Ubud
Keywords            : kosong
Min Rating          : 4
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

### Kecamatan Alternatif

```text
Ubud
Gianyar
Tegallalang
Blahbatuh
Tampaksiring
Sukawati
Payangan
```

### Keyword Opsional

```text
pura
museum
sawah
air terjun
```

---

## 4.2 Kabupaten Badung

### Sample Aman

```text
Kategori Preferensi : Alam, Budaya, Rekreasi, Umum
Kabupaten/Kota      : Kabupaten Badung
Kecamatan           : Kuta
Keywords            : kosong
Min Rating          : 0
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

### Sample Lebih Spesifik

```text
Kategori Preferensi : Alam, Rekreasi, Umum
Kabupaten/Kota      : Kabupaten Badung
Kecamatan           : Kuta
Keywords            : pantai
Min Rating          : 4
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

### Kecamatan Alternatif

```text
Kuta
Kuta Selatan
Kuta Utara
Mengwi
Abiansemal
Petang
```

### Keyword Opsional

```text
pantai
sunset
waterpark
taman
```

---

## 4.3 Kabupaten Tabanan

### Sample Aman

```text
Kategori Preferensi : Alam, Budaya, Rekreasi, Umum
Kabupaten/Kota      : Kabupaten Tabanan
Kecamatan           : Baturiti
Keywords            : kosong
Min Rating          : 0
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

### Sample Lebih Spesifik

```text
Kategori Preferensi : Alam, Budaya, Umum
Kabupaten/Kota      : Kabupaten Tabanan
Kecamatan           : Baturiti
Keywords            : danau, pura
Min Rating          : 4
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

### Kecamatan Alternatif

```text
Tabanan
Kediri
Penebel
Baturiti
Pupuan
Selemadeg
Selemadeg Timur
Selemadeg Barat
Kerambitan
Marga
```

### Keyword Opsional

```text
danau
pura
alam
sawah
```

---

## 4.4 Kabupaten Buleleng

### Sample Aman

```text
Kategori Preferensi : Alam, Budaya, Rekreasi, Umum
Kabupaten/Kota      : Kabupaten Buleleng
Kecamatan           : Buleleng
Keywords            : kosong
Min Rating          : 0
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

### Sample Lebih Spesifik

```text
Kategori Preferensi : Alam, Budaya, Umum
Kabupaten/Kota      : Kabupaten Buleleng
Kecamatan           : Buleleng
Keywords            : pantai, air terjun
Min Rating          : 4
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

### Kecamatan Alternatif

```text
Buleleng
Gerokgak
Seririt
Busungbiu
Banjar
Sukasada
Sawan
Kubutambahan
Tejakula
```

### Keyword Opsional

```text
pantai
air terjun
danau
alam
```

---

## 4.5 Kabupaten Karangasem

### Sample Aman

```text
Kategori Preferensi : Alam, Budaya, Rekreasi, Umum
Kabupaten/Kota      : Kabupaten Karangasem
Kecamatan           : Karangasem
Keywords            : kosong
Min Rating          : 0
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

### Sample Lebih Spesifik

```text
Kategori Preferensi : Alam, Budaya, Umum
Kabupaten/Kota      : Kabupaten Karangasem
Kecamatan           : Karangasem
Keywords            : pura, pantai
Min Rating          : 4
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

### Kecamatan Alternatif

```text
Karangasem
Rendang
Sidemen
Manggis
Abang
Bebandem
Selat
Kubu
```

### Keyword Opsional

```text
pura
pantai
bukit
alam
```

---

## 4.6 Kabupaten Bangli

### Sample Aman

```text
Kategori Preferensi : Alam, Budaya, Rekreasi, Umum
Kabupaten/Kota      : Kabupaten Bangli
Kecamatan           : Kintamani
Keywords            : kosong
Min Rating          : 0
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

### Sample Lebih Spesifik

```text
Kategori Preferensi : Alam, Budaya, Umum
Kabupaten/Kota      : Kabupaten Bangli
Kecamatan           : Kintamani
Keywords            : danau, gunung
Min Rating          : 4
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

### Kecamatan Alternatif

```text
Kintamani
Bangli
Susut
Tembuku
```

### Keyword Opsional

```text
danau
gunung
alam
pura
```

---

## 4.7 Kabupaten Klungkung

### Sample Aman

```text
Kategori Preferensi : Alam, Budaya, Rekreasi, Umum
Kabupaten/Kota      : Kabupaten Klungkung
Kecamatan           : Nusa Penida
Keywords            : kosong
Min Rating          : 0
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

### Sample Lebih Spesifik

```text
Kategori Preferensi : Alam, Budaya, Umum
Kabupaten/Kota      : Kabupaten Klungkung
Kecamatan           : Nusa Penida
Keywords            : pantai, tebing
Min Rating          : 4
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

### Kecamatan Alternatif

```text
Nusa Penida
Klungkung
Banjarangkan
Dawan
```

### Keyword Opsional

```text
pantai
tebing
laut
pura
```

---

## 4.8 Kabupaten Jembrana

### Sample Aman

```text
Kategori Preferensi : Alam, Budaya, Rekreasi, Umum
Kabupaten/Kota      : Kabupaten Jembrana
Kecamatan           : Negara
Keywords            : kosong
Min Rating          : 0
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

### Sample Lebih Spesifik

```text
Kategori Preferensi : Alam, Budaya, Umum
Kabupaten/Kota      : Kabupaten Jembrana
Kecamatan           : Negara
Keywords            : pantai, alam
Min Rating          : 4
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

### Kecamatan Alternatif

```text
Negara
Jembrana
Mendoyo
Melaya
Pekutatan
```

### Keyword Opsional

```text
pantai
alam
taman
```

---

## 4.9 Kota Denpasar

### Sample Aman

```text
Kategori Preferensi : Alam, Budaya, Rekreasi, Umum
Kabupaten/Kota      : Kota Denpasar
Kecamatan           : Denpasar Selatan
Keywords            : kosong
Min Rating          : 0
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

### Sample Lebih Spesifik

```text
Kategori Preferensi : Budaya, Rekreasi, Umum
Kabupaten/Kota      : Kota Denpasar
Kecamatan           : Denpasar Selatan
Keywords            : museum, taman
Min Rating          : 4
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

### Kecamatan Alternatif

```text
Denpasar Selatan
Denpasar Barat
Denpasar Timur
Denpasar Utara
```

### Keyword Opsional

```text
museum
taman
pantai
budaya
```

---

## 5. Sample Test Tanpa BMKG

Gunakan ini jika ingin menguji CARS manual tanpa mengambil cuaca BMKG.

```text
Kategori Preferensi : Alam, Budaya
Kabupaten/Kota      : Kabupaten Gianyar
Kecamatan           : Ubud
Keywords            : kosong
Min Rating          : 4
Top N               : 10
Cuaca Manual        : Hujan
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : tidak dicentang
```

Ekspektasi:

```text
- Sistem memakai cuaca manual.
- Destinasi indoor/mixed akan lebih diprioritaskan saat hujan.
- Destinasi outdoor bisa turun skor.
```

---

## 6. Sample Test BMKG Aktif

Gunakan ini untuk memastikan ADM4 otomatis berjalan.

```text
Kategori Preferensi : Alam, Budaya
Kabupaten/Kota      : Kabupaten Gianyar
Kecamatan           : Ubud
Keywords            : kosong
Min Rating          : 4
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : centang
```

Ekspektasi:

```text
- User tidak mengisi ADM4.
- Laravel otomatis menentukan ADM4 dari Kabupaten/Kota + Kecamatan.
- FastAPI menerima use_bmkg = true.
- Weather source menjadi BMKG.
- Riwayat rekomendasi tersimpan ke user yang sedang login.
```

---

## 7. Sample Test High Season

```text
Kategori Preferensi : Alam, Budaya, Rekreasi, Umum
Kabupaten/Kota      : Kabupaten Badung
Kecamatan           : Kuta
Keywords            : kosong
Min Rating          : 4
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekend
High Season         : dicentang
Gunakan BMKG        : centang
```

Ekspektasi:

```text
- CARS mempertimbangkan kondisi weekend dan high season.
- Destinasi yang terlalu ramai dapat mengalami penyesuaian skor.
- Sistem tetap menampilkan Top-N berdasarkan final score.
```

---

## 8. Checklist Pengujian

Gunakan checklist berikut saat mengetes sistem:

```text
[ ] User bisa register.
[ ] User bisa login.
[ ] User bisa membuka /user/dashboard.
[ ] User bisa membuka /tourhub/rekomendasi setelah login.
[ ] User tidak bisa membuka /tourhub/rekomendasi sebelum login.
[ ] Form rekomendasi bisa submit.
[ ] Hasil rekomendasi muncul.
[ ] Final score muncul.
[ ] CBF score muncul.
[ ] Context multiplier muncul.
[ ] Source cuaca muncul.
[ ] Data masuk ke log rekomendasi.
[ ] Riwayat muncul di dashboard user.
[ ] Detail riwayat bisa dibuka.
[ ] Request payload tampil di detail.
[ ] Response payload tampil di detail.
```

---

## 9. Jika Hasil 0 Candidates

Jika hasil rekomendasi kosong atau `0 candidates`, lakukan ini:

```text
1. Kosongkan Keywords.
2. Turunkan Min Rating ke 0.
3. Centang semua kategori.
4. Pastikan Kabupaten/Kota benar.
5. Pastikan Kecamatan sesuai dengan dataset.
6. Coba matikan BMKG untuk memastikan bukan masalah cuaca.
7. Coba pilih wilayah yang paling banyak data, misalnya Kabupaten Gianyar atau Kabupaten Badung.
```

Contoh fallback paling aman:

```text
Kategori Preferensi : Alam, Budaya, Rekreasi, Umum
Kabupaten/Kota      : Kabupaten Gianyar
Kecamatan           : Ubud
Keywords            : kosong
Min Rating          : 0
Top N               : 10
Cuaca Manual        : Cerah
Hari Kunjungan      : Weekday
High Season         : tidak dicentang
Gunakan BMKG        : tidak dicentang
```

---

## 10. Narasi Singkat untuk Laporan

Berikut narasi yang bisa digunakan pada laporan implementasi:

> Pengujian sistem dilakukan dengan memasukkan parameter preferensi wisata melalui halaman web Laravel. Parameter yang diuji meliputi kategori wisata, kabupaten/kota, kecamatan, kata kunci, rating minimum, jumlah rekomendasi, cuaca manual, hari kunjungan, high season, dan penggunaan BMKG. Jika BMKG diaktifkan, sistem Laravel secara otomatis menentukan kode ADM4 berdasarkan kabupaten/kota dan kecamatan yang dipilih oleh user. Setelah parameter dikirim, Laravel meneruskan request ke FastAPI Machine Learning untuk diproses menggunakan Content-Based Filtering dan Context-Aware Recommender System. Hasil rekomendasi kemudian ditampilkan pada halaman web dan disimpan sebagai riwayat rekomendasi berdasarkan user yang sedang login.

---

## 11. Catatan Penting

1. User tidak perlu mengisi ADM4.
2. ADM4 otomatis ditentukan oleh sistem.
3. Gunakan `Keywords` kosong saat pengujian awal.
4. Gunakan `Min Rating = 0` saat ingin memastikan ada hasil.
5. Gunakan `Gunakan BMKG = tidak dicentang` jika ingin mengetes cuaca manual.
6. Gunakan `Gunakan BMKG = dicentang` jika ingin mengetes integrasi BMKG.
7. Riwayat rekomendasi hanya tampil untuk user yang sedang login.
