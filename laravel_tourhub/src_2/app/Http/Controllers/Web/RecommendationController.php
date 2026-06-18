<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use Throwable;
use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\RecommendationLog;
use Illuminate\Http\JsonResponse;
use App\Services\TourHubMlService;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Validation\ValidationException;

final class RecommendationController extends Controller
{
    /**
     * View utama halaman simulasi rekomendasi.
     *
     * File:
     * resources/views/tourhub/recommendation/index.blade.php
     */
    private const VIEW_PATH = 'tourhub.recommendation.index';

    /**
     * Fallback terakhir jika user mengaktifkan BMKG tetapi lokasi tidak dikenali.
     *
     * Sengaja dibuat fallback agar user tidak terkena error ADM4.
     * Nilai ini memakai wilayah Ubud, Gianyar.
     */
    private const DEFAULT_BALI_ADM4 = '51.04.05.1005';

    public function index(): View
    {
        $this->ensureViewExists();

        return view(self::VIEW_PATH, $this->baseViewData());
    }

    public function health(TourHubMlService $ml): JsonResponse
    {
        try {
            return response()->json($ml->health());
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'status' => 'failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function recommend(Request $request, TourHubMlService $ml): View|RedirectResponse
    {
        $this->ensureViewExists();

        $locationPairValues = $this->locationPairValues();

        $validated = $request->validate([
            'kategori_preferensi' => ['required', 'array', 'min:1'],
            'kategori_preferensi.*' => [
                'required',
                Rule::in(['Alam', 'Budaya', 'Rekreasi', 'Umum']),
            ],

            'lokasi_wisata' => [
                'nullable',
                'string',
                'max:255',
                Rule::in($locationPairValues),
            ],

            // Tetap disediakan untuk backward compatibility jika ada request lama/API internal
            // yang masih mengirim kabupaten_kota dan kecamatan secara terpisah.
            'kabupaten_kota' => ['nullable', 'string', 'max:150'],
            'kecamatan' => ['nullable', 'string', 'max:150'],
            'keywords' => ['nullable', 'string', 'max:255'],

            'min_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'top_n' => ['required', 'integer', 'min:1', 'max:50'],

            'weather' => [
                'nullable',
                Rule::in(['cerah', 'hujan', 'mendung', 'berawan', 'unknown']),
            ],

            'visit_day' => [
                'nullable',
                Rule::in(['weekday', 'weekend']),
            ],

            'is_high_season' => ['nullable', 'boolean'],
            'use_bmkg' => ['nullable', 'boolean'],
            'bmkg_adm4' => ['nullable', 'string', 'max:50'],
        ]);

        $payload = $this->buildPayload($validated);

        $startedAt = microtime(true);

        try {
            $result = $ml->recommend($payload);

            $responseTimeMs = $this->calculateResponseTime($startedAt);

            $this->storeSuccessLog(
                payload: $payload,
                result: $result,
                responseTimeMs: $responseTimeMs,
            );

            return view(self::VIEW_PATH, array_merge($this->baseViewData(), [
                'payload' => $payload,
                'result' => $result,
                'responseTimeMs' => $responseTimeMs,
            ]));
        } catch (Throwable $e) {
            $responseTimeMs = $this->calculateResponseTime($startedAt);

            $this->storeFailedLog(
                payload: $payload,
                errorMessage: $e->getMessage(),
                responseTimeMs: $responseTimeMs,
            );

            return back()
                ->withInput()
                ->withErrors([
                    'ml_api' => $e->getMessage(),
                ]);
        }
    }

    /**
     * Data default untuk view.
     *
     * @return array<string, mixed>
     */
    private function baseViewData(): array
    {
        $latestLogsQuery = RecommendationLog::query()
            ->with('user')
            ->latest()
            ->take(5);

        if (Auth::check()) {
            $latestLogsQuery->where('user_id', Auth::id());
        }

        return [
            'defaultBaseUrl' => config('tourhub.ml_base_url'),
            'latestLogs' => $latestLogsQuery->get(),
        ];
    }

    /**
     * Membentuk payload yang akan dikirim ke FastAPI ML.
     *
     * PERBAIKAN BMKG OTOMATIS:
     * - Cuaca manual tidak lagi dijadikan input utama.
     * - Weather selalu dikirim sebagai fallback default: "cerah".
     * - Controller otomatis mencari kode ADM4 dari lokasi yang dipilih.
     * - Jika ADM4 ditemukan, FastAPI akan memakai BMKG sebagai konteks cuaca.
     * - Jika suatu saat ADM4 gagal ditemukan, sistem tetap aman dengan fallback cerah.
     *
     * Catatan:
     * Logic lama TIDAK DIHAPUS. Logic lama disimpan sebagai CODE MATI
     * tepat di bawah method ini agar bisa dibandingkan atau rollback.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function buildPayload(array $validated): array
    {
        [$kabupatenKota, $kecamatan] = $this->resolvePostedLocation($validated);

        $validated['kabupaten_kota'] = $kabupatenKota;
        $validated['kecamatan'] = $kecamatan;

        /*
         * BMKG OTOMATIS UNTUK WEB
         *
         * Alur:
         * 1. Jika request membawa bmkg_adm4, pakai nilai tersebut.
         * 2. Jika kosong, cari otomatis dari kabupaten/kota + kecamatan.
         * 3. Jika masih kosong, sistem tetap jalan dengan use_bmkg=false
         *    dan weather fallback "cerah".
         *
         * Pada controller ini resolveBmkgAdm4() sudah memiliki fallback
         * terakhir DEFAULT_BALI_ADM4, sehingga normalnya bmkg_adm4 tetap terisi.
         */
        $bmkgAdm4 = $this->nullableString($validated['bmkg_adm4'] ?? null);

        if (! $bmkgAdm4) {
            $bmkgAdm4 = $this->resolveBmkgAdm4($validated);
        }

        $useBmkg = $bmkgAdm4 !== null && $bmkgAdm4 !== '';

        return [
            'kategori_preferensi' => $validated['kategori_preferensi'],

            'kabupaten_kota' => $kabupatenKota,
            'kecamatan' => $kecamatan,

            'keywords' => $this->parseKeywords($validated['keywords'] ?? null),

            'min_rating' => isset($validated['min_rating'])
                ? (float) $validated['min_rating']
                : null,

            'top_n' => (int) $validated['top_n'],

            /*
             * Weather hanya fallback.
             * Jika use_bmkg=true dan bmkg_adm4 terisi, FastAPI akan mengambil
             * prakiraan cuaca BMKG otomatis. Jika BMKG gagal, FastAPI tetap
             * memakai fallback "cerah".
             */
            'weather' => 'cerah',

            'visit_day' => $this->nullableString($validated['visit_day'] ?? null),

            'is_high_season' => $this->toBoolean($validated['is_high_season'] ?? false),

            /*
             * BMKG otomatis aktif selama ADM4 berhasil tersedia.
             */
            'use_bmkg' => $useBmkg,
            'bmkg_adm4' => $useBmkg ? $bmkgAdm4 : null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CODE MATI - buildPayload lama sebelum BMKG otomatis
    |--------------------------------------------------------------------------
    |
    | Logic lama sengaja DIKOMENTARKAN, bukan dihapus.
    | Perbedaan utama:
    | - Dulu use_bmkg mengikuti input checkbox/form.
    | - Dulu weather mengikuti input manual dari user.
    | - Dulu jika use_bmkg=true tetapi ADM4 kosong, controller melempar validasi error.
    |
    | Sekarang:
    | - BMKG dicari otomatis dari lokasi.
    | - weather default/fallback selalu "cerah".
    | - User tidak perlu memilih cuaca manual.
    |
    */
    //     /**
    //      * Membentuk payload yang akan dikirim ke FastAPI ML.
    //      *
    //      * Jika use_bmkg = true dan bmkg_adm4 kosong,
    //      * sistem otomatis mencari ADM4 berdasarkan kabupaten/kota + kecamatan.
    //      *
    //      * @param  array<string, mixed>  $validated
    //      * @return array<string, mixed>
    //      *
    //      * @throws ValidationException
    //      */
    //     private function buildPayload(array $validated): array
    //     {
    //         [$kabupatenKota, $kecamatan] = $this->resolvePostedLocation($validated);
    //
    //         $validated['kabupaten_kota'] = $kabupatenKota;
    //         $validated['kecamatan'] = $kecamatan;
    //
    //         $useBmkg = $this->toBoolean($validated['use_bmkg'] ?? false);
    //
    //         $bmkgAdm4 = $this->nullableString($validated['bmkg_adm4'] ?? null);
    //
    //         if ($useBmkg && ! $bmkgAdm4) {
    //             $bmkgAdm4 = $this->resolveBmkgAdm4($validated);
    //         }
    //
    //         if ($useBmkg && ! $bmkgAdm4) {
    //             throw ValidationException::withMessages([
    //                 'bmkg_adm4' => 'Kode ADM4 BMKG belum tersedia untuk wilayah ini. Coba isi kecamatan yang valid atau matikan opsi Gunakan BMKG.',
    //             ]);
    //         }
    //
    //         return [
    //             'kategori_preferensi' => $validated['kategori_preferensi'],
    //
    //             'kabupaten_kota' => $kabupatenKota,
    //             'kecamatan' => $kecamatan,
    //
    //             'keywords' => $this->parseKeywords($validated['keywords'] ?? null),
    //
    //             'min_rating' => isset($validated['min_rating'])
    //                 ? (float) $validated['min_rating']
    //                 : null,
    //
    //             'top_n' => (int) $validated['top_n'],
    //
    //             'weather' => $this->nullableString($validated['weather'] ?? null),
    //             'visit_day' => $this->nullableString($validated['visit_day'] ?? null),
    //
    //             'is_high_season' => $this->toBoolean($validated['is_high_season'] ?? false),
    //             'use_bmkg' => $useBmkg,
    //             'bmkg_adm4' => $useBmkg ? $bmkgAdm4 : null,
    //         ];
    //     }

    /**
     * Resolve ADM4 BMKG berdasarkan kabupaten/kota dan kecamatan.
     *
     * Urutan pencarian:
     * 1. Mapping manual ADM4 lengkap per kecamatan Bali.
     * 2. Fallback berdasarkan kecamatan saja jika kabupaten/kota kosong.
     * 3. Fallback berdasarkan kabupaten/kota jika kecamatan kosong atau typo.
     * 4. Resolve dari ADM3 BMKG melalui halaman BMKG.
     * 5. Scan kandidat ADM4 umum dari ADM3.
     * 6. Fallback terakhir ke Ubud agar tidak error.
     *
     * @param  array<string, mixed>  $validated
     */
    private function resolveBmkgAdm4(array $validated): ?string
    {
        $kabupatenKota = $this->normalizeKabupatenKota(
            $this->normalizeLocation($validated['kabupaten_kota'] ?? null)
        );

        $kecamatan = $this->normalizeLocation($validated['kecamatan'] ?? null);

        /*
         * PERBAIKAN VALIDASI ADM4 BMKG
         *
         * Sebelumnya mapping manual langsung dikembalikan begitu ditemukan.
         * Akibatnya, jika ada satu kode ADM4 manual yang sudah tidak valid
         * atau tidak dikenali API BMKG, FastAPI akan fallback dengan error 404.
         *
         * Sekarang setiap kandidat ADM4 dicek dulu ke API publik BMKG melalui
         * isValidBmkgAdm4(). Jika kandidat tidak valid, sistem lanjut ke
         * fallback berikutnya sampai mendapatkan ADM4 yang benar-benar valid.
         */

        $manualAdm4 = $this->resolveManualBmkgAdm4(
            kabupatenKota: $kabupatenKota,
            kecamatan: $kecamatan,
        );

        $validManualAdm4 = $this->resolveValidatedAdm4Candidate($manualAdm4);

        if ($validManualAdm4) {
            return $validManualAdm4;
        }

        $adm4ByKecamatanOnly = $this->resolveBmkgAdm4ByKecamatanOnly($kecamatan);
        $validAdm4ByKecamatanOnly = $this->resolveValidatedAdm4Candidate($adm4ByKecamatanOnly);

        if ($validAdm4ByKecamatanOnly) {
            return $validAdm4ByKecamatanOnly;
        }

        $adm4ByKabupaten = $this->resolveFallbackBmkgAdm4ByKabupaten($kabupatenKota);
        $validAdm4ByKabupaten = $this->resolveValidatedAdm4Candidate($adm4ByKabupaten);

        if ($validAdm4ByKabupaten) {
            return $validAdm4ByKabupaten;
        }

        $adm3 = $this->resolveBmkgAdm3(
            kabupatenKota: $kabupatenKota,
            kecamatan: $kecamatan,
        );

        if ($adm3) {
            $adm4FromBmkgPage = $this->resolveFirstAdm4FromBmkgAdm3($adm3);
            $validAdm4FromBmkgPage = $this->resolveValidatedAdm4Candidate($adm4FromBmkgPage);

            if ($validAdm4FromBmkgPage) {
                return $validAdm4FromBmkgPage;
            }

            /*
             * resolveAdm4ByCandidateScan() sudah melakukan validasi satu per satu.
             * Namun tetap dilewatkan ke resolveValidatedAdm4Candidate() sebagai
             * lapisan pengaman terakhir.
             */
            $adm4FromCandidateScan = $this->resolveAdm4ByCandidateScan($adm3);
            $validAdm4FromCandidateScan = $this->resolveValidatedAdm4Candidate($adm4FromCandidateScan);

            if ($validAdm4FromCandidateScan) {
                return $validAdm4FromCandidateScan;
            }
        }

        /*
         * Fallback terakhir hanya dipakai jika kode default Bali valid.
         * Jika suatu saat API BMKG berubah dan default juga tidak valid,
         * return null agar buildPayload() otomatis menonaktifkan BMKG dan
         * FastAPI memakai fallback cuaca cerah tanpa memunculkan error.
         */
        return $this->resolveValidatedAdm4Candidate(self::DEFAULT_BALI_ADM4);
    }

    /*
    |--------------------------------------------------------------------------
    | CODE MATI - resolveBmkgAdm4 lama sebelum validasi ADM4
    |--------------------------------------------------------------------------
    |
    | Logic lama sengaja DIKOMENTARKAN, bukan dihapus.
    | Perbedaan utama:
    | - Dulu setiap ADM4 dari mapping manual langsung di-return tanpa dicek ke API BMKG.
    | - Jika mapping manual ternyata tidak valid/404, FastAPI akan fallback dan menampilkan
    |   source default_cerah_bmkg_error.
    |
    | Sekarang:
    | - Setiap kandidat ADM4 dicek dulu menggunakan resolveValidatedAdm4Candidate().
    | - Jika kandidat tidak valid, sistem lanjut ke fallback berikutnya.
    | - Jika semua kandidat gagal, BMKG dimatikan otomatis dan cuaca fallback tetap cerah.
    |
    | Potongan logic lama yang dimatikan:
    |
    | $manualAdm4 = $this->resolveManualBmkgAdm4(
    |     kabupatenKota: $kabupatenKota,
    |     kecamatan: $kecamatan,
    | );
    |
    | if ($manualAdm4) {
    |     return $manualAdm4;
    | }
    |
    | $adm4ByKecamatanOnly = $this->resolveBmkgAdm4ByKecamatanOnly($kecamatan);
    |
    | if ($adm4ByKecamatanOnly) {
    |     return $adm4ByKecamatanOnly;
    | }
    |
    | $adm4ByKabupaten = $this->resolveFallbackBmkgAdm4ByKabupaten($kabupatenKota);
    |
    | if ($adm4ByKabupaten) {
    |     return $adm4ByKabupaten;
    | }
    |
    | if ($adm3) {
    |     $adm4FromBmkgPage = $this->resolveFirstAdm4FromBmkgAdm3($adm3);
    |
    |     if ($adm4FromBmkgPage) {
    |         return $adm4FromBmkgPage;
    |     }
    |
    |     $adm4FromCandidateScan = $this->resolveAdm4ByCandidateScan($adm3);
    |
    |     if ($adm4FromCandidateScan) {
    |         return $adm4FromCandidateScan;
    |     }
    | }
    |
    | return self::DEFAULT_BALI_ADM4;
    |
    */

    /**
     * Validasi satu kandidat ADM4 sebelum dipakai sebagai payload BMKG.
     *
     * Method ini sengaja dibuat terpisah agar mapping manual lama tetap ada,
     * tetapi kode yang tidak valid tidak lagi langsung dikirim ke FastAPI.
     */
    private function resolveValidatedAdm4Candidate(?string $adm4): ?string
    {
        $adm4 = $this->nullableString($adm4);

        if (! $adm4) {
            return null;
        }

        if ($this->isValidBmkgAdm4($adm4)) {
            return $adm4;
        }

        return null;
    }

    /**
     * Mapping ADM4 manual per kecamatan.
     *
     * Catatan:
     * - Ini memakai satu desa/kelurahan representatif di masing-masing kecamatan.
     * - Tujuannya agar user cukup memilih kabupaten/kota + kecamatan,
     *   tanpa perlu memahami kode ADM4.
     * - Jika user ingin sangat presisi sampai desa/kelurahan, nanti bisa dibuat tabel database bmkg_areas.
     */
    private function resolveManualBmkgAdm4(string $kabupatenKota, string $kecamatan): ?string
    {
        $map = [
            'kabupaten jembrana|negara' => '51.01.01.1001',
            'kabupaten jembrana|mendoyo' => '51.01.02.2001',
            'kabupaten jembrana|pekutatan' => '51.01.03.2001',
            'kabupaten jembrana|melaya' => '51.01.04.2001',
            'kabupaten jembrana|jembrana' => '51.01.05.1001',

            'kabupaten tabanan|selemadeg' => '51.02.01.2001',
            'kabupaten tabanan|selemadeg timur' => '51.02.02.2001',
            'kabupaten tabanan|selemadeg barat' => '51.02.03.2001',
            'kabupaten tabanan|kerambitan' => '51.02.04.2001',
            'kabupaten tabanan|tabanan' => '51.02.05.2001', // CODE MATI nilai lama: '51.02.05.1001' (404 BMKG)
            'kabupaten tabanan|kediri' => '51.02.06.2001',
            'kabupaten tabanan|marga' => '51.02.07.2001',
            'kabupaten tabanan|penebel' => '51.02.08.2001',
            'kabupaten tabanan|baturiti' => '51.02.09.2001',
            'kabupaten tabanan|pupuan' => '51.02.10.2001',

            'kabupaten badung|kuta' => '51.03.01.1001',
            'kabupaten badung|mengwi' => '51.03.02.2001',
            'kabupaten badung|abiansemal' => '51.03.03.2001',
            'kabupaten badung|petang' => '51.03.04.2001',
            'kabupaten badung|kuta selatan' => '51.03.05.1001',
            'kabupaten badung|kuta utara' => '51.03.06.1001',

            'kabupaten gianyar|sukawati' => '51.04.01.2001',
            'kabupaten gianyar|blahbatuh' => '51.04.02.2001',
            'kabupaten gianyar|gianyar' => '51.04.03.1001',
            'kabupaten gianyar|tampaksiring' => '51.04.04.2001',
            'kabupaten gianyar|ubud' => '51.04.05.1005',
            'kabupaten gianyar|tegallalang' => '51.04.06.2001',
            'kabupaten gianyar|tegalalang' => '51.04.06.2001',
            'kabupaten gianyar|payangan' => '51.04.07.2001',

            'kabupaten klungkung|nusa penida' => '51.05.01.2001',
            'kabupaten klungkung|banjarangkan' => '51.05.02.2001',
            'kabupaten klungkung|klungkung' => '51.05.03.1001',
            'kabupaten klungkung|dawan' => '51.05.04.2001',

            'kabupaten bangli|susut' => '51.06.01.2001',
            'kabupaten bangli|bangli' => '51.06.02.1001',
            'kabupaten bangli|tembuku' => '51.06.03.2001',
            'kabupaten bangli|kintamani' => '51.06.04.2001',

            'kabupaten karangasem|rendang' => '51.07.01.2001',
            'kabupaten karangasem|sidemen' => '51.07.02.2001',
            'kabupaten karangasem|manggis' => '51.07.03.2001',
            'kabupaten karangasem|karangasem' => '51.07.04.1001',
            'kabupaten karangasem|abang' => '51.07.05.2001',
            'kabupaten karangasem|bebandem' => '51.07.06.2001',
            'kabupaten karangasem|selat' => '51.07.07.2001',
            'kabupaten karangasem|kubu' => '51.07.08.2001',

            'kabupaten buleleng|gerokgak' => '51.08.01.2001',
            'kabupaten buleleng|seririt' => '51.08.02.1001',
            'kabupaten buleleng|busungbiu' => '51.08.03.2001',
            'kabupaten buleleng|banjar' => '51.08.04.2001',
            'kabupaten buleleng|sukasada' => '51.08.05.2001',
            'kabupaten buleleng|buleleng' => '51.08.06.1001',
            'kabupaten buleleng|sawan' => '51.08.07.2001',
            'kabupaten buleleng|kubutambahan' => '51.08.08.2001',
            'kabupaten buleleng|tejakula' => '51.08.09.2001',

            'kota denpasar|denpasar selatan' => '51.71.01.1006',
            'kota denpasar|denpasar timur' => '51.71.02.1001',
            'kota denpasar|denpasar barat' => '51.71.03.1001',
            'kota denpasar|denpasar utara' => '51.71.04.1001',
        ];

        $key = $kabupatenKota.'|'.$kecamatan;

        return $map[$key] ?? null;
    }

    /**
     * Fallback jika kabupaten/kota kosong, tetapi kecamatan diisi.
     */
    private function resolveBmkgAdm4ByKecamatanOnly(string $kecamatan): ?string
    {
        if (! $kecamatan) {
            return null;
        }

        $map = [
            'negara' => '51.01.01.1001',
            'mendoyo' => '51.01.02.2001',
            'pekutatan' => '51.01.03.2001',
            'melaya' => '51.01.04.2001',
            'jembrana' => '51.01.05.1001',

            'selemadeg' => '51.02.01.2001',
            'selemadeg timur' => '51.02.02.2001',
            'selemadeg barat' => '51.02.03.2001',
            'kerambitan' => '51.02.04.2001',
            'tabanan' => '51.02.05.2001', // CODE MATI nilai lama: '51.02.05.1001' (404 BMKG)
            'kediri' => '51.02.06.2001',
            'marga' => '51.02.07.2001',
            'penebel' => '51.02.08.2001',
            'baturiti' => '51.02.09.2001',
            'pupuan' => '51.02.10.2001',

            'kuta' => '51.03.01.1001',
            'mengwi' => '51.03.02.2001',
            'abiansemal' => '51.03.03.2001',
            'petang' => '51.03.04.2001',
            'kuta selatan' => '51.03.05.1001',
            'kuta utara' => '51.03.06.1001',

            'sukawati' => '51.04.01.2001',
            'blahbatuh' => '51.04.02.2001',
            'gianyar' => '51.04.03.1001',
            'tampaksiring' => '51.04.04.2001',
            'ubud' => '51.04.05.1005',
            'tegallalang' => '51.04.06.2001',
            'tegalalang' => '51.04.06.2001',
            'payangan' => '51.04.07.2001',

            'nusa penida' => '51.05.01.2001',
            'banjarangkan' => '51.05.02.2001',
            'klungkung' => '51.05.03.1001',
            'dawan' => '51.05.04.2001',

            'susut' => '51.06.01.2001',
            'bangli' => '51.06.02.1001',
            'tembuku' => '51.06.03.2001',
            'kintamani' => '51.06.04.2001',

            'rendang' => '51.07.01.2001',
            'sidemen' => '51.07.02.2001',
            'manggis' => '51.07.03.2001',
            'karangasem' => '51.07.04.1001',
            'abang' => '51.07.05.2001',
            'bebandem' => '51.07.06.2001',
            'selat' => '51.07.07.2001',
            'kubu' => '51.07.08.2001',

            'gerokgak' => '51.08.01.2001',
            'seririt' => '51.08.02.1001',
            'busungbiu' => '51.08.03.2001',
            'banjar' => '51.08.04.2001',
            'sukasada' => '51.08.05.2001',
            'buleleng' => '51.08.06.1001',
            'sawan' => '51.08.07.2001',
            'kubutambahan' => '51.08.08.2001',
            'tejakula' => '51.08.09.2001',

            'denpasar selatan' => '51.71.01.1006',
            'denpasar timur' => '51.71.02.1001',
            'denpasar barat' => '51.71.03.1001',
            'denpasar utara' => '51.71.04.1001',
        ];

        return $map[$kecamatan] ?? null;
    }

    /**
     * Fallback jika user memilih kabupaten/kota,
     * tetapi kecamatan dikosongkan atau typo.
     */
    private function resolveFallbackBmkgAdm4ByKabupaten(string $kabupatenKota): ?string
    {
        $map = [
            'kabupaten jembrana' => '51.01.01.1001',
            'kabupaten tabanan' => '51.02.05.2001', // CODE MATI nilai lama: '51.02.05.1001' (404 BMKG)
            'kabupaten badung' => '51.03.01.1001',
            'kabupaten gianyar' => '51.04.05.1005',
            'kabupaten klungkung' => '51.05.03.1001',
            'kabupaten bangli' => '51.06.02.1001',
            'kabupaten karangasem' => '51.07.04.1001',
            'kabupaten buleleng' => '51.08.06.1001',
            'kota denpasar' => '51.71.01.1006',
        ];

        return $map[$kabupatenKota] ?? null;
    }

    /**
     * Resolve ADM3 BMKG berdasarkan kabupaten/kota dan kecamatan.
     *
     * ADM3 = level kecamatan.
     */
    private function resolveBmkgAdm3(string $kabupatenKota, string $kecamatan): ?string
    {
        $map = [
            'kabupaten jembrana|negara' => '51.01.01',
            'kabupaten jembrana|mendoyo' => '51.01.02',
            'kabupaten jembrana|pekutatan' => '51.01.03',
            'kabupaten jembrana|melaya' => '51.01.04',
            'kabupaten jembrana|jembrana' => '51.01.05',

            'kabupaten tabanan|selemadeg' => '51.02.01',
            'kabupaten tabanan|selemadeg timur' => '51.02.02',
            'kabupaten tabanan|selemadeg barat' => '51.02.03',
            'kabupaten tabanan|kerambitan' => '51.02.04',
            'kabupaten tabanan|tabanan' => '51.02.05',
            'kabupaten tabanan|kediri' => '51.02.06',
            'kabupaten tabanan|marga' => '51.02.07',
            'kabupaten tabanan|penebel' => '51.02.08',
            'kabupaten tabanan|baturiti' => '51.02.09',
            'kabupaten tabanan|pupuan' => '51.02.10',

            'kabupaten badung|kuta' => '51.03.01',
            'kabupaten badung|mengwi' => '51.03.02',
            'kabupaten badung|abiansemal' => '51.03.03',
            'kabupaten badung|petang' => '51.03.04',
            'kabupaten badung|kuta selatan' => '51.03.05',
            'kabupaten badung|kuta utara' => '51.03.06',

            'kabupaten gianyar|sukawati' => '51.04.01',
            'kabupaten gianyar|blahbatuh' => '51.04.02',
            'kabupaten gianyar|gianyar' => '51.04.03',
            'kabupaten gianyar|tampaksiring' => '51.04.04',
            'kabupaten gianyar|ubud' => '51.04.05',
            'kabupaten gianyar|tegallalang' => '51.04.06',
            'kabupaten gianyar|tegalalang' => '51.04.06',
            'kabupaten gianyar|payangan' => '51.04.07',

            'kabupaten klungkung|nusa penida' => '51.05.01',
            'kabupaten klungkung|banjarangkan' => '51.05.02',
            'kabupaten klungkung|klungkung' => '51.05.03',
            'kabupaten klungkung|dawan' => '51.05.04',

            'kabupaten bangli|susut' => '51.06.01',
            'kabupaten bangli|bangli' => '51.06.02',
            'kabupaten bangli|tembuku' => '51.06.03',
            'kabupaten bangli|kintamani' => '51.06.04',

            'kabupaten karangasem|rendang' => '51.07.01',
            'kabupaten karangasem|sidemen' => '51.07.02',
            'kabupaten karangasem|manggis' => '51.07.03',
            'kabupaten karangasem|karangasem' => '51.07.04',
            'kabupaten karangasem|abang' => '51.07.05',
            'kabupaten karangasem|bebandem' => '51.07.06',
            'kabupaten karangasem|selat' => '51.07.07',
            'kabupaten karangasem|kubu' => '51.07.08',

            'kabupaten buleleng|gerokgak' => '51.08.01',
            'kabupaten buleleng|seririt' => '51.08.02',
            'kabupaten buleleng|busungbiu' => '51.08.03',
            'kabupaten buleleng|banjar' => '51.08.04',
            'kabupaten buleleng|sukasada' => '51.08.05',
            'kabupaten buleleng|buleleng' => '51.08.06',
            'kabupaten buleleng|sawan' => '51.08.07',
            'kabupaten buleleng|kubutambahan' => '51.08.08',
            'kabupaten buleleng|tejakula' => '51.08.09',

            'kota denpasar|denpasar selatan' => '51.71.01',
            'kota denpasar|denpasar timur' => '51.71.02',
            'kota denpasar|denpasar barat' => '51.71.03',
            'kota denpasar|denpasar utara' => '51.71.04',
        ];

        $key = $kabupatenKota.'|'.$kecamatan;

        if (isset($map[$key])) {
            return $map[$key];
        }

        $fallbackByKabupaten = [
            'kabupaten jembrana' => '51.01.01',
            'kabupaten tabanan' => '51.02.05',
            'kabupaten badung' => '51.03.01',
            'kabupaten gianyar' => '51.04.05',
            'kabupaten klungkung' => '51.05.03',
            'kabupaten bangli' => '51.06.02',
            'kabupaten karangasem' => '51.07.04',
            'kabupaten buleleng' => '51.08.06',
            'kota denpasar' => '51.71.01',
        ];

        return $fallbackByKabupaten[$kabupatenKota] ?? null;
    }

    /**
     * Ambil ADM4 pertama dari halaman BMKG berdasarkan ADM3 kecamatan.
     *
     * Ini hanya fallback jika mapping manual belum mencakup input user.
     */
    private function resolveFirstAdm4FromBmkgAdm3(string $adm3): ?string
    {
        return Cache::remember(
            key: 'bmkg_adm4_from_adm3_'.str_replace('.', '_', $adm3),
            ttl: now()->addDays(30),
            callback: function () use ($adm3): ?string {
                $url = 'https://www.bmkg.go.id/cuaca/prakiraan-cuaca/'.$adm3;

                try {
                    $response = Http::timeout(15)
                        ->accept('text/html')
                        ->get($url);
                } catch (Throwable) {
                    return null;
                }

                if (! $response->successful()) {
                    return null;
                }

                $html = $response->body();

                $escapedAdm3 = preg_quote($adm3, '#');

                $patterns = [
                    "#/cuaca/prakiraan-cuaca/({$escapedAdm3}\\.(?:10|20|30)[0-9]{2})#",
                    "#href=[\"'][^\"']*({$escapedAdm3}\\.(?:10|20|30)[0-9]{2})#i",
                    "#({$escapedAdm3}\\.(?:10|20|30)[0-9]{2})#",
                ];

                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $html, $matches)) {
                        return $matches[1];
                    }
                }

                return null;
            }
        );
    }

