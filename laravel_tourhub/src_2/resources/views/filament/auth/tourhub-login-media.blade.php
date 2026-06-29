<div class="tourhub-auth-media-root" aria-hidden="true">
    <div id="tourhub-auth-carousel" class="tourhub-auth-carousel"></div>
    <div class="tourhub-auth-glass"></div>
    <div class="tourhub-auth-gradient"></div>

    <div class="tourhub-auth-content">
        <div class="tourhub-auth-brand-row">
            <div class="tourhub-auth-logo">T</div>
            <div>
                <div class="tourhub-auth-brand-name">TourHub Bali</div>
                <div class="tourhub-auth-brand-subtitle">Temukan destinasi wisata terbaik</div>
            </div>
        </div>

        <div class="tourhub-auth-pill">Rekomendasi wisata sesuai preferensi dan kondisi perjalanan</div>

        <h1 id="tourhub-auth-title" class="tourhub-auth-title">
            Jelajahi Bali dengan rekomendasi yang lebih personal.
        </h1>

        <p id="tourhub-auth-description" class="tourhub-auth-description">
            TourHub membantu memilih destinasi wisata Bali berdasarkan minat, cuaca, waktu kunjungan, dan kenyamanan perjalanan.
        </p>

        <div class="tourhub-auth-stats">
            <div class="tourhub-auth-stat-card">
                <strong>CBF</strong>
                <span>Preferensi wisata</span>
            </div>
            <div class="tourhub-auth-stat-card">
                <strong>CARS</strong>
                <span>Konteks perjalanan</span>
            </div>
            <div class="tourhub-auth-stat-card">
                <strong>Bali</strong>
                <span>Destinasi pilihan</span>
            </div>
        </div>

        <div class="tourhub-auth-footer-row">
            <div class="tourhub-auth-dots">
                <span class="tourhub-auth-dot is-active" data-tourhub-dot="0"></span>
                <span class="tourhub-auth-dot" data-tourhub-dot="1"></span>
                <span class="tourhub-auth-dot" data-tourhub-dot="2"></span>
                <span class="tourhub-auth-dot" data-tourhub-dot="3"></span>
            </div>
            <span id="tourhub-auth-place" class="tourhub-auth-place">Bali, Indonesia</span>
        </div>
    </div>
</div>

<style>
    .tourhub-auth-media-root,
    .tourhub-auth-media-root * {
        box-sizing: border-box;
    }

    .tourhub-auth-media-root {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        min-height: 100vh;
        overflow: hidden;
        pointer-events: none;
        background: #020617;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .tourhub-auth-carousel {
        position: absolute;
        inset: 0;
        background-size: cover;
        background-position: center;
        transform: scale(1.04);
        transition: opacity 650ms ease, transform 900ms ease, filter 650ms ease;
        opacity: 1;
        filter: saturate(1.1) contrast(1.02);
    }

    .tourhub-auth-glass {
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 18% 22%, rgba(59, 130, 246, 0.48), transparent 28%),
            radial-gradient(circle at 80% 20%, rgba(14, 165, 233, 0.26), transparent 26%),
            linear-gradient(90deg, rgba(2, 6, 23, 0.82), rgba(2, 6, 23, 0.36) 48%, rgba(2, 6, 23, 0.12));
        z-index: 1;
    }

    .tourhub-auth-gradient {
        position: absolute;
        inset: 0;
        z-index: 2;
        background:
            linear-gradient(180deg, rgba(2, 6, 23, 0.10), rgba(2, 6, 23, 0.72)),
            linear-gradient(90deg, rgba(2, 6, 23, 0.86), rgba(15, 23, 42, 0.28) 55%, rgba(2, 6, 23, 0.10));
    }

    .tourhub-auth-content {
        position: relative;
        z-index: 3;
        min-height: 100vh;
        width: min(760px, 74%);
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: clamp(36px, 5vw, 76px);
        color: #ffffff;
    }

    .tourhub-auth-brand-row {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 30px;
    }

    .tourhub-auth-logo {
        width: 50px;
        height: 50px;
        display: grid;
        place-items: center;
        border-radius: 18px;
        background: linear-gradient(135deg, #2563eb, #0f172a);
        border: 1px solid rgba(255, 255, 255, 0.25);
        box-shadow: 0 18px 45px rgba(37, 99, 235, 0.34);
        color: #ffffff;
        font-size: 22px;
        font-weight: 900;
        letter-spacing: -0.04em;
    }

    .tourhub-auth-brand-name {
        font-size: 23px;
        font-weight: 900;
        letter-spacing: -0.04em;
        line-height: 1;
    }

    .tourhub-auth-brand-subtitle {
        margin-top: 6px;
        color: rgba(226, 232, 240, 0.82);
        font-size: 13px;
        font-weight: 700;
    }

    .tourhub-auth-pill {
        width: fit-content;
        max-width: 100%;
        margin-bottom: 22px;
        padding: 10px 14px;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        background: rgba(255, 255, 255, 0.13);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        color: #dbeafe;
        font-size: 13px;
        font-weight: 800;
        line-height: 1.4;
    }

    .tourhub-auth-title {
        max-width: 720px;
        margin: 0;
        font-size: clamp(42px, 5.4vw, 76px);
        line-height: 0.95;
        letter-spacing: -0.075em;
        font-weight: 950;
        text-shadow: 0 18px 50px rgba(0, 0, 0, 0.28);
    }

    .tourhub-auth-description {
        max-width: 620px;
        margin: 22px 0 0;
        color: rgba(226, 232, 240, 0.88);
        font-size: clamp(15px, 1.45vw, 18px);
        line-height: 1.65;
        font-weight: 650;
    }

    .tourhub-auth-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        max-width: 640px;
        margin-top: 34px;
    }

    .tourhub-auth-stat-card {
        padding: 16px;
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.13);
        border: 1px solid rgba(255, 255, 255, 0.18);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.24);
    }

    .tourhub-auth-stat-card strong {
        display: block;
        color: #ffffff;
        font-size: 20px;
        font-weight: 950;
        line-height: 1;
    }

    .tourhub-auth-stat-card span {
        display: block;
        margin-top: 8px;
        color: rgba(226, 232, 240, 0.80);
        font-size: 12px;
        font-weight: 750;
        line-height: 1.35;
    }

    .tourhub-auth-footer-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        max-width: 640px;
        margin-top: 36px;
        gap: 20px;
    }

    .tourhub-auth-dots {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .tourhub-auth-dot {
        width: 9px;
        height: 9px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.38);
        transition: width 350ms ease, background 350ms ease;
    }

    .tourhub-auth-dot.is-active {
        width: 34px;
        background: #ffffff;
    }

    .tourhub-auth-place {
        color: rgba(226, 232, 240, 0.86);
        font-size: 13px;
        font-weight: 850;
    }

    @media (max-width: 1100px) {
        .tourhub-auth-content {
            width: 88%;
            padding: 34px;
        }

        .tourhub-auth-title {
            font-size: clamp(34px, 5.5vw, 58px);
        }
    }

    @media (max-width: 768px) {
        .tourhub-auth-media-root {
            min-height: 320px;
        }

        .tourhub-auth-content {
            min-height: 320px;
            width: 100%;
            padding: 28px;
        }

        .tourhub-auth-title {
            font-size: 34px;
            line-height: 1;
        }

        .tourhub-auth-description,
        .tourhub-auth-stats,
        .tourhub-auth-footer-row {
            display: none;
        }
    }
