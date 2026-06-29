# Bali Tourist Destination Dataset

Dataset ini berisi informasi tempat wisata yang berada di Bali. Setiap baris pada dataset merepresentasikan satu tempat wisata, sedangkan setiap kolom menjelaskan atribut dari tempat wisata tersebut, seperti nama tempat, kategori wisata, lokasi administratif, rating, jumlah rating, koordinat, link Google Maps, dan link gambar.

## Informasi Umum Dataset

| Keterangan | Nilai |
|---|---:|
| Nama file | `bali_tourist_destination.csv` |
| Jumlah baris data | 1.452 baris |
| Jumlah kolom | 11 kolom |
| Jumlah baris termasuk header | 1.453 baris |
| Jumlah kategori wisata | 4 kategori |
| Jumlah kecamatan | 56 kecamatan |
| Jumlah kabupaten/kota | 9 kabupaten/kota |
| Jumlah ID unik | 1.452 ID |
| Jumlah data duplikat penuh | 0 data |

## Deskripsi Dataset

Dataset ini dapat digunakan untuk analisis data pariwisata, visualisasi lokasi wisata, pemetaan destinasi wisata, maupun pengembangan sistem rekomendasi tempat wisata di Bali. Informasi rating dan jumlah rating dapat digunakan untuk melihat popularitas atau tingkat kepuasan pengunjung terhadap suatu destinasi.

Secara umum, dataset ini memiliki data yang cukup lengkap. Namun, terdapat beberapa catatan kualitas data, seperti adanya nilai kosong pada kolom `link_gambar`, beberapa nama tempat wisata yang muncul lebih dari satu kali, serta beberapa koordinat yang perlu divalidasi ulang.

## Struktur Kolom

| No | Nama Kolom | Tipe Data | Deskripsi |
|---:|---|---|---|
| 1 | `id_tempat` | Teks | ID unik untuk setiap tempat wisata. |
| 2 | `nama_tempat_wisata` | Teks | Nama tempat wisata. |
| 3 | `kategori` | Teks | Kategori wisata, seperti Alam, Budaya, Rekreasi, atau Umum. |
| 4 | `kecamatan` | Teks | Kecamatan tempat wisata berada. |
| 5 | `kabupaten_kota` | Teks | Kabupaten atau kota lokasi tempat wisata. |
| 6 | `rating` | Angka desimal | Nilai rating tempat wisata. |
| 7 | `jumlah_rating` | Angka bulat | Jumlah pengguna yang memberikan rating. |
| 8 | `latitude` | Angka desimal | Koordinat garis lintang tempat wisata. |
| 9 | `longitude` | Angka desimal | Koordinat garis bujur tempat wisata. |
| 10 | `link_google_maps` | Teks/URL | Link lokasi tempat wisata di Google Maps. |
| 11 | `link_gambar` | Teks/URL | Link gambar tempat wisata. |

## Distribusi Kategori Wisata

| Kategori | Jumlah Data |
|---|---:|
| Umum | 577 |
| Alam | 529 |
| Budaya | 182 |
| Rekreasi | 164 |

Kategori dengan jumlah data terbanyak adalah **Umum**, sedangkan kategori dengan jumlah data paling sedikit adalah **Rekreasi**.

## Distribusi Kabupaten/Kota

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

Dataset mencakup 9 wilayah administratif di Bali, yaitu 8 kabupaten dan 1 kota.

## Ringkasan Statistik Rating

| Statistik | Nilai |
|---|---:|
| Rata-rata rating | 4,51 |
| Rating minimum | 0,00 |
| Rating maksimum | 5,00 |
| Median rating | 4,60 |
| Jumlah data dengan rating 0 | 15 data |

Nilai rating rata-rata berada di sekitar **4,51**, yang menunjukkan bahwa sebagian besar tempat wisata dalam dataset memiliki penilaian yang cukup tinggi. Namun, terdapat 15 data dengan rating 0. Nilai ini kemungkinan muncul karena tempat tersebut belum memiliki rating atau data rating belum tersedia.

