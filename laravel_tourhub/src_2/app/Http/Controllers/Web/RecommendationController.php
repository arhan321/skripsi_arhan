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

        /*
         * Payload dibuat sebelum try-catch ML API.
         * Jika ADM4 belum bisa ditemukan, error akan tampil sebagai error validasi form.
         */
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
                'bmkg_adm4' => 'Kode ADM4 BMKG belum tersedia untuk wilayah ini. Coba isi kecamatan yang valid, misalnya Ubud, Kuta, Denpasar Barat, Buleleng, Tabanan, atau matikan opsi Gunakan BMKG.',
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

            /*
             * Jika BMKG aktif, FastAPI akan memakai bmkg_adm4.
             * Field weather tetap dikirim sebagai fallback/manual context.
             */
            'weather' => $this->nullableString($validated['weather'] ?? null),
            'visit_day' => $this->nullableString($validated['visit_day'] ?? null),

            'is_high_season' => $this->toBoolean($validated['is_high_season'] ?? false),
            'use_bmkg' => $useBmkg,
            'bmkg_adm4' => $bmkgAdm4,
        ];
    }

    /**
     * Resolve ADM4 BMKG berdasarkan kabupaten/kota dan kecamatan.
     *
     * Dataset kamu hanya punya level kecamatan.
     * BMKG butuh level desa/kelurahan atau ADM4.
     *
     * Alur:
     * 1. Cek manual ADM4 map untuk lokasi yang sudah pasti.
     * 2. Kalau tidak ada, cari ADM3 kecamatan dari map.
     * 3. Buka halaman BMKG kecamatan.
     * 4. Ambil ADM4 pertama dari halaman tersebut.
     *
     * @param array<string, mixed> $validated
     */
    private function resolveBmkgAdm4(array $validated): ?string
    {
        $kabupatenKota = $this->normalizeLocation($validated['kabupaten_kota'] ?? null);
        $kecamatan = $this->normalizeLocation($validated['kecamatan'] ?? null);

        /*
         * Manual ADM4 yang sudah pasti.
         */
        $manualAdm4Map = [
            'kabupaten gianyar|ubud' => '51.04.05.1005',
        ];

        $key = $kabupatenKota . '|' . $kecamatan;

        if (isset($manualAdm4Map[$key])) {
            return $manualAdm4Map[$key];
        }

        $adm3 = $this->resolveBmkgAdm3(
            kabupatenKota: $kabupatenKota,
            kecamatan: $kecamatan,
        );

        if (! $adm3) {
            return null;
        }

        return $this->resolveFirstAdm4FromBmkgAdm3($adm3);
    }

    /**
     * Resolve ADM3 BMKG berdasarkan kabupaten/kota dan kecamatan.
     *
     * ADM3 = level kecamatan.
     *
     * Mapping ini disesuaikan dengan wilayah Bali:
     * 51.01 = Jembrana
     * 51.02 = Tabanan
     * 51.03 = Badung
     * 51.04 = Gianyar
     * 51.05 = Klungkung
     * 51.06 = Bangli
     * 51.07 = Karangasem
     * 51.08 = Buleleng
     * 51.71 = Kota Denpasar
     */
    private function resolveBmkgAdm3(string $kabupatenKota, string $kecamatan): ?string
    {
        $map = [
            /*
            |--------------------------------------------------------------------------
            | Kabupaten Jembrana
            |--------------------------------------------------------------------------
            */
            'kabupaten jembrana|negara' => '51.01.01',
            'kabupaten jembrana|mendoyo' => '51.01.02',
            'kabupaten jembrana|pekutatan' => '51.01.03',
            'kabupaten jembrana|melaya' => '51.01.04',
            'kabupaten jembrana|jembrana' => '51.01.05',

            /*
            |--------------------------------------------------------------------------
            | Kabupaten Tabanan
            |--------------------------------------------------------------------------
            */
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

            /*
            |--------------------------------------------------------------------------
            | Kabupaten Badung
            |--------------------------------------------------------------------------
            */
            'kabupaten badung|kuta' => '51.03.01',
            'kabupaten badung|mengwi' => '51.03.02',
            'kabupaten badung|abiansemal' => '51.03.03',
            'kabupaten badung|petang' => '51.03.04',
            'kabupaten badung|kuta selatan' => '51.03.05',
            'kabupaten badung|kuta utara' => '51.03.06',

            /*
            |--------------------------------------------------------------------------
            | Kabupaten Gianyar
            |--------------------------------------------------------------------------
            */
            'kabupaten gianyar|sukawati' => '51.04.01',
            'kabupaten gianyar|blahbatuh' => '51.04.02',
            'kabupaten gianyar|gianyar' => '51.04.03',
            'kabupaten gianyar|tampaksiring' => '51.04.04',
            'kabupaten gianyar|ubud' => '51.04.05',
            'kabupaten gianyar|tegallalang' => '51.04.06',
            'kabupaten gianyar|tegalalang' => '51.04.06',
            'kabupaten gianyar|payangan' => '51.04.07',

            /*
            |--------------------------------------------------------------------------
            | Kabupaten Klungkung
            |--------------------------------------------------------------------------
            */
            'kabupaten klungkung|nusa penida' => '51.05.01',
            'kabupaten klungkung|banjarangkan' => '51.05.02',
            'kabupaten klungkung|klungkung' => '51.05.03',
            'kabupaten klungkung|dawan' => '51.05.04',

            /*
            |--------------------------------------------------------------------------
            | Kabupaten Bangli
            |--------------------------------------------------------------------------
            */
            'kabupaten bangli|susut' => '51.06.01',
            'kabupaten bangli|bangli' => '51.06.02',
            'kabupaten bangli|tembuku' => '51.06.03',
            'kabupaten bangli|kintamani' => '51.06.04',

            /*
            |--------------------------------------------------------------------------
            | Kabupaten Karangasem
            |--------------------------------------------------------------------------
            */
            'kabupaten karangasem|rendang' => '51.07.01',
            'kabupaten karangasem|sidemen' => '51.07.02',
            'kabupaten karangasem|manggis' => '51.07.03',
            'kabupaten karangasem|karangasem' => '51.07.04',
            'kabupaten karangasem|abang' => '51.07.05',
            'kabupaten karangasem|bebandem' => '51.07.06',
            'kabupaten karangasem|selat' => '51.07.07',
            'kabupaten karangasem|kubu' => '51.07.08',

            /*
            |--------------------------------------------------------------------------
            | Kabupaten Buleleng
            |--------------------------------------------------------------------------
            */
            'kabupaten buleleng|gerokgak' => '51.08.01',
            'kabupaten buleleng|seririt' => '51.08.02',
            'kabupaten buleleng|busungbiu' => '51.08.03',
            'kabupaten buleleng|banjar' => '51.08.04',
            'kabupaten buleleng|sukasada' => '51.08.05',
            'kabupaten buleleng|buleleng' => '51.08.06',
            'kabupaten buleleng|sawan' => '51.08.07',
            'kabupaten buleleng|kubutambahan' => '51.08.08',
            'kabupaten buleleng|tejakula' => '51.08.09',

            /*
            |--------------------------------------------------------------------------
            | Kota Denpasar
            |--------------------------------------------------------------------------
            */
            'kota denpasar|denpasar selatan' => '51.71.01',
            'kota denpasar|denpasar timur' => '51.71.02',
            'kota denpasar|denpasar barat' => '51.71.03',
            'kota denpasar|denpasar utara' => '51.71.04',
        ];

        $key = $kabupatenKota . '|' . $kecamatan;

        if (isset($map[$key])) {
            return $map[$key];
        }

        /*
         * Fallback jika user hanya memilih kabupaten/kota
         * dan kecamatan dikosongkan.
         */
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
     * Contoh:
     * ADM3 Ubud = 51.04.05
     * Hasil ADM4 pertama bisa berupa 51.04.05.1005
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
                        ->acceptHtml()
                        ->get($url);
                } catch (Throwable) {
                    return null;
                }

                if (! $response->successful()) {
                    return null;
                }

                $html = $response->body();

                /*
                 * Cari pola:
                 * /cuaca/prakiraan-cuaca/51.04.05.1005
                 */
                $pattern = '#/cuaca/prakiraan-cuaca/(' . preg_quote($adm3, '#') . '\.[0-9]{4})#';

                if (preg_match($pattern, $html, $matches)) {
                    return $matches[1];
                }

                /*
                 * Fallback kedua:
                 * Cari kode ADM4 mentah di HTML.
                 */
                $fallbackPattern = '#(' . preg_quote($adm3, '#') . '\.[0-9]{4})#';

                if (preg_match($fallbackPattern, $html, $matches)) {
                    return $matches[1];
                }

                return null;
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
     * - "Gianyar" => "gianyar"
     * - "Kecamatan Ubud" => "ubud"
     * - "kec. ubud" => "ubud"
     */
    private function normalizeLocation(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = mb_strtolower(trim((string) $value));

        $value = str_replace([
            'kec. ',
            'kec ',
            'kecamatan ',
        ], '', $value);

        $value = preg_replace('/\s+/', ' ', $value) ?: '';

        return trim($value);
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