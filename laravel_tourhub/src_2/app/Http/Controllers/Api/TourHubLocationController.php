<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

final class TourHubLocationController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                ['kabupaten_kota' => 'Kabupaten Gianyar', 'kecamatan' => 'Ubud', 'bmkg_adm4' => '51.04.05.1005'],
                ['kabupaten_kota' => 'Kabupaten Gianyar', 'kecamatan' => 'Gianyar', 'bmkg_adm4' => '51.04.03.1001'],
                ['kabupaten_kota' => 'Kabupaten Gianyar', 'kecamatan' => 'Tegallalang', 'bmkg_adm4' => '51.04.06.2001'],
                ['kabupaten_kota' => 'Kabupaten Gianyar', 'kecamatan' => 'Blahbatuh', 'bmkg_adm4' => '51.04.02.2001'],
                ['kabupaten_kota' => 'Kabupaten Gianyar', 'kecamatan' => 'Tampaksiring', 'bmkg_adm4' => '51.04.04.2001'],
                ['kabupaten_kota' => 'Kabupaten Gianyar', 'kecamatan' => 'Sukawati', 'bmkg_adm4' => '51.04.01.2001'],
                ['kabupaten_kota' => 'Kabupaten Gianyar', 'kecamatan' => 'Payangan', 'bmkg_adm4' => '51.04.07.2001'],

                ['kabupaten_kota' => 'Kabupaten Badung', 'kecamatan' => 'Kuta', 'bmkg_adm4' => '51.03.01.1001'],
                ['kabupaten_kota' => 'Kabupaten Badung', 'kecamatan' => 'Kuta Selatan', 'bmkg_adm4' => '51.03.05.1001'],
                ['kabupaten_kota' => 'Kabupaten Badung', 'kecamatan' => 'Kuta Utara', 'bmkg_adm4' => '51.03.06.1001'],
                ['kabupaten_kota' => 'Kabupaten Badung', 'kecamatan' => 'Mengwi', 'bmkg_adm4' => '51.03.02.2001'],
                ['kabupaten_kota' => 'Kabupaten Badung', 'kecamatan' => 'Abiansemal', 'bmkg_adm4' => '51.03.03.2001'],
                ['kabupaten_kota' => 'Kabupaten Badung', 'kecamatan' => 'Petang', 'bmkg_adm4' => '51.03.04.2001'],

                ['kabupaten_kota' => 'Kabupaten Tabanan', 'kecamatan' => 'Tabanan', 'bmkg_adm4' => '51.02.05.1001'],
                ['kabupaten_kota' => 'Kabupaten Tabanan', 'kecamatan' => 'Kediri', 'bmkg_adm4' => '51.02.06.2001'],
                ['kabupaten_kota' => 'Kabupaten Tabanan', 'kecamatan' => 'Penebel', 'bmkg_adm4' => '51.02.08.2001'],
                ['kabupaten_kota' => 'Kabupaten Tabanan', 'kecamatan' => 'Baturiti', 'bmkg_adm4' => '51.02.09.2001'],
                ['kabupaten_kota' => 'Kabupaten Tabanan', 'kecamatan' => 'Pupuan', 'bmkg_adm4' => '51.02.10.2001'],
                ['kabupaten_kota' => 'Kabupaten Tabanan', 'kecamatan' => 'Selemadeg Timur', 'bmkg_adm4' => '51.02.02.2001'],
                ['kabupaten_kota' => 'Kabupaten Tabanan', 'kecamatan' => 'Selemadeg Barat', 'bmkg_adm4' => '51.02.03.2001'],
                ['kabupaten_kota' => 'Kabupaten Tabanan', 'kecamatan' => 'Kerambitan', 'bmkg_adm4' => '51.02.04.2001'],
                ['kabupaten_kota' => 'Kabupaten Tabanan', 'kecamatan' => 'Marga', 'bmkg_adm4' => '51.02.07.2001'],

                ['kabupaten_kota' => 'Kabupaten Buleleng', 'kecamatan' => 'Buleleng', 'bmkg_adm4' => '51.08.06.1001'],
                ['kabupaten_kota' => 'Kabupaten Buleleng', 'kecamatan' => 'Gerokgak', 'bmkg_adm4' => '51.08.01.2001'],
                ['kabupaten_kota' => 'Kabupaten Buleleng', 'kecamatan' => 'Seririt', 'bmkg_adm4' => '51.08.02.1001'],
                ['kabupaten_kota' => 'Kabupaten Buleleng', 'kecamatan' => 'Busungbiu', 'bmkg_adm4' => '51.08.03.2001'],
                ['kabupaten_kota' => 'Kabupaten Buleleng', 'kecamatan' => 'Banjar', 'bmkg_adm4' => '51.08.04.2001'],
                ['kabupaten_kota' => 'Kabupaten Buleleng', 'kecamatan' => 'Sukasada', 'bmkg_adm4' => '51.08.05.2001'],
                ['kabupaten_kota' => 'Kabupaten Buleleng', 'kecamatan' => 'Sawan', 'bmkg_adm4' => '51.08.07.2001'],
                ['kabupaten_kota' => 'Kabupaten Buleleng', 'kecamatan' => 'Kubutambahan', 'bmkg_adm4' => '51.08.08.2001'],
                ['kabupaten_kota' => 'Kabupaten Buleleng', 'kecamatan' => 'Tejakula', 'bmkg_adm4' => '51.08.09.2001'],

                ['kabupaten_kota' => 'Kabupaten Karangasem', 'kecamatan' => 'Karangasem', 'bmkg_adm4' => '51.07.04.1001'],
                ['kabupaten_kota' => 'Kabupaten Karangasem', 'kecamatan' => 'Rendang', 'bmkg_adm4' => '51.07.01.2001'],
                ['kabupaten_kota' => 'Kabupaten Karangasem', 'kecamatan' => 'Sidemen', 'bmkg_adm4' => '51.07.02.2001'],
                ['kabupaten_kota' => 'Kabupaten Karangasem', 'kecamatan' => 'Manggis', 'bmkg_adm4' => '51.07.03.2001'],
                ['kabupaten_kota' => 'Kabupaten Karangasem', 'kecamatan' => 'Abang', 'bmkg_adm4' => '51.07.05.2001'],
                ['kabupaten_kota' => 'Kabupaten Karangasem', 'kecamatan' => 'Bebandem', 'bmkg_adm4' => '51.07.06.2001'],
                ['kabupaten_kota' => 'Kabupaten Karangasem', 'kecamatan' => 'Selat', 'bmkg_adm4' => '51.07.07.2001'],
                ['kabupaten_kota' => 'Kabupaten Karangasem', 'kecamatan' => 'Kubu', 'bmkg_adm4' => '51.07.08.2001'],

                ['kabupaten_kota' => 'Kabupaten Bangli', 'kecamatan' => 'Kintamani', 'bmkg_adm4' => '51.06.04.2001'],
                ['kabupaten_kota' => 'Kabupaten Bangli', 'kecamatan' => 'Bangli', 'bmkg_adm4' => '51.06.02.1001'],
                ['kabupaten_kota' => 'Kabupaten Bangli', 'kecamatan' => 'Susut', 'bmkg_adm4' => '51.06.01.2001'],
                ['kabupaten_kota' => 'Kabupaten Bangli', 'kecamatan' => 'Tembuku', 'bmkg_adm4' => '51.06.03.2001'],

                ['kabupaten_kota' => 'Kabupaten Klungkung', 'kecamatan' => 'Nusa Penida', 'bmkg_adm4' => '51.05.01.2001'],
                ['kabupaten_kota' => 'Kabupaten Klungkung', 'kecamatan' => 'Klungkung', 'bmkg_adm4' => '51.05.03.1001'],
                ['kabupaten_kota' => 'Kabupaten Klungkung', 'kecamatan' => 'Banjarangkan', 'bmkg_adm4' => '51.05.02.2001'],
                ['kabupaten_kota' => 'Kabupaten Klungkung', 'kecamatan' => 'Dawan', 'bmkg_adm4' => '51.05.04.2001'],

                ['kabupaten_kota' => 'Kabupaten Jembrana', 'kecamatan' => 'Negara', 'bmkg_adm4' => '51.01.01.1001'],
                ['kabupaten_kota' => 'Kabupaten Jembrana', 'kecamatan' => 'Jembrana', 'bmkg_adm4' => '51.01.05.1001'],
                ['kabupaten_kota' => 'Kabupaten Jembrana', 'kecamatan' => 'Mendoyo', 'bmkg_adm4' => '51.01.02.2001'],
                ['kabupaten_kota' => 'Kabupaten Jembrana', 'kecamatan' => 'Melaya', 'bmkg_adm4' => '51.01.04.2001'],
                ['kabupaten_kota' => 'Kabupaten Jembrana', 'kecamatan' => 'Pekutatan', 'bmkg_adm4' => '51.01.03.2001'],

                ['kabupaten_kota' => 'Kota Denpasar', 'kecamatan' => 'Denpasar Selatan', 'bmkg_adm4' => '51.71.01.1006'],
                ['kabupaten_kota' => 'Kota Denpasar', 'kecamatan' => 'Denpasar Barat', 'bmkg_adm4' => '51.71.03.1001'],
                ['kabupaten_kota' => 'Kota Denpasar', 'kecamatan' => 'Denpasar Timur', 'bmkg_adm4' => '51.71.02.1001'],
                ['kabupaten_kota' => 'Kota Denpasar', 'kecamatan' => 'Denpasar Utara', 'bmkg_adm4' => '51.71.04.1001'],
            ],
        ]);
    }
}