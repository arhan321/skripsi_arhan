<x-filament-panels::page>
    @php
        $stats = $this->getRecommendationDashboardStats();
        $qualityItems = $this->getRecommendationQualityItems();
        $weatherDistribution = $this->getWeatherDistribution();
        $sourceDistribution = $this->getSourceDistribution();
        $topDestinations = $this->getTopDestinationDistribution();
        $recentUsers = $this->getRecentUsersDistribution();
        $hourlyTrend = $this->getHourlyRecommendationTrend();
        $latestLogs = $this->getLatestRecommendationPreview();

        $formatNumber = fn ($value): string => number_format((float) $value, 0, ',', '.');
        $formatPercent = fn ($value): string => number_format((float) $value, 1, ',', '.') . '%';
        $formatMs = fn ($value): string => filled($value) ? number_format((float) $value, 0, ',', '.') . ' ms' : '-';
        $safe = fn ($key, $default = 0) => data_get($stats, $key, $default);

        $timeLabel = function ($value): string {
            if (blank($value)) {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)->format('d M Y H:i');
            } catch (\Throwable $exception) {
                return (string) $value;
            }
        };

        $agoLabel = function ($value): string {
            if (blank($value)) {
                return 'Belum ada data';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)->diffForHumans();
            } catch (\Throwable $exception) {
                return (string) $value;
            }
        };

        $toneClass = function (?string $tone): string {
            return match (strtolower((string) $tone)) {
                'emerald' => 'rl-tone-emerald',
                'green' => 'rl-tone-emerald',
                'blue' => 'rl-tone-blue',
                'cyan' => 'rl-tone-cyan',
                'amber' => 'rl-tone-amber',
                'orange' => 'rl-tone-orange',
                'rose' => 'rl-tone-rose',
                'red' => 'rl-tone-rose',
                'purple' => 'rl-tone-purple',
                default => 'rl-tone-slate',
            };
        };

        $statusClass = function (?string $status): string {
            return match (strtolower((string) $status)) {
                'success' => 'rl-status-success',
                'failed' => 'rl-status-failed',
                default => 'rl-status-neutral',
            };
        };

        $weatherIcon = function (?string $weather): string {
            return match (strtolower((string) $weather)) {
                'cerah' => '☀️',
                'hujan' => '🌧️',
                'mendung' => '☁️',
                'berawan' => '⛅',
                default => '🌐',
            };
        };

        $healthTone = $toneClass(data_get($stats, 'health_tone', 'slate'));
        $successRate = min(100, max(0, (float) $safe('success_rate')));
        $failedRate = min(100, max(0, (float) $safe('failed_rate')));
        $bmkgRate = min(100, max(0, (float) $safe('bmkg_rate')));
        $responseAvg = (float) $safe('avg_response');
        $responseHealth = $responseAvg <= 800 ? 'Sangat Cepat' : ($responseAvg <= 2000 ? 'Stabil' : ($responseAvg <= 5000 ? 'Perlu Optimasi' : 'Lambat'));
        $responseHealthClass = $responseAvg <= 800 ? 'rl-chip-green' : ($responseAvg <= 2000 ? 'rl-chip-cyan' : ($responseAvg <= 5000 ? 'rl-chip-yellow' : 'rl-chip-red'));
        $responseGauge = $responseAvg <= 0 ? 8 : max(8, min(100, 100 - (($responseAvg / 5000) * 100)));
        $corePulse = max(8, min(100, $successRate));
        $failurePulse = max(0, min(100, $failedRate));
        $bmkgPulse = max(0, min(100, $bmkgRate));
        $lastTopDestination = $safe('last_top_destination', '-') ?: '-';
        $cockpitStatus = $safe('failed') > 0 ? 'Monitoring Diperlukan' : 'Pipeline Stabil';
        $cockpitStatusClass = $safe('failed') > 0 ? 'rl-chip-yellow' : 'rl-chip-green';
    @endphp

    <style>
        .recommendation-log-page,
        .recommendation-log-page * {
            box-sizing: border-box;
        }

        .recommendation-log-page {
            --rl-bg: #020617;
            --rl-panel: rgba(15, 23, 42, 0.86);
            --rl-panel-strong: rgba(15, 23, 42, 0.96);
            --rl-panel-soft: rgba(15, 23, 42, 0.62);
            --rl-white: #ffffff;
            --rl-text: #f8fafc;
            --rl-soft: #cbd5e1;
            --rl-muted: #94a3b8;
            --rl-faint: #64748b;
            --rl-border: rgba(148, 163, 184, 0.24);
            --rl-border-strong: rgba(255, 255, 255, 0.16);
            --rl-blue: #2563eb;
            --rl-blue-2: #3b82f6;
            --rl-cyan: #06b6d4;
            --rl-cyan-2: #22d3ee;
            --rl-emerald: #10b981;
            --rl-emerald-2: #34d399;
            --rl-amber: #f59e0b;
            --rl-orange: #f97316;
            --rl-rose: #f43f5e;
            --rl-purple: #8b5cf6;
            --rl-shadow: 0 24px 80px rgba(2, 6, 23, 0.34);
            width: 100%;
            color: var(--rl-text);
        }

        .recommendation-log-page a {
            color: inherit;
        }

        .rl-page-stack {
            display: grid;
            gap: 1.15rem;
        }

        .rl-shell,
        .rl-card,
        .rl-table-shell {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--rl-border);
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.22), transparent 30%),
                radial-gradient(circle at bottom right, rgba(6, 182, 212, 0.16), transparent 28%),
                linear-gradient(135deg, rgba(15, 23, 42, 0.98), rgba(15, 23, 42, 0.88));
            box-shadow: var(--rl-shadow), inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .rl-shell {
            min-height: 19.5rem;
            border-radius: 2rem;
        }

        .rl-card,
        .rl-table-shell {
            border-radius: 1.45rem;
        }

        .rl-inner {
            position: relative;
            z-index: 3;
            padding: 1.2rem;
        }

        @media (min-width: 1024px) {
            .rl-inner {
                padding: 1.7rem;
            }
        }

        .rl-grid-bg::before,
        .rl-grid-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        .rl-grid-bg::before {
            opacity: 0.48;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.047) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.047) 1px, transparent 1px);
            background-size: 30px 30px;
            mask-image: linear-gradient(to bottom, black, transparent 88%);
        }

        .rl-grid-bg::after {
            opacity: 0.5;
            background:
                linear-gradient(110deg, transparent 0%, rgba(255,255,255,.07) 35%, transparent 70%);
            transform: translateX(-70%);
            animation: rlScan 8s linear infinite;
        }

        @keyframes rlScan {
            0% { transform: translateX(-90%); }
            55% { transform: translateX(130%); }
            100% { transform: translateX(130%); }
        }

        .rl-orb {
            position: absolute;
            width: 28rem;
            height: 28rem;
            border-radius: 999px;
            filter: blur(84px);
            opacity: 0.38;
            pointer-events: none;
            animation: rlFloat 9s ease-in-out infinite;
        }

        .rl-orb-blue { left: -11rem; top: -13rem; background: var(--rl-blue); }
        .rl-orb-cyan { right: -10rem; bottom: -14rem; background: var(--rl-cyan); animation-delay: -2s; }
        .rl-orb-emerald { right: 27%; top: -12rem; background: var(--rl-emerald); opacity: 0.16; animation-delay: -4s; }
        .rl-orb-rose { left: 44%; bottom: -18rem; background: var(--rl-rose); opacity: 0.12; animation-delay: -6s; }

        @keyframes rlFloat {
            0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
            50% { transform: translate3d(1.2rem, -0.8rem, 0) scale(1.05); }
        }

        .rl-hero-layout {
            display: grid;
            gap: 1.25rem;
            align-items: stretch;
        }

        @media (min-width: 1180px) {
            .rl-hero-layout {
                grid-template-columns: minmax(0, 1.42fr) minmax(350px, 0.58fr);
            }
        }

        .rl-eyebrow-row,
        .rl-action-row,
        .rl-pill-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.55rem;
        }

        .rl-chip,
        .rl-btn,
        .rl-mini-badge,
        .rl-status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.42rem;
            border-radius: 999px;
            font-size: 0.72rem;
            line-height: 1rem;
            font-weight: 950;
            text-decoration: none;
            white-space: nowrap;
        }

        .rl-chip {
            padding: 0.42rem 0.78rem;
            border: 1px solid var(--rl-border-strong);
            background: rgba(255, 255, 255, 0.08);
            color: #dbeafe;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .rl-chip-dot {
            width: 0.52rem;
            height: 0.52rem;
            border-radius: 999px;
            background: var(--rl-emerald-2);
            box-shadow: 0 0 18px rgba(52, 211, 153, 0.85);
        }

        .rl-chip-blue { background: rgba(37, 99, 235, 0.16); color: #bfdbfe; border-color: rgba(96, 165, 250, 0.34); }
        .rl-chip-cyan { background: rgba(6, 182, 212, 0.14); color: #a5f3fc; border-color: rgba(34, 211, 238, 0.32); }
        .rl-chip-green { background: rgba(16, 185, 129, 0.14); color: #a7f3d0; border-color: rgba(52, 211, 153, 0.32); }
        .rl-chip-yellow { background: rgba(245, 158, 11, 0.16); color: #fde68a; border-color: rgba(251, 191, 36, 0.34); }
        .rl-chip-red { background: rgba(244, 63, 94, 0.14); color: #fecdd3; border-color: rgba(251, 113, 133, 0.32); }
        .rl-chip-purple { background: rgba(139, 92, 246, 0.15); color: #ddd6fe; border-color: rgba(167, 139, 250, 0.32); }

        .rl-title {
            margin: 1.08rem 0 0;
            max-width: 64rem;
            color: white;
            font-size: clamp(2.15rem, 3.7vw, 4.6rem);
            line-height: 0.96;
            font-weight: 1000;
            letter-spacing: -0.065em;
        }

        .rl-gradient-text {
            background: linear-gradient(120deg, #ffffff, #bfdbfe 35%, #67e8f9 68%, #a7f3d0);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .rl-subtitle {
            margin: 1.05rem 0 0;
            max-width: 55rem;
            color: #cbd5e1;
            font-size: 0.96rem;
            line-height: 1.75;
            font-weight: 650;
        }

        .rl-subtitle strong {
            color: #ffffff;
            font-weight: 950;
        }

        .rl-action-row {
            margin-top: 1.35rem;
        }

        .rl-btn {
            min-height: 2.85rem;
            padding: 0.86rem 1.08rem;
            cursor: pointer;
            border: 0;
            transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease, opacity 180ms ease;
        }

        .rl-btn:hover {
            transform: translateY(-2px);
        }

        .rl-btn-primary {
            color: white;
            background: linear-gradient(135deg, #2563eb, #06b6d4);
            box-shadow: 0 18px 45px rgba(37, 99, 235, 0.28);
        }

        .rl-btn-success {
            color: #022c22;
            background: linear-gradient(135deg, #86efac, #22d3ee);
            box-shadow: 0 18px 45px rgba(16, 185, 129, 0.25);
        }

        .rl-btn-soft {
            color: #dbeafe;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--rl-border-strong);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .rl-hero-side {
            display: grid;
            gap: 0.85rem;
        }

        .rl-glass {
            border: 1px solid var(--rl-border-strong);
            background: rgba(255, 255, 255, 0.075);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .rl-health-card {
            position: relative;
            overflow: hidden;
            padding: 1.1rem;
            border-radius: 1.45rem;
        }

        .rl-health-card::after {
            content: '';
            position: absolute;
            right: -3rem;
            bottom: -4rem;
            width: 10rem;
            height: 10rem;
            border-radius: 999px;
            background: rgba(34, 211, 238, 0.16);
            filter: blur(18px);
        }

        .rl-health-top {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .rl-small-label {
            color: var(--rl-muted);
            font-size: 0.67rem;
            line-height: 1rem;
            font-weight: 950;
            letter-spacing: 0.09em;
            text-transform: uppercase;
        }

        .rl-health-number {
            margin-top: 0.33rem;
            color: #ffffff;
            font-size: 3.15rem;
            line-height: 1;
            font-weight: 1000;
            letter-spacing: -0.065em;
        }

        .rl-health-note {
            margin-top: 0.28rem;
            color: #cbd5e1;
            font-size: 0.82rem;
            line-height: 1.45;
            font-weight: 750;
        }

        .rl-health-badge {
            min-width: 6.2rem;
            border-radius: 1.2rem;
            padding: 0.75rem;
            text-align: right;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .rl-progress {
            width: 100%;
            height: 0.58rem;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.22);
        }

        .rl-progress span {
            display: block;
            height: 100%;
            min-width: 0.15rem;
            border-radius: 999px;
            background: linear-gradient(90deg, #60a5fa, #22d3ee, #34d399);
            box-shadow: 0 0 22px rgba(34, 211, 238, 0.36);
        }

        .rl-health-card .rl-progress {
            position: relative;
            z-index: 2;
            margin-top: 1rem;
        }

        .rl-mini-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.72rem;
        }

        .rl-mini-stat {
            min-height: 6rem;
            padding: 0.92rem;
            border-radius: 1.1rem;
        }

        .rl-mini-stat strong {
            display: block;
            margin-top: 0.28rem;
            color: white;
            font-size: 1.35rem;
            line-height: 1.18;
            font-weight: 1000;
            letter-spacing: -0.045em;
        }

        .rl-mini-stat p {
            margin: 0.28rem 0 0;
            color: #94a3b8;
            font-size: 0.72rem;
            line-height: 1.35;
            font-weight: 700;
        }

        .rl-kpi-grid {
            display: grid;
            gap: 0.88rem;
            grid-template-columns: 1fr;
        }

        @media (min-width: 700px) {
            .rl-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1280px) {
            .rl-kpi-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }

        .rl-kpi-card {
            position: relative;
            overflow: hidden;
            min-height: 9.1rem;
            padding: 1.08rem;
            border-radius: 1.35rem;
            border: 1px solid var(--rl-border);
            background:
                radial-gradient(circle at top right, rgba(96, 165, 250, 0.14), transparent 30%),
                linear-gradient(145deg, rgba(15, 23, 42, 0.92), rgba(15, 23, 42, 0.68));
            box-shadow: 0 18px 52px rgba(2, 6, 23, 0.24);
            transition: transform 220ms ease, border-color 220ms ease, box-shadow 220ms ease;
        }

        .rl-kpi-card:hover {
            transform: translateY(-3px);
            border-color: rgba(96, 165, 250, 0.4);
            box-shadow: 0 24px 68px rgba(2, 6, 23, 0.32);
        }

        .rl-kpi-card::after {
            content: '';
            position: absolute;
            right: -3.5rem;
            bottom: -3.5rem;
            width: 9rem;
            height: 9rem;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.18);
            filter: blur(5px);
        }

        .rl-kpi-icon {
            position: relative;
            z-index: 2;
            width: 2.35rem;
            height: 2.35rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.95rem;
            border: 1px solid rgba(255, 255, 255, 0.11);
            background: rgba(255, 255, 255, 0.08);
            font-size: 1rem;
        }

        .rl-kpi-value {
            position: relative;
            z-index: 2;
            margin: 0.72rem 0 0;
            color: white;
            font-size: 2.28rem;
            line-height: 1;
            font-weight: 1000;
            letter-spacing: -0.06em;
        }

        .rl-kpi-desc {
            position: relative;
            z-index: 2;
            margin: 0.42rem 0 0;
            color: #bfdbfe;
            font-size: 0.76rem;
            line-height: 1.4;
            font-weight: 800;
        }

        .rl-section-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 820px) {
            .rl-section-header {
                flex-direction: column;
            }
        }

        .rl-section-kicker {
            color: #93c5fd;
            font-size: 0.72rem;
            line-height: 1rem;
            font-weight: 1000;
            letter-spacing: 0.105em;
            text-transform: uppercase;
        }

        .rl-section-title {
            margin: 0.25rem 0 0;
            color: white;
            font-size: 1.42rem;
            line-height: 1.18;
            font-weight: 1000;
            letter-spacing: -0.04em;
        }

        .rl-section-subtitle {
            margin: 0.35rem 0 0;
            color: #94a3b8;
            font-size: 0.86rem;
            line-height: 1.6;
            font-weight: 650;
        }

        .rl-content-grid {
            display: grid;
            gap: 1.15rem;
        }

        @media (min-width: 1160px) {
            .rl-content-grid-main {
                grid-template-columns: minmax(0, 1.38fr) minmax(350px, 0.62fr);
            }

            .rl-content-grid-three {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .rl-quality-grid {
            display: grid;
            gap: 0.8rem;
        }

        @media (min-width: 760px) {
            .rl-quality-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .rl-quality-card {
            position: relative;
            overflow: hidden;
            padding: 1rem;
            border-radius: 1.2rem;
        }

        .rl-quality-card::after {
            content: '';
            position: absolute;
            width: 7rem;
            height: 7rem;
            right: -3rem;
            top: -3.4rem;
            border-radius: 999px;
            background: rgba(255,255,255,.08);
            filter: blur(12px);
        }

        .rl-quality-top {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.8rem;
        }

        .rl-quality-number {
            margin-top: 0.35rem;
            color: white;
            font-size: 1.75rem;
            line-height: 1;
            font-weight: 1000;
            letter-spacing: -0.05em;
        }

        .rl-quality-badge {
            position: relative;
            z-index: 2;
            border-radius: 999px;
            padding: 0.34rem 0.62rem;
            background: rgba(255, 255, 255, 0.09);
            color: white;
            font-size: 0.7rem;
            font-weight: 950;
            border: 1px solid var(--rl-border-strong);
        }

        .rl-quality-desc,
        .rl-list-desc {
            color: #94a3b8;
            font-size: 0.77rem;
            line-height: 1.55;
            font-weight: 650;
        }

        .rl-quality-desc {
            position: relative;
            z-index: 2;
            margin: 0.72rem 0 0;
        }

        .rl-quality-card .rl-progress {
            position: relative;
            z-index: 2;
            margin-top: 0.86rem;
        }

        .rl-tone-emerald .rl-progress span { background: linear-gradient(90deg, #34d399, #10b981); }
        .rl-tone-blue .rl-progress span { background: linear-gradient(90deg, #60a5fa, #2563eb); }
        .rl-tone-cyan .rl-progress span { background: linear-gradient(90deg, #67e8f9, #06b6d4); }
        .rl-tone-amber .rl-progress span { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
        .rl-tone-orange .rl-progress span { background: linear-gradient(90deg, #fdba74, #f97316); }
        .rl-tone-rose .rl-progress span { background: linear-gradient(90deg, #fb7185, #e11d48); }
        .rl-tone-purple .rl-progress span { background: linear-gradient(90deg, #c084fc, #8b5cf6); }
        .rl-tone-slate .rl-progress span { background: linear-gradient(90deg, #cbd5e1, #64748b); }

        .rl-radar-card {
            min-height: 100%;
            padding: 1rem;
            border-radius: 1.25rem;
        }

        .rl-radar-ring {
            position: relative;
            width: min(17rem, 100%);
            aspect-ratio: 1;
            margin: 1rem auto 0;
            border-radius: 999px;
            background:
                radial-gradient(circle at center, rgba(15, 23, 42, 0.95) 0 38%, transparent 39%),
                conic-gradient(from 90deg, rgba(52,211,153,.95) 0deg, rgba(34,211,238,.9) calc(var(--rl-success-rate) * 3.6deg), rgba(244,63,94,.75) calc(var(--rl-success-rate) * 3.6deg) calc((var(--rl-success-rate) + var(--rl-failed-rate)) * 3.6deg), rgba(148,163,184,.22) 0deg);
            box-shadow: inset 0 0 45px rgba(2, 6, 23, 0.72), 0 24px 70px rgba(2, 6, 23, 0.3);
        }

        .rl-radar-ring::before,
        .rl-radar-ring::after {
            content: '';
            position: absolute;
            inset: 13%;
            border-radius: inherit;
            border: 1px solid rgba(255,255,255,.08);
        }

        .rl-radar-ring::after {
            inset: 28%;
        }

        .rl-radar-center {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }

        .rl-radar-center strong {
            color: white;
            font-size: 2.1rem;
            line-height: 1;
            font-weight: 1000;
            letter-spacing: -0.06em;
        }

        .rl-radar-center span {
            margin-top: 0.35rem;
            color: #cbd5e1;
            font-size: 0.74rem;
            line-height: 1.35;
            font-weight: 850;
        }

        .rl-legend-grid {
            display: grid;
            gap: 0.58rem;
            margin-top: 1rem;
        }

        .rl-legend-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.72rem 0.78rem;
            border-radius: 0.95rem;
        }

        .rl-legend-left {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            min-width: 0;
        }

        .rl-legend-dot {
            width: 0.62rem;
            height: 0.62rem;
            flex: 0 0 auto;
            border-radius: 999px;
            background: #64748b;
        }

        .rl-dot-success { background: #34d399; box-shadow: 0 0 14px rgba(52,211,153,.55); }
        .rl-dot-failed { background: #fb7185; box-shadow: 0 0 14px rgba(251,113,133,.45); }
        .rl-dot-bmkg { background: #22d3ee; box-shadow: 0 0 14px rgba(34,211,238,.55); }
        .rl-dot-neutral { background: #94a3b8; }

        .rl-legend-label {
            color: #e2e8f0;
            font-size: 0.78rem;
            line-height: 1.25;
            font-weight: 850;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .rl-legend-value {
            color: white;
            font-size: 0.8rem;
            font-weight: 1000;
            white-space: nowrap;
        }

        .rl-trend-wrap {
            display: grid;
            gap: 0.72rem;
        }

        .rl-trend-chart {
            height: 17rem;
            display: flex;
            align-items: end;
            gap: 0.62rem;
            padding: 1rem 0.75rem 0.85rem;
            border-radius: 1.2rem;
        }

        .rl-trend-bar-item {
            flex: 1;
            min-width: 0;
            display: grid;
            gap: 0.45rem;
            align-items: end;
            height: 100%;
        }

        .rl-trend-bar-shell {
            position: relative;
            display: flex;
            align-items: end;
            justify-content: center;
            height: 12.8rem;
            border-radius: 999px;
            background: rgba(15,23,42,.72);
            border: 1px solid rgba(255,255,255,.08);
            overflow: hidden;
        }

        .rl-trend-bar {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            min-height: 0.35rem;
            border-radius: 999px 999px 0 0;
            background: linear-gradient(180deg, #67e8f9, #2563eb);
            box-shadow: 0 -8px 22px rgba(34,211,238,.16);
            transition: height 620ms cubic-bezier(.2,.8,.2,1);
        }

        .rl-trend-bar-failed {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            min-height: 0;
            background: linear-gradient(180deg, rgba(251,113,133,.95), rgba(225,29,72,.9));
            opacity: 0.88;
        }

        .rl-trend-count {
            position: relative;
            z-index: 2;
            align-self: start;
            margin-top: 0.55rem;
            color: white;
            font-size: 0.68rem;
            font-weight: 1000;
            text-shadow: 0 1px 10px rgba(0,0,0,.45);
        }

        .rl-trend-label {
            color: #94a3b8;
            font-size: 0.66rem;
            line-height: 1rem;
            text-align: center;
            font-weight: 850;
            white-space: nowrap;
        }

        .rl-list,
        .rl-weather-list,
        .rl-latest-list {
            display: grid;
            gap: 0.72rem;
        }

        .rl-list-item,
        .rl-weather-item,
        .rl-source-item,
        .rl-latest-item {
            position: relative;
            overflow: hidden;
            padding: 0.88rem;
            border-radius: 1.1rem;
        }

        .rl-list-item::after,
        .rl-weather-item::after,
        .rl-source-item::after,
        .rl-latest-item::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(120deg, rgba(255,255,255,.06), transparent 40%);
            opacity: 0;
            transition: opacity 180ms ease;
        }

        .rl-list-item:hover::after,
        .rl-weather-item:hover::after,
        .rl-source-item:hover::after,
        .rl-latest-item:hover::after {
            opacity: 1;
        }

        .rl-list-head,
        .rl-weather-head,
        .rl-source-head,
        .rl-latest-head {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.9rem;
        }

        .rl-list-title {
            color: white;
            font-size: 0.87rem;
            line-height: 1.35;
            font-weight: 1000;
        }

        .rl-list-value {
            color: white;
            font-size: 0.88rem;
            line-height: 1.25;
            font-weight: 1000;
            white-space: nowrap;
        }

        .rl-weather-icon {
            width: 2.25rem;
            height: 2.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.95rem;
            background: rgba(255,255,255,.09);
            border: 1px solid rgba(255,255,255,.1);
            font-size: 1rem;
        }

        .rl-weather-main {
            display: flex;
            align-items: flex-start;
            gap: 0.7rem;
            min-width: 0;
        }

        .rl-source-code {
            color: #a5f3fc;
            font-size: 0.74rem;
            line-height: 1.35;
            font-weight: 850;
            word-break: break-word;
        }

        .rl-mini-badge {
            padding: 0.3rem 0.58rem;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.08);
            color: white;
        }

        .rl-status-badge {
            padding: 0.34rem 0.64rem;
            border: 1px solid rgba(255,255,255,.1);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .rl-status-success { background: rgba(16,185,129,.16); color: #a7f3d0; border-color: rgba(52,211,153,.26); }
        .rl-status-failed { background: rgba(244,63,94,.15); color: #fecdd3; border-color: rgba(251,113,133,.28); }
        .rl-status-neutral { background: rgba(148,163,184,.14); color: #e2e8f0; border-color: rgba(148,163,184,.24); }

        .rl-latest-item {
            display: block;
            text-decoration: none;
            transition: transform 180ms ease, background 180ms ease;
        }

        .rl-latest-item:hover {
            transform: translateY(-2px);
            background: rgba(255,255,255,.1);
        }

        .rl-empty-state {
            border-radius: 1.15rem;
            padding: 1.6rem;
            color: #94a3b8;
            text-align: center;
            font-weight: 800;
        }

        .rl-table-shell .rl-inner {
            padding: 1rem;
        }

        @media (min-width: 768px) {
            .rl-table-shell .rl-inner {
                padding: 1.25rem;
            }
        }

        .rl-table-box {
            overflow: hidden;
            border-radius: 1.15rem;
            background: rgb(17, 24, 39);
            border: 1px solid rgba(255,255,255,.08);
            box-shadow: 0 18px 56px rgba(2, 6, 23, 0.28);
        }

        .rl-table-box .fi-ta,
        .rl-table-box .fi-ta-ctn,
        .rl-table-box .fi-ta-content,
        .rl-table-box .fi-ta-table {
            border-radius: 1.15rem;
        }

        .rl-table-box .fi-ta-header,
        .rl-table-box .fi-ta-content,
        .rl-table-box .fi-ta-footer {
            background: transparent;
        }

        .rl-watermark {
            position: absolute;
            right: 1.2rem;
            top: 0.85rem;
            z-index: 1;
            color: rgba(255,255,255,.035);
            font-size: clamp(4rem, 11vw, 9rem);
            line-height: 1;
            font-weight: 1000;
            letter-spacing: -0.08em;
            pointer-events: none;
            user-select: none;
        }

        .rl-metric-ribbon {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.72rem;
            margin-top: 1rem;
        }

        @media (min-width: 720px) {
            .rl-metric-ribbon {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .rl-ribbon-item {
            padding: 0.78rem 0.85rem;
            border-radius: 1rem;
        }

        .rl-ribbon-item strong {
            display: block;
            margin-top: 0.22rem;
            color: white;
            font-size: 1.05rem;
            line-height: 1.25;
            font-weight: 1000;
        }

        .rl-scroll-thin {
            scrollbar-width: thin;
            scrollbar-color: rgba(96,165,250,.55) rgba(15,23,42,.55);
        }

        .rl-scroll-thin::-webkit-scrollbar {
            width: 0.45rem;
        }

        .rl-scroll-thin::-webkit-scrollbar-track {
            background: rgba(15,23,42,.55);
            border-radius: 999px;
        }

        .rl-scroll-thin::-webkit-scrollbar-thumb {
            background: rgba(96,165,250,.55);
            border-radius: 999px;
        }

        .rl-reveal {
            opacity: 1;
            transform: translateY(0);
        }

        .rl-js-ready .rl-reveal {
            opacity: 0;
            transform: translateY(16px);
            transition: opacity 620ms ease, transform 620ms ease;
        }

        .rl-js-ready .rl-reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .rl-js-ready .rl-reveal[data-delay="1"] { transition-delay: 70ms; }
        .rl-js-ready .rl-reveal[data-delay="2"] { transition-delay: 140ms; }
        .rl-js-ready .rl-reveal[data-delay="3"] { transition-delay: 210ms; }
        .rl-js-ready .rl-reveal[data-delay="4"] { transition-delay: 280ms; }
        .rl-js-ready .rl-reveal[data-delay="5"] { transition-delay: 350ms; }
        .rl-js-ready .rl-reveal[data-delay="6"] { transition-delay: 420ms; }


        .rl-luxury-grid-overlay {
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: 0.62;
            background:
                linear-gradient(90deg, rgba(34, 211, 238, 0.08) 1px, transparent 1px),
                linear-gradient(0deg, rgba(96, 165, 250, 0.07) 1px, transparent 1px),
                radial-gradient(circle at 14% 18%, rgba(37, 99, 235, 0.24), transparent 24%),
                radial-gradient(circle at 86% 76%, rgba(16, 185, 129, 0.2), transparent 28%);
            background-size: 34px 34px, 34px 34px, auto, auto;
            mask-image: radial-gradient(circle at center, black, transparent 82%);
        }

        .rl-circuit-line {
            position: absolute;
            height: 1px;
            width: 34%;
            pointer-events: none;
            background: linear-gradient(90deg, transparent, rgba(34, 211, 238, 0.55), transparent);
            opacity: 0.55;
            animation: rlCircuitMove 6.5s linear infinite;
        }

        .rl-circuit-line:nth-child(1) { left: 8%; top: 21%; animation-delay: -1s; }
        .rl-circuit-line:nth-child(2) { right: 12%; top: 42%; animation-delay: -3s; }
        .rl-circuit-line:nth-child(3) { left: 16%; bottom: 24%; animation-delay: -5s; }

        @keyframes rlCircuitMove {
            0% { transform: translateX(-12%) scaleX(0.65); opacity: 0; }
            20% { opacity: 0.62; }
            60% { opacity: 0.35; }
            100% { transform: translateX(42%) scaleX(1.25); opacity: 0; }
        }

        .rl-cockpit-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.15rem;
        }

        @media (min-width: 1180px) {
            .rl-cockpit-grid {
                grid-template-columns: minmax(0, 1.18fr) minmax(340px, 0.82fr);
            }
        }

        .rl-cockpit-card {
            position: relative;
            overflow: hidden;
            min-height: 100%;
            border-radius: 1.55rem;
            border: 1px solid rgba(148, 163, 184, 0.26);
            background:
                radial-gradient(circle at 10% 15%, rgba(37, 99, 235, 0.24), transparent 26%),
                radial-gradient(circle at 92% 88%, rgba(34, 211, 238, 0.18), transparent 30%),
                linear-gradient(145deg, rgba(15, 23, 42, 0.96), rgba(15, 23, 42, 0.74));
            box-shadow:
                0 24px 80px rgba(2, 6, 23, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .rl-cockpit-card::before {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(115deg, rgba(255,255,255,.09), transparent 22%, transparent 72%, rgba(34,211,238,.08)),
                repeating-linear-gradient(135deg, rgba(255,255,255,.035) 0 1px, transparent 1px 16px);
            opacity: 0.55;
        }

        .rl-cockpit-card::after {
            content: '';
            position: absolute;
            width: 14rem;
            height: 14rem;
            right: -6rem;
            top: -6rem;
            border-radius: 999px;
            background: rgba(96, 165, 250, 0.18);
            filter: blur(18px);
            pointer-events: none;
        }

        .rl-cockpit-inner {
            position: relative;
            z-index: 3;
            padding: 1.2rem;
        }

        @media (min-width: 1024px) {
            .rl-cockpit-inner {
                padding: 1.55rem;
            }
        }

        .rl-terminal-window {
            position: relative;
            overflow: hidden;
            border-radius: 1.25rem;
            border: 1px solid rgba(34, 211, 238, 0.2);
            background:
                radial-gradient(circle at top right, rgba(34, 211, 238, 0.16), transparent 30%),
                linear-gradient(180deg, rgba(2, 6, 23, 0.92), rgba(15, 23, 42, 0.86));
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.03), 0 20px 55px rgba(2,6,23,.28);
        }

        .rl-terminal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.82rem 0.95rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
            background: rgba(255, 255, 255, 0.045);
        }

        .rl-terminal-dots {
            display: inline-flex;
            align-items: center;
            gap: 0.38rem;
        }

        .rl-terminal-dot {
            width: 0.62rem;
            height: 0.62rem;
            border-radius: 999px;
            background: #64748b;
            box-shadow: 0 0 12px rgba(255,255,255,.12);
        }

        .rl-terminal-dot-red { background: #fb7185; }
        .rl-terminal-dot-yellow { background: #fbbf24; }
        .rl-terminal-dot-green { background: #34d399; }

        .rl-terminal-title {
            color: #a5f3fc;
            font-size: 0.72rem;
            line-height: 1rem;
            font-weight: 1000;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .rl-terminal-body {
            display: grid;
            gap: 0.45rem;
            padding: 0.95rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        }

        .rl-terminal-line {
            display: flex;
            align-items: flex-start;
            gap: 0.58rem;
            color: #dbeafe;
            font-size: 0.77rem;
            line-height: 1.55;
            font-weight: 780;
            word-break: break-word;
        }

        .rl-terminal-prefix {
            color: #34d399;
            flex: 0 0 auto;
            text-shadow: 0 0 14px rgba(52,211,153,.45);
        }

        .rl-terminal-key {
            color: #67e8f9;
            font-weight: 1000;
        }

        .rl-terminal-value {
            color: #ffffff;
            font-weight: 900;
        }

        .rl-terminal-muted {
            color: #94a3b8;
        }

        .rl-terminal-caret {
            display: inline-block;
            width: 0.42rem;
            height: 0.88rem;
            margin-left: 0.12rem;
            vertical-align: -0.12rem;
            background: #67e8f9;
            animation: rlCaretBlink 1s steps(2, jump-none) infinite;
        }

        @keyframes rlCaretBlink {
            0%, 48% { opacity: 1; }
            49%, 100% { opacity: 0; }
        }

        .rl-nexus-layout {
            display: grid;
            gap: 1rem;
            align-items: center;
        }

        @media (min-width: 920px) {
            .rl-nexus-layout {
                grid-template-columns: minmax(260px, 0.76fr) minmax(0, 1.24fr);
            }
        }

        .rl-nexus-core {
            position: relative;
            width: min(18rem, 100%);
            aspect-ratio: 1;
            margin: 0 auto;
            border-radius: 999px;
            background:
                radial-gradient(circle at center, rgba(2, 6, 23, 0.98) 0 31%, transparent 32%),
                conic-gradient(from 180deg, rgba(37, 99, 235, 0.95), rgba(34, 211, 238, 0.92), rgba(52, 211, 153, 0.85), rgba(245, 158, 11, 0.76), rgba(244, 63, 94, 0.74), rgba(37, 99, 235, 0.95));
            box-shadow:
                0 30px 84px rgba(2,6,23,.38),
                inset 0 0 58px rgba(2,6,23,.78);
            animation: rlNexusGlow 5s ease-in-out infinite;
        }

        @keyframes rlNexusGlow {
            0%, 100% { filter: saturate(1) brightness(1); }
            50% { filter: saturate(1.2) brightness(1.12); }
        }

        .rl-nexus-core::before,
        .rl-nexus-core::after {
            content: '';
            position: absolute;
            border-radius: inherit;
            pointer-events: none;
        }

        .rl-nexus-core::before {
            inset: 8%;
            border: 1px dashed rgba(255,255,255,.18);
            animation: rlSpin 22s linear infinite;
        }

        .rl-nexus-core::after {
            inset: 19%;
            border: 1px solid rgba(255,255,255,.1);
            box-shadow: inset 0 0 32px rgba(34,211,238,.08);
            animation: rlSpinReverse 18s linear infinite;
        }

        @keyframes rlSpin {
            to { transform: rotate(360deg); }
        }

        @keyframes rlSpinReverse {
            to { transform: rotate(-360deg); }
        }

        .rl-nexus-center {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2.2rem;
        }

        .rl-nexus-center strong {
            color: white;
            font-size: 2.35rem;
            line-height: 1;
            font-weight: 1000;
            letter-spacing: -0.065em;
        }

        .rl-nexus-center span {
            margin-top: 0.32rem;
            color: #a5f3fc;
            font-size: 0.72rem;
            line-height: 1.35;
            font-weight: 1000;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .rl-nexus-ping {
            position: absolute;
            width: 0.72rem;
            height: 0.72rem;
            border-radius: 999px;
            background: #67e8f9;
            box-shadow: 0 0 18px rgba(34,211,238,.7);
            animation: rlPing 2.4s ease-out infinite;
        }

        .rl-nexus-ping:nth-child(1) { left: 18%; top: 32%; animation-delay: -0.5s; }
        .rl-nexus-ping:nth-child(2) { right: 18%; top: 24%; animation-delay: -1.1s; background: #34d399; }
        .rl-nexus-ping:nth-child(3) { left: 46%; bottom: 12%; animation-delay: -1.7s; background: #fbbf24; }

        @keyframes rlPing {
            0% { transform: scale(0.8); opacity: 0.95; }
            70% { transform: scale(2.7); opacity: 0; }
            100% { transform: scale(2.7); opacity: 0; }
        }

        .rl-signal-grid {
            display: grid;
            gap: 0.72rem;
            grid-template-columns: 1fr;
        }

        @media (min-width: 720px) {
            .rl-signal-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .rl-signal-card {
            position: relative;
            overflow: hidden;
            min-height: 8rem;
            padding: 0.95rem;
            border-radius: 1.1rem;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.065);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.08);
        }

        .rl-signal-card::after {
            content: '';
            position: absolute;
            right: -2.5rem;
            bottom: -2.5rem;
            width: 7rem;
            height: 7rem;
            border-radius: 999px;
            background: rgba(34,211,238,.12);
            filter: blur(8px);
        }

        .rl-signal-value {
            position: relative;
            z-index: 2;
            margin-top: 0.45rem;
            color: white;
            font-size: 1.65rem;
            line-height: 1;
            font-weight: 1000;
            letter-spacing: -0.055em;
        }

        .rl-signal-desc {
            position: relative;
            z-index: 2;
            margin: 0.45rem 0 0;
            color: #94a3b8;
            font-size: 0.74rem;
            line-height: 1.45;
            font-weight: 760;
        }

        .rl-flow-map {
            display: grid;
            gap: 0.75rem;
        }

        .rl-flow-node {
            position: relative;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 0.85rem;
            align-items: center;
            padding: 0.86rem;
            border-radius: 1.05rem;
            border: 1px solid rgba(255,255,255,.11);
            background: rgba(255,255,255,.065);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.08);
        }

        .rl-flow-node + .rl-flow-node::before {
            content: '';
            position: absolute;
            left: 1.65rem;
            top: -0.78rem;
            width: 1px;
            height: 0.78rem;
            background: linear-gradient(to bottom, rgba(34,211,238,.65), transparent);
        }

        .rl-flow-icon {
            width: 2.2rem;
            height: 2.2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.9rem;
            color: white;
            background: linear-gradient(135deg, rgba(37,99,235,.92), rgba(6,182,212,.88));
            box-shadow: 0 12px 32px rgba(37,99,235,.22);
            font-size: 0.98rem;
        }

        .rl-flow-title {
            color: white;
            font-size: 0.83rem;
            line-height: 1.25;
            font-weight: 1000;
        }

        .rl-flow-desc {
            margin: 0.2rem 0 0;
            color: #94a3b8;
            font-size: 0.72rem;
            line-height: 1.35;
            font-weight: 700;
        }

        .rl-flow-status {
            color: #a7f3d0;
            font-size: 0.68rem;
            line-height: 1rem;
            font-weight: 1000;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .rl-stream-wall {
            position: relative;
            overflow: hidden;
            border-radius: 1.55rem;
            border: 1px solid rgba(148, 163, 184, 0.24);
            background:
                radial-gradient(circle at 6% 16%, rgba(139, 92, 246, 0.18), transparent 28%),
                radial-gradient(circle at 94% 72%, rgba(16, 185, 129, 0.16), transparent 30%),
                linear-gradient(135deg, rgba(15,23,42,.96), rgba(15,23,42,.78));
            box-shadow: 0 24px 72px rgba(2,6,23,.3);
        }

        .rl-stream-marquee {
            position: relative;
            display: flex;
            gap: 0.85rem;
            overflow-x: auto;
            padding: 0.25rem 0.1rem 0.35rem;
            scroll-snap-type: x proximity;
        }

        .rl-stream-card {
            position: relative;
            overflow: hidden;
            flex: 0 0 min(21rem, 84vw);
            min-height: 10.5rem;
            padding: 1rem;
            border-radius: 1.2rem;
            border: 1px solid rgba(255,255,255,.12);
            background:
                linear-gradient(145deg, rgba(255,255,255,.08), rgba(255,255,255,.045)),
                radial-gradient(circle at top right, rgba(34,211,238,.13), transparent 34%);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.08);
            scroll-snap-align: start;
        }

        .rl-stream-card::before {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(120deg, rgba(255,255,255,.1), transparent 35%, transparent 72%, rgba(34,211,238,.07));
            opacity: 0.62;
        }

        .rl-stream-top {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .rl-stream-title {
            position: relative;
            z-index: 2;
            margin: 0.75rem 0 0;
            color: white;
            font-size: 1.02rem;
            line-height: 1.28;
            font-weight: 1000;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
        }

        .rl-stream-meta {
            position: relative;
            z-index: 2;
            margin: 0.45rem 0 0;
            color: #94a3b8;
            font-size: 0.74rem;
            line-height: 1.45;
            font-weight: 720;
        }

        .rl-stream-footer {
            position: relative;
            z-index: 2;
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            margin-top: 0.75rem;
        }

        .rl-data-matrix {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            opacity: 0.16;
            mask-image: linear-gradient(to bottom, transparent, black 20%, black 70%, transparent);
        }

        .rl-data-matrix span {
            position: absolute;
            top: -20%;
            color: #67e8f9;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 0.72rem;
            line-height: 1.05;
            white-space: pre;
            animation: rlMatrixFall 9s linear infinite;
        }

        .rl-data-matrix span:nth-child(1) { left: 5%; animation-delay: -1s; }
        .rl-data-matrix span:nth-child(2) { left: 15%; animation-delay: -5s; animation-duration: 12s; }
        .rl-data-matrix span:nth-child(3) { left: 27%; animation-delay: -2s; animation-duration: 10s; }
        .rl-data-matrix span:nth-child(4) { left: 39%; animation-delay: -7s; animation-duration: 14s; }
        .rl-data-matrix span:nth-child(5) { left: 52%; animation-delay: -4s; animation-duration: 11s; }
        .rl-data-matrix span:nth-child(6) { left: 64%; animation-delay: -8s; animation-duration: 13s; }
        .rl-data-matrix span:nth-child(7) { left: 76%; animation-delay: -3s; animation-duration: 10.5s; }
        .rl-data-matrix span:nth-child(8) { left: 88%; animation-delay: -6s; animation-duration: 12.5s; }

        @keyframes rlMatrixFall {
            0% { transform: translateY(-10%); opacity: 0; }
            12% { opacity: 1; }
            86% { opacity: 0.72; }
            100% { transform: translateY(140%); opacity: 0; }
        }

        .rl-mega-stat-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.82rem;
            align-items: stretch;
        }

        @media (min-width: 720px) {
            .rl-mega-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1280px) {
            .rl-mega-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .rl-mega-stat {
            --rl-card-accent: #22d3ee;
            position: relative;
            overflow: hidden;
            min-height: 8.2rem;
            padding: 0.95rem;
            border-radius: 1.25rem;
            border: 1px solid rgba(255,255,255,.13);
            background:
                linear-gradient(145deg, rgba(255,255,255,.105), rgba(255,255,255,.052)),
                radial-gradient(circle at top left, color-mix(in srgb, var(--rl-card-accent) 28%, transparent), transparent 34%),
                radial-gradient(circle at bottom right, rgba(255,255,255,.085), transparent 34%);
            box-shadow:
                0 18px 46px rgba(2, 6, 23, .20),
                inset 0 1px 0 rgba(255,255,255,.11);
            transition: transform 190ms ease, border-color 190ms ease, background 190ms ease, box-shadow 190ms ease;
        }

        .rl-mega-stat:hover {
            transform: translateY(-3px);
            border-color: color-mix(in srgb, var(--rl-card-accent) 48%, rgba(255,255,255,.14));
            box-shadow:
                0 24px 62px rgba(2, 6, 23, .28),
                0 0 0 1px color-mix(in srgb, var(--rl-card-accent) 18%, transparent),
                inset 0 1px 0 rgba(255,255,255,.14);
        }

        .rl-mega-stat::before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            height: 0.2rem;
            background: linear-gradient(90deg, transparent, var(--rl-card-accent), transparent);
            opacity: 0.9;
        }

        .rl-mega-stat::after {
            content: attr(data-watermark);
            position: absolute;
            right: -0.28rem;
            bottom: -0.72rem;
            color: rgba(255,255,255,.038);
            font-size: 3.55rem;
            line-height: 1;
            font-weight: 1000;
            letter-spacing: -0.08em;
            pointer-events: none;
            transform: skewX(-8deg);
        }

        .rl-mega-stat-head {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: 2.55rem minmax(0, 1fr) auto;
            gap: 0.72rem;
            align-items: start;
        }

        .rl-mega-icon {
            width: 2.55rem;
            height: 2.55rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            color: white;
            background:
                radial-gradient(circle at top left, rgba(255,255,255,.28), transparent 45%),
                color-mix(in srgb, var(--rl-card-accent) 68%, #020617);
            border: 1px solid rgba(255,255,255,.14);
            box-shadow:
                0 12px 28px color-mix(in srgb, var(--rl-card-accent) 22%, transparent),
                inset 0 1px 0 rgba(255,255,255,.16);
            font-size: 1.02rem;
            flex: 0 0 auto;
        }

        .rl-mega-topline {
            color: #dbeafe;
            font-size: 0.62rem;
            line-height: 1rem;
            font-weight: 1000;
            letter-spacing: 0.105em;
            text-transform: uppercase;
        }

        .rl-mega-value {
            position: relative;
            z-index: 2;
            margin-top: 0.12rem;
            color: white;
            font-size: clamp(1.55rem, 2.1vw, 2.25rem);
            line-height: 0.96;
            font-weight: 1000;
            letter-spacing: -0.06em;
            word-break: break-word;
        }

        .rl-mega-status-chip {
            min-width: max-content;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 0.32rem 0.54rem;
            color: white;
            background: color-mix(in srgb, var(--rl-card-accent) 18%, rgba(255,255,255,.08));
            border: 1px solid color-mix(in srgb, var(--rl-card-accent) 35%, rgba(255,255,255,.12));
            font-size: 0.62rem;
            line-height: 1;
            font-weight: 1000;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .rl-mega-desc {
            position: relative;
            z-index: 2;
            margin: 0.72rem 0 0;
            color: #bfdbfe;
            font-size: 0.73rem;
            line-height: 1.48;
            font-weight: 760;
        }

        .rl-mega-microbar {
            position: relative;
            z-index: 2;
            height: 0.46rem;
            margin-top: 0.82rem;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(15, 23, 42, .58);
            border: 1px solid rgba(255,255,255,.08);
        }

        .rl-mega-microbar span {
            display: block;
            height: 100%;
            width: var(--rl-bar-width, 45%);
            min-width: 0.25rem;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--rl-card-accent), #ffffff);
            box-shadow: 0 0 18px color-mix(in srgb, var(--rl-card-accent) 50%, transparent);
        }

        .rl-mega-foot {
            position: relative;
            z-index: 2;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.45rem;
            margin-top: 0.75rem;
        }

        .rl-mega-foot-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.32rem;
            border-radius: 999px;
            padding: 0.3rem 0.55rem;
            color: #e2e8f0;
            background: rgba(255,255,255,.075);
            border: 1px solid rgba(255,255,255,.095);
            font-size: 0.64rem;
            line-height: 1;
            font-weight: 900;
        }

        .rl-success-glow {
            --rl-card-accent: #34d399;
            box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.14), 0 18px 56px rgba(16, 185, 129, 0.08), inset 0 1px 0 rgba(255,255,255,.1);
        }

        .rl-danger-glow {
            --rl-card-accent: #fb7185;
            box-shadow: 0 0 0 1px rgba(244, 63, 94, 0.14), 0 18px 56px rgba(244, 63, 94, 0.08), inset 0 1px 0 rgba(255,255,255,.1);
        }

        .rl-blue-glow {
            --rl-card-accent: #60a5fa;
            box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.14), 0 18px 56px rgba(37, 99, 235, 0.1), inset 0 1px 0 rgba(255,255,255,.1);
        }

        .rl-cyan-glow {
            --rl-card-accent: #22d3ee;
            box-shadow: 0 0 0 1px rgba(6, 182, 212, 0.14), 0 18px 56px rgba(6, 182, 212, 0.1), inset 0 1px 0 rgba(255,255,255,.1);
        }

        @media (max-width: 760px) {
            .rl-mega-stat-head {
                grid-template-columns: 2.45rem minmax(0, 1fr);
            }

            .rl-mega-status-chip {
                grid-column: 1 / -1;
                width: fit-content;
            }
        }

        .rl-floating-action-dock {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            margin-top: 1rem;
        }

        .rl-dock-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 2.55rem;
            padding: 0.72rem 0.9rem;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.075);
            color: #dbeafe;
            text-decoration: none;
            font-size: 0.74rem;
            line-height: 1rem;
            font-weight: 950;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.08);
            transition: transform 180ms ease, border-color 180ms ease, background 180ms ease;
        }

        .rl-dock-button:hover {
            transform: translateY(-2px);
            border-color: rgba(34,211,238,.35);
            background: rgba(34,211,238,.1);
        }

        @media (max-width: 700px) {
            .rl-title {
                font-size: 2.25rem;
            }

            .rl-health-number {
                font-size: 2.45rem;
            }

            .rl-mini-grid {
                grid-template-columns: 1fr;
            }

            .rl-action-row .rl-btn {
                width: 100%;
            }

            .rl-trend-chart {
                overflow-x: auto;
                justify-content: flex-start;
            }

            .rl-trend-bar-item {
                min-width: 2.55rem;
            }
        }
    </style>

    <div class="recommendation-log-page" id="recommendation-log-page">
        <div class="rl-page-stack">
            <section class="rl-shell rl-grid-bg rl-reveal">
                <span class="rl-orb rl-orb-blue"></span>
                <span class="rl-orb rl-orb-cyan"></span>
                <span class="rl-orb rl-orb-emerald"></span>
                <span class="rl-orb rl-orb-rose"></span>
                <div class="rl-watermark">LOG</div>

                <div class="rl-inner">
                    <div class="rl-hero-layout">
                        <div>
                            <div class="rl-eyebrow-row">
                                <span class="rl-chip"><span class="rl-chip-dot"></span>Recommendation Intelligence</span>
                                <span class="rl-chip rl-chip-green">{{ $formatNumber($safe('success')) }} Success</span>
                                <span class="rl-chip {{ $safe('failed') > 0 ? 'rl-chip-red' : 'rl-chip-green' }}">{{ $formatNumber($safe('failed')) }} Failed</span>
                                <span class="rl-chip rl-chip-cyan">{{ $formatNumber($safe('bmkg_count')) }} BMKG Context</span>
                                <span class="rl-chip {{ data_get($stats, 'health_tone') === 'rose' ? 'rl-chip-red' : 'rl-chip-blue' }}">{{ data_get($stats, 'health_label') }}</span>
                            </div>

                            <h2 class="rl-title">
                                Command Center <span class="rl-gradient-text">Log Rekomendasi</span> TourHub Bali
                            </h2>

                            <p class="rl-subtitle">
                                Pantau seluruh request rekomendasi dari user, sumber cuaca BMKG, status pipeline Laravel → FastAPI,
                                top destination yang keluar, response time, serta histori penggunaan sistem. Halaman ini dibuat untuk
                                membantu admin mengecek kualitas rekomendasi secara cepat dan siap dijadikan bukti pengujian skripsi.
                            </p>

                            <div class="rl-action-row">
                                <a href="{{ route('tourhub.recommendation.index') }}" target="_blank" class="rl-btn rl-btn-primary">✨ Test Rekomendasi</a>
                                <a href="#recommendation-log-table" class="rl-btn rl-btn-soft">↓ Lihat Tabel Log</a>
                                <button type="button" class="rl-btn rl-btn-success" onclick="window.location.reload()">↻ Refresh Dashboard</button>
                            </div>

                            <div class="rl-metric-ribbon">
                                <div class="rl-ribbon-item rl-glass">
                                    <div class="rl-small-label">Last Request</div>
                                    <strong>{{ $agoLabel($safe('last_log_at')) }}</strong>
                                </div>
                                <div class="rl-ribbon-item rl-glass">
                                    <div class="rl-small-label">Last Success</div>
                                    <strong>{{ $agoLabel($safe('last_success_at')) }}</strong>
                                </div>
                                <div class="rl-ribbon-item rl-glass">
                                    <div class="rl-small-label">Latest Top Destination</div>
                                    <strong>{{ $safe('last_top_destination', '-') ?: '-' }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="rl-hero-side">
                            <div class="rl-health-card rl-glass {{ $healthTone }}">
                                <div class="rl-health-top">
                                    <div>
                                        <div class="rl-small-label">Pipeline Success Rate</div>
                                        <div class="rl-health-number">{{ $formatPercent($safe('success_rate')) }}</div>
                                        <div class="rl-health-note">status kesehatan rekomendasi</div>
                                    </div>
                                    <div class="rl-health-badge">
                                        <div class="rl-small-label" style="color:#a7f3d0;">Health</div>
                                        <strong style="display:block;margin-top:.25rem;color:white;font-size:1.2rem;line-height:1.15;font-weight:1000;">{{ data_get($stats, 'health_label') }}</strong>
                                    </div>
                                </div>
                                <div class="rl-progress"><span style="width: {{ $successRate }}%"></span></div>
                            </div>

                            <div class="rl-mini-grid">
                                <div class="rl-mini-stat rl-glass">
                                    <div class="rl-small-label">Total Log</div>
                                    <strong>{{ $formatNumber($safe('total')) }}</strong>
                                    <p>seluruh request</p>
                                </div>
                                <div class="rl-mini-stat rl-glass">
                                    <div class="rl-small-label">Hari Ini</div>
                                    <strong>{{ $formatNumber($safe('today')) }}</strong>
                                    <p>{{ $formatNumber($safe('today_success')) }} sukses</p>
                                </div>
                                <div class="rl-mini-stat rl-glass">
                                    <div class="rl-small-label">Avg Response</div>
                                    <strong>{{ $formatMs($safe('avg_response')) }}</strong>
                                    <p>rata-rata proses</p>
                                </div>
                                <div class="rl-mini-stat rl-glass">
                                    <div class="rl-small-label">Fastest</div>
                                    <strong>{{ $formatMs($safe('fastest_response')) }}</strong>
                                    <p>respons tercepat</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rl-kpi-grid">
                <div class="rl-kpi-card rl-reveal" data-delay="1">
                    <div class="rl-kpi-icon">📊</div>
                    <div class="rl-small-label" style="margin-top:.72rem;">Total Request</div>
                    <p class="rl-kpi-value">{{ $formatNumber($safe('total')) }}</p>
                    <p class="rl-kpi-desc">semua request rekomendasi yang tercatat di database</p>
                </div>

                <div class="rl-kpi-card rl-reveal" data-delay="2">
                    <div class="rl-kpi-icon">✅</div>
                    <div class="rl-small-label" style="margin-top:.72rem;">Success</div>
                    <p class="rl-kpi-value">{{ $formatNumber($safe('success')) }}</p>
                    <p class="rl-kpi-desc">{{ $formatPercent($safe('success_rate')) }} berhasil diproses</p>
                </div>

                <div class="rl-kpi-card rl-reveal" data-delay="3">
                    <div class="rl-kpi-icon">⚠️</div>
                    <div class="rl-small-label" style="margin-top:.72rem;">Failed</div>
                    <p class="rl-kpi-value">{{ $formatNumber($safe('failed')) }}</p>
                    <p class="rl-kpi-desc">{{ $formatPercent($safe('failed_rate')) }} perlu dicek</p>
                </div>

                <div class="rl-kpi-card rl-reveal" data-delay="4">
                    <div class="rl-kpi-icon">🌦️</div>
                    <div class="rl-small-label" style="margin-top:.72rem;">BMKG Source</div>
                    <p class="rl-kpi-value">{{ $formatNumber($safe('bmkg_count')) }}</p>
                    <p class="rl-kpi-desc">{{ $formatPercent($safe('bmkg_rate')) }} memakai konteks cuaca</p>
                </div>

                <div class="rl-kpi-card rl-reveal" data-delay="5">
                    <div class="rl-kpi-icon">⚡</div>
                    <div class="rl-small-label" style="margin-top:.72rem;">Response Avg</div>
                    <p class="rl-kpi-value">{{ $formatMs($safe('avg_response')) }}</p>
                    <p class="rl-kpi-desc">slowest {{ $formatMs($safe('slowest_response')) }}</p>
                </div>
            </section>

            <section class="rl-cockpit-grid rl-reveal" data-delay="1">
                <div class="rl-cockpit-card">
                    <span class="rl-luxury-grid-overlay"></span>
                    <span class="rl-circuit-line"></span>
                    <span class="rl-circuit-line"></span>
                    <span class="rl-circuit-line"></span>
                    <div class="rl-data-matrix" aria-hidden="true">
                        <span>10110\A0FF\REQ\OK\BMKG\FASTAPI\</span>
                        <span>TOURHUB\CBF\CTX\RAIN\SUN\NDCG\</span>
                        <span>LAT\LON\ADM4\BALI\USER\LOG\</span>
                        <span>STATUS\SUCCESS\FAILED\TRACE\</span>
                        <span>FASTAPI\LARAVEL\SANCTUM\JSON\</span>
                        <span>WEATHER\CERAH\HUJAN\BERAWAN\</span>
                        <span>TOPK\RANK\SCORE\DESTINATION\</span>
                        <span>PIPELINE\SYNC\RELOAD\HEALTH\</span>
                    </div>

                    <div class="rl-cockpit-inner">
                        <div class="rl-section-header">
                            <div>
                                <div class="rl-section-kicker" style="color:#67e8f9;">Luxury Command Layer</div>
                                <h3 class="rl-section-title">FastAPI Telemetry Cockpit</h3>
                                <p class="rl-section-subtitle">Layer visual tambahan untuk membaca kondisi request, health, response time, BMKG, dan top destination terakhir dengan gaya command center.</p>
                            </div>
                            <span class="rl-chip {{ $cockpitStatusClass }}">{{ $cockpitStatus }}</span>
                        </div>

                        <div class="rl-nexus-layout">
                            <div class="rl-nexus-core" style="--rl-core-pulse: {{ $corePulse }}; --rl-failure-pulse: {{ $failurePulse }}; --rl-bmkg-pulse: {{ $bmkgPulse }};">
                                <span class="rl-nexus-ping"></span>
                                <span class="rl-nexus-ping"></span>
                                <span class="rl-nexus-ping"></span>
                                <div class="rl-nexus-center">
                                    <strong>{{ $formatPercent($successRate) }}</strong>
                                    <span>core success</span>
                                </div>
                            </div>

                            <div>
                                <div class="rl-mega-stat-grid">
                                    <div class="rl-mega-stat rl-success-glow" data-watermark="OK">
                                        <div class="rl-mega-stat-head">
                                            <div class="rl-mega-icon">✓</div>
                                            <div>
                                                <div class="rl-mega-topline">Success Signal</div>
                                                <div class="rl-mega-value">{{ $formatNumber($safe('success')) }}</div>
                                            </div>
                                            <span class="rl-mega-status-chip">Stable</span>
                                        </div>
                                        <p class="rl-mega-desc">Request berhasil diproses oleh pipeline rekomendasi Laravel → FastAPI.</p>
                                        <div class="rl-mega-microbar" style="--rl-bar-width: {{ $successRate }}%;"><span></span></div>
                                        <div class="rl-mega-foot">
                                            <span class="rl-mega-foot-pill">{{ $formatPercent($successRate) }}</span>
                                            <span class="rl-mega-foot-pill">healthy traffic</span>
                                        </div>
                                    </div>

                                    <div class="rl-mega-stat {{ $safe('failed') > 0 ? 'rl-danger-glow' : 'rl-success-glow' }}" data-watermark="ERR">
                                        <div class="rl-mega-stat-head">
                                            <div class="rl-mega-icon">!</div>
                                            <div>
                                                <div class="rl-mega-topline">Failure Signal</div>
                                                <div class="rl-mega-value">{{ $formatNumber($safe('failed')) }}</div>
                                            </div>
                                            <span class="rl-mega-status-chip">{{ $safe('failed') > 0 ? 'Review' : 'Clean' }}</span>
                                        </div>
                                        <p class="rl-mega-desc">Request gagal yang perlu dilihat detailnya oleh admin melalui halaman view log.</p>
                                        <div class="rl-mega-microbar" style="--rl-bar-width: {{ max(4, $failedRate) }}%;"><span></span></div>
                                        <div class="rl-mega-foot">
                                            <span class="rl-mega-foot-pill">{{ $formatPercent($failedRate) }}</span>
                                            <span class="rl-mega-foot-pill">error monitor</span>
                                        </div>
                                    </div>

                                    <div class="rl-mega-stat rl-cyan-glow" data-watermark="BMKG">
                                        <div class="rl-mega-stat-head">
                                            <div class="rl-mega-icon">☁</div>
                                            <div>
                                                <div class="rl-mega-topline">Weather Intelligence</div>
                                                <div class="rl-mega-value">{{ $formatPercent($bmkgRate) }}</div>
                                            </div>
                                            <span class="rl-mega-status-chip">Context</span>
                                        </div>
                                        <p class="rl-mega-desc">Persentase rekomendasi yang memakai konteks cuaca BMKG/ADM4.</p>
                                        <div class="rl-mega-microbar" style="--rl-bar-width: {{ $bmkgRate }}%;"><span></span></div>
                                        <div class="rl-mega-foot">
                                            <span class="rl-mega-foot-pill">{{ $formatNumber($safe('bmkg_count')) }} log</span>
                                            <span class="rl-mega-foot-pill">BMKG active</span>
                                        </div>
                                    </div>

                                    <div class="rl-mega-stat rl-blue-glow" data-watermark="MS">
                                        <div class="rl-mega-stat-head">
                                            <div class="rl-mega-icon">↯</div>
                                            <div>
                                                <div class="rl-mega-topline">Response Mood</div>
                                                <div class="rl-mega-value">{{ $formatMs($safe('avg_response')) }}</div>
                                            </div>
                                            <span class="rl-mega-status-chip">Speed</span>
                                        </div>
                                        <p class="rl-mega-desc">{{ $responseHealth }} berdasarkan rata-rata response time rekomendasi.</p>
                                        <div class="rl-mega-microbar" style="--rl-bar-width: {{ $responseGauge }}%;"><span></span></div>
                                        <div class="rl-mega-foot">
                                            <span class="rl-mega-foot-pill">fastest {{ $formatMs($safe('fastest_response')) }}</span>
                                            <span class="rl-mega-foot-pill">slowest {{ $formatMs($safe('slowest_response')) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="rl-floating-action-dock">
                                    <a href="#recommendation-log-table" class="rl-dock-button">📚 Buka Tabel</a>
                                    <a href="{{ route('tourhub.recommendation.index') }}" target="_blank" class="rl-dock-button">🧪 Test Engine</a>
                                    <button type="button" class="rl-dock-button" onclick="window.location.reload()">🔄 Refresh Pulse</button>
                                    <span class="rl-dock-button">🏝️ {{ $lastTopDestination }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rl-cockpit-card">
                    <div class="rl-cockpit-inner">
                        <div class="rl-section-kicker" style="color:#a7f3d0;">Execution Console</div>
                        <h3 class="rl-section-title">Live Recommendation Trace</h3>
                        <p class="rl-section-subtitle">Ringkasan log paling penting dalam gaya terminal agar halaman tidak terasa seperti tabel admin biasa.</p>

                        <div class="rl-terminal-window" style="margin-top:1rem;">
                            <div class="rl-terminal-header">
                                <div class="rl-terminal-dots">
                                    <span class="rl-terminal-dot rl-terminal-dot-red"></span>
                                    <span class="rl-terminal-dot rl-terminal-dot-yellow"></span>
                                    <span class="rl-terminal-dot rl-terminal-dot-green"></span>
                                </div>
                                <div class="rl-terminal-title">tourhub://recommendation-logs</div>
                            </div>
                            <div class="rl-terminal-body">
                                <div class="rl-terminal-line"><span class="rl-terminal-prefix">$</span><span><span class="rl-terminal-key">pipeline.status</span> = <span class="rl-terminal-value">{{ $cockpitStatus }}</span></span></div>
                                <div class="rl-terminal-line"><span class="rl-terminal-prefix">$</span><span><span class="rl-terminal-key">request.total</span> = <span class="rl-terminal-value">{{ $formatNumber($safe('total')) }}</span> <span class="rl-terminal-muted">records</span></span></div>
                                <div class="rl-terminal-line"><span class="rl-terminal-prefix">$</span><span><span class="rl-terminal-key">request.today</span> = <span class="rl-terminal-value">{{ $formatNumber($safe('today')) }}</span> <span class="rl-terminal-muted">requests</span></span></div>
                                <div class="rl-terminal-line"><span class="rl-terminal-prefix">$</span><span><span class="rl-terminal-key">success.rate</span> = <span class="rl-terminal-value">{{ $formatPercent($successRate) }}</span></span></div>
                                <div class="rl-terminal-line"><span class="rl-terminal-prefix">$</span><span><span class="rl-terminal-key">bmkg.coverage</span> = <span class="rl-terminal-value">{{ $formatPercent($bmkgRate) }}</span></span></div>
                                <div class="rl-terminal-line"><span class="rl-terminal-prefix">$</span><span><span class="rl-terminal-key">fastapi.response_avg</span> = <span class="rl-terminal-value">{{ $formatMs($safe('avg_response')) }}</span> <span class="rl-terminal-muted">// {{ $responseHealth }}</span></span></div>
                                <div class="rl-terminal-line"><span class="rl-terminal-prefix">$</span><span><span class="rl-terminal-key">last.top_destination</span> = <span class="rl-terminal-value">{{ $lastTopDestination }}</span></span></div>
                                <div class="rl-terminal-line"><span class="rl-terminal-prefix">$</span><span><span class="rl-terminal-key">last.request</span> = <span class="rl-terminal-value">{{ $agoLabel($safe('last_log_at')) }}</span><span class="rl-terminal-caret"></span></span></div>
                            </div>
                        </div>

                        <div class="rl-flow-map" style="margin-top:1rem;">
                            <div class="rl-flow-node">
                                <span class="rl-flow-icon">1</span>
                                <div><div class="rl-flow-title">Mobile / Web User</div><p class="rl-flow-desc">User mengirim preferensi wisata dan konteks lokasi/cuaca.</p></div>
                                <span class="rl-flow-status">input</span>
                            </div>
                            <div class="rl-flow-node">
                                <span class="rl-flow-icon">2</span>
                                <div><div class="rl-flow-title">Laravel Recommendation Proxy</div><p class="rl-flow-desc">Request divalidasi, diteruskan, lalu hasilnya disimpan ke database log.</p></div>
                                <span class="rl-flow-status">api</span>
                            </div>
                            <div class="rl-flow-node">
                                <span class="rl-flow-icon">3</span>
                                <div><div class="rl-flow-title">FastAPI Context-Aware Recommender</div><p class="rl-flow-desc">CBF, rating, popularitas, dan context multiplier diproses.</p></div>
                                <span class="rl-flow-status">ml</span>
                            </div>
                            <div class="rl-flow-node">
                                <span class="rl-flow-icon">4</span>
                                <div><div class="rl-flow-title">Admin Log Intelligence</div><p class="rl-flow-desc">Admin memantau status, top destination, dan response time di halaman ini.</p></div>
                                <span class="rl-flow-status">audit</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rl-content-grid rl-content-grid-main">
                <div class="rl-card rl-grid-bg rl-reveal">
                    <div class="rl-inner">
                        <div class="rl-section-header">
                            <div>
                                <div class="rl-section-kicker">Pipeline Quality</div>
                                <h3 class="rl-section-title">Kualitas Request Rekomendasi</h3>
                                <p class="rl-section-subtitle">Ringkasan performa Laravel API, FastAPI recommender, dan integrasi cuaca BMKG.</p>
                            </div>
                            <span class="rl-chip rl-chip-green">{{ $formatNumber($safe('this_month')) }} bulan ini</span>
                        </div>

                        <div class="rl-quality-grid">
                            @foreach ($qualityItems as $item)
                                @php
                                    $itemTone = $toneClass(data_get($item, 'tone', 'slate'));
                                    $itemPercent = min(100, max(0, (float) data_get($item, 'percent', 0)));
                                @endphp

                                <div class="rl-quality-card rl-glass {{ $itemTone }}">
                                    <div class="rl-quality-top">
                                        <div>
                                            <div class="rl-small-label">{{ data_get($item, 'label') }}</div>
                                            <div class="rl-quality-number">{{ $formatNumber(data_get($item, 'value', 0)) }}{{ data_get($item, 'suffix') }}</div>
                                        </div>
                                        <div class="rl-quality-badge">{{ $formatPercent($itemPercent) }}</div>
                                    </div>
                                    <div class="rl-progress"><span style="width: {{ $itemPercent }}%"></span></div>
                                    <p class="rl-quality-desc">{{ data_get($item, 'description') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="rl-card rl-reveal" data-delay="1">
                    <div class="rl-inner">
                        <div class="rl-section-kicker" style="color:#67e8f9;">Health Radar</div>
                        <h3 class="rl-section-title">Komposisi Status</h3>
                        <p class="rl-section-subtitle">Perbandingan request sukses, gagal, dan penggunaan sumber cuaca BMKG.</p>

                        <div class="rl-radar-card rl-glass" style="margin-top:1rem;">
                            <div class="rl-radar-ring" style="--rl-success-rate: {{ $successRate }}; --rl-failed-rate: {{ $failedRate }};">
                                <div class="rl-radar-center">
                                    <strong>{{ $formatPercent($successRate) }}</strong>
                                    <span>success rate</span>
                                </div>
                            </div>

                            <div class="rl-legend-grid">
                                <div class="rl-legend-item rl-glass">
                                    <div class="rl-legend-left"><span class="rl-legend-dot rl-dot-success"></span><span class="rl-legend-label">Success</span></div>
                                    <span class="rl-legend-value">{{ $formatNumber($safe('success')) }}</span>
                                </div>
                                <div class="rl-legend-item rl-glass">
                                    <div class="rl-legend-left"><span class="rl-legend-dot rl-dot-failed"></span><span class="rl-legend-label">Failed</span></div>
                                    <span class="rl-legend-value">{{ $formatNumber($safe('failed')) }}</span>
                                </div>
                                <div class="rl-legend-item rl-glass">
                                    <div class="rl-legend-left"><span class="rl-legend-dot rl-dot-bmkg"></span><span class="rl-legend-label">BMKG</span></div>
                                    <span class="rl-legend-value">{{ $formatPercent($bmkgRate) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rl-card rl-reveal" data-delay="2">
                <div class="rl-inner">
                    <div class="rl-section-header">
                        <div>
                            <div class="rl-section-kicker" style="color:#a7f3d0;">Realtime Trend</div>
                            <h3 class="rl-section-title">Aktivitas Request 12 Jam Terakhir</h3>
                            <p class="rl-section-subtitle">Grafik ringan untuk membaca jam paling aktif dan potensi error terbaru.</p>
                        </div>
                        <div class="rl-pill-row">
                            <span class="rl-chip rl-chip-blue">Max {{ $formatNumber(data_get($hourlyTrend, 'max_total', 0)) }} request/jam</span>
                            <span class="rl-chip rl-chip-green">{{ $formatNumber($safe('this_week')) }} minggu ini</span>
                        </div>
                    </div>

                    <div class="rl-trend-chart rl-glass rl-scroll-thin">
                        @foreach (data_get($hourlyTrend, 'items', []) as $trend)
                            <div class="rl-trend-bar-item">
                                <div class="rl-trend-bar-shell" title="{{ data_get($trend, 'label') }} • {{ $formatNumber(data_get($trend, 'total', 0)) }} request • {{ $formatNumber(data_get($trend, 'success', 0)) }} success • {{ $formatNumber(data_get($trend, 'failed', 0)) }} failed">
                                    <div class="rl-trend-count">{{ $formatNumber(data_get($trend, 'total', 0)) }}</div>
                                    <div class="rl-trend-bar" style="height: {{ data_get($trend, 'height', 8) }}%;"></div>
                                    <div class="rl-trend-bar-failed" style="height: {{ data_get($trend, 'failed_height', 0) }}%;"></div>
                                </div>
                                <div class="rl-trend-label">{{ data_get($trend, 'label') }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="rl-stream-wall rl-reveal" data-delay="3">
                <div class="rl-inner">
                    <div class="rl-section-header">
                        <div>
                            <div class="rl-section-kicker" style="color:#c4b5fd;">Request Stream Wall</div>
                            <h3 class="rl-section-title">Aliran Log Rekomendasi Terbaru</h3>
                            <p class="rl-section-subtitle">Preview horizontal bergaya monitoring wall supaya admin langsung melihat request terbaru tanpa harus scroll ke tabel bawah.</p>
                        </div>
                        <div class="rl-pill-row">
                            <span class="rl-chip rl-chip-purple">{{ $formatNumber($latestLogs->count()) }} latest cards</span>
                            <span class="rl-chip {{ $responseHealthClass }}">{{ $responseHealth }}</span>
                        </div>
                    </div>

                    <div class="rl-stream-marquee rl-scroll-thin">
                        @forelse ($latestLogs as $log)
                            <a href="{{ \App\Filament\Admin\Resources\RecommendationLogs\RecommendationLogResource::getUrl('view', ['record' => $log]) }}" class="rl-stream-card">
                                <div class="rl-stream-top">
                                    <span class="rl-status-badge {{ $statusClass($log->status) }}">{{ $log->status ?: 'unknown' }}</span>
                                    <span class="rl-mini-badge">{{ $formatMs($log->response_time_ms) }}</span>
                                </div>
                                <h4 class="rl-stream-title">{{ $log->top_destination_name ?: 'Belum ada top destination' }}</h4>
                                <p class="rl-stream-meta">{{ $log->user?->name ?: 'Guest / Unknown' }} • {{ $timeLabel($log->created_at) }}</p>
                                <div class="rl-stream-footer">
                                    <span class="rl-mini-badge">{{ $weatherIcon($log->weather_used) }} {{ $log->weather_used ?: 'unknown' }}</span>
                                    <span class="rl-mini-badge">{{ $formatNumber($log->total_candidates ?? 0) }} kandidat</span>
                                    <span class="rl-mini-badge">{{ $log->weather_source ?: 'manual / unknown' }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="rl-empty-state rl-glass" style="width:100%;">Belum ada request stream untuk ditampilkan.</div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="rl-content-grid rl-content-grid-three">
                <div class="rl-card rl-reveal" data-delay="1">
                    <div class="rl-inner">
                        <div class="rl-section-kicker" style="color:#93c5fd;">Weather Context</div>
                        <h3 class="rl-section-title">Distribusi Cuaca</h3>
                        <p class="rl-section-subtitle">Cuaca yang dipakai saat FastAPI membuat rekomendasi.</p>

                        <div class="rl-weather-list" style="margin-top:1rem;">
                            @forelse ($weatherDistribution as $weather)
                                @php
                                    $weatherPercent = $safe('total') > 0 ? min(100, round(((int) data_get($weather, 'total', 0) / (int) $safe('total')) * 100, 1)) : 0;
                                    $weatherTone = $toneClass(data_get($weather, 'tone'));
                                @endphp

                                <div class="rl-weather-item rl-glass {{ $weatherTone }}">
                                    <div class="rl-weather-head">
                                        <div class="rl-weather-main">
                                            <span class="rl-weather-icon">{{ $weatherIcon(data_get($weather, 'weather')) }}</span>
                                            <div>
                                                <div class="rl-list-title">{{ data_get($weather, 'label') }}</div>
                                                <p class="rl-list-desc" style="margin:.25rem 0 0;">{{ $formatNumber(data_get($weather, 'success', 0)) }} success • {{ $formatNumber(data_get($weather, 'failed', 0)) }} failed</p>
                                            </div>
                                        </div>
                                        <span class="rl-list-value">{{ $formatNumber(data_get($weather, 'total', 0)) }}</span>
                                    </div>
                                    <div class="rl-progress" style="margin-top:.78rem;"><span style="width: {{ $weatherPercent }}%"></span></div>
                                </div>
                            @empty
                                <div class="rl-empty-state rl-glass">Belum ada distribusi cuaca.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="rl-card rl-reveal" data-delay="2">
                    <div class="rl-inner">
                        <div class="rl-section-kicker" style="color:#67e8f9;">Weather Source</div>
                        <h3 class="rl-section-title">Sumber Cuaca</h3>
                        <p class="rl-section-subtitle">ADM4/BMKG atau manual context yang muncul dalam request rekomendasi.</p>

                        <div class="rl-list rl-scroll-thin" style="margin-top:1rem;max-height:27rem;overflow:auto;padding-right:.25rem;">
                            @forelse ($sourceDistribution as $source)
                                @php
                                    $sourcePercent = $safe('total') > 0 ? min(100, round(((int) data_get($source, 'total', 0) / (int) $safe('total')) * 100, 1)) : 0;
                                @endphp

                                <div class="rl-source-item rl-glass rl-tone-cyan">
                                    <div class="rl-source-head">
                                        <div style="min-width:0;">
                                            <div class="rl-source-code">{{ data_get($source, 'source') }}</div>
                                            <p class="rl-list-desc" style="margin:.32rem 0 0;">{{ $formatNumber(data_get($source, 'success', 0)) }} success • {{ $formatNumber(data_get($source, 'failed', 0)) }} failed</p>
                                        </div>
                                        <span class="rl-mini-badge">{{ $formatNumber(data_get($source, 'total', 0)) }}</span>
                                    </div>
                                    <div class="rl-progress" style="margin-top:.78rem;"><span style="width: {{ $sourcePercent }}%"></span></div>
                                </div>
                            @empty
                                <div class="rl-empty-state rl-glass">Belum ada data sumber cuaca.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="rl-card rl-reveal" data-delay="3">
                    <div class="rl-inner">
                        <div class="rl-section-kicker" style="color:#fcd34d;">Top Destination</div>
                        <h3 class="rl-section-title">Destinasi Sering Muncul</h3>
                        <p class="rl-section-subtitle">Diambil dari top destination hasil rekomendasi sukses terbaru.</p>

                        <div class="rl-list rl-scroll-thin" style="margin-top:1rem;max-height:27rem;overflow:auto;padding-right:.25rem;">
                            @forelse ($topDestinations as $destination)
                                @php
                                    $maxDestination = max(1, (int) data_get($topDestinations->first(), 'total', 1));
                                    $destinationPercent = min(100, round(((int) data_get($destination, 'total', 0) / $maxDestination) * 100, 1));
                                @endphp

                                <div class="rl-list-item rl-glass rl-tone-amber">
                                    <div class="rl-list-head">
                                        <div style="min-width:0;">
                                            <div class="rl-list-title">{{ data_get($destination, 'name') }}</div>
                                            <p class="rl-list-desc" style="margin:.28rem 0 0;">muncul sebagai rekomendasi teratas</p>
                                        </div>
                                        <span class="rl-list-value">{{ $formatNumber(data_get($destination, 'total', 0)) }}x</span>
                                    </div>
                                    <div class="rl-progress" style="margin-top:.78rem;"><span style="width: {{ $destinationPercent }}%"></span></div>
                                </div>
                            @empty
                                <div class="rl-empty-state rl-glass">Belum ada top destination dari response payload.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            <section class="rl-content-grid rl-content-grid-main">
                <div class="rl-card rl-reveal" data-delay="1">
                    <div class="rl-inner">
                        <div class="rl-section-header">
                            <div>
                                <div class="rl-section-kicker" style="color:#c4b5fd;">Latest Activity</div>
                                <h3 class="rl-section-title">Request Terbaru</h3>
                                <p class="rl-section-subtitle">Preview cepat sebelum masuk ke tabel penuh.</p>
                            </div>
                            <span class="rl-chip rl-chip-purple">{{ $formatNumber($latestLogs->count()) }} preview</span>
                        </div>

                        <div class="rl-latest-list">
                            @forelse ($latestLogs as $log)
                                <a href="{{ \App\Filament\Admin\Resources\RecommendationLogs\RecommendationLogResource::getUrl('view', ['record' => $log]) }}" class="rl-latest-item rl-glass">
                                    <div class="rl-latest-head">
                                        <div style="min-width:0;">
                                            <div class="rl-pill-row">
                                                <span class="rl-status-badge {{ $statusClass($log->status) }}">{{ $log->status ?: 'unknown' }}</span>
                                                <span class="rl-mini-badge">{{ $weatherIcon($log->weather_used) }} {{ $log->weather_used ?: 'unknown' }}</span>
                                                <span class="rl-mini-badge">{{ $formatMs($log->response_time_ms) }}</span>
                                            </div>
                                            <div class="rl-list-title" style="margin-top:.62rem;">{{ $log->top_destination_name ?: '-' }}</div>
                                            <p class="rl-list-desc" style="margin:.28rem 0 0;">{{ $log->user?->name ?: 'Guest / Unknown' }} • {{ $timeLabel($log->created_at) }}</p>
                                        </div>
                                        <span class="rl-list-value">{{ $formatNumber($log->total_candidates ?? 0) }}</span>
                                    </div>
                                </a>
                            @empty
                                <div class="rl-empty-state rl-glass">Belum ada request rekomendasi terbaru.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="rl-card rl-reveal" data-delay="2">
                    <div class="rl-inner">
                        <div class="rl-section-kicker" style="color:#a7f3d0;">User Activity</div>
                        <h3 class="rl-section-title">Pengguna Paling Aktif</h3>
                        <p class="rl-section-subtitle">Berdasarkan request rekomendasi terbaru yang tersimpan di log.</p>

                        <div class="rl-list rl-scroll-thin" style="margin-top:1rem;max-height:32rem;overflow:auto;padding-right:.25rem;">
                            @forelse ($recentUsers as $user)
                                @php
                                    $maxUser = max(1, (int) data_get($recentUsers->first(), 'total', 1));
                                    $userPercent = min(100, round(((int) data_get($user, 'total', 0) / $maxUser) * 100, 1));
                                @endphp

                                <div class="rl-list-item rl-glass rl-tone-emerald">
                                    <div class="rl-list-head">
                                        <div style="min-width:0;">
                                            <div class="rl-list-title">{{ data_get($user, 'name') }}</div>
                                            <p class="rl-list-desc" style="margin:.25rem 0 0;">{{ $formatNumber(data_get($user, 'success', 0)) }} success • {{ $formatNumber(data_get($user, 'failed', 0)) }} failed</p>
                                            <p class="rl-list-desc" style="margin:.18rem 0 0;">terakhir {{ $agoLabel(data_get($user, 'last_at')) }}</p>
                                        </div>
                                        <span class="rl-list-value">{{ $formatNumber(data_get($user, 'total', 0)) }}</span>
                                    </div>
                                    <div class="rl-progress" style="margin-top:.78rem;"><span style="width: {{ $userPercent }}%"></span></div>
                                </div>
                            @empty
                                <div class="rl-empty-state rl-glass">Belum ada aktivitas user.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            <section class="rl-table-shell rl-grid-bg rl-reveal" id="recommendation-log-table" data-delay="3">
                <div class="rl-inner">
                    <div class="rl-section-header">
                        <div>
                            <div class="rl-section-kicker">Data Table</div>
                            <h3 class="rl-section-title">Daftar Seluruh Log Rekomendasi</h3>
                            <p class="rl-section-subtitle">Gunakan filter, search, sort, view, delete, dan bulk action langsung dari tabel Filament.</p>
                        </div>
                        <div class="rl-eyebrow-row">
                            <span class="rl-chip">{{ $formatNumber($safe('total')) }} log</span>
                            <span class="rl-chip rl-chip-green">{{ $formatNumber($safe('success')) }} success</span>
                            <span class="rl-chip {{ $safe('failed') > 0 ? 'rl-chip-red' : 'rl-chip-green' }}">{{ $formatNumber($safe('failed')) }} failed</span>
                            <span class="rl-chip rl-chip-cyan">{{ $formatMs($safe('avg_response')) }} avg</span>
                        </div>
                    </div>

                    <div class="rl-table-box">
                        {{ $this->table }}
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        (() => {
            const bootRecommendationLogLuxury = () => {
                const page = document.getElementById('recommendation-log-page');

                if (!page) {
                    return;
                }

                page.classList.add('rl-js-ready');

                const revealItems = page.querySelectorAll('.rl-reveal');

                if (!('IntersectionObserver' in window)) {
                    revealItems.forEach((item) => item.classList.add('is-visible'));
                    return;
                }

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.08,
                    rootMargin: '0px 0px -40px 0px',
                });

                revealItems.forEach((item) => {
                    observer.observe(item);
                });
            };

            bootRecommendationLogLuxury();

            document.addEventListener('livewire:navigated', bootRecommendationLogLuxury);
            document.addEventListener('livewire:init', () => {
                if (window.Livewire) {
                    window.Livewire.hook('morph.updated', bootRecommendationLogLuxury);
                }
            });
        })();
    </script>
</x-filament-panels::page>