## Ringkasan Statistik Jumlah Rating

| Statistik | Nilai |
|---|---:|
| Rata-rata jumlah rating | 1.176,18 |
| Jumlah rating minimum | 0 |
| Jumlah rating maksimum | 101.523 |
| Median jumlah rating | 110 |
| Jumlah data dengan jumlah rating 0 | 15 data |

Kolom `jumlah_rating` menunjukkan seberapa banyak pengguna yang memberikan penilaian terhadap tempat wisata. Jumlah rating yang tinggi dapat menjadi indikator popularitas suatu destinasi.

## Kualitas Data

Berikut adalah ringkasan kualitas data pada dataset:

| Aspek | Keterangan |
|---|---|
| Missing value | Terdapat 4 nilai kosong pada kolom `link_gambar`. |
| Duplikat baris penuh | Tidak ditemukan duplikat baris penuh. |
| Duplikat ID | Tidak ditemukan duplikat pada kolom `id_tempat`. |
| Duplikat nama tempat | Terdapat 15 nama tempat wisata yang muncul lebih dari satu kali. |
| Rating 0 | Terdapat 15 data dengan rating bernilai 0. |
| Jumlah rating 0 | Terdapat 15 data dengan jumlah rating bernilai 0. |
| Koordinat | Terdapat beberapa koordinat yang perlu divalidasi ulang karena berada di luar area Bali. |

## Catatan Validasi Data

Beberapa data pada kolom koordinat perlu diperiksa kembali. Misalnya, terdapat beberapa tempat wisata dengan longitude sekitar 111–112 dan latitude sekitar -7,8. Koordinat tersebut cenderung mengarah ke wilayah Jawa Timur, bukan Bali. Hal ini bisa terjadi karena proses pengambilan data dari Google Maps menangkap lokasi dengan nama kecamatan yang sama, misalnya **Kediri**, tetapi bukan Kediri yang berada di Kabupaten Tabanan, Bali.

Contoh data yang perlu divalidasi ulang:

| Nama Tempat Wisata | Kecamatan | Kabupaten/Kota | Latitude | Longitude |
|---|---|---|---:|---:|
| Goa Selomangleng | Kediri | Kabupaten Tabanan | -7.807188 | 111.972672 |
| Taman Wisata Tirtoyoso Park | Kediri | Kabupaten Tabanan | -7.816438 | 112.029748 |
| Air Terjun Dolo | Kediri | Kabupaten Tabanan | -7.867689 | 111.833469 |
| Gumul Paradise Island | Kediri | Kabupaten Tabanan | -7.811681 | 112.061169 |
| Taman Hutan Joyoboyo Kediri | Kediri | Kabupaten Tabanan | -7.818138 | 112.029716 |
| Wisata Sempu Exotic Park | Kediri | Kabupaten Tabanan | -7.960526 | 112.217889 |
| Air Terjun Irenggolo | Kediri | Kabupaten Tabanan | -7.863541 | 111.850352 |

Dalam penelitian atau proyek analisis, data seperti ini sebaiknya dicek ulang agar hasil analisis tidak bias.

## Potensi Penggunaan Dataset

Dataset ini dapat digunakan untuk beberapa kebutuhan, antara lain:

1. Analisis persebaran tempat wisata di Bali berdasarkan kabupaten/kota.
2. Analisis kategori wisata yang paling banyak tersedia.
3. Analisis tempat wisata dengan rating tertinggi.
4. Analisis tempat wisata paling populer berdasarkan jumlah rating.
5. Visualisasi lokasi wisata menggunakan latitude dan longitude.
6. Pengembangan sistem rekomendasi wisata berdasarkan kategori, lokasi, rating, dan popularitas.
7. Pembuatan aplikasi pencarian tempat wisata di Bali.

## Contoh Pertanyaan Analisis

Beberapa pertanyaan yang dapat dijawab menggunakan dataset ini:

