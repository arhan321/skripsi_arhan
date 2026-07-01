<x-filament-panels::page>
    @php
        $stats = $this->getDestinationStats();
        $categorySummary = $this->getCategorySummary();
        $typeSummary = $this->getTypeSummary();
        $locationSummary = $this->getLocationSummary();
        $featuredDestinations = $this->getFeaturedDestinations();
        $latestDestinations = $this->getLatestDestinations();
        $qualityItems = $this->getDatasetQualityItems();
        $issueItems = $this->getIssueItems();

        $formatNumber = fn ($value): string => number_format((float) $value, 0, ',', '.');
        $formatRating = fn ($value): string => number_format((float) $value, 2, ',', '.');
        $formatPercent = fn ($value): string => number_format((float) $value, 1, ',', '.') . '%';
        $formatMoney = fn ($value): string => 'Rp ' . number_format((float) $value, 0, ',', '.');

        $safe = fn ($key, $default = 0) => data_get($stats, $key, $default);

        $typeLabel = function (?string $type): string {
            return match (strtolower((string) $type)) {
                'indoor' => 'Dalam Ruangan',
                'outdoor' => 'Luar Ruangan',
                'mixed' => 'Fleksibel',
                default => filled($type) ? ucfirst((string) $type) : '-',
            };
        };

        $categoryClass = function (?string $category): string {
            return match (strtolower((string) $category)) {
                'alam' => 'th-chip-green',
                'budaya' => 'th-chip-purple',
                'rekreasi' => 'th-chip-blue',
                'kuliner' => 'th-chip-orange',
                'bahari' => 'th-chip-cyan',
                default => 'th-chip-yellow',
            };
        };

        $toneClass = function (?string $tone): string {
            return match (strtolower((string) $tone)) {
                'emerald' => 'th-tone-green',
                'green' => 'th-tone-green',
                'blue' => 'th-tone-blue',
                'amber' => 'th-tone-yellow',
                'cyan' => 'th-tone-cyan',
                'rose' => 'th-tone-red',
                'red' => 'th-tone-red',
                'orange' => 'th-tone-orange',
                'yellow' => 'th-tone-yellow',
                'purple' => 'th-tone-purple',
                default => 'th-tone-slate',
            };
        };

        $latestUpdatedAt = $safe('latest_updated_at');
        $latestUpdatedLabel = '-';

        if ($latestUpdatedAt) {
            try {
                $latestUpdatedLabel = \Illuminate\Support\Carbon::parse($latestUpdatedAt)->format('d M Y H:i');
            } catch (\Throwable $exception) {
                $latestUpdatedLabel = (string) $latestUpdatedAt;
            }
        }

        $readyPercent = min(100, max(0, (float) $safe('ready_percentage')));
        $activePercent = min(100, max(0, (float) $safe('active_percentage')));
        $attentionCount = (int) $safe('needs_attention');
        $attentionLabel = $attentionCount > 0 ? 'Perlu Dicek' : 'Sehat';

        $categoryCollection = collect($categorySummary ?? []);
        $typeCollection = collect($typeSummary ?? []);
        $locationCollection = collect($locationSummary ?? []);
        $featuredCollection = collect($featuredDestinations ?? []);
        $latestCollection = collect($latestDestinations ?? []);

        $topCategory = $categoryCollection->first();
        $topType = $typeCollection->first();
        $topLocation = $locationCollection->first();

        $topCategoryName = data_get($topCategory, 'kategori', '-');
        $topCategoryTotal = data_get($topCategory, 'total', 0);
        $topLocationName = data_get($topLocation, 'kabupaten_kota', '-');
        $topLocationTotal = data_get($topLocation, 'total', 0);
        $topTypeName = $typeLabel(data_get($topType, 'tipe_wisata', '-'));
        $topTypeTotal = data_get($topType, 'total', 0);

        $recommendedStatus = $readyPercent >= 85 && $attentionCount === 0
            ? 'Sangat Siap'
            : ($readyPercent >= 70 ? 'Siap Operasional' : 'Butuh Perapihan');

        $recommendedStatusClass = $readyPercent >= 85 && $attentionCount === 0
            ? 'th-chip-green'
            : ($readyPercent >= 70 ? 'th-chip-blue' : 'th-chip-red');
    @endphp

    <style>
        .tourhub-lux-page,
        .tourhub-lux-page *,
        .tourhub-lux-page *::before,
        .tourhub-lux-page *::after {
            box-sizing: border-box;
        }

        .tourhub-lux-page {
            --th-ink: #f8fafc;
            --th-ink-soft: #e2e8f0;
            --th-muted: #94a3b8;
            --th-muted-2: #64748b;
            --th-bg-0: #020617;
            --th-bg-1: #07111f;
            --th-bg-2: #0f172a;
            --th-panel: rgba(15, 23, 42, 0.76);
            --th-panel-solid: #0f172a;
            --th-panel-soft: rgba(15, 23, 42, 0.55);
            --th-glass: rgba(255, 255, 255, 0.075);
            --th-glass-2: rgba(255, 255, 255, 0.105);
            --th-white-border: rgba(255, 255, 255, 0.12);
            --th-white-border-2: rgba(255, 255, 255, 0.19);
            --th-border: rgba(148, 163, 184, 0.22);
            --th-border-strong: rgba(148, 163, 184, 0.34);
            --th-shadow: 0 30px 90px rgba(2, 6, 23, 0.40);
            --th-shadow-soft: 0 20px 52px rgba(2, 6, 23, 0.28);
            --th-blue: #2563eb;
            --th-blue-2: #60a5fa;
            --th-cyan: #06b6d4;
            --th-cyan-2: #67e8f9;
            --th-green: #10b981;
            --th-green-2: #86efac;
            --th-yellow: #f59e0b;
            --th-yellow-2: #fcd34d;
            --th-orange: #f97316;
            --th-purple: #7c3aed;
            --th-pink: #ec4899;
            --th-red: #f43f5e;
            --th-red-2: #fda4af;
            --th-radius-xl: 2rem;
            --th-radius-lg: 1.55rem;
            --th-radius-md: 1.15rem;
            --th-radius-sm: 0.86rem;
            position: relative;
            width: 100%;
            color: var(--th-ink);
            isolation: isolate;
        }

        .tourhub-lux-page a {
            color: inherit;
        }

        .tourhub-lux-page button,
        .tourhub-lux-page a {
            -webkit-tap-highlight-color: transparent;
        }

        .th-page-bg {
            position: fixed;
            inset: 0;
            z-index: -5;
            pointer-events: none;
            background:
                radial-gradient(circle at 12% 14%, rgba(37, 99, 235, 0.24), transparent 31rem),
                radial-gradient(circle at 86% 7%, rgba(6, 182, 212, 0.20), transparent 28rem),
                radial-gradient(circle at 50% 100%, rgba(16, 185, 129, 0.16), transparent 34rem),
                linear-gradient(135deg, var(--th-bg-0), var(--th-bg-1) 46%, #050816 100%);
        }

        .th-page-bg::before {
            content: '';
            position: absolute;
            inset: 0;
            opacity: 0.52;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.036) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.036) 1px, transparent 1px);
            background-size: 34px 34px;
            mask-image: radial-gradient(circle at center, black 0, black 54%, transparent 78%);
        }

        .th-page-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(115deg, transparent 0%, rgba(255, 255, 255, 0.045) 28%, transparent 46%),
                radial-gradient(circle at 50% 50%, transparent 0, rgba(2, 6, 23, 0.16) 72%);
            animation: th-bg-pan 18s linear infinite alternate;
        }

        .th-shell {
            position: relative;
            display: grid;
            gap: 1.25rem;
            width: 100%;
        }

        .th-section {
            position: relative;
        }

        .th-card,
        .th-hero,
        .th-table-shell,
        .th-lux-panel {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--th-border);
            background:
                radial-gradient(circle at 0 0, rgba(96, 165, 250, 0.18), transparent 26rem),
                radial-gradient(circle at 100% 100%, rgba(16, 185, 129, 0.13), transparent 26rem),
                linear-gradient(145deg, rgba(15, 23, 42, 0.92), rgba(15, 23, 42, 0.70));
            box-shadow:
                var(--th-shadow-soft),
                inset 0 1px 0 rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .th-card::before,
        .th-hero::before,
        .th-table-shell::before,
        .th-lux-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            opacity: 0.72;
            pointer-events: none;
            background:
                linear-gradient(110deg, rgba(255, 255, 255, 0.08), transparent 18%, transparent 72%, rgba(255, 255, 255, 0.05)),
                radial-gradient(circle at var(--th-mouse-x, 50%) var(--th-mouse-y, 50%), rgba(255, 255, 255, 0.10), transparent 18rem);
        }

        .th-card::after,
        .th-hero::after,
        .th-table-shell::after {
            content: '';
            position: absolute;
            inset: 1px;
            pointer-events: none;
            border-radius: inherit;
            border: 1px solid rgba(255, 255, 255, 0.045);
        }

        .th-hero {
            min-height: 25rem;
            border-radius: var(--th-radius-xl);
        }

        .th-card,
        .th-lux-panel,
        .th-table-shell {
            border-radius: var(--th-radius-lg);
        }

        .th-grid-layer {
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: 0.45;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
            background-size: 26px 26px;
            mask-image: linear-gradient(to bottom, black, transparent 80%);
        }

        .th-noise-layer {
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: 0.11;
            background-image:
                radial-gradient(circle at 25% 25%, rgba(255,255,255,.18) 0 1px, transparent 1px),
                radial-gradient(circle at 75% 75%, rgba(255,255,255,.10) 0 1px, transparent 1px);
            background-size: 18px 18px, 22px 22px;
        }

        .th-scan-line {
            position: absolute;
            left: -20%;
            right: -20%;
            top: 0;
            height: 1px;
            opacity: 0.52;
            pointer-events: none;
            background: linear-gradient(90deg, transparent, rgba(103, 232, 249, 0.72), transparent);
            animation: th-scan 7s ease-in-out infinite;
        }

        .th-orb {
            position: absolute;
            width: 28rem;
            height: 28rem;
            border-radius: 999px;
            filter: blur(78px);
            opacity: 0.32;
            pointer-events: none;
            transform: translate3d(0, 0, 0);
        }

        .th-orb-blue {
            left: -12rem;
            top: -12rem;
            background: var(--th-blue);
            animation: th-float-a 12s ease-in-out infinite;
        }

        .th-orb-green {
            right: -10rem;
            bottom: -15rem;
            background: var(--th-green);
            animation: th-float-b 14s ease-in-out infinite;
        }

        .th-orb-cyan {
            right: 24%;
            top: -13rem;
            background: var(--th-cyan);
            opacity: 0.18;
            animation: th-float-c 16s ease-in-out infinite;
        }

        .th-orb-purple {
            left: 44%;
            bottom: -17rem;
            background: var(--th-purple);
            opacity: 0.16;
            animation: th-float-d 18s ease-in-out infinite;
        }

        .th-inner {
            position: relative;
            z-index: 2;
            padding: 1.25rem;
        }

        @media (min-width: 768px) {
            .th-inner {
                padding: 1.65rem;
            }
        }

        @media (min-width: 1180px) {
            .th-inner {
                padding: 2rem;
            }
        }

        .th-hero-layout {
            display: grid;
            gap: 1.25rem;
            align-items: stretch;
        }

        @media (min-width: 1180px) {
            .th-hero-layout {
                grid-template-columns: minmax(0, 1.35fr) minmax(370px, 0.65fr);
            }
        }

        .th-hero-copy {
            display: flex;
            min-height: 21rem;
            flex-direction: column;
            justify-content: space-between;
            gap: 1.25rem;
        }

        .th-eyebrow-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.58rem;
            align-items: center;
        }

        .th-chip,
        .th-btn,
        .th-link-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            border-radius: 999px;
            font-size: 0.74rem;
            line-height: 1rem;
            font-weight: 950;
            text-decoration: none;
            white-space: nowrap;
            letter-spacing: 0.01em;
        }

        .th-chip {
            min-height: 2rem;
            padding: 0.43rem 0.78rem;
            border: 1px solid var(--th-white-border);
            background: rgba(255, 255, 255, 0.08);
            color: #dbeafe;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .th-chip-dot {
            width: 0.48rem;
            height: 0.48rem;
            border-radius: 999px;
            background: #34d399;
            box-shadow: 0 0 18px rgba(52, 211, 153, 0.92);
        }

        .th-chip-pulse {
            position: relative;
        }

        .th-chip-pulse::after {
            content: '';
            position: absolute;
            left: 0.52rem;
            width: 0.48rem;
            height: 0.48rem;
            border-radius: 999px;
            border: 1px solid rgba(52, 211, 153, 0.9);
            animation: th-ping 1.9s cubic-bezier(0, 0, .2, 1) infinite;
        }

        .th-chip-green {
            color: #a7f3d0;
            border-color: rgba(52, 211, 153, 0.28);
            background: rgba(16, 185, 129, 0.14);
        }

        .th-chip-blue {
            color: #bfdbfe;
            border-color: rgba(96, 165, 250, 0.32);
            background: rgba(37, 99, 235, 0.17);
        }

        .th-chip-purple {
            color: #ddd6fe;
            border-color: rgba(167, 139, 250, 0.34);
            background: rgba(124, 58, 237, 0.17);
        }

        .th-chip-yellow {
            color: #fde68a;
            border-color: rgba(251, 191, 36, 0.34);
            background: rgba(245, 158, 11, 0.16);
        }

        .th-chip-orange {
            color: #fed7aa;
            border-color: rgba(253, 186, 116, 0.34);
            background: rgba(249, 115, 22, 0.16);
        }

        .th-chip-cyan {
            color: #cffafe;
            border-color: rgba(103, 232, 249, 0.34);
            background: rgba(6, 182, 212, 0.16);
        }

        .th-chip-red {
            color: #fecdd3;
            border-color: rgba(251, 113, 133, 0.34);
            background: rgba(244, 63, 94, 0.15);
        }

        .th-title {
            max-width: 62rem;
            margin: 1.2rem 0 0;
            color: #ffffff;
            font-size: clamp(2.25rem, 4.5vw, 5.8rem);
            line-height: 0.88;
            font-weight: 1000;
            letter-spacing: -0.075em;
            text-wrap: balance;
        }

        .th-title-gradient {
            display: inline;
            color: transparent;
            background: linear-gradient(90deg, #ffffff, #bfdbfe 35%, #67e8f9 68%, #bbf7d0);
            -webkit-background-clip: text;
            background-clip: text;
            filter: drop-shadow(0 18px 32px rgba(37, 99, 235, 0.24));
        }

        .th-subtitle {
            max-width: 51rem;
            margin: 1.05rem 0 0;
            color: #cbd5e1;
            font-size: 0.96rem;
            line-height: 1.78;
            font-weight: 650;
        }

        .th-subtitle strong {
            color: white;
            font-weight: 950;
        }

        .th-hero-meta-row {
            display: grid;
            gap: 0.75rem;
            margin-top: 1.3rem;
        }

        @media (min-width: 720px) {
            .th-hero-meta-row {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .th-hero-meta-card {
            position: relative;
            overflow: hidden;
            min-height: 5rem;
            padding: 0.9rem;
            border-radius: 1.2rem;
            border: 1px solid var(--th-white-border);
            background: rgba(255, 255, 255, 0.07);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .th-hero-meta-card::after {
            content: '';
            position: absolute;
            right: -2rem;
            top: -2rem;
            width: 5rem;
            height: 5rem;
            border-radius: 999px;
            background: rgba(103, 232, 249, 0.12);
            filter: blur(2px);
        }

        .th-small-label {
            color: #94a3b8;
            font-size: 0.675rem;
            line-height: 1rem;
            font-weight: 1000;
            letter-spacing: 0.09em;
            text-transform: uppercase;
        }

        .th-hero-meta-card strong {
            position: relative;
            z-index: 2;
            display: block;
            margin-top: 0.28rem;
            color: white;
            font-size: 1.12rem;
            line-height: 1.25;
            font-weight: 1000;
            letter-spacing: -0.04em;
        }

        .th-action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.72rem;
            align-items: center;
            margin-top: 1.5rem;
        }

        .th-btn {
            position: relative;
            min-height: 2.95rem;
            overflow: hidden;
            padding: 0.85rem 1.08rem;
            border: 0;
            cursor: pointer;
            transition:
                transform 180ms ease,
                border-color 180ms ease,
                box-shadow 180ms ease,
                opacity 180ms ease,
                background 180ms ease;
        }

        .th-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            opacity: 0;
            background: linear-gradient(110deg, transparent, rgba(255, 255, 255, 0.28), transparent);
            transform: translateX(-110%);
            transition: opacity 180ms ease;
        }

        .th-btn:hover {
            transform: translateY(-2px);
        }

        .th-btn:hover::before {
            opacity: 1;
            animation: th-shine 820ms ease;
        }

        .th-btn:disabled {
            opacity: 0.66;
            cursor: wait;
            transform: none;
        }

        .th-btn-primary {
            color: white;
            background: linear-gradient(135deg, #2563eb, #06b6d4);
            box-shadow: 0 18px 42px rgba(37, 99, 235, 0.28);
        }

        .th-btn-success {
            color: #042f2e;
            background: linear-gradient(135deg, #86efac, #67e8f9);
            box-shadow: 0 18px 42px rgba(16, 185, 129, 0.25);
        }

        .th-btn-soft {
            color: #dbeafe;
            border: 1px solid var(--th-white-border);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .th-btn-danger {
            color: #ffe4e6;
            border: 1px solid rgba(251, 113, 133, 0.28);
            background: rgba(244, 63, 94, 0.13);
        }

        .th-hero-aside {
            display: grid;
            gap: 0.9rem;
        }

        .th-glass {
            border: 1px solid var(--th-white-border);
            background: rgba(255, 255, 255, 0.075);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.08),
                0 16px 38px rgba(2, 6, 23, 0.16);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .th-readiness-card {
            position: relative;
            display: grid;
            gap: 1rem;
            min-height: 14.8rem;
            padding: 1rem;
            border-radius: 1.45rem;
            overflow: hidden;
        }

        .th-readiness-card::before {
            content: '';
            position: absolute;
            inset: -40% -40% auto auto;
            width: 16rem;
            height: 16rem;
            border-radius: 999px;
            background: conic-gradient(from 180deg, rgba(96, 165, 250, .22), rgba(103, 232, 249, .12), rgba(134, 239, 172, .22), transparent);
            animation: th-spin-slow 12s linear infinite;
        }

        .th-readiness-top {
            position: relative;
            z-index: 2;
            display: flex;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
        }

        .th-readiness-ring {
            position: relative;
            width: 8.2rem;
            height: 8.2rem;
            flex: 0 0 auto;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background:
                radial-gradient(circle at center, rgba(15, 23, 42, 0.96) 0 54%, transparent 55%),
                conic-gradient(from -90deg, #67e8f9 var(--p, 0%), rgba(148, 163, 184, 0.18) 0);
            box-shadow:
                inset 0 0 0 1px rgba(255,255,255,.08),
                0 20px 50px rgba(6, 182, 212, 0.18);
        }

        .th-readiness-ring::before {
            content: '';
            position: absolute;
            inset: 0.7rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.10);
        }

        .th-readiness-number {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.72rem;
            line-height: 1;
            font-weight: 1000;
            letter-spacing: -0.06em;
        }

        .th-readiness-number small {
            margin-top: 0.24rem;
            color: #93c5fd;
            font-size: 0.58rem;
            font-weight: 1000;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .th-readiness-content {
            min-width: 0;
        }

        .th-readiness-title {
            margin: 0.35rem 0 0;
            color: white;
            font-size: 1.28rem;
            line-height: 1.15;
            font-weight: 1000;
            letter-spacing: -0.05em;
        }

        .th-readiness-desc {
            margin: 0.48rem 0 0;
            color: #cbd5e1;
            font-size: 0.78rem;
            line-height: 1.6;
            font-weight: 720;
        }

        .th-progress {
            position: relative;
            height: 0.58rem;
            width: 100%;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.20);
            box-shadow: inset 0 1px 2px rgba(2, 6, 23, 0.2);
        }

        .th-progress span {
            display: block;
            height: 100%;
            width: 0;
            min-width: 0.3rem;
            border-radius: inherit;
            background: linear-gradient(90deg, #22d3ee, #10b981, #f59e0b);
            box-shadow: 0 0 22px rgba(34, 211, 238, 0.34);
            transform-origin: left center;
            transition: width 900ms cubic-bezier(.2, .8, .2, 1);
        }

        .th-readiness-card .th-progress {
            position: relative;
            z-index: 2;
        }

        .th-mini-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .th-mini-stat {
            position: relative;
            min-height: 6rem;
            overflow: hidden;
            padding: 0.95rem;
            border-radius: 1.15rem;
        }

        .th-mini-stat::after {
            content: '';
            position: absolute;
            right: -2.3rem;
            bottom: -2.4rem;
            width: 6rem;
            height: 6rem;
            border-radius: 999px;
            background: rgba(96, 165, 250, 0.12);
        }

        .th-mini-stat strong {
            position: relative;
            z-index: 2;
            display: block;
            margin-top: 0.32rem;
            color: white;
            font-size: 1.35rem;
            line-height: 1.18;
            font-weight: 1000;
            letter-spacing: -0.055em;
        }

        .th-mini-stat p {
            position: relative;
            z-index: 2;
            margin: 0.35rem 0 0;
            color: #94a3b8;
            font-size: 0.72rem;
            line-height: 1.4;
            font-weight: 700;
        }

        .th-command-strip {
            display: grid;
            gap: 0.75rem;
            margin-top: 0.85rem;
        }

        @media (min-width: 720px) {
            .th-command-strip {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .th-command-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            min-height: 4.8rem;
            padding: 0.85rem;
            border-radius: 1.15rem;
            border: 1px solid var(--th-white-border);
            background: rgba(255, 255, 255, 0.065);
            text-decoration: none;
            transition: transform 180ms ease, background 180ms ease, border-color 180ms ease;
        }

        .th-command-item:hover {
            transform: translateY(-2px);
            border-color: rgba(103, 232, 249, 0.35);
            background: rgba(255, 255, 255, 0.095);
        }

        .th-command-icon {
            width: 2.3rem;
            height: 2.3rem;
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            border-radius: 0.82rem;
            color: white;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.95), rgba(6, 182, 212, 0.85));
            box-shadow: 0 14px 26px rgba(37, 99, 235, 0.22);
        }

        .th-command-title {
            color: white;
            font-size: 0.86rem;
            line-height: 1.2;
            font-weight: 1000;
        }

        .th-command-desc {
            margin-top: 0.18rem;
            color: #94a3b8;
            font-size: 0.70rem;
            line-height: 1.35;
            font-weight: 700;
        }

        .th-kpi-grid {
            display: grid;
            gap: 0.9rem;
            grid-template-columns: 1fr;
        }

        @media (min-width: 680px) {
            .th-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .th-kpi-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 1400px) {
            .th-kpi-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }

        .th-kpi-card {
            position: relative;
            overflow: hidden;
            min-height: 9.4rem;
            padding: 1.08rem;
            border-radius: 1.35rem;
            border: 1px solid var(--th-border);
            background:
                radial-gradient(circle at 100% 0, rgba(96, 165, 250, 0.15), transparent 8rem),
                linear-gradient(145deg, rgba(15, 23, 42, 0.88), rgba(15, 23, 42, 0.63));
            box-shadow: 0 18px 48px rgba(2, 6, 23, 0.22);
            transform-style: preserve-3d;
            transition: transform 220ms ease, border-color 220ms ease, box-shadow 220ms ease;
        }

        .th-kpi-card:hover {
            border-color: rgba(96, 165, 250, 0.45);
            box-shadow: 0 24px 60px rgba(2, 6, 23, 0.30);
        }

        .th-kpi-card::before {
            content: '';
            position: absolute;
            inset: 0;
            opacity: 0;
            pointer-events: none;
            background: radial-gradient(circle at var(--th-mouse-x, 50%) var(--th-mouse-y, 50%), rgba(255, 255, 255, 0.12), transparent 9rem);
            transition: opacity 180ms ease;
        }

        .th-kpi-card:hover::before {
            opacity: 1;
        }

        .th-kpi-card::after {
            content: '';
            position: absolute;
            right: -3rem;
            bottom: -3.2rem;
            width: 8.5rem;
            height: 8.5rem;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.16);
            filter: blur(2px);
        }

        .th-kpi-top {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.8rem;
        }

        .th-kpi-icon {
            width: 2.65rem;
            height: 2.65rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.11);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .th-kpi-value {
            position: relative;
            z-index: 2;
            margin: 0.65rem 0 0;
            color: white;
            font-size: 2.55rem;
            line-height: 0.92;
            font-weight: 1000;
            letter-spacing: -0.075em;
        }

        .th-kpi-desc {
            position: relative;
            z-index: 2;
            margin-top: 0.5rem;
            color: #bfdbfe;
            font-size: 0.76rem;
            line-height: 1.35;
            font-weight: 820;
        }

        .th-kpi-trend {
            position: relative;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            gap: 0.32rem;
            margin-top: 0.9rem;
            padding: 0.32rem 0.55rem;
            border-radius: 999px;
            color: #a7f3d0;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(52, 211, 153, 0.20);
            font-size: 0.66rem;
            font-weight: 950;
            letter-spacing: 0.03em;
        }

        .th-section-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.05rem;
        }

        @media (max-width: 820px) {
            .th-section-header {
                flex-direction: column;
            }
        }

        .th-section-kicker {
            color: #93c5fd;
            font-size: 0.72rem;
            line-height: 1rem;
            font-weight: 1000;
            letter-spacing: 0.105em;
            text-transform: uppercase;
        }

        .th-section-title {
            margin: 0.25rem 0 0;
            color: white;
            font-size: clamp(1.32rem, 2.2vw, 2.05rem);
            line-height: 1.08;
            font-weight: 1000;
            letter-spacing: -0.055em;
            text-wrap: balance;
        }

        .th-section-subtitle {
            max-width: 52rem;
            margin: 0.42rem 0 0;
            color: #94a3b8;
            font-size: 0.86rem;
            line-height: 1.64;
            font-weight: 680;
        }

        .th-content-grid {
            display: grid;
            gap: 1.25rem;
        }

        @media (min-width: 1150px) {
            .th-content-grid-main {
                grid-template-columns: minmax(0, 1.44fr) minmax(340px, 0.56fr);
            }

            .th-content-grid-three {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .th-insight-row {
            display: grid;
            gap: 0.85rem;
        }

        @media (min-width: 980px) {
            .th-insight-row {
                grid-template-columns: 1fr 1fr 1fr;
            }
        }

        .th-insight-card {
            position: relative;
            display: flex;
            min-height: 7.8rem;
            gap: 0.9rem;
            align-items: flex-start;
            padding: 1rem;
            border-radius: 1.35rem;
            border: 1px solid var(--th-border);
            background:
                radial-gradient(circle at 100% 0, rgba(103, 232, 249, 0.10), transparent 9rem),
                rgba(15, 23, 42, 0.62);
            box-shadow: 0 18px 44px rgba(2, 6, 23, 0.22);
            overflow: hidden;
        }

        .th-insight-card::before {
            content: '';
            position: absolute;
            left: -6rem;
            bottom: -7rem;
            width: 10rem;
            height: 10rem;
            border-radius: 999px;
            background: rgba(16, 185, 129, 0.10);
        }

        .th-insight-icon {
            position: relative;
            z-index: 2;
            width: 2.75rem;
            height: 2.75rem;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 1rem;
            color: white;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.95), rgba(6, 182, 212, 0.84));
            box-shadow: 0 16px 28px rgba(37, 99, 235, 0.22);
        }

        .th-insight-title {
            position: relative;
            z-index: 2;
            margin: 0;
            color: white;
            font-size: 1rem;
            line-height: 1.25;
            font-weight: 1000;
            letter-spacing: -0.035em;
        }

        .th-insight-desc {
            position: relative;
            z-index: 2;
            margin: 0.36rem 0 0;
            color: #94a3b8;
            font-size: 0.78rem;
            line-height: 1.55;
            font-weight: 700;
        }

        .th-quality-grid {
            display: grid;
            gap: 0.85rem;
        }

        @media (min-width: 760px) {
            .th-quality-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .th-quality-card {
            position: relative;
            overflow: hidden;
            min-height: 10.8rem;
            padding: 1rem;
            border-radius: 1.25rem;
        }

        .th-quality-card::before {
            content: '';
            position: absolute;
            inset: auto -4rem -5rem auto;
            width: 10rem;
            height: 10rem;
            border-radius: 999px;
            background: rgba(96, 165, 250, 0.10);
        }

        .th-quality-top {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.85rem;
        }

        .th-quality-number {
            margin-top: 0.38rem;
            color: white;
            font-size: 2.15rem;
            line-height: 0.96;
            font-weight: 1000;
            letter-spacing: -0.06em;
        }

        .th-quality-percent {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 4.2rem;
            border-radius: 999px;
            padding: 0.38rem 0.68rem;
            background: rgba(255, 255, 255, 0.09);
            color: white;
            font-size: 0.72rem;
            font-weight: 1000;
            border: 1px solid var(--th-white-border);
        }

        .th-quality-desc,
        .th-issue-desc,
        .th-list-desc {
            color: #94a3b8;
            font-size: 0.78rem;
            line-height: 1.58;
            font-weight: 690;
        }

        .th-quality-desc {
            position: relative;
            z-index: 2;
            margin-top: 0.75rem;
        }

        .th-tone-green .th-progress span {
            background: linear-gradient(90deg, #34d399, #10b981);
        }

        .th-tone-blue .th-progress span {
            background: linear-gradient(90deg, #60a5fa, #2563eb);
        }

        .th-tone-cyan .th-progress span {
            background: linear-gradient(90deg, #67e8f9, #06b6d4);
        }

        .th-tone-yellow .th-progress span {
            background: linear-gradient(90deg, #fbbf24, #f59e0b);
        }

        .th-tone-orange .th-progress span {
            background: linear-gradient(90deg, #fdba74, #f97316);
        }

        .th-tone-purple .th-progress span {
            background: linear-gradient(90deg, #a78bfa, #7c3aed);
        }

        .th-tone-red .th-progress span {
            background: linear-gradient(90deg, #fb7185, #e11d48);
        }

        .th-tone-slate .th-progress span {
            background: linear-gradient(90deg, #cbd5e1, #64748b);
        }

        .th-issue-list,
        .th-summary-list,
        .th-timeline,
        .th-latest-grid {
            display: grid;
            gap: 0.75rem;
        }

        .th-issue-item {
            position: relative;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            overflow: hidden;
            padding: 0.95rem;
            border-radius: 1.15rem;
        }

        .th-issue-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.78rem;
            bottom: 0.78rem;
            width: 3px;
            border-radius: 999px;
            background: linear-gradient(to bottom, #fda4af, #f59e0b);
        }

        .th-issue-value {
            min-width: 3.2rem;
            border-radius: 0.95rem;
            padding: 0.58rem 0.75rem;
            text-align: center;
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--th-white-border);
            font-size: 1.16rem;
            font-weight: 1000;
            letter-spacing: -0.05em;
        }

        .th-watchlist-footer {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 1.15rem;
            border: 1px dashed rgba(251, 191, 36, 0.32);
            background: rgba(245, 158, 11, 0.08);
        }

        .th-watchlist-footer strong {
            color: #fde68a;
            font-size: 0.82rem;
            font-weight: 1000;
        }

        .th-watchlist-footer p {
            margin: 0.35rem 0 0;
            color: #cbd5e1;
            font-size: 0.76rem;
            line-height: 1.55;
            font-weight: 700;
        }

        .th-feature-grid {
            display: grid;
            gap: 0.9rem;
            grid-template-columns: 1fr;
        }

        @media (min-width: 700px) {
            .th-feature-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1350px) {
            .th-feature-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .th-destination-card {
            position: relative;
            display: block;
            min-height: 20rem;
            overflow: hidden;
            border-radius: 1.35rem;
            border: 1px solid var(--th-white-border);
            background: #0f172a;
            box-shadow: 0 20px 52px rgba(2, 6, 23, 0.26);
            text-decoration: none;
            transform-style: preserve-3d;
            transition:
                transform 240ms ease,
                border-color 240ms ease,
                box-shadow 240ms ease;
        }

        .th-destination-card:hover {
            transform: translateY(-4px);
            border-color: rgba(96, 165, 250, 0.52);
            box-shadow: 0 28px 72px rgba(2, 6, 23, 0.36);
        }

        .th-destination-card img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 650ms ease, filter 650ms ease;
        }

        .th-destination-card:hover img {
            transform: scale(1.08);
            filter: saturate(1.12) contrast(1.03);
        }

        .th-img-fallback {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 0.82rem;
            font-weight: 1000;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.26), transparent 34%),
                radial-gradient(circle at bottom right, rgba(16, 185, 129, 0.18), transparent 36%),
                linear-gradient(135deg, #1e293b, #020617);
        }

        .th-image-overlay {
            position: absolute;
            inset: 0;
            background:
                linear-gradient(to top, rgba(2, 6, 23, 0.96), rgba(2, 6, 23, 0.44), rgba(2, 6, 23, 0.13)),
                radial-gradient(circle at 50% 0, rgba(255, 255, 255, 0.12), transparent 40%);
        }

        .th-destination-top {
            position: absolute;
            left: 1rem;
            right: 1rem;
            top: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .th-rating-pill {
            border-radius: 999px;
            padding: 0.43rem 0.72rem;
            color: #020617;
            background: rgba(255, 255, 255, 0.94);
            font-size: 0.72rem;
            font-weight: 1000;
            box-shadow: 0 12px 28px rgba(2, 6, 23, 0.22);
        }

        .th-destination-bottom {
            position: absolute;
            left: 1rem;
            right: 1rem;
            bottom: 1rem;
            transform: translateZ(24px);
        }

        .th-destination-name {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
            margin: 0;
            color: white;
            font-size: 1.08rem;
            line-height: 1.18;
            font-weight: 1000;
            letter-spacing: -0.035em;
        }

        .th-destination-location {
            margin-top: 0.48rem;
            color: #e2e8f0;
            font-size: 0.76rem;
            line-height: 1.35;
            font-weight: 760;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .th-destination-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.75rem;
        }

        .th-edit-hint {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: #bfdbfe;
            font-size: 0.67rem;
            line-height: 1rem;
            font-weight: 1000;
            text-transform: uppercase;
            letter-spacing: 0.085em;
        }

        .th-feature-empty {
            grid-column: 1 / -1;
            min-height: 13rem;
            display: grid;
            place-items: center;
            border-radius: 1.25rem;
            padding: 2rem;
            text-align: center;
            color: #94a3b8;
            font-weight: 850;
        }

        .th-pipeline-panel {
            min-height: 100%;
        }

        .th-timeline-item {
            position: relative;
            padding: 0.95rem 0.95rem 0.95rem 3.15rem;
            border-radius: 1.15rem;
            overflow: hidden;
        }

        .th-timeline-item::after {
            content: '';
            position: absolute;
            left: 1.65rem;
            top: 2.6rem;
            bottom: -1rem;
            width: 1px;
            background: linear-gradient(to bottom, rgba(103, 232, 249, 0.6), transparent);
        }

        .th-timeline-item:last-child::after {
            display: none;
        }

        .th-timeline-number {
            position: absolute;
            left: 0.95rem;
            top: 0.95rem;
            width: 1.55rem;
            height: 1.55rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            color: white;
            background: linear-gradient(135deg, #2563eb, #06b6d4);
            font-size: 0.74rem;
            font-weight: 1000;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.13);
            z-index: 2;
        }

        .th-timeline-title,
        .th-list-title {
            color: white;
            font-size: 0.88rem;
            line-height: 1.35;
            font-weight: 1000;
            letter-spacing: -0.02em;
        }

        .th-terminal-box {
            margin-top: 1rem;
            overflow: hidden;
            border-radius: 1.15rem;
            border: 1px solid rgba(103, 232, 249, 0.18);
            background: rgba(2, 6, 23, 0.54);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        .th-terminal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.72rem 0.82rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.055);
        }

        .th-terminal-dots {
            display: inline-flex;
            align-items: center;
            gap: 0.34rem;
        }

        .th-terminal-dots span {
            width: 0.52rem;
            height: 0.52rem;
            border-radius: 999px;
            background: #f87171;
        }

        .th-terminal-dots span:nth-child(2) {
            background: #fbbf24;
        }

        .th-terminal-dots span:nth-child(3) {
            background: #34d399;
        }

        .th-terminal-title {
            color: #bfdbfe;
            font-size: 0.68rem;
            font-weight: 1000;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .th-terminal-body {
            display: grid;
            gap: 0.78rem;
            padding: 0.9rem;
        }

        .th-terminal-line {
            min-width: 0;
        }

        .th-terminal-line label {
            display: block;
            color: #67e8f9;
            font-size: 0.66rem;
            font-weight: 1000;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .th-terminal-line p {
            margin: 0.34rem 0 0;
            color: #cbd5e1;
            font-size: 0.73rem;
            line-height: 1.48;
            font-weight: 750;
            word-break: break-all;
        }

        .th-list-item {
            position: relative;
            overflow: hidden;
            padding: 0.95rem;
            border-radius: 1.15rem;
        }

        .th-list-item::after {
            content: '';
            position: absolute;
            right: -2.5rem;
            bottom: -2.8rem;
            width: 6rem;
            height: 6rem;
            border-radius: 999px;
            background: rgba(96, 165, 250, 0.10);
        }

        .th-list-head {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }

        .th-list-value {
            color: white;
            font-size: 0.9rem;
            font-weight: 1000;
            white-space: nowrap;
            letter-spacing: -0.035em;
        }

        .th-scroll-area {
            max-height: 31rem;
            overflow: auto;
            padding-right: 0.25rem;
            scrollbar-width: thin;
            scrollbar-color: rgba(96, 165, 250, .45) rgba(255, 255, 255, .06);
        }

        .th-scroll-area::-webkit-scrollbar {
            width: 0.45rem;
        }

        .th-scroll-area::-webkit-scrollbar-track {
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
        }

        .th-scroll-area::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: rgba(96, 165, 250, 0.45);
        }

        .th-latest-grid {
            grid-template-columns: 1fr;
        }

        @media (min-width: 700px) {
            .th-latest-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1280px) {
            .th-latest-grid {
                grid-template-columns: repeat(6, minmax(0, 1fr));
            }
        }

        .th-latest-item {
            position: relative;
            display: block;
            min-height: 7.3rem;
            overflow: hidden;
            padding: 0.95rem;
            border-radius: 1.1rem;
            text-decoration: none;
            transition:
                transform 180ms ease,
                background 180ms ease,
                border-color 180ms ease;
        }

        .th-latest-item::after {
            content: '';
            position: absolute;
            right: -2.8rem;
            bottom: -3rem;
            width: 6rem;
            height: 6rem;
            border-radius: 999px;
            background: rgba(6, 182, 212, 0.10);
        }

        .th-latest-item:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.10);
            border-color: rgba(96, 165, 250, 0.30);
        }

        .th-latest-name {
            position: relative;
            z-index: 2;
            color: white;
            font-size: 0.86rem;
            line-height: 1.25;
            font-weight: 1000;
            letter-spacing: -0.025em;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
        }

        .th-status-green {
            color: #86efac;
        }

        .th-status-red {
            color: #fda4af;
        }

        .th-status-blue {
            color: #bfdbfe;
        }

        .th-table-shell .th-inner {
            padding: 1rem;
        }

        @media (min-width: 768px) {
            .th-table-shell .th-inner {
                padding: 1.25rem;
            }
        }

        .th-table-box {
            overflow: hidden;
            border-radius: 1.18rem;
            background: white;
            box-shadow: 0 18px 50px rgba(2, 6, 23, 0.26);
        }

        .dark .th-table-box {
            background: rgb(17, 24, 39);
        }

        .th-table-box .fi-ta,
        .th-table-box .fi-ta-ctn,
        .th-table-box .fi-ta-content {
            border-radius: 1.18rem;
        }

        .th-table-box .fi-ta-header,
        .th-table-box .fi-ta-toolbar {
            border-color: rgba(148, 163, 184, 0.16);
        }

        .th-table-box .fi-ta-row:hover {
            background: rgba(59, 130, 246, 0.035);
        }

        .th-table-top-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.55rem;
        }

        .th-divider {
            height: 1px;
            width: 100%;
            margin: 1rem 0;
            background: linear-gradient(90deg, transparent, rgba(148, 163, 184, 0.24), transparent);
        }

        .th-map-visual {
            position: relative;
            min-height: 15.2rem;
            overflow: hidden;
            border-radius: 1.35rem;
            border: 1px solid var(--th-white-border);
            background:
                radial-gradient(circle at 48% 42%, rgba(103, 232, 249, 0.18), transparent 5rem),
                linear-gradient(145deg, rgba(2, 6, 23, 0.72), rgba(15, 23, 42, 0.68));
        }

        .th-map-visual::before {
            content: '';
            position: absolute;
            inset: 0;
            opacity: 0.25;
            background-image:
                linear-gradient(rgba(255,255,255,.10) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.10) 1px, transparent 1px);
            background-size: 22px 22px;
            transform: perspective(500px) rotateX(58deg) scale(1.22);
            transform-origin: center bottom;
        }

        .th-map-ring {
            position: absolute;
            inset: 2.3rem;
            border-radius: 999px;
            border: 1px solid rgba(103, 232, 249, 0.22);
            animation: th-pulse-ring 3s ease-in-out infinite;
        }

        .th-map-ring:nth-child(2) {
            inset: 4.2rem;
            animation-delay: 450ms;
        }

        .th-map-ring:nth-child(3) {
            inset: 6rem;
            animation-delay: 900ms;
        }

        .th-map-pin {
            position: absolute;
            width: 0.84rem;
            height: 0.84rem;
            border-radius: 999px;
            background: #67e8f9;
            box-shadow:
                0 0 0 7px rgba(103, 232, 249, 0.10),
                0 0 24px rgba(103, 232, 249, 0.75);
        }

        .th-map-pin.pin-a {
            left: 28%;
            top: 32%;
        }

        .th-map-pin.pin-b {
            left: 62%;
            top: 54%;
            background: #86efac;
            box-shadow:
                0 0 0 7px rgba(134, 239, 172, 0.10),
                0 0 24px rgba(134, 239, 172, 0.75);
        }

        .th-map-pin.pin-c {
            left: 48%;
            top: 70%;
            background: #fcd34d;
            box-shadow:
                0 0 0 7px rgba(252, 211, 77, 0.10),
                0 0 24px rgba(252, 211, 77, 0.75);
        }

        .th-map-card {
            position: absolute;
            left: 1rem;
            right: 1rem;
            bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.82rem;
            border-radius: 1rem;
            border: 1px solid rgba(255,255,255,.11);
            background: rgba(2, 6, 23, 0.52);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .th-map-card strong {
            color: white;
            font-size: 0.86rem;
            line-height: 1.25;
            font-weight: 1000;
        }

        .th-map-card span {
            display: block;
            margin-top: 0.18rem;
            color: #94a3b8;
            font-size: 0.69rem;
            font-weight: 760;
        }

        .th-empty-state {
            border-radius: 1.15rem;
            padding: 1.5rem;
            text-align: center;
            color: #94a3b8;
            font-weight: 850;
        }

        /*
         |--------------------------------------------------------------------------
         | Livewire-safe reveal animation
         |--------------------------------------------------------------------------
         | Versi sebelumnya membuat .th-reveal opacity: 0 secara default. Saat action
         | Filament/Livewire seperti reloadMlDataset selesai, DOM dapat di-morph ulang
         | tanpa menjalankan ulang script Blade dari awal. Akibatnya section baru tetap
         | transparan dan halaman terlihat kosong walaupun data sebenarnya sudah ada.
         |
         | Perbaikan: konten selalu visible secara default. Animasi tetap ada lewat
         | class .is-visible dan keyframe halus, tetapi tidak pernah mengunci konten
         | menjadi opacity: 0 jika Livewire selesai re-render.
         */
        .th-reveal {
            opacity: 1;
            transform: translateY(0);
            animation: th-safe-reveal 520ms cubic-bezier(.2, .8, .2, 1) both;
        }

        .th-reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
            transition:
                opacity 620ms ease,
                transform 620ms cubic-bezier(.2, .8, .2, 1);
        }

        .th-delay-1 {
            animation-delay: 60ms;
        }

        .th-delay-2 {
            animation-delay: 110ms;
        }

        .th-delay-3 {
            animation-delay: 160ms;
        }

        .th-delay-1.is-visible {
            transition-delay: 80ms;
        }

        .th-delay-2.is-visible {
            transition-delay: 140ms;
        }

        .th-delay-3.is-visible {
            transition-delay: 200ms;
        }

        @keyframes th-safe-reveal {
            from {
                opacity: 0.01;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes th-bg-pan {
            0% {
                transform: translate3d(-2%, -1%, 0);
            }
            100% {
                transform: translate3d(2%, 1%, 0);
            }
        }

        @keyframes th-float-a {
            0%, 100% {
                transform: translate3d(0, 0, 0) scale(1);
            }
            50% {
                transform: translate3d(2rem, 1rem, 0) scale(1.08);
            }
        }

        @keyframes th-float-b {
            0%, 100% {
                transform: translate3d(0, 0, 0) scale(1);
            }
            50% {
                transform: translate3d(-1.5rem, -1rem, 0) scale(1.05);
            }
        }

        @keyframes th-float-c {
            0%, 100% {
                transform: translate3d(0, 0, 0) scale(1);
            }
            50% {
                transform: translate3d(1rem, 1.6rem, 0) scale(1.07);
            }
        }

        @keyframes th-float-d {
            0%, 100% {
                transform: translate3d(0, 0, 0) scale(1);
            }
            50% {
                transform: translate3d(-1rem, -1.6rem, 0) scale(1.09);
            }
        }

        @keyframes th-scan {
            0%, 100% {
                transform: translateY(0);
                opacity: 0;
            }
            12% {
                opacity: 0.52;
            }
            50% {
                transform: translateY(32rem);
                opacity: 0.45;
            }
            88% {
                opacity: 0;
            }
        }

        @keyframes th-ping {
            75%, 100% {
                transform: scale(2.4);
                opacity: 0;
            }
        }

        @keyframes th-shine {
            from {
                transform: translateX(-110%);
            }
            to {
                transform: translateX(110%);
            }
        }

        @keyframes th-spin-slow {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes th-pulse-ring {
            0%, 100% {
                opacity: 0.25;
                transform: scale(0.97);
            }
            50% {
                opacity: 0.68;
                transform: scale(1.03);
            }
        }

        @media (max-width: 700px) {
            .th-hero {
                min-height: auto;
                border-radius: 1.4rem;
            }

            .th-title {
                font-size: clamp(2rem, 12vw, 3.3rem);
                letter-spacing: -0.07em;
            }

            .th-readiness-top {
                align-items: flex-start;
                flex-direction: column;
            }

            .th-readiness-ring {
                width: 7rem;
                height: 7rem;
            }

            .th-readiness-number {
                font-size: 1.42rem;
            }

            .th-mini-grid {
                grid-template-columns: 1fr;
            }

            .th-action-row .th-btn {
                width: 100%;
            }

            .th-table-top-actions {
                justify-content: flex-start;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .tourhub-lux-page *,
            .tourhub-lux-page *::before,
            .tourhub-lux-page *::after {
                animation-duration: 0.001ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
                transition-duration: 0.001ms !important;
            }

            .th-reveal {
                opacity: 1;
                transform: none;
            }
        }
    </style>

    <div class="tourhub-lux-page" data-th-page>
        <div class="th-page-bg"></div>

        <div class="th-shell" id="tourhub-destination-dashboard">
            <section class="th-hero th-section th-reveal" data-th-glow>
                <span class="th-grid-layer"></span>
                <span class="th-noise-layer"></span>
                <span class="th-scan-line"></span>
                <span class="th-orb th-orb-blue"></span>
                <span class="th-orb th-orb-green"></span>
                <span class="th-orb th-orb-cyan"></span>
                <span class="th-orb th-orb-purple"></span>

                <div class="th-inner">
                    <div class="th-hero-layout">
                        <div class="th-hero-copy">
                            <div>
                                <div class="th-eyebrow-row">
                                    <span class="th-chip th-chip-pulse">
                                        <span class="th-chip-dot"></span>
                                        Master Data Rekomendasi
                                    </span>
                                    <span class="th-chip th-chip-green">{{ $formatNumber($safe('active')) }} Aktif</span>
                                    <span class="th-chip th-chip-blue">FastAPI Sync Ready</span>
                                    <span class="th-chip {{ $attentionCount > 0 ? 'th-chip-red' : 'th-chip-green' }}">{{ $attentionLabel }}</span>
                                    <span class="th-chip {{ $recommendedStatusClass }}">{{ $recommendedStatus }}</span>
                                </div>

                                <h2 class="th-title">
                                    <span class="th-title-gradient">Command Center</span>
                                    Data Wisata TourHub Bali
                                </h2>

                                <p class="th-subtitle">
                                    Kelola destinasi wisata yang menjadi sumber utama rekomendasi. Data aktif dari
                                    <strong>Laravel</strong> dapat dibaca ulang oleh <strong>FastAPI</strong> setelah tombol reload dijalankan,
                                    sehingga hasil rekomendasi aplikasi mobile tetap selaras dengan data terbaru.
                                </p>

                                <div class="th-hero-meta-row">
                                    <div class="th-hero-meta-card">
                                        <div class="th-small-label">Top Kategori</div>
                                        <strong>{{ filled($topCategoryName) ? $topCategoryName : '-' }}</strong>
                                        <p class="th-list-desc" style="margin:.28rem 0 0;">{{ $formatNumber($topCategoryTotal) }} destinasi</p>
                                    </div>

                                    <div class="th-hero-meta-card">
                                        <div class="th-small-label">Wilayah Terkuat</div>
                                        <strong>{{ filled($topLocationName) ? $topLocationName : '-' }}</strong>
                                        <p class="th-list-desc" style="margin:.28rem 0 0;">{{ $formatNumber($topLocationTotal) }} data</p>
                                    </div>

                                    <div class="th-hero-meta-card">
                                        <div class="th-small-label">Tipe Dominan</div>
                                        <strong>{{ $topTypeName }}</strong>
                                        <p class="th-list-desc" style="margin:.28rem 0 0;">{{ $formatNumber($topTypeTotal) }} data</p>
                                    </div>
                                </div>

                                <div class="th-action-row">
                                    <a href="{{ $this->getCreateDestinationUrl() }}" class="th-btn th-btn-primary">
                                        + Tambah Destinasi
                                    </a>
                                    <button type="button" wire:click="mountAction('reloadMlDataset')" wire:loading.attr="disabled" class="th-btn th-btn-success">
                                        ↻ Reload FastAPI
                                    </button>
                                    <a href="#tourhub-quality-check" class="th-btn th-btn-soft" data-th-scroll>
                                        ✓ Cek Kualitas Data
                                    </a>
                                    <a href="#tourhub-table-section" class="th-btn th-btn-soft" data-th-scroll>
                                        ↓ Lihat Tabel
                                    </a>
                                </div>
                            </div>

                            <div class="th-command-strip">
                                <a href="#tourhub-quality-check" class="th-command-item" data-th-scroll>
                                    <span class="th-command-icon">✓</span>
                                    <span>
                                        <span class="th-command-title">Validasi Data</span>
                                        <span class="th-command-desc">Cek koordinat, gambar, rating, dan status rekomendasi.</span>
                                    </span>
                                </a>

                                <a href="#tourhub-featured-section" class="th-command-item" data-th-scroll>
                                    <span class="th-command-icon">★</span>
                                    <span>
                                        <span class="th-command-title">Preview Unggulan</span>
                                        <span class="th-command-desc">Lihat destinasi terbaik dari rating dan jumlah review.</span>
                                    </span>
                                </a>

                                <a href="#tourhub-table-section" class="th-command-item" data-th-scroll>
                                    <span class="th-command-icon">⌘</span>
                                    <span>
                                        <span class="th-command-title">Kelola Tabel</span>
                                        <span class="th-command-desc">Search, filter, edit, maps, delete, dan bulk action.</span>
                                    </span>
                                </a>
                            </div>
                        </div>

                        <div class="th-hero-aside">
                            <div class="th-readiness-card th-glass" data-th-glow>
                                <div class="th-readiness-top">
                                    <div class="th-readiness-content">
                                        <div class="th-small-label">Kesiapan Dataset</div>
                                        <h3 class="th-readiness-title">{{ $formatNumber($safe('ready_for_recommendation')) }} destinasi siap rekomendasi</h3>
                                        <p class="th-readiness-desc">
                                            Persentase ini membaca kesiapan data untuk kebutuhan sistem rekomendasi TourHub.
                                        </p>
                                    </div>

                                    <div class="th-readiness-ring" style="--p: {{ $readyPercent }}%;" aria-label="Kesiapan dataset {{ $formatPercent($readyPercent) }}">
                                        <div class="th-readiness-number">
                                            {{ $formatPercent($readyPercent) }}
                                            <small>Ready</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="th-progress" data-th-progress="{{ $readyPercent }}">
                                    <span style="width: {{ $readyPercent }}%"></span>
                                </div>
                            </div>

                            <div class="th-map-visual th-glass">
                                <span class="th-map-ring"></span>
                                <span class="th-map-ring"></span>
                                <span class="th-map-ring"></span>
                                <span class="th-map-pin pin-a"></span>
                                <span class="th-map-pin pin-b"></span>
                                <span class="th-map-pin pin-c"></span>
                                <div class="th-map-card">
                                    <div>
                                        <strong>Coverage Bali</strong>
                                        <span>{{ $formatNumber($safe('cities')) }} wilayah terdata</span>
                                    </div>
                                    <span class="th-chip th-chip-cyan">Live Dataset</span>
                                </div>
                            </div>

                            <div class="th-mini-grid">
                                <div class="th-mini-stat th-glass">
                                    <div class="th-small-label">Total Data</div>
                                    <strong data-th-count="{{ (int) $safe('total') }}">{{ $formatNumber($safe('total')) }}</strong>
                                    <p>Seluruh destinasi tersimpan.</p>
                                </div>
                                <div class="th-mini-stat th-glass">
                                    <div class="th-small-label">Update Hari Ini</div>
                                    <strong data-th-count="{{ (int) $safe('updated_today') }}">{{ $formatNumber($safe('updated_today')) }}</strong>
                                    <p>Aktivitas perubahan terbaru.</p>
                                </div>
                                <div class="th-mini-stat th-glass">
                                    <div class="th-small-label">Wilayah</div>
                                    <strong data-th-count="{{ (int) $safe('cities') }}">{{ $formatNumber($safe('cities')) }}</strong>
                                    <p>Kabupaten/kota terjangkau.</p>
                                </div>
                                <div class="th-mini-stat th-glass">
                                    <div class="th-small-label">Terakhir Update</div>
                                    <strong style="font-size:0.98rem;line-height:1.35;letter-spacing:0;">{{ $latestUpdatedLabel }}</strong>
                                    <p>Timestamp data terbaru.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="th-kpi-grid th-section th-reveal th-delay-1">
                <div class="th-kpi-card" data-th-tilt data-th-glow>
                    <div class="th-kpi-top">
                        <div class="th-small-label">Total Destinasi</div>
                        <div class="th-kpi-icon">⌘</div>
                    </div>
                    <p class="th-kpi-value" data-th-count="{{ (int) $safe('total') }}">{{ $formatNumber($safe('total')) }}</p>
                    <p class="th-kpi-desc">data tersimpan di database Laravel</p>
                    <span class="th-kpi-trend">● Data Center</span>
                </div>

                <div class="th-kpi-card" data-th-tilt data-th-glow>
                    <div class="th-kpi-top">
                        <div class="th-small-label">Destinasi Aktif</div>
                        <div class="th-kpi-icon">✓</div>
                    </div>
                    <p class="th-kpi-value" data-th-count="{{ (int) $safe('active') }}">{{ $formatNumber($safe('active')) }}</p>
                    <p class="th-kpi-desc">{{ $formatPercent($activePercent) }} dari total data</p>
                    <span class="th-kpi-trend">● Recommendation Pool</span>
                </div>

                <div class="th-kpi-card" data-th-tilt data-th-glow>
                    <div class="th-kpi-top">
                        <div class="th-small-label">Rating Rata-rata</div>
                        <div class="th-kpi-icon">★</div>
                    </div>
                    <p class="th-kpi-value">{{ $formatRating($safe('average_rating')) }}</p>
                    <p class="th-kpi-desc">berdasarkan destinasi yang memiliki rating</p>
                    <span class="th-kpi-trend">● Quality Signal</span>
                </div>

                <div class="th-kpi-card" data-th-tilt data-th-glow>
                    <div class="th-kpi-top">
                        <div class="th-small-label">Total Ulasan</div>
                        <div class="th-kpi-icon">◌</div>
                    </div>
                    <p class="th-kpi-value" data-th-count="{{ (int) $safe('reviews') }}">{{ $formatNumber($safe('reviews')) }}</p>
                    <p class="th-kpi-desc">akumulasi review dari destinasi</p>
                    <span class="th-kpi-trend">● Social Proof</span>
                </div>

                <div class="th-kpi-card" data-th-tilt data-th-glow>
                    <div class="th-kpi-top">
                        <div class="th-small-label">Rating Tinggi</div>
                        <div class="th-kpi-icon">◆</div>
                    </div>
                    <p class="th-kpi-value" data-th-count="{{ (int) $safe('high_rating') }}">{{ $formatNumber($safe('high_rating')) }}</p>
                    <p class="th-kpi-desc">aktif dengan rating minimal 4,5</p>
                    <span class="th-kpi-trend">● Premium Picks</span>
                </div>
            </section>

            <section class="th-insight-row th-section th-reveal th-delay-2">
                <div class="th-insight-card" data-th-glow>
                    <span class="th-insight-icon">01</span>
                    <div>
                        <h3 class="th-insight-title">Dataset {{ $recommendedStatus }}</h3>
                        <p class="th-insight-desc">
                            Kesiapan saat ini berada di angka {{ $formatPercent($readyPercent) }} dengan {{ $formatNumber($attentionCount) }} item yang perlu dicek.
                        </p>
                    </div>
                </div>

                <div class="th-insight-card" data-th-glow>
                    <span class="th-insight-icon">02</span>
                    <div>
                        <h3 class="th-insight-title">Dominasi {{ filled($topCategoryName) ? $topCategoryName : 'Kategori' }}</h3>
                        <p class="th-insight-desc">
                            Kategori paling terlihat adalah {{ filled($topCategoryName) ? $topCategoryName : '-' }} dengan {{ $formatNumber($topCategoryTotal) }} data.
                        </p>
                    </div>
                </div>

                <div class="th-insight-card" data-th-glow>
                    <span class="th-insight-icon">03</span>
                    <div>
                        <h3 class="th-insight-title">Wilayah {{ filled($topLocationName) ? $topLocationName : 'Utama' }}</h3>
                        <p class="th-insight-desc">
                            Sebaran teratas berada di {{ filled($topLocationName) ? $topLocationName : '-' }} dengan {{ $formatNumber($topLocationTotal) }} destinasi.
                        </p>
                    </div>
                </div>
            </section>

            <section class="th-content-grid th-content-grid-main th-section th-reveal" id="tourhub-quality-check">
                <div class="th-card" data-th-glow>
                    <span class="th-grid-layer"></span>
                    <div class="th-inner">
                        <div class="th-section-header">
                            <div>
                                <div class="th-section-kicker">Dataset Health</div>
                                <h3 class="th-section-title">Kualitas Data Rekomendasi</h3>
                                <p class="th-section-subtitle">
                                    Ringkasan kesiapan data sebelum digunakan oleh FastAPI dan aplikasi mobile TourHub.
                                </p>
                            </div>
                            <span class="th-chip th-chip-green">{{ $formatNumber($safe('ready_for_recommendation')) }} siap</span>
                        </div>

                        <div class="th-quality-grid">
                            @forelse ($qualityItems as $item)
                                @php
                                    $itemPercent = min(100, max(0, (float) data_get($item, 'percent', 0)));
                                    $itemTone = $toneClass(data_get($item, 'tone', 'slate'));
                                @endphp

                                <div class="th-quality-card th-glass {{ $itemTone }}" data-th-tilt data-th-glow>
                                    <div class="th-quality-top">
                                        <div>
                                            <div class="th-small-label">{{ data_get($item, 'label') }}</div>
                                            <div class="th-quality-number" data-th-count="{{ (int) data_get($item, 'value', 0) }}">{{ $formatNumber(data_get($item, 'value', 0)) }}</div>
                                        </div>
                                        <div class="th-quality-percent">{{ $formatPercent($itemPercent) }}</div>
                                    </div>
                                    <div class="th-progress" style="margin-top:1rem;" data-th-progress="{{ $itemPercent }}">
                                        <span style="width: {{ $itemPercent }}%"></span>
                                    </div>
                                    <p class="th-quality-desc">{{ data_get($item, 'description') }}</p>
                                </div>
                            @empty
                                <div class="th-empty-state th-glass" style="grid-column:1/-1;">
                                    Belum ada data kualitas untuk ditampilkan.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="th-card" data-th-glow>
                    <div class="th-inner">
                        <div class="th-section-kicker" style="color:#fda4af;">Data Watchlist</div>
                        <h3 class="th-section-title">Yang Perlu Dicek</h3>
                        <p class="th-section-subtitle">
                            Fokuskan pengecekan pada item ini supaya rekomendasi tetap rapi, lengkap, dan informatif.
                        </p>

                        <div class="th-issue-list" style="margin-top:1rem;">
                            @forelse ($issueItems as $item)
                                <div class="th-issue-item th-glass" data-th-glow>
                                    <div>
                                        <div class="th-list-title">{{ data_get($item, 'label') }}</div>
                                        <p class="th-issue-desc" style="margin-top:.25rem;">{{ data_get($item, 'description') }}</p>
                                    </div>
                                    <div class="th-issue-value" data-th-count="{{ (int) data_get($item, 'value', 0) }}">{{ $formatNumber(data_get($item, 'value', 0)) }}</div>
                                </div>
                            @empty
                                <div class="th-empty-state th-glass">
                                    Tidak ada item watchlist. Data terlihat sehat.
                                </div>
                            @endforelse
                        </div>

                        <div class="th-watchlist-footer">
                            <strong>Catatan admin</strong>
                            <p>
                                Setelah perubahan besar pada destinasi, klik tombol Reload FastAPI agar model rekomendasi membaca data terbaru.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="th-content-grid th-content-grid-main th-section th-reveal" id="tourhub-featured-section">
                <div class="th-card" data-th-glow>
                    <div class="th-inner">
                        <div class="th-section-header">
                            <div>
                                <div class="th-section-kicker">Highlight Destinasi</div>
                                <h3 class="th-section-title">Destinasi Unggulan</h3>
                                <p class="th-section-subtitle">
                                    Diambil dari rating dan jumlah ulasan tertinggi untuk membantu admin melihat kualitas data secara visual.
                                </p>
                            </div>
                            <span class="th-chip th-chip-blue">Preview Admin</span>
                        </div>

                        <div class="th-feature-grid">
                            @forelse ($featuredDestinations as $destination)
                                <a href="{{ $this->getEditDestinationUrl($destination) }}" class="th-destination-card" data-th-tilt data-th-glow>
                                    @if (filled($destination->link_gambar))
                                        <img src="{{ $destination->link_gambar }}" alt="{{ $destination->nama_tempat_wisata }}">
                                    @else
                                        <div class="th-img-fallback">No Image</div>
                                    @endif

                                    <div class="th-image-overlay"></div>

                                    <div class="th-destination-top">
                                        <span class="th-chip {{ $categoryClass($destination->kategori) }}">{{ $destination->kategori ?: '-' }}</span>
                                        <span class="th-rating-pill">⭐ {{ number_format((float) $destination->rating, 1) }}</span>
                                    </div>

                                    <div class="th-destination-bottom">
                                        <h4 class="th-destination-name">{{ $destination->nama_tempat_wisata }}</h4>
                                        <div class="th-destination-location">
                                            📍 {{ $destination->kecamatan ?: '-' }} - {{ $destination->kabupaten_kota ?: '-' }}
                                        </div>
                                        <div class="th-destination-meta">
                                            <span class="th-edit-hint">Klik untuk edit →</span>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="th-feature-empty th-glass">
                                    Belum ada destinasi bergambar untuk ditampilkan.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="th-card th-pipeline-panel" data-th-glow>
                    <span class="th-grid-layer"></span>
                    <div class="th-inner">
                        <div class="th-section-kicker" style="color:#86efac;">Pipeline Sinkronisasi</div>
                        <h3 class="th-section-title">Alur Data Sistem</h3>
                        <p class="th-section-subtitle">
                            Pastikan reload FastAPI setelah admin melakukan perubahan besar pada data destinasi.
                        </p>

                        <div class="th-timeline" style="margin-top:1rem;">
                            <div class="th-timeline-item th-glass">
                                <span class="th-timeline-number">1</span>
                                <div class="th-timeline-title">Laravel Database</div>
                                <p class="th-list-desc" style="margin-top:.25rem;">
                                    Admin tambah, edit, hapus, atau menonaktifkan destinasi dari panel Filament.
                                </p>
                            </div>
                            <div class="th-timeline-item th-glass">
                                <span class="th-timeline-number">2</span>
                                <div class="th-timeline-title">Reload Dataset FastAPI</div>
                                <p class="th-list-desc" style="margin-top:.25rem;">
                                    FastAPI membaca ulang destinasi aktif dari endpoint internal Laravel.
                                </p>
                            </div>
                            <div class="th-timeline-item th-glass">
                                <span class="th-timeline-number">3</span>
                                <div class="th-timeline-title">Mobile TourHub</div>
                                <p class="th-list-desc" style="margin-top:.25rem;">
                                    User menerima rekomendasi terbaru berdasarkan data yang sudah diperbarui.
                                </p>
                            </div>
                        </div>

                        <div class="th-terminal-box">
                            <div class="th-terminal-header">
                                <div class="th-terminal-dots">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                                <div class="th-terminal-title">System Endpoint</div>
                            </div>
                            <div class="th-terminal-body">
                                <div class="th-terminal-line">
                                    <label>Endpoint Internal</label>
                                    <p>{{ $this->getInternalDatasetUrl() }}</p>
                                </div>
                                <div class="th-terminal-line">
                                    <label>FastAPI Base URL</label>
                                    <p>{{ $this->getFastApiBaseUrl() ?: '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="th-content-grid th-content-grid-three th-section th-reveal">
                <div class="th-card" data-th-glow>
                    <div class="th-inner">
                        <div class="th-section-kicker" style="color:#86efac;">Komposisi Data</div>
                        <h3 class="th-section-title">Kategori Wisata</h3>
                        <p class="th-section-subtitle">Komposisi kategori untuk membaca kekuatan dataset.</p>

                        <div class="th-summary-list" style="margin-top:1rem;">
                            @forelse ($categorySummary as $category)
                                @php
                                    $categoryPercent = (float) $safe('total') > 0 ? round(((int) $category->total / (int) $safe('total')) * 100, 1) : 0;
                                @endphp

                                <div class="th-list-item th-glass" data-th-glow>
                                    <div class="th-list-head">
                                        <span class="th-chip {{ $categoryClass($category->kategori) }}">{{ $category->kategori ?: 'Lainnya' }}</span>
                                        <span class="th-list-value">{{ $formatNumber($category->total) }}</span>
                                    </div>
                                    <div class="th-progress" style="margin-top:.8rem;" data-th-progress="{{ min(100, max(0, $categoryPercent)) }}">
                                        <span style="width: {{ min(100, max(0, $categoryPercent)) }}%"></span>
                                    </div>
                                    <p class="th-list-desc" style="position:relative;z-index:2;margin-top:.55rem;">
                                        {{ $formatPercent($categoryPercent) }} dari seluruh data • rating {{ $formatRating($category->average_rating ?? 0) }}
                                    </p>
                                </div>
                            @empty
                                <div class="th-empty-state th-glass">Belum ada ringkasan kategori.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="th-card" data-th-glow>
                    <div class="th-inner">
                        <div class="th-section-kicker" style="color:#67e8f9;">Tipe Wisata</div>
                        <h3 class="th-section-title">Kondisi Kunjungan</h3>
                        <p class="th-section-subtitle">Distribusi indoor, outdoor, dan fleksibel.</p>

                        <div class="th-summary-list" style="margin-top:1rem;">
                            @forelse ($typeSummary as $type)
                                @php
                                    $typePercent = (float) $safe('total') > 0 ? round(((int) $type->total / (int) $safe('total')) * 100, 1) : 0;
                                @endphp

                                <div class="th-list-item th-glass" data-th-glow>
                                    <div class="th-list-head">
                                        <span class="th-list-title">{{ $typeLabel($type->tipe_wisata) }}</span>
                                        <span class="th-list-value">{{ $formatNumber($type->total) }}</span>
                                    </div>
                                    <div class="th-progress" style="margin-top:.8rem;" data-th-progress="{{ min(100, max(0, $typePercent)) }}">
                                        <span style="width: {{ min(100, max(0, $typePercent)) }}%"></span>
                                    </div>
                                    <p class="th-list-desc" style="position:relative;z-index:2;margin-top:.55rem;">
                                        {{ $formatPercent($typePercent) }} dari seluruh data • {{ $formatNumber($type->active_total ?? 0) }} aktif
                                    </p>
                                </div>
                            @empty
                                <div class="th-empty-state th-glass">Belum ada ringkasan tipe wisata.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="th-card" data-th-glow>
                    <div class="th-inner">
                        <div class="th-section-kicker" style="color:#fcd34d;">Sebaran Lokasi</div>
                        <h3 class="th-section-title">Kabupaten / Kota</h3>
                        <p class="th-section-subtitle">Sebaran wilayah destinasi wisata Bali.</p>

                        <div class="th-summary-list th-scroll-area" style="margin-top:1rem;">
                            @forelse ($locationSummary as $location)
                                @php
                                    $locationPercent = (float) $safe('total') > 0 ? round(((int) $location->total / (int) $safe('total')) * 100, 1) : 0;
                                @endphp

                                <div class="th-list-item th-glass" data-th-glow>
                                    <div class="th-list-head">
                                        <div>
                                            <div class="th-list-title">{{ $location->kabupaten_kota ?: '-' }}</div>
                                            <p class="th-list-desc" style="position:relative;z-index:2;margin-top:.25rem;">
                                                {{ $formatNumber($location->active_total ?? 0) }} aktif • rating {{ $formatRating($location->average_rating ?? 0) }}
                                            </p>
                                        </div>
                                        <span class="th-list-value">{{ $formatNumber($location->total) }}</span>
                                    </div>
                                    <div class="th-progress" style="margin-top:.8rem;" data-th-progress="{{ min(100, max(0, $locationPercent)) }}">
                                        <span style="width: {{ min(100, max(0, $locationPercent)) }}%"></span>
                                    </div>
                                </div>
                            @empty
                                <div class="th-empty-state th-glass">Belum ada ringkasan lokasi.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            <section class="th-card th-section th-reveal" data-th-glow>
                <div class="th-inner">
                    <div class="th-section-header">
                        <div>
                            <div class="th-section-kicker" style="color:#fcd34d;">Aktivitas Data</div>
                            <h3 class="th-section-title">Update Terbaru</h3>
                            <p class="th-section-subtitle">
                                Destinasi yang terakhir dibuat atau diubah oleh admin.
                            </p>
                        </div>
                        <span class="th-chip th-chip-yellow">{{ $formatNumber($latestCollection->count()) }} aktivitas</span>
                    </div>

                    <div class="th-latest-grid">
                        @forelse ($latestDestinations as $destination)
                            <a href="{{ $this->getEditDestinationUrl($destination) }}" class="th-latest-item th-glass" data-th-glow>
                                <div class="th-latest-name">{{ $destination->nama_tempat_wisata }}</div>
                                <p class="th-list-desc" style="position:relative;z-index:2;margin-top:.4rem;">
                                    {{ $destination->updated_at ? $destination->updated_at->format('d M Y H:i') : '-' }}
                                </p>
                                <p class="{{ $destination->is_active ? 'th-status-green' : 'th-status-red' }}" style="position:relative;z-index:2;margin:.55rem 0 0;font-size:.68rem;line-height:1rem;font-weight:1000;text-transform:uppercase;letter-spacing:.08em;">
                                    {{ $destination->is_active ? 'Aktif' : 'Nonaktif' }} • {{ $typeLabel($destination->tipe_wisata) }}
                                </p>
                            </a>
                        @empty
                            <div class="th-empty-state th-glass" style="grid-column:1/-1;">Belum ada aktivitas data.</div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="th-table-shell th-section th-reveal" id="tourhub-table-section" data-th-glow>
                <span class="th-grid-layer"></span>
                <div class="th-inner">
                    <div class="th-section-header">
                        <div>
                            <div class="th-section-kicker">Tabel Manajemen</div>
                            <h3 class="th-section-title">Daftar Seluruh Destinasi Wisata</h3>
                            <p class="th-section-subtitle">
                                Gunakan search, filter, sort, edit, maps, delete, dan bulk action langsung dari tabel.
                            </p>
                        </div>
                        <div class="th-table-top-actions">
                            <span class="th-chip">{{ $formatNumber($safe('total')) }} data</span>
                            <span class="th-chip th-chip-green">{{ $formatNumber($safe('active')) }} aktif</span>
                            <span class="th-chip {{ $attentionCount > 0 ? 'th-chip-red' : 'th-chip-green' }}">{{ $formatNumber($attentionCount) }} perlu dicek</span>
                        </div>
                    </div>

                    <div class="th-table-box">
                        {{ $this->table }}
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        (() => {
            let thDashboardInitTimer = null;

            const markTourHubDashboardVisible = (root) => {
                if (!root) {
                    return;
                }

                root.querySelectorAll('.th-reveal').forEach((item) => {
                    item.classList.add('is-visible');
                });
            };

            const initTourHubLuxuryDashboard = (options = {}) => {
                const root = document.querySelector('[data-th-page]');

                if (!root) {
                    return;
                }

                if (options.force === true) {
                    root.dataset.thInitialized = 'false';
                }

                /*
                 * Safety first: setelah Livewire action selesai, konten harus langsung
                 * visible walaupun observer/animasi belum sempat re-bind. Ini mencegah
                 * bug halaman kosong setelah tombol Reload FastAPI sukses.
                 */
                markTourHubDashboardVisible(root);

                if (root.dataset.thInitialized === 'true') {
                    return;
                }

                root.dataset.thInitialized = 'true';

                const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                const formatNumber = (value) => {
                    const numericValue = Number(value || 0);

                    return new Intl.NumberFormat('id-ID', {
                        maximumFractionDigits: 0,
                    }).format(numericValue);
                };

                const revealItems = root.querySelectorAll('.th-reveal');

                if (prefersReducedMotion || !('IntersectionObserver' in window)) {
                    revealItems.forEach((item) => item.classList.add('is-visible'));
                } else {
                    const revealObserver = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            if (entry.isIntersecting) {
                                entry.target.classList.add('is-visible');
                                revealObserver.unobserve(entry.target);
                            }
                        });
                    }, {
                        rootMargin: '0px 0px -8% 0px',
                        threshold: 0.08,
                    });

                    revealItems.forEach((item) => revealObserver.observe(item));
                }

                const counters = root.querySelectorAll('[data-th-count]');

                const animateCounter = (element) => {
                    const target = Number(element.dataset.thCount || 0);
                    const duration = 900;
                    const start = performance.now();

                    if (prefersReducedMotion || target <= 0) {
                        element.textContent = formatNumber(target);
                        return;
                    }

                    const step = (now) => {
                        const progress = Math.min((now - start) / duration, 1);
                        const eased = 1 - Math.pow(1 - progress, 3);
                        const current = Math.round(target * eased);
                        element.textContent = formatNumber(current);

                        if (progress < 1) {
                            requestAnimationFrame(step);
                        } else {
                            element.textContent = formatNumber(target);
                        }
                    };

                    requestAnimationFrame(step);
                };

                if ('IntersectionObserver' in window) {
                    const counterObserver = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            if (entry.isIntersecting) {
                                animateCounter(entry.target);
                                counterObserver.unobserve(entry.target);
                            }
                        });
                    }, {
                        threshold: 0.5,
                    });

                    counters.forEach((counter) => counterObserver.observe(counter));
                } else {
                    counters.forEach(animateCounter);
                }

                const progressBars = root.querySelectorAll('[data-th-progress] span');

                progressBars.forEach((bar) => {
                    const parent = bar.closest('[data-th-progress]');
                    const value = parent ? Number(parent.dataset.thProgress || 0) : Number.parseFloat(bar.style.width || 0);

                    if (prefersReducedMotion) {
                        bar.style.width = `${Math.max(0, Math.min(100, value))}%`;
                        return;
                    }

                    const target = `${Math.max(0, Math.min(100, value))}%`;
                    bar.style.width = '0%';

                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            bar.style.width = target;
                        });
                    });
                });

                root.querySelectorAll('[data-th-scroll]').forEach((trigger) => {
                    trigger.addEventListener('click', (event) => {
                        const href = trigger.getAttribute('href');

                        if (!href || !href.startsWith('#')) {
                            return;
                        }

                        const target = root.querySelector(href);

                        if (!target) {
                            return;
                        }

                        event.preventDefault();
                        target.scrollIntoView({
                            behavior: prefersReducedMotion ? 'auto' : 'smooth',
                            block: 'start',
                        });
                    });
                });

                root.querySelectorAll('[data-th-glow]').forEach((item) => {
                    item.addEventListener('pointermove', (event) => {
                        const rect = item.getBoundingClientRect();
                        const x = ((event.clientX - rect.left) / rect.width) * 100;
                        const y = ((event.clientY - rect.top) / rect.height) * 100;

                        item.style.setProperty('--th-mouse-x', `${x}%`);
                        item.style.setProperty('--th-mouse-y', `${y}%`);
                    }, {
                        passive: true,
                    });
                });

                if (!prefersReducedMotion) {
                    root.querySelectorAll('[data-th-tilt]').forEach((card) => {
                        card.addEventListener('pointermove', (event) => {
                            const rect = card.getBoundingClientRect();
                            const centerX = rect.left + rect.width / 2;
                            const centerY = rect.top + rect.height / 2;
                            const rotateX = ((event.clientY - centerY) / rect.height) * -5;
                            const rotateY = ((event.clientX - centerX) / rect.width) * 5;

                            card.style.transform = `perspective(900px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-2px)`;
                        }, {
                            passive: true,
                        });

                        card.addEventListener('pointerleave', () => {
                            card.style.transform = '';
                        });
                    });
                }
            };

            const scheduleTourHubDashboardInit = (force = false) => {
                if (thDashboardInitTimer) {
                    window.clearTimeout(thDashboardInitTimer);
                }

                thDashboardInitTimer = window.setTimeout(() => {
                    initTourHubLuxuryDashboard({ force });
                }, 40);
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => scheduleTourHubDashboardInit(true), { once: true });
            } else {
                scheduleTourHubDashboardInit(true);
            }

            document.addEventListener('livewire:navigated', () => scheduleTourHubDashboardInit(true));
            document.addEventListener('livewire:load', () => scheduleTourHubDashboardInit(true));
            document.addEventListener('livewire:update', () => scheduleTourHubDashboardInit(true));

            document.addEventListener('livewire:init', () => {
                if (!window.Livewire || typeof window.Livewire.hook !== 'function') {
                    return;
                }

                const safeHook = (name) => {
                    try {
                        window.Livewire.hook(name, () => scheduleTourHubDashboardInit(true));
                    } catch (error) {
                        // Hook berbeda antar versi Livewire. Abaikan jika tidak tersedia.
                    }
                };

                safeHook('morph.updated');
                safeHook('morph.added');
                safeHook('commit');
                safeHook('message.processed');
            });

            window.addEventListener('pageshow', () => scheduleTourHubDashboardInit(true));
        })();
    </script>
</x-filament-panels::page>