    /**
     * Scan kandidat ADM4 umum.
     *
     * Dipakai hanya sebagai fallback terakhir jika map manual dan halaman BMKG gagal.
     */
    private function resolveAdm4ByCandidateScan(string $adm3): ?string
    {
        return Cache::remember(
            key: 'bmkg_adm4_scan_'.str_replace('.', '_', $adm3),
            ttl: now()->addDays(30),
            callback: function () use ($adm3): ?string {
                foreach ($this->buildCommonAdm4Candidates($adm3) as $adm4) {
                    if ($this->isValidBmkgAdm4($adm4)) {
                        return $adm4;
                    }
                }

                return null;
            }
        );
    }

    /**
     * Kandidat umum:
     * - 1001 - 1020 untuk kelurahan
     * - 2001 - 2040 untuk desa
     * - 3001 - 3010 sebagai cadangan
     *
     * @return array<int, string>
     */
    private function buildCommonAdm4Candidates(string $adm3): array
    {
        $suffixes = [];

        foreach (range(1001, 1020) as $suffix) {
            $suffixes[] = (string) $suffix;
        }

        foreach (range(2001, 2040) as $suffix) {
            $suffixes[] = (string) $suffix;
        }

        foreach (range(3001, 3010) as $suffix) {
            $suffixes[] = (string) $suffix;
        }

        return array_map(
            fn (string $suffix): string => $adm3.'.'.$suffix,
            $suffixes
        );
    }