</style>

<script>
    (() => {
        const slides = [
            {
                image: 'https://images.unsplash.com/photo-1537953773345-d172ccf13cf1?auto=format&fit=crop&w=1800&q=85',
                title: 'Jelajahi Bali dengan rekomendasi yang lebih personal.',
                description: 'TourHub membantu memilih destinasi wisata Bali berdasarkan minat, cuaca, waktu kunjungan, dan kenyamanan perjalanan.',
                place: 'Tegallalang, Bali',
            },
            {
                image: 'https://images.unsplash.com/photo-1512100356356-de1b84283e18?auto=format&fit=crop&w=1800&q=85',
                title: 'Temukan tempat wisata yang cocok dengan rencana liburanmu.',
                description: 'Mulai dari pantai, budaya, alam, sampai destinasi keluarga, semuanya disusun agar lebih mudah dipilih.',
                place: 'Uluwatu, Bali',
            },
            {
                image: 'https://images.unsplash.com/photo-1555400038-63f5ba517a47?auto=format&fit=crop&w=1800&q=85',
                title: 'Rekomendasi wisata yang memperhatikan kondisi perjalanan.',
                description: 'Cuaca, waktu kunjungan, dan popularitas destinasi digunakan untuk membantu menampilkan pilihan yang lebih nyaman.',
                place: 'Pura Bali',
            },
            {
                image: 'https://images.unsplash.com/photo-1604999333679-b86d54738315?auto=format&fit=crop&w=1800&q=85',
                title: 'Satu dashboard untuk mengelola data destinasi TourHub.',
                description: 'Admin dapat mengelola data wisata, melihat riwayat rekomendasi, dan memastikan data destinasi tetap rapi.',
                place: 'Nusa Penida, Bali',
            },
        ];

        const carousel = document.getElementById('tourhub-auth-carousel');
        const title = document.getElementById('tourhub-auth-title');
        const description = document.getElementById('tourhub-auth-description');
        const place = document.getElementById('tourhub-auth-place');
        const dots = Array.from(document.querySelectorAll('[data-tourhub-dot]'));

        if (!carousel || !title || !description || !place) {
            return;
        }

        let activeIndex = 0;

        slides.forEach((slide) => {
            const img = new Image();
            img.src = slide.image;
        });

        const setSlide = (index, instant = false) => {
            const slide = slides[index];

            if (!instant) {
                carousel.style.opacity = '0.36';
                carousel.style.transform = 'scale(1.08)';
            }

            window.setTimeout(() => {
                carousel.style.backgroundImage = `url('${slide.image}')`;
                title.textContent = slide.title;
                description.textContent = slide.description;
                place.textContent = slide.place;

                dots.forEach((dot, dotIndex) => {
                    dot.classList.toggle('is-active', dotIndex === index);
                });

                carousel.style.opacity = '1';
                carousel.style.transform = 'scale(1.04)';
            }, instant ? 0 : 280);
        };

        setSlide(activeIndex, true);

        window.setInterval(() => {
            activeIndex = (activeIndex + 1) % slides.length;
            setSlide(activeIndex);
        }, 5200);
    })();
</script>
