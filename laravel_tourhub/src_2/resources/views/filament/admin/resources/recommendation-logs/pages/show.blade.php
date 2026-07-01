<x-filament-panels::page>
    @php
        $log = $this->record ?? (isset($record) ? $record : null);

        $requestUser = $log?->user;
        $requestUserName = $requestUser?->name ?: 'Guest / Unknown';
        $requestUserEmail = $requestUser?->email ?: '-';
        $requestUserId = $requestUser?->id ?: '-';
        $requestUserCreatedAt = $requestUser?->created_at?->format('d M Y H:i') ?? '-';
        $requestUserVerifiedAt = $requestUser?->email_verified_at?->format('d M Y H:i') ?? '-';
        $requestUserRoles = '-';

        if ($requestUser && method_exists($requestUser, 'roles')) {
            try {
                $roleText = $requestUser->roles?->pluck('name')->filter()->implode(', ');
                $requestUserRoles = filled($roleText) ? $roleText : '-';
            } catch (\Throwable $exception) {
                $requestUserRoles = '-';
            }
        }

        $requestUserInitials = collect(preg_split('/\s+/', trim((string) $requestUserName)))
            ->filter()
            ->take(2)
            ->map(fn ($word) => strtoupper(substr((string) $word, 0, 1)))
            ->implode('');

        $requestUserInitials = filled($requestUserInitials) ? $requestUserInitials : 'U';

        $requestPayload = (array) ($log?->request_payload ?? []);
        $responsePayload = (array) ($log?->response_payload ?? []);

        $recommendations = collect(data_get($responsePayload, 'recommendations', []))
            ->sortByDesc(fn ($item) => (float) data_get($item, 'final_score', 0))
            ->values();

        $bestRecommendation = $recommendations->first();

        $formatValue = function (mixed $value, string $empty = '-') {
            if ($value === null || $value === '') {
                return $empty;
            }

            if (is_bool($value)) {
                return $value ? 'Ya' : 'Tidak';
            }

            return $value;
        };

        $formatDecimal = function (mixed $value, int $digits = 4): string {
            if ($value === null || $value === '' || ! is_numeric($value)) {
                return '-';
            }

            return rtrim(rtrim(number_format((float) $value, $digits, '.', ''), '0'), '.');
        };

        $formatNumber = function (mixed $value): string {
            if ($value === null || $value === '' || ! is_numeric($value)) {
                return '-';
            }

            return number_format((float) $value, 0, ',', '.');
        };

        $humanize = function (mixed $value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            if (is_bool($value)) {
                return $value ? 'Ya' : 'Tidak';
            }

            return ucwords(str_replace('_', ' ', (string) $value));
        };

        $weatherLabel = function (mixed $value) use ($humanize): string {
            $normalized = strtolower((string) $value);

            return match ($normalized) {
                'cerah' => 'Cerah',
                'hujan' => 'Hujan',
                'hujan ringan' => 'Hujan Ringan',
                'hujan sedang' => 'Hujan Sedang',
                'hujan lebat' => 'Hujan Lebat',
                'mendung' => 'Mendung',
                'berawan' => 'Berawan',
                'unknown', '' => 'Tidak Diketahui',
                default => $humanize($value),
            };
        };

        $categories = (array) data_get($requestPayload, 'kategori_preferensi', []);
        $keywords = (array) data_get($requestPayload, 'keywords', []);

        $kabupatenKota = data_get($requestPayload, 'kabupaten_kota');
        $kecamatan = data_get($requestPayload, 'kecamatan');
        $minRating = data_get($requestPayload, 'min_rating');
        $topN = data_get($requestPayload, 'top_n');
        $visitDay = data_get($requestPayload, 'visit_day');
        $useBmkg = (bool) data_get($requestPayload, 'use_bmkg', false);
        $bmkgAdm4 = data_get($requestPayload, 'bmkg_adm4');
        $isHighSeason = (bool) data_get($requestPayload, 'is_high_season', false);

        $clientPlatform = data_get($requestPayload, 'platform')
            ?: data_get($requestPayload, 'client_platform')
            ?: data_get($requestPayload, 'device')
            ?: data_get($requestPayload, 'client')
            ?: '-';

        $clientVersion = data_get($requestPayload, 'app_version')
            ?: data_get($requestPayload, 'version')
            ?: data_get($requestPayload, 'build_number')
            ?: '-';

        $requestLatitude = data_get($requestPayload, 'latitude')
            ?? data_get($requestPayload, 'lat')
            ?? data_get($requestPayload, 'user_latitude');

        $requestLongitude = data_get($requestPayload, 'longitude')
            ?? data_get($requestPayload, 'lon')
            ?? data_get($requestPayload, 'lng')
            ?? data_get($requestPayload, 'user_longitude');

        $requestCoordinate = (filled($requestLatitude) && filled($requestLongitude))
            ? $formatDecimal($requestLatitude, 6) . ', ' . $formatDecimal($requestLongitude, 6)
            : '-';

        $weatherSource = $log?->weather_source ?? data_get($responsePayload, 'weather_source');
        $weatherUsed = $log?->weather_used ?? data_get($responsePayload, 'weather_used');
        $totalCandidates = $log?->total_candidates ?? data_get($responsePayload, 'total_candidates', $recommendations->count());
        $responseTimeMs = $log?->response_time_ms;

        $bestName = data_get($bestRecommendation, 'nama_tempat_wisata', $log?->top_destination_name ?? 'Belum ada rekomendasi');
        $bestScore = data_get($bestRecommendation, 'final_score');
        $bestImage = data_get($bestRecommendation, 'link_gambar');
        $bestMaps = data_get($bestRecommendation, 'link_google_maps');

        $isSuccess = $log?->status === 'success';

        $resultLimitText = filled($topN)
            ? $totalCandidates . ' hasil dari maksimal ' . $topN . ' rekomendasi'
            : $totalCandidates . ' hasil rekomendasi';

        $createdAt = $log?->created_at?->format('d M Y H:i') ?? '-';

        $scorePercent = is_numeric($bestScore)
            ? min(100, max(0, (float) $bestScore * 100))
            : 0;

        $backUrl = url('/admin/recommendation-logs');
    @endphp

    <style>
        .th-admin {
            --th-bg: #f6f8fb;
            --th-card: #ffffff;
            --th-ink: #0f172a;
            --th-muted: #64748b;
            --th-line: #e2e8f0;
            --th-soft: #f8fafc;
            --th-blue: #2563eb;
            --th-blue-dark: #1e3a8a;
            --th-emerald: #059669;
            --th-amber: #d97706;
            --th-red: #dc2626;
            color: var(--th-ink);
        }

        .dark .th-admin {
            --th-bg: #020617;
            --th-card: #0f172a;
            --th-ink: #f8fafc;
            --th-muted: #94a3b8;
            --th-line: rgba(148, 163, 184, .22);
            --th-soft: rgba(15, 23, 42, .68);
        }

        .th-stack {
            display: grid;
            gap: 1.25rem;
        }

        .th-hero {
            position: relative;
            overflow: hidden;
            border-radius: 28px;
            background:
                radial-gradient(circle at 0% 0%, rgba(59, 130, 246, .55), transparent 34%),
                radial-gradient(circle at 100% 15%, rgba(245, 158, 11, .30), transparent 30%),
                linear-gradient(135deg, #020617 0%, #0f172a 48%, #082f49 100%);
            color: white;
            box-shadow: 0 28px 70px rgba(15, 23, 42, .20);
        }

        .th-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            opacity: .18;
            background-image:
                linear-gradient(rgba(255, 255, 255, .16) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .16) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
        }

        .th-hero::after {
            content: "";
            position: absolute;
            width: 380px;
            height: 380px;
            right: -150px;
            bottom: -180px;
            border-radius: 999px;
            background: rgba(14, 165, 233, .22);
            filter: blur(2px);
            pointer-events: none;
        }

        .th-hero-inner {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 1.25rem;
            padding: 1.5rem;
        }

        @media (min-width: 768px) {
            .th-hero-inner {
                grid-template-columns: 1fr 320px;
                align-items: stretch;
                padding: 2rem;
            }
        }

        .th-kicker-row {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            align-items: center;
        }

        .th-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            width: fit-content;
            border-radius: 999px;
            padding: .42rem .78rem;
            font-size: .72rem;
            font-weight: 800;
            line-height: 1;
            border: 1px solid rgba(255, 255, 255, .16);
            background: rgba(255, 255, 255, .10);
            color: rgba(255, 255, 255, .92);
            backdrop-filter: blur(14px);
        }

        .th-pill-success {
            border-color: rgba(52, 211, 153, .25);
            background: rgba(16, 185, 129, .18);
            color: #d1fae5;
        }

        .th-pill-danger {
            border-color: rgba(248, 113, 113, .25);
            background: rgba(239, 68, 68, .18);
            color: #fee2e2;
        }

        .th-title {
            margin-top: 1rem;
            font-size: clamp(1.75rem, 3vw, 2.7rem);
            line-height: 1.05;
            font-weight: 950;
            letter-spacing: -.04em;
        }

        .th-desc {
            margin-top: .8rem;
            max-width: 760px;
            color: rgba(226, 232, 240, .92);
            font-size: .95rem;
            line-height: 1.75;
        }

        .th-hero-meta {
            margin-top: 1.1rem;
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
        }

        .th-meta {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            border-radius: 999px;
            padding: .55rem .78rem;
            font-size: .77rem;
            font-weight: 750;
            color: rgba(255, 255, 255, .93);
            background: rgba(255, 255, 255, .11);
            border: 1px solid rgba(255, 255, 255, .12);
        }

        .th-hero-side {
            display: grid;
            gap: .75rem;
        }

        .th-score-card {
            border-radius: 24px;
            padding: 1.1rem;
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .14);
            backdrop-filter: blur(18px);
        }

        .th-score-label {
            font-size: .76rem;
            font-weight: 800;
            color: rgba(226, 232, 240, .88);
        }

        .th-score-value {
            margin-top: .35rem;
            font-size: 2rem;
            line-height: 1;
            font-weight: 950;
            letter-spacing: -.04em;
        }

        .th-score-bar {
            margin-top: .85rem;
            height: .55rem;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(255, 255, 255, .14);
        }

        .th-score-bar span {
            display: block;
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #38bdf8, #34d399, #fbbf24);
        }

        .th-actions {
            margin-top: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
        }

        .th-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            min-height: 40px;
            border-radius: 14px;
            padding: .72rem 1rem;
            font-size: .86rem;
            font-weight: 850;
            line-height: 1;
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .th-btn:hover {
            transform: translateY(-1px);
        }

        .th-btn-light {
            color: #0f172a;
            background: white;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .18);
        }

        .th-btn-blue {
            color: white;
            background: #2563eb;
            box-shadow: 0 10px 24px rgba(37, 99, 235, .28);
        }

        .th-btn-green {
            color: white;
            background: #059669;
            box-shadow: 0 10px 24px rgba(5, 150, 105, .18);
        }

        .th-btn-dark {
            color: white;
            background: #020617;
        }

        .th-btn-muted {
            color: var(--th-ink);
            background: var(--th-soft);
            border: 1px solid var(--th-line);
        }

        .th-user-console {
            position: relative;
            overflow: hidden;
            border-radius: 30px;
            padding: 1.25rem;
            color: white;
            border: 1px solid rgba(255, 255, 255, .13);
            background:
                radial-gradient(circle at 5% 8%, rgba(34, 211, 238, .32), transparent 26%),
                radial-gradient(circle at 96% 20%, rgba(168, 85, 247, .26), transparent 29%),
                radial-gradient(circle at 60% 120%, rgba(16, 185, 129, .20), transparent 34%),
                linear-gradient(135deg, #020617 0%, #0f172a 42%, #111827 100%);
            box-shadow: 0 28px 70px rgba(2, 6, 23, .22);
        }

        .th-user-console::before,
        .th-user-console::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        .th-user-console::before {
            opacity: .16;
            background-image:
                linear-gradient(rgba(255, 255, 255, .20) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .20) 1px, transparent 1px);
            background-size: 34px 34px;
            mask-image: radial-gradient(circle at top left, black, transparent 78%);
        }

        .th-user-console::after {
            background:
                linear-gradient(110deg, transparent 0%, rgba(255, 255, 255, .08) 38%, transparent 72%);
            transform: translateX(-85%);
            animation: thUserSweep 7s linear infinite;
        }

        @keyframes thUserSweep {
            0% { transform: translateX(-90%); }
            48% { transform: translateX(128%); }
            100% { transform: translateX(128%); }
        }

        .th-user-bg-text {
            position: absolute;
            right: 1rem;
            top: -.55rem;
            color: rgba(255, 255, 255, .035);
            font-size: clamp(4rem, 12vw, 10rem);
            line-height: 1;
            font-weight: 1000;
            letter-spacing: -.08em;
            user-select: none;
            pointer-events: none;
        }

        .th-user-orb {
            position: absolute;
            width: 18rem;
            height: 18rem;
            border-radius: 999px;
            filter: blur(62px);
            pointer-events: none;
            opacity: .28;
        }

        .th-user-orb.one {
            left: -8rem;
            top: -8rem;
            background: #22d3ee;
        }

        .th-user-orb.two {
            right: -7rem;
            bottom: -9rem;
            background: #8b5cf6;
        }

        .th-user-console-inner {
            position: relative;
            z-index: 2;
            display: grid;
            gap: 1rem;
        }

        .th-user-head {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        @media (min-width: 900px) {
            .th-user-head {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }

        .th-user-profile {
            display: flex;
            align-items: center;
            gap: .95rem;
            min-width: 0;
        }

        .th-user-avatar {
            position: relative;
            width: 4.6rem;
            height: 4.6rem;
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            border-radius: 1.45rem;
            color: #020617;
            background:
                radial-gradient(circle at 30% 20%, #ffffff, transparent 28%),
                linear-gradient(135deg, #67e8f9, #34d399 46%, #fbbf24);
            border: 1px solid rgba(255, 255, 255, .42);
            box-shadow: 0 18px 42px rgba(34, 211, 238, .22);
            font-size: 1.42rem;
            font-weight: 1000;
            letter-spacing: -.035em;
        }

        .th-user-avatar::after {
            content: "";
            position: absolute;
            inset: -.35rem;
            border-radius: 1.75rem;
            border: 1px solid rgba(255, 255, 255, .12);
        }

        .th-user-kicker {
            margin: 0;
            color: #a5f3fc;
            font-size: .72rem;
            line-height: 1rem;
            font-weight: 950;
            letter-spacing: .09em;
            text-transform: uppercase;
        }

        .th-user-name {
            margin: .3rem 0 0;
            color: white;
            font-size: clamp(1.4rem, 2.2vw, 2.2rem);
            line-height: 1.05;
            font-weight: 1000;
            letter-spacing: -.045em;
            word-break: break-word;
        }

        .th-user-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            margin-top: .55rem;
        }

        .th-user-meta-pill {
            display: inline-flex;
            align-items: center;
            gap: .38rem;
            border-radius: 999px;
            padding: .42rem .68rem;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .12);
            color: #e2e8f0;
            font-size: .72rem;
            line-height: 1rem;
            font-weight: 850;
            max-width: 100%;
        }

        .th-user-status-card {
            min-width: min(100%, 17rem);
            border-radius: 1.35rem;
            padding: .95rem 1rem;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .13);
            backdrop-filter: blur(16px);
        }

        .th-user-status-label {
            color: #94a3b8;
            font-size: .68rem;
            font-weight: 950;
            line-height: 1rem;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .th-user-status-value {
            display: flex;
            align-items: center;
            gap: .55rem;
            margin-top: .35rem;
            color: white;
            font-size: 1.05rem;
            font-weight: 1000;
        }

        .th-user-live-dot {
            width: .65rem;
            height: .65rem;
            border-radius: 999px;
            background: #34d399;
            box-shadow: 0 0 18px rgba(52, 211, 153, .95);
        }

        .th-user-grid {
            display: grid;
            gap: .75rem;
            grid-template-columns: 1fr;
        }

        @media (min-width: 760px) {
            .th-user-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1180px) {
            .th-user-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .th-user-field {
            position: relative;
            overflow: hidden;
            min-height: 6.1rem;
            border-radius: 1.2rem;
            padding: .92rem;
            background: rgba(255, 255, 255, .075);
            border: 1px solid rgba(255, 255, 255, .12);
            backdrop-filter: blur(14px);
        }

        .th-user-field::after {
            content: "";
            position: absolute;
            right: -2rem;
            bottom: -2.2rem;
            width: 5.5rem;
            height: 5.5rem;
            border-radius: 999px;
            background: rgba(34, 211, 238, .12);
            filter: blur(5px);
        }

        .th-user-field-label {
            position: relative;
            z-index: 1;
            color: #94a3b8;
            font-size: .67rem;
            line-height: 1rem;
            font-weight: 950;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .th-user-field-value {
            position: relative;
            z-index: 1;
            margin-top: .38rem;
            color: white;
            font-size: .96rem;
            line-height: 1.35;
            font-weight: 950;
            word-break: break-word;
        }

        .th-user-field-note {
            position: relative;
            z-index: 1;
            margin: .35rem 0 0;
            color: #94a3b8;
            font-size: .72rem;
            line-height: 1.4;
            font-weight: 700;
        }

        .th-user-trace {
            display: grid;
            gap: .7rem;
            border-radius: 1.2rem;
            padding: .85rem;
            background: rgba(2, 6, 23, .45);
            border: 1px solid rgba(255, 255, 255, .10);
        }

        @media (min-width: 900px) {
            .th-user-trace {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .th-user-trace-step {
            position: relative;
            display: flex;
            gap: .65rem;
            align-items: flex-start;
            min-width: 0;
        }

        .th-user-trace-icon {
            width: 2rem;
            height: 2rem;
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            border-radius: .8rem;
            background: rgba(255, 255, 255, .10);
            border: 1px solid rgba(255, 255, 255, .12);
        }

        .th-user-trace-title {
            color: white;
            font-size: .78rem;
            line-height: 1.25;
            font-weight: 950;
        }

        .th-user-trace-text {
            margin-top: .18rem;
            color: #94a3b8;
            font-size: .7rem;
            line-height: 1.35;
            font-weight: 700;
            word-break: break-word;
        }

        .th-grid-4 {
            display: grid;
            gap: 1rem;
            grid-template-columns: 1fr;
        }

        @media (min-width: 768px) {
            .th-grid-4 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1180px) {
            .th-grid-4 {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .th-stat {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            padding: 1.15rem;
            background: var(--th-card);
            border: 1px solid var(--th-line);
            box-shadow: 0 14px 30px rgba(15, 23, 42, .05);
        }

        .th-stat::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent, #2563eb);
        }

        .th-stat-head {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
        }

        .th-stat-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 16px;
            background: color-mix(in srgb, var(--accent, #2563eb) 12%, transparent);
            color: var(--accent, #2563eb);
            font-size: 1.2rem;
            font-weight: 950;
        }

        .th-stat-label {
            color: var(--th-muted);
            font-size: .78rem;
            font-weight: 800;
        }

        .th-stat-value {
            margin-top: .45rem;
            color: var(--accent, #2563eb);
            font-size: 1.65rem;
            font-weight: 950;
            letter-spacing: -.035em;
        }

        .th-stat-note {
            margin-top: .55rem;
            color: var(--th-muted);
            font-size: .78rem;
            line-height: 1.45;
        }

        .th-section {
            overflow: hidden;
            border-radius: 26px;
            background: var(--th-card);
            border: 1px solid var(--th-line);
            box-shadow: 0 18px 40px rgba(15, 23, 42, .05);
        }

        .th-section-head {
            display: flex;
            flex-direction: column;
            gap: .85rem;
            padding: 1.25rem;
            border-bottom: 1px solid var(--th-line);
            background: linear-gradient(180deg, rgba(248, 250, 252, .78), rgba(255, 255, 255, 0));
        }

        .dark .th-section-head {
            background: linear-gradient(180deg, rgba(30, 41, 59, .55), rgba(15, 23, 42, 0));
        }

        @media (min-width: 768px) {
            .th-section-head {
                flex-direction: row;
                align-items: flex-end;
                justify-content: space-between;
                padding: 1.35rem 1.5rem;
            }
        }

        .th-section-kicker {
            color: var(--th-blue);
            font-size: .72rem;
            font-weight: 950;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .th-section-title {
            margin-top: .25rem;
            color: var(--th-ink);
            font-size: 1.2rem;
            font-weight: 950;
            letter-spacing: -.02em;
        }

        .th-section-sub {
            margin-top: .25rem;
            color: var(--th-muted);
            font-size: .86rem;
            line-height: 1.6;
        }

        .th-section-body {
            padding: 1.25rem;
        }

        @media (min-width: 768px) {
            .th-section-body {
                padding: 1.5rem;
            }
        }

        .th-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .th-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .45rem .75rem;
            font-size: .75rem;
            font-weight: 850;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #dbeafe;
        }

        .dark .th-chip {
            background: rgba(37, 99, 235, .13);
            color: #93c5fd;
            border-color: rgba(59, 130, 246, .20);
        }

        .th-field-grid {
            margin-top: 1rem;
            display: grid;
            gap: .8rem;
            grid-template-columns: 1fr;
        }

        @media (min-width: 768px) {
            .th-field-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1180px) {
            .th-field-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .th-field {
            border-radius: 18px;
            padding: .95rem;
            background: var(--th-soft);
            border: 1px solid var(--th-line);
        }

        .th-field-label {
            color: var(--th-muted);
            font-size: .70rem;
            font-weight: 950;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .th-field-value {
            margin-top: .35rem;
            color: var(--th-ink);
            font-size: .94rem;
            font-weight: 900;
            word-break: break-word;
        }

        .th-best {
            display: grid;
            gap: 1.25rem;
        }

        @media (min-width: 1024px) {
            .th-best {
                grid-template-columns: 360px 1fr;
                align-items: stretch;
            }
        }

        .th-best-image-wrap {
            position: relative;
            min-height: 260px;
            overflow: hidden;
            border-radius: 24px;
            background: var(--th-soft);
            border: 1px solid var(--th-line);
        }

        .th-best-image {
            width: 100%;
            height: 100%;
            min-height: 260px;
            object-fit: cover;
            display: block;
        }

        .th-image-placeholder {
            width: 100%;
            min-height: 260px;
            display: grid;
            place-items: center;
            color: var(--th-muted);
            font-size: .9rem;
            font-weight: 800;
        }

        .th-rank-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            border-radius: 999px;
            padding: .55rem .85rem;
            background: #fbbf24;
            color: #0f172a;
            font-size: .8rem;
            font-weight: 950;
            box-shadow: 0 12px 24px rgba(146, 64, 14, .22);
        }

        .th-best-score-floating {
            position: absolute;
            left: 14px;
            right: 14px;
            bottom: 14px;
            border-radius: 20px;
            padding: 1rem;
            background: rgba(255, 255, 255, .90);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(255, 255, 255, .55);
            color: #0f172a;
        }

        .th-best-title {
            color: var(--th-ink);
            font-size: 1.8rem;
            font-weight: 950;
            letter-spacing: -.035em;
        }

        .th-best-location {
            margin-top: .35rem;
            color: var(--th-muted);
            font-size: .9rem;
            font-weight: 750;
        }

        .th-metric-grid {
            margin-top: 1.1rem;
            display: grid;
            gap: .75rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        @media (min-width: 1180px) {
            .th-metric-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .th-metric {
            border-radius: 18px;
            padding: .95rem;
            background: var(--th-soft);
            border: 1px solid var(--th-line);
        }

        .th-metric-label {
            color: var(--th-muted);
            font-size: .70rem;
            font-weight: 950;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .th-metric-value {
            margin-top: .35rem;
            color: var(--th-ink);
            font-size: 1.15rem;
            font-weight: 950;
        }

        .th-reason {
            margin-top: 1rem;
            border-radius: 20px;
            padding: 1rem;
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #78350f;
        }

        .dark .th-reason {
            background: rgba(120, 53, 15, .18);
            border-color: rgba(245, 158, 11, .22);
            color: #fde68a;
        }

        .th-reason-label {
            font-size: .72rem;
            font-weight: 950;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .th-reason-text {
            margin-top: .5rem;
            font-size: .88rem;
            line-height: 1.65;
        }

        .th-ranking-grid {
            display: grid;
            gap: 1rem;
        }

        .th-rank-card {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            background: var(--th-card);
            border: 1px solid var(--th-line);
            transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
        }

        .th-rank-card:hover {
            transform: translateY(-2px);
            border-color: rgba(37, 99, 235, .32);
            box-shadow: 0 18px 38px rgba(15, 23, 42, .08);
        }

        .th-rank-card.is-top {
            border-color: rgba(245, 158, 11, .55);
            background: linear-gradient(135deg, #fffbeb, #ffffff);
        }

        .dark .th-rank-card.is-top {
            background: linear-gradient(135deg, rgba(120, 53, 15, .20), rgba(15, 23, 42, .95));
        }

        .th-rank-content {
            display: grid;
            gap: 1rem;
            padding: 1rem;
        }

        @media (min-width: 860px) {
            .th-rank-content {
                grid-template-columns: 180px 1fr 160px;
                align-items: start;
            }
        }

        .th-thumb {
            position: relative;
            overflow: hidden;
            border-radius: 18px;
            min-height: 130px;
            background: var(--th-soft);
            border: 1px solid var(--th-line);
        }

        .th-thumb img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            display: block;
        }

        @media (min-width: 860px) {
            .th-thumb img {
                height: 130px;
            }
        }

        .th-thumb-empty {
            min-height: 130px;
            display: grid;
            place-items: center;
            color: var(--th-muted);
            font-size: .8rem;
            font-weight: 800;
        }

        .th-rank-number {
            position: absolute;
            top: .65rem;
            left: .65rem;
            display: grid;
            place-items: center;
            min-width: 2.25rem;
            height: 2.25rem;
            border-radius: 12px;
            background: #020617;
            color: white;
            font-size: .83rem;
            font-weight: 950;
            box-shadow: 0 12px 24px rgba(15, 23, 42, .24);
        }

        .th-rank-card.is-top .th-rank-number {
            background: #fbbf24;
            color: #0f172a;
        }

        .th-rank-title {
            color: var(--th-ink);
            font-size: 1.15rem;
            line-height: 1.2;
            font-weight: 950;
            letter-spacing: -.02em;
        }

        .th-rank-location {
            margin-top: .35rem;
            color: var(--th-muted);
            font-size: .82rem;
            font-weight: 750;
        }

        .th-small-badges {
            margin-top: .75rem;
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
        }

        .th-small-badge {
            display: inline-flex;
            border-radius: 999px;
            padding: .35rem .6rem;
            background: var(--th-soft);
            border: 1px solid var(--th-line);
            color: var(--th-muted);
            font-size: .72rem;
            font-weight: 850;
        }

        .th-small-badge.blue {
            background: #eff6ff;
            border-color: #dbeafe;
            color: #1d4ed8;
        }

        .dark .th-small-badge.blue {
            background: rgba(37, 99, 235, .13);
            border-color: rgba(59, 130, 246, .20);
            color: #93c5fd;
        }

        .th-rank-metrics {
            margin-top: .9rem;
            display: grid;
            gap: .55rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        @media (min-width: 1180px) {
            .th-rank-metrics {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .th-mini-metric {
            border-radius: 15px;
            padding: .75rem;
            background: var(--th-soft);
            border: 1px solid var(--th-line);
        }

        .th-mini-metric span {
            display: block;
            color: var(--th-muted);
            font-size: .68rem;
            font-weight: 950;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .th-mini-metric strong {
            display: block;
            margin-top: .25rem;
            color: var(--th-ink);
            font-size: .92rem;
            font-weight: 950;
        }

        .th-rank-side {
            display: grid;
            gap: .75rem;
            align-content: start;
        }

        .th-final-box {
            border-radius: 18px;
            padding: .95rem;
            background: #020617;
            color: white;
            text-align: center;
        }

        .dark .th-final-box {
            background: #ffffff;
            color: #020617;
        }

        .th-final-box span {
            display: block;
            font-size: .72rem;
            font-weight: 850;
            opacity: .72;
        }

        .th-final-box strong {
            display: block;
            margin-top: .25rem;
            font-size: 1.35rem;
            font-weight: 950;
            letter-spacing: -.03em;
        }

        .th-rank-reason {
            margin-top: .9rem;
            color: var(--th-muted);
            font-size: .82rem;
            line-height: 1.65;
        }

        .th-info-grid {
            display: grid;
            gap: 1rem;
        }

        @media (min-width: 1024px) {
            .th-info-grid {
                grid-template-columns: 1fr 2fr;
            }
        }

        .th-note {
            border-radius: 22px;
            padding: 1rem;
            background: var(--th-soft);
            border: 1px solid var(--th-line);
            color: var(--th-muted);
            font-size: .88rem;
            line-height: 1.7;
        }

        .th-note strong {
            color: var(--th-ink);
        }

        .th-json-grid {
            display: grid;
            gap: 1rem;
        }

        @media (min-width: 1024px) {
            .th-json-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .th-json-title {
            margin-bottom: .5rem;
            color: var(--th-ink);
            font-size: .85rem;
            font-weight: 900;
        }

        .th-json {
            max-height: 420px;
            overflow: auto;
            border-radius: 18px;
            padding: 1rem;
            background: #020617;
            color: #dbeafe;
            font-size: .76rem;
            line-height: 1.65;
            border: 1px solid rgba(148, 163, 184, .25);
        }

        .th-empty {
            border-radius: 22px;
            padding: 2rem;
            border: 1px dashed var(--th-line);
            text-align: center;
            color: var(--th-muted);
        }

        .th-error {
            border-radius: 22px;
            padding: 1rem;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            line-height: 1.65;
        }

        .dark .th-error {
            background: rgba(127, 29, 29, .20);
            border-color: rgba(248, 113, 113, .22);
            color: #fecaca;
        }

        @media (max-width: 640px) {
            .th-actions .th-btn {
                width: 100%;
            }
        }
    </style>

    <div class="th-admin th-stack">
        <section class="th-hero">
            <div class="th-hero-inner">
                <div>
                    <div class="th-kicker-row">
                        <span class="th-pill">📄 Detail Log Rekomendasi</span>
                        <span class="th-pill {{ $isSuccess ? 'th-pill-success' : 'th-pill-danger' }}">
                            {{ $isSuccess ? '✅ Berhasil' : '⚠️ Gagal' }}
                        </span>
                    </div>

                    <h1 class="th-title">{{ $bestName }}</h1>

                    <p class="th-desc">
                        Halaman ini dibuat untuk admin supaya hasil rekomendasi bisa dibaca dengan jelas tanpa perlu
                        membuka JSON mentah. Ranking destinasi disusun berdasarkan <strong>final score</strong>, yaitu
                        kombinasi skor CBF, rating, popularitas, dan penyesuaian konteks CARS.
                    </p>

                    <div class="th-hero-meta">
                        <span class="th-meta">#{{ $log?->id ?? '-' }}</span>
                        <span class="th-meta">👤 {{ $requestUserName }}</span>
                        <span class="th-meta">🕒 {{ $createdAt }}</span>
                        <span class="th-meta">📌 {{ $resultLimitText }}</span>
                    </div>

                    <div class="th-actions">
                        <a href="{{ $backUrl }}" class="th-btn th-btn-light">← Kembali ke Log</a>
                        <a href="{{ url('/tourhub/rekomendasi') }}" target="_blank" rel="noopener noreferrer" class="th-btn th-btn-blue">
                            Buka Simulasi
                        </a>

                        @if ($bestMaps)
                            <a href="{{ $bestMaps }}" target="_blank" rel="noopener noreferrer" class="th-btn th-btn-green">
                                📍 Maps Destinasi Teratas
                            </a>
                        @endif
                    </div>
                </div>

                <div class="th-hero-side">
                    <div class="th-score-card">
                        <p class="th-score-label">Final Score Teratas</p>
                        <p class="th-score-value">{{ $formatDecimal($bestScore, 6) }}</p>
                        <div class="th-score-bar">
                            <span style="width: {{ $scorePercent }}%"></span>
                        </div>
                    </div>

                    <div class="th-score-card">
                        <p class="th-score-label">Response Time</p>
                        <p class="th-score-value">
                            {{ filled($responseTimeMs) ? number_format((int) $responseTimeMs, 0, ',', '.') . ' ms' : '-' }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="th-user-console">
            <span class="th-user-orb one"></span>
            <span class="th-user-orb two"></span>
            <div class="th-user-bg-text">USER</div>

            <div class="th-user-console-inner">
                <div class="th-user-head">
                    <div class="th-user-profile">
                        <div class="th-user-avatar">{{ $requestUserInitials }}</div>

                        <div style="min-width: 0;">
                            <p class="th-user-kicker">Request Owner / User Identity</p>
                            <h2 class="th-user-name">{{ $requestUserName }}</h2>

                            <div class="th-user-meta-row">
                                <span class="th-user-meta-pill">🆔 User ID: {{ $requestUserId }}</span>
                                <span class="th-user-meta-pill">✉️ {{ $requestUserEmail }}</span>
                                <span class="th-user-meta-pill">🛡️ Role: {{ $requestUserRoles }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="th-user-status-card">
                        <div class="th-user-status-label">Request Link Status</div>
                        <div class="th-user-status-value">
                            <span class="th-user-live-dot"></span>
                            {{ $requestUser ? 'Terhubung ke akun user' : 'Guest / user tidak terbaca' }}
                        </div>
                    </div>
                </div>

                <div class="th-user-grid">
                    <div class="th-user-field">
                        <div class="th-user-field-label">Nama User</div>
                        <div class="th-user-field-value">{{ $requestUserName }}</div>
                        <p class="th-user-field-note">Akun yang melakukan request rekomendasi ini.</p>
                    </div>

                    <div class="th-user-field">
                        <div class="th-user-field-label">Email User</div>
                        <div class="th-user-field-value">{{ $requestUserEmail }}</div>
                        <p class="th-user-field-note">Email akun dari tabel users Laravel.</p>
                    </div>

                    <div class="th-user-field">
                        <div class="th-user-field-label">Waktu Request</div>
                        <div class="th-user-field-value">{{ $createdAt }}</div>
                        <p class="th-user-field-note">Waktu log rekomendasi dibuat oleh sistem.</p>
                    </div>

                    <div class="th-user-field">
                        <div class="th-user-field-label">Member Since</div>
                        <div class="th-user-field-value">{{ $requestUserCreatedAt }}</div>
                        <p class="th-user-field-note">Tanggal akun user dibuat.</p>
                    </div>

                    <div class="th-user-field">
                        <div class="th-user-field-label">Email Verified</div>
                        <div class="th-user-field-value">{{ $requestUserVerifiedAt }}</div>
                        <p class="th-user-field-note">Status verifikasi email jika tersedia.</p>
                    </div>

                    <div class="th-user-field">
                        <div class="th-user-field-label">Platform / Client</div>
                        <div class="th-user-field-value">{{ $clientPlatform }}</div>
                        <p class="th-user-field-note">Diambil dari request payload jika dikirim mobile/web.</p>
                    </div>

                    <div class="th-user-field">
                        <div class="th-user-field-label">App Version</div>
                        <div class="th-user-field-value">{{ $clientVersion }}</div>
                        <p class="th-user-field-note">Versi aplikasi jika tersedia pada payload.</p>
                    </div>

                    <div class="th-user-field">
                        <div class="th-user-field-label">Koordinat User</div>
                        <div class="th-user-field-value">{{ $requestCoordinate }}</div>
                        <p class="th-user-field-note">Latitude dan longitude user saat request jika tersedia.</p>
                    </div>
                </div>

                <div class="th-user-trace">
                    <div class="th-user-trace-step">
                        <div class="th-user-trace-icon">👤</div>
                        <div>
                            <div class="th-user-trace-title">User Request</div>
                            <div class="th-user-trace-text">{{ $requestUserName }} mengirim preferensi wisata.</div>
                        </div>
                    </div>

                    <div class="th-user-trace-step">
                        <div class="th-user-trace-icon">🧭</div>
                        <div>
                            <div class="th-user-trace-title">Context Filter</div>
                            <div class="th-user-trace-text">{{ $kabupatenKota ?: 'Semua wilayah' }} • {{ $weatherLabel($weatherUsed) }}</div>
                        </div>
                    </div>

                    <div class="th-user-trace-step">
                        <div class="th-user-trace-icon">⚙️</div>
                        <div>
                            <div class="th-user-trace-title">Laravel → FastAPI</div>
                            <div class="th-user-trace-text">{{ filled($responseTimeMs) ? number_format((int) $responseTimeMs, 0, ',', '.') . ' ms' : 'response belum tersedia' }}</div>
                        </div>
                    </div>

                    <div class="th-user-trace-step">
                        <div class="th-user-trace-icon">🏆</div>
                        <div>
                            <div class="th-user-trace-title">Top Result</div>
                            <div class="th-user-trace-text">{{ $bestName }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if (! $isSuccess && filled($log?->error_message))
            <section class="th-section">
                <div class="th-section-head">
                    <div>
                        <p class="th-section-kicker">Request Gagal</p>
                        <h2 class="th-section-title">Pesan Error</h2>
                        <p class="th-section-sub">Informasi ini membantu admin mengetahui alasan request gagal.</p>
                    </div>
                </div>
                <div class="th-section-body">
                    <div class="th-error">{{ $log->error_message }}</div>
                </div>
            </section>
        @endif

        <section class="th-grid-4">
            <div class="th-stat" style="--accent: {{ $isSuccess ? '#059669' : '#dc2626' }}">
                <div class="th-stat-head">
                    <div>
                        <p class="th-stat-label">Status</p>
                        <p class="th-stat-value">{{ $isSuccess ? 'Success' : 'Failed' }}</p>
                    </div>
                    <div class="th-stat-icon">{{ $isSuccess ? '✓' : '!' }}</div>
                </div>
                <p class="th-stat-note">Status request rekomendasi ke FastAPI ML.</p>
            </div>

            <div class="th-stat" style="--accent: #2563eb">
                <div class="th-stat-head">
                    <div>
                        <p class="th-stat-label">Cuaca Dipakai</p>
                        <p class="th-stat-value">{{ $weatherLabel($weatherUsed) }}</p>
                    </div>
                    <div class="th-stat-icon">☁</div>
                </div>
                <p class="th-stat-note">
                    Sumber: {{ $weatherSource ? $humanize($weatherSource) : ($useBmkg ? 'BMKG' : 'Manual') }}
                </p>
            </div>

            <div class="th-stat" style="--accent: #7c3aed">
                <div class="th-stat-head">
                    <div>
                        <p class="th-stat-label">Jumlah Hasil</p>
                        <p class="th-stat-value">{{ $totalCandidates }}</p>
                    </div>
                    <div class="th-stat-icon">#</div>
                </div>
                <p class="th-stat-note">Dari maksimal {{ $topN ?: '-' }} rekomendasi.</p>
            </div>

            <div class="th-stat" style="--accent: #d97706">
                <div class="th-stat-head">
                    <div>
                        <p class="th-stat-label">BMKG</p>
                        <p class="th-stat-value">{{ $useBmkg ? 'Aktif' : 'Manual' }}</p>
                    </div>
                    <div class="th-stat-icon">🌦</div>
                </div>
                <p class="th-stat-note">ADM4: {{ $bmkgAdm4 ?: '-' }}</p>
            </div>
        </section>

        <section class="th-section">
            <div class="th-section-head">
                <div>
                    <p class="th-section-kicker">Parameter User</p>
                    <h2 class="th-section-title">Preferensi yang Dipakai Sistem</h2>
                    <p class="th-section-sub">Ringkasan input user yang dikirim Laravel ke FastAPI.</p>
                </div>

                <div class="th-chip-row">
                    @forelse ($categories as $category)
                        <span class="th-chip">{{ $category }}</span>
                    @empty
                        <span class="th-chip">Tidak ada kategori</span>
                    @endforelse
                </div>
            </div>

            <div class="th-section-body">
                <div class="th-field-grid">
                    <div class="th-field">
                        <p class="th-field-label">Kabupaten/Kota</p>
                        <p class="th-field-value">{{ $kabupatenKota ?: '-' }}</p>
                    </div>

                    <div class="th-field">
                        <p class="th-field-label">Kecamatan</p>
                        <p class="th-field-value">{{ $kecamatan ?: '-' }}</p>
                    </div>

                    <div class="th-field">
                        <p class="th-field-label">Min Rating</p>
                        <p class="th-field-value">{{ $formatValue($minRating) }}</p>
                    </div>

                    <div class="th-field">
                        <p class="th-field-label">Top N</p>
                        <p class="th-field-value">Maksimal {{ $formatValue($topN) }} hasil</p>
                    </div>

                    <div class="th-field">
                        <p class="th-field-label">Hari Kunjungan</p>
                        <p class="th-field-value">{{ $humanize($visitDay) }}</p>
                    </div>

                    <div class="th-field">
                        <p class="th-field-label">High Season</p>
                        <p class="th-field-value">{{ $isHighSeason ? 'Ya' : 'Tidak' }}</p>
                    </div>

                    <div class="th-field">
                        <p class="th-field-label">Keywords</p>
                        <p class="th-field-value">{{ count($keywords) ? implode(', ', $keywords) : '-' }}</p>
                    </div>

                    <div class="th-field">
                        <p class="th-field-label">Cuaca Manual</p>
                        <p class="th-field-value">{{ $weatherLabel(data_get($requestPayload, 'weather')) }}</p>
                    </div>
                </div>
            </div>
        </section>

        @if ($bestRecommendation)
            <section class="th-section">
                <div class="th-section-head">
                    <div>
                        <p class="th-section-kicker">Top Recommendation</p>
                        <h2 class="th-section-title">Destinasi Paling Direkomendasikan</h2>
                        <p class="th-section-sub">Item dengan final score tertinggi pada log ini.</p>
                    </div>
                </div>

                <div class="th-section-body">
                    <div class="th-best">
                        <div class="th-best-image-wrap">
                            @if ($bestImage)
                                <img src="{{ $bestImage }}" alt="{{ $bestName }}" class="th-best-image">
                            @else
                                <div class="th-image-placeholder">Tidak ada gambar</div>
                            @endif

                            <div class="th-rank-badge">🏆 Rank #1</div>

                            <div class="th-best-score-floating">
                                <div class="th-metric-label">Final Score Tertinggi</div>
                                <div style="margin-top: .2rem; font-size: 2rem; font-weight: 950; letter-spacing: -.04em;">
                                    {{ $formatDecimal(data_get($bestRecommendation, 'final_score'), 6) }}
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="th-chip-row">
                                <span class="th-chip">{{ data_get($bestRecommendation, 'kategori', '-') }}</span>
                                <span class="th-small-badge">{{ data_get($bestRecommendation, 'tipe_wisata', '-') }}</span>
                            </div>

                            <h2 class="th-best-title" style="margin-top: 1rem;">{{ $bestName }}</h2>

                            <p class="th-best-location">
                                {{ data_get($bestRecommendation, 'kecamatan', '-') }} -
                                {{ data_get($bestRecommendation, 'kabupaten_kota', '-') }}
                            </p>

                            <div class="th-actions">
                                @if ($bestMaps)
                                    <a href="{{ $bestMaps }}" target="_blank" rel="noopener noreferrer" class="th-btn th-btn-green">
                                        📍 Buka Google Maps
                                    </a>
                                @endif
                            </div>

                            <div class="th-metric-grid">
                                <div class="th-metric">
                                    <p class="th-metric-label">Rating</p>
                                    <p class="th-metric-value">{{ $formatDecimal(data_get($bestRecommendation, 'rating'), 2) }}</p>
                                </div>

                                <div class="th-metric">
                                    <p class="th-metric-label">Jumlah Rating</p>
                                    <p class="th-metric-value">{{ $formatNumber(data_get($bestRecommendation, 'jumlah_rating')) }}</p>
                                </div>

                                <div class="th-metric">
                                    <p class="th-metric-label">CBF Score</p>
                                    <p class="th-metric-value">{{ $formatDecimal(data_get($bestRecommendation, 'cbf_score'), 6) }}</p>
                                </div>

                                <div class="th-metric">
                                    <p class="th-metric-label">Context</p>
                                    <p class="th-metric-value">{{ $formatDecimal(data_get($bestRecommendation, 'context_multiplier'), 6) }}</p>
                                </div>
                            </div>

                            @if (data_get($bestRecommendation, 'alasan'))
                                <div class="th-reason">
                                    <p class="th-reason-label">Alasan Rekomendasi</p>
                                    <p class="th-reason-text">{{ data_get($bestRecommendation, 'alasan') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <section class="th-section">
            <div class="th-section-head">
                <div>
                    <p class="th-section-kicker">Ranking Rekomendasi</p>
                    <h2 class="th-section-title">Daftar Hasil Berdasarkan Final Score</h2>
                    <p class="th-section-sub">
                        Semua hasil sudah diurutkan dari final score tertinggi. Top N adalah batas maksimal, bukan jumlah wajib.
                    </p>
                </div>

                <span class="th-chip">{{ $resultLimitText }}</span>
            </div>

            <div class="th-section-body">
                @if ($recommendations->isEmpty())
                    <div class="th-empty">
                        <strong>Belum ada hasil rekomendasi</strong>
                        <p style="margin-top: .35rem;">Jika status sukses tetapi hasil kosong, kemungkinan filter terlalu spesifik.</p>
                    </div>
                @else
                    <div class="th-ranking-grid">
                        @foreach ($recommendations as $index => $item)
                            <article class="th-rank-card {{ $index === 0 ? 'is-top' : '' }}">
                                <div class="th-rank-content">
                                    <div class="th-thumb">
                                        @if (data_get($item, 'link_gambar'))
                                            <img src="{{ data_get($item, 'link_gambar') }}" alt="{{ data_get($item, 'nama_tempat_wisata') }}">
                                        @else
                                            <div class="th-thumb-empty">Tidak ada gambar</div>
                                        @endif

                                        <div class="th-rank-number">#{{ $index + 1 }}</div>
                                    </div>

                                    <div>
                                        <div class="th-chip-row">
                                            @if ($index === 0)
                                                <span class="th-small-badge" style="background: #fef3c7; border-color: #fde68a; color: #92400e;">
                                                    🏆 Paling Direkomendasikan
                                                </span>
                                            @endif

                                            <span class="th-small-badge blue">{{ data_get($item, 'kategori', '-') }}</span>
                                            <span class="th-small-badge">{{ data_get($item, 'tipe_wisata', '-') }}</span>
                                        </div>

                                        <h3 class="th-rank-title" style="margin-top: .8rem;">
                                            {{ data_get($item, 'nama_tempat_wisata', '-') }}
                                        </h3>

                                        <p class="th-rank-location">
                                            {{ data_get($item, 'kecamatan', '-') }} -
                                            {{ data_get($item, 'kabupaten_kota', '-') }}
                                        </p>

                                        <div class="th-rank-metrics">
                                            <div class="th-mini-metric">
                                                <span>Rating</span>
                                                <strong>{{ $formatDecimal(data_get($item, 'rating'), 2) }}</strong>
                                            </div>

                                            <div class="th-mini-metric">
                                                <span>Ulasan</span>
                                                <strong>{{ $formatNumber(data_get($item, 'jumlah_rating')) }}</strong>
                                            </div>

                                            <div class="th-mini-metric">
                                                <span>CBF</span>
                                                <strong>{{ $formatDecimal(data_get($item, 'cbf_score'), 6) }}</strong>
                                            </div>

                                            <div class="th-mini-metric">
                                                <span>Context</span>
                                                <strong>{{ $formatDecimal(data_get($item, 'context_multiplier'), 6) }}</strong>
                                            </div>
                                        </div>

                                        @if (data_get($item, 'alasan'))
                                            <p class="th-rank-reason">{{ data_get($item, 'alasan') }}</p>
                                        @endif
                                    </div>

                                    <div class="th-rank-side">
                                        <div class="th-final-box">
                                            <span>Final Score</span>
                                            <strong>{{ $formatDecimal(data_get($item, 'final_score'), 6) }}</strong>
                                        </div>

                                        @if (data_get($item, 'link_google_maps'))
                                            <a
                                                href="{{ data_get($item, 'link_google_maps') }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="th-btn th-btn-green"
                                            >
                                                📍 Buka Maps
                                            </a>
                                        @else
                                            <span class="th-btn th-btn-muted">Maps tidak ada</span>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <div class="th-info-grid">
            <section class="th-section">
                <div class="th-section-head">
                    <div>
                        <p class="th-section-kicker">Panduan Admin</p>
                        <h2 class="th-section-title">Cara Membaca Skor</h2>
                    </div>
                </div>

                <div class="th-section-body">
                    <div class="th-note">
                        <p><strong>CBF Score</strong> menunjukkan kecocokan destinasi dengan preferensi user.</p>
                        <p style="margin-top: .65rem;"><strong>Context</strong> adalah pengali dari CARS berdasarkan cuaca, hari kunjungan, high season, dan potensi keramaian.</p>
                        <p style="margin-top: .65rem;"><strong>Final Score</strong> adalah skor akhir yang dipakai untuk menentukan ranking rekomendasi.</p>
                    </div>
                </div>
            </section>

            <section class="th-section">
                <div class="th-section-head">
                    <div>
                        <p class="th-section-kicker">Catatan</p>
                        <h2 class="th-section-title">Jumlah Hasil Lebih Sedikit dari Top N Itu Normal</h2>
                    </div>
                </div>

                <div class="th-section-body">
                    <div class="th-note">
                        Jika jumlah hasil lebih sedikit dari Top N, itu bukan error. Top N adalah batas maksimal hasil,
                        sedangkan sistem tetap hanya menampilkan destinasi yang memenuhi filter dan relevansi. Dengan
                        begitu, rekomendasi tetap berkualitas dan tidak dipaksa memenuhi jumlah.
                    </div>
                </div>
            </section>
        </div>

        <section class="th-section">
            <div class="th-section-head">
                <div>
                    <p class="th-section-kicker">Developer Area</p>
                    <h2 class="th-section-title">Data Teknis JSON</h2>
                    <p class="th-section-sub">Bagian ini untuk debugging. Admin cukup memakai ringkasan dan ranking di atas.</p>
                </div>
            </div>

            <div class="th-section-body">
                <details>
                    <summary class="th-btn th-btn-muted" style="cursor: pointer; width: fit-content;">
                        Tampilkan / Sembunyikan JSON
                    </summary>

                    <div class="th-json-grid" style="margin-top: 1rem;">
                        <div>
                            <p class="th-json-title">Request Payload</p>
                            <pre class="th-json">{{ json_encode($requestPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>

                        <div>
                            <p class="th-json-title">Response Payload</p>
                            <pre class="th-json">{{ json_encode($responsePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>
                </details>
            </div>
        </section>
    </div>
</x-filament-panels::page>