    /**
     * Validasi ADM4 ke API publik BMKG.
     *
     * Dipakai untuk semua sumber ADM4:
     * - mapping manual,
     * - fallback kecamatan,
     * - fallback kabupaten,
     * - hasil parsing halaman BMKG,
     * - dan hasil scan kandidat.
     *
     * Catatan:
     * Cache key memakai versi v2 agar tidak bentrok dengan hasil cache lama.
     * Hasil valid disimpan lebih lama, sedangkan hasil invalid disimpan lebih
     * pendek supaya jika BMKG sempat error sementara, sistem tidak terkunci
     * terlalu lama pada status invalid.
     */
    private function isValidBmkgAdm4(string $adm4): bool
    {
        $adm4 = mb_trim($adm4);

        if ($adm4 === '') {
            return false;
        }

        $cacheKey = 'bmkg_adm4_valid_v2_'.str_replace('.', '_', $adm4);

        $cached = Cache::get($cacheKey);

        if (is_bool($cached)) {
            return $cached;
        }

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->get('https://api.bmkg.go.id/publik/prakiraan-cuaca', [
                    'adm4' => $adm4,
                ]);
        } catch (Throwable) {
            Cache::put($cacheKey, false, now()->addHours(6));

            return false;
        }

        if (! $response->successful()) {
            Cache::put($cacheKey, false, now()->addHours(12));

            return false;
        }