1. Kabupaten/kota mana yang memiliki jumlah tempat wisata terbanyak?
2. Kategori wisata apa yang paling banyak tersedia di Bali?
3. Tempat wisata mana yang memiliki rating tertinggi?
4. Tempat wisata mana yang memiliki jumlah rating paling banyak?
5. Apakah rating tinggi selalu diikuti jumlah rating yang tinggi?
6. Bagaimana persebaran tempat wisata berdasarkan koordinat geografis?
7. Wilayah mana yang memiliki dominasi kategori wisata alam?

## Contoh Penjelasan untuk Presentasi atau Sidang

Berikut contoh penjelasan singkat yang dapat digunakan saat ditanya oleh penguji:

> Dataset yang saya gunakan adalah dataset tempat wisata di Bali. Dataset ini terdiri dari 1.452 baris data dan 11 kolom. Setiap baris merepresentasikan satu tempat wisata, sedangkan setiap kolom menjelaskan informasi seperti ID tempat, nama tempat wisata, kategori, kecamatan, kabupaten atau kota, rating, jumlah rating, latitude, longitude, link Google Maps, dan link gambar. Dataset ini memiliki empat kategori wisata, yaitu Umum, Alam, Budaya, dan Rekreasi. Secara umum, data cukup lengkap, hanya terdapat empat nilai kosong pada kolom link gambar. Namun, terdapat beberapa data yang perlu divalidasi ulang, terutama pada bagian koordinat yang kemungkinan tidak berada di area Bali.

## Penjelasan Kolom Penting

### `rating`
Kolom ini menunjukkan nilai penilaian dari pengguna terhadap tempat wisata. Nilai rating dapat digunakan untuk mengetahui kualitas atau tingkat kepuasan pengunjung.

### `jumlah_rating`
Kolom ini menunjukkan jumlah pengguna yang memberikan rating. Kolom ini penting karena rating dengan jumlah penilai yang banyak biasanya lebih kuat dibandingkan rating tinggi tetapi hanya dinilai oleh sedikit pengguna.

### `latitude` dan `longitude`
Kolom ini menunjukkan posisi geografis tempat wisata. Data ini dapat digunakan untuk visualisasi peta, analisis jarak, atau sistem rekomendasi berbasis lokasi.

### `kategori`
Kolom ini menunjukkan jenis tempat wisata. Kategori dapat digunakan untuk mengelompokkan tempat wisata sesuai minat pengguna, misalnya wisata alam, budaya, rekreasi, atau umum.

## Saran Preprocessing

Sebelum dataset digunakan untuk analisis lanjutan atau pemodelan, beberapa langkah preprocessing yang disarankan adalah:

1. Menghapus atau melengkapi nilai kosong pada kolom `link_gambar`.
2. Memvalidasi ulang koordinat yang berada di luar wilayah Bali.
3. Mengecek nama tempat wisata yang muncul lebih dari satu kali.
4. Mengecek data dengan rating dan jumlah rating bernilai 0.
5. Menstandarkan format teks pada kolom kategori, kecamatan, dan kabupaten/kota.
6. Memastikan semua link Google Maps dan link gambar masih dapat diakses.

## Lisensi dan Sumber Data

Sumber data pada dataset ini terlihat berasal dari informasi tempat wisata yang tersedia di Google Maps, karena setiap data memiliki kolom `link_google_maps` dan sebagian besar memiliki `link_gambar` dari sumber gambar online. Jika dataset ini digunakan untuk publikasi atau penelitian, sebaiknya cantumkan sumber data sesuai dengan asal dataset yang digunakan.

## Ringkasan Singkat

Dataset `bali_tourist_destination.csv` merupakan dataset tempat wisata di Bali yang terdiri dari **1.452 baris data** dan **11 kolom**. Dataset ini mencakup informasi nama tempat wisata, kategori, lokasi administratif, rating, jumlah rating, koordinat geografis, link Google Maps, dan link gambar. Dataset ini cocok digunakan untuk analisis pariwisata, visualisasi peta, dan pengembangan sistem rekomendasi wisata.
