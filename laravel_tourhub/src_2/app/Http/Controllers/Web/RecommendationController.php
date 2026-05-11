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

        $validated = $request->validate([
            'kategori_preferensi' => ['required', 'array', 'min:1'],
            'kategori_preferensi.*' => [
                'required',
                Rule::in(['Alam', 'Budaya', 'Rekreasi', 'Umum']),
            ],

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
     * Jika use_bmkg = true dan bmkg_adm4 kosong,
     * sistem otomatis mencari ADM4 berdasarkan kabupaten/kota + kecamatan.
     *
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function buildPayload(array $validated): array
    {
        $useBmkg = $this->toBoolean($validated['use_bmkg'] ?? false);

        $bmkgAdm4 = $this->nullableString($validated['bmkg_adm4'] ?? null);

        if ($useBmkg && ! $bmkgAdm4) {
            $bmkgAdm4 = $this->resolveBmkgAdm4($validated);
        }

        if ($useBmkg && ! $bmkgAdm4) {
            throw ValidationException::withMessages([
                'bmkg_adm4' => 'Kode ADM4 BMKG belum tersedia untuk wilayah ini. Coba isi kecamatan yang valid atau matikan opsi Gunakan BMKG.',
            ]);
        }

        return [
            'kategori_preferensi' => $validated['kategori_preferensi'],

            'kabupaten_kota' => $this->nullableString($validated['kabupaten_kota'] ?? null),
            'kecamatan' => $this->nullableString($validated['kecamatan'] ?? null),

            'keywords' => $this->parseKeywords($validated['keywords'] ?? null),

            'min_rating' => isset($validated['min_rating'])
                ? (float) $validated['min_rating']
                : null,

            'top_n' => (int) $validated['top_n'],

            'weather' => $this->nullableString($validated['weather'] ?? null),
            'visit_day' => $this->nullableString($validated['visit_day'] ?? null),

            'is_high_season' => $this->toBoolean($validated['is_high_season'] ?? false),
            'use_bmkg' => $useBmkg,
            'bmkg_adm4' => $useBmkg ? $bmkgAdm4 : null,
        ];
    }

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
     * @param array<string, mixed> $validated
     */
    private function resolveBmkgAdm4(array $validated): ?string
    {
        $kabupatenKota = $this->normalizeKabupatenKota(
            $this->normalizeLocation($validated['kabupaten_kota'] ?? null)
        );

        $kecamatan = $this->normalizeLocation($validated['kecamatan'] ?? null);

        $manualAdm4 = $this->resolveManualBmkgAdm4(
            kabupatenKota: $kabupatenKota,
            kecamatan: $kecamatan,
        );

        if ($manualAdm4) {
            return $manualAdm4;
        }

        $adm4ByKecamatanOnly = $this->resolveBmkgAdm4ByKecamatanOnly($kecamatan);

        if ($adm4ByKecamatanOnly) {
            return $adm4ByKecamatanOnly;
        }

        $adm4ByKabupaten = $this->resolveFallbackBmkgAdm4ByKabupaten($kabupatenKota);

        if ($adm4ByKabupaten) {
            return $adm4ByKabupaten;
        }

        $adm3 = $this->resolveBmkgAdm3(
            kabupatenKota: $kabupatenKota,
            kecamatan: $kecamatan,
        );

        if ($adm3) {
            $adm4FromBmkgPage = $this->resolveFirstAdm4FromBmkgAdm3($adm3);

            if ($adm4FromBmkgPage) {
                return $adm4FromBmkgPage;
            }

            $adm4FromCandidateScan = $this->resolveAdm4ByCandidateScan($adm3);

            if ($adm4FromCandidateScan) {
                return $adm4FromCandidateScan;
            }
        }

        return self::DEFAULT_BALI_ADM4;
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
            'kabupaten tabanan|tabanan' => '51.02.05.1001',
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

        $key = $kabupatenKota . '|' . $kecamatan;

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
            'tabanan' => '51.02.05.1001',
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
            'kabupaten tabanan' => '51.02.05.1001',
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

        $key = $kabupatenKota . '|' . $kecamatan;

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
            key: 'bmkg_adm4_from_adm3_' . str_replace('.', '_', $adm3),
            ttl: now()->addDays(30),
            callback: function () use ($adm3): ?string {
                $url = 'https://www.bmkg.go.id/cuaca/prakiraan-cuaca/' . $adm3;

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
            key: 'bmkg_adm4_scan_' . str_replace('.', '_', $adm3),
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
            fn (string $suffix): string => $adm3 . '.' . $suffix,
            $suffixes
        );
    }

    /**
     * Validasi ADM4 ke API publik BMKG.
     *
     * Ini hanya dipakai pada fallback scan, bukan pada mapping manual.
     */
    private function isValidBmkgAdm4(string $adm4): bool
    {
        return Cache::remember(
            key: 'bmkg_adm4_valid_' . str_replace('.', '_', $adm4),
            ttl: now()->addDays(30),
            callback: function () use ($adm4): bool {
                try {
                    $response = Http::timeout(8)
                        ->acceptJson()
                        ->get('https://api.bmkg.go.id/publik/prakiraan-cuaca', [
                            'adm4' => $adm4,
                        ]);
                } catch (Throwable) {
                    return false;
                }

                if (! $response->successful()) {
                    return false;
                }

                try {
                    $json = $response->json();
                } catch (Throwable) {
                    return false;
                }

                if (! is_array($json)) {
                    return false;
                }

                return data_get($json, 'lokasi') !== null
                    || data_get($json, 'data.0') !== null;
            }
        );
    }

    /**
     * Simpan log jika request ke FastAPI berhasil.
     *
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $result
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
     * @param array<string, mixed> $payload
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
            ->map(fn (string $keyword): string => trim($keyword))
            ->filter()
            ->values()
            ->all();
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

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

        $value = mb_strtolower(trim((string) $value));

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

        return trim($value);
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
            return 'kabupaten ' . $value;
        }

        return $value;
    }

    private function ensureViewExists(): void
    {
        if (! ViewFacade::exists(self::VIEW_PATH)) {
            throw new RuntimeException(
                'View ' . self::VIEW_PATH . ' tidak ditemukan. Buat file: resources/views/tourhub/recommendation/index.blade.php'
            );
        }
    }
}