        try {
            $json = $response->json();
        } catch (Throwable) {
            Cache::put($cacheKey, false, now()->addHours(12));

            return false;
        }

        if (! is_array($json)) {
            Cache::put($cacheKey, false, now()->addHours(12));

            return false;
        }

        $isValid = data_get($json, 'lokasi') !== null
            || data_get($json, 'data.0') !== null;

        Cache::put(
            $cacheKey,
            $isValid,
            $isValid ? now()->addDays(30) : now()->addHours(12)
        );

        return $isValid;
    }

    /*
    |--------------------------------------------------------------------------
    | CODE MATI - isValidBmkgAdm4 lama sebelum cache valid/invalid terpisah
    |--------------------------------------------------------------------------
    |
    | Logic lama sengaja DIKOMENTARKAN, bukan dihapus.
    | Perbedaan utama:
    | - Dulu memakai Cache::remember() dengan TTL 30 hari untuk semua hasil.
    | - Jika API BMKG sedang error sementara, hasil false bisa tersimpan terlalu lama.
    |
    | Sekarang:
    | - Cache key dibuat versi v2.
    | - Hasil valid disimpan 30 hari.
    | - Hasil invalid hanya disimpan 6-12 jam agar bisa pulih otomatis saat BMKG kembali normal.
    |
    | Potongan logic lama yang dimatikan:
    |
    | return Cache::remember(
    |     key: 'bmkg_adm4_valid_'.str_replace('.', '_', $adm4),
    |     ttl: now()->addDays(30),
    |     callback: function () use ($adm4): bool {
    |         try {
    |             $response = Http::timeout(8)
    |                 ->acceptJson()
    |                 ->get('https://api.bmkg.go.id/publik/prakiraan-cuaca', [
    |                     'adm4' => $adm4,
    |                 ]);
    |         } catch (Throwable) {
    |             return false;
    |         }
    |
    |         if (! $response->successful()) {
    |             return false;
    |         }
    |
    |         try {
    |             $json = $response->json();
    |         } catch (Throwable) {
    |             return false;
    |         }
    |
    |         if (! is_array($json)) {
    |             return false;
    |         }
    |
    |         return data_get($json, 'lokasi') !== null
    |             || data_get($json, 'data.0') !== null;
    |     }
    | );
    |
    */

    /**
     * Simpan log jika request ke FastAPI berhasil.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $result
     */
    private function storeSuccessLog(array $payload, array $result, int $responseTimeMs): void
    {
        RecommendationLog::query()->create([
            'user_id' => Auth::id(),
            'weather_source' => data_get($result, 'weather_source'),
            'weather_used' => data_get($result, 'weather_used'),
            'total_candidates' => data_get($result, 'total_candidates'),
            'response_time_ms' => $responseTimeMs,
            'request_payload' => $payload,
            'response_payload' => $result,
            'status' => 'success',
            'error_message' => null,
        ]);
    }

    /**
     * Simpan log jika request ke FastAPI gagal.
     *
     * @param  array<string, mixed>  $payload
     */
    private function storeFailedLog(array $payload, string $errorMessage, int $responseTimeMs): void
    {
        RecommendationLog::query()->create([
            'user_id' => Auth::id(),
            'response_time_ms' => $responseTimeMs,
            'request_payload' => $payload,
            'response_payload' => null,
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    private function calculateResponseTime(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    /**
     * Daftar lokasi valid yang dipakai form.
     *
     * Format value form dibuat menjadi:
     * kabupaten_kota|kecamatan
     *
     * Dengan cara ini user tidak bisa memilih kombinasi salah seperti:
     * Kabupaten Buleleng + Tegallalang.
     *
     * @return array<string, array<int, string>>
     */
    private function locationOptions(): array
    {
        return [
            'Kabupaten Gianyar' => ['Ubud', 'Gianyar', 'Tegallalang', 'Blahbatuh', 'Tampaksiring', 'Sukawati', 'Payangan'],
            'Kabupaten Badung' => ['Kuta', 'Kuta Selatan', 'Kuta Utara', 'Mengwi', 'Abiansemal', 'Petang'],
            'Kabupaten Tabanan' => ['Tabanan', 'Kediri', 'Penebel', 'Baturiti', 'Pupuan', 'Selemadeg Timur', 'Selemadeg Barat', 'Kerambitan', 'Marga'],
            'Kabupaten Buleleng' => ['Buleleng', 'Gerokgak', 'Seririt', 'Busungbiu', 'Banjar', 'Sukasada', 'Sawan', 'Kubutambahan', 'Tejakula'],
            'Kabupaten Karangasem' => ['Karangasem', 'Rendang', 'Sidemen', 'Manggis', 'Abang', 'Bebandem', 'Selat', 'Kubu'],
            'Kabupaten Bangli' => ['Kintamani', 'Bangli', 'Susut', 'Tembuku'],
            'Kabupaten Klungkung' => ['Nusa Penida', 'Klungkung', 'Banjarangkan', 'Dawan'],
            'Kabupaten Jembrana' => ['Negara', 'Jembrana', 'Mendoyo', 'Melaya', 'Pekutatan'],
            'Kota Denpasar' => ['Denpasar Selatan', 'Denpasar Barat', 'Denpasar Timur', 'Denpasar Utara'],
        ];
    }

    /**
     * Value lokasi yang valid untuk Rule::in().
     *
     * @return array<int, string>
     */
    private function locationPairValues(): array
    {
        $values = [''];

        foreach ($this->locationOptions() as $kabupatenKota => $kecamatans) {
            $values[] = $kabupatenKota.'|';

            foreach ($kecamatans as $kecamatan) {
                $values[] = $kabupatenKota.'|'.$kecamatan;
            }
        }

        return $values;
    }

    /**
     * Mengambil kabupaten/kota dan kecamatan dari input lokasi_wisata.
     *
     * Jika lokasi_wisata kosong, sistem tetap menerima field lama
     * kabupaten_kota dan kecamatan sebagai fallback.
     *
     * @param  array<string, mixed>  $validated
     * @return array{0: ?string, 1: ?string}
     */
    private function resolvePostedLocation(array $validated): array
    {
        $locationPair = $this->nullableString($validated['lokasi_wisata'] ?? null);

        if ($locationPair) {
            return $this->parseLocationPair($locationPair);
        }

        return [
            $this->nullableString($validated['kabupaten_kota'] ?? null),
            $this->nullableString($validated['kecamatan'] ?? null),
        ];
    }

    /**
     * Parse value "Kabupaten Gianyar|Ubud" menjadi payload ML.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function parseLocationPair(string $locationPair): array
    {
        [$kabupatenKota, $kecamatan] = array_pad(explode('|', $locationPair, 2), 2, null);

        $kabupatenKota = $this->nullableString($kabupatenKota);
        $kecamatan = $this->nullableString($kecamatan);

        if (! $kabupatenKota) {
            return [null, null];
        }

        $options = $this->locationOptions();

        if (! array_key_exists($kabupatenKota, $options)) {
            return [null, null];
        }

        if ($kecamatan && ! in_array($kecamatan, $options[$kabupatenKota], true)) {
            $kecamatan = null;
        }

        return [$kabupatenKota, $kecamatan];
    }

    /**
     * Ubah keyword dari string menjadi array.
     *
     * Contoh:
     * "pantai, sunset" menjadi ["pantai", "sunset"]
     *
     * @return array<int, string>
     */
    private function parseKeywords(?string $keywords): array
    {
        if (! $keywords) {
            return [];
        }

        return collect(explode(',', $keywords))
            ->map(fn (string $keyword): string => mb_trim($keyword))
            ->filter()
            ->values()
            ->all();
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = mb_trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function toBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Normalisasi input lokasi.
     *
     * Contoh:
     * - "Kabupaten Gianyar" => "kabupaten gianyar"
     * - "Kab. Gianyar" => "kabupaten gianyar"
     * - "Kecamatan Ubud" => "ubud"
     * - "kec. ubud" => "ubud"
     */
    private function normalizeLocation(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = mb_strtolower(mb_trim((string) $value));

        $value = str_replace(
            ['.', ',', ';', ':'],
            ' ',
            $value
        );

        $value = str_replace([
            'kabupaten ',
            'kab ',
            'kab. ',
            'kota ',
            'kecamatan ',
            'kec ',
            'kec. ',
        ], [
            'kabupaten ',
            'kabupaten ',
            'kabupaten ',
            'kota ',
            '',
            '',
            '',
        ], $value);

        $value = preg_replace('/\s+/', ' ', $value) ?: '';

        return mb_trim($value);
    }

    /**
     * Normalisasi nama kabupaten/kota supaya input seperti:
     * - "Badung" menjadi "kabupaten badung"
     * - "Denpasar" menjadi "kota denpasar"
     */
    private function normalizeKabupatenKota(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if ($value === 'denpasar') {
            return 'kota denpasar';
        }

        $regencies = [
            'jembrana',
            'tabanan',
            'badung',
            'gianyar',
            'klungkung',
            'bangli',
            'karangasem',
            'buleleng',
        ];

        if (in_array($value, $regencies, true)) {
            return 'kabupaten '.$value;
        }

        return $value;
    }

    private function ensureViewExists(): void
    {
        if (! ViewFacade::exists(self::VIEW_PATH)) {
            throw new RuntimeException(
                'View '.self::VIEW_PATH.' tidak ditemukan. Buat file: resources/views/tourhub/recommendation/index.blade.php'
            );
        }
    }
}
