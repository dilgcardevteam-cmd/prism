<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Error') - {{ config('app.name', 'PRISM') }}</title>
    <link rel="icon" href="{{ asset('DILG-Logo.png') }}" type="image/png">

    @include('partials.google-sans-font')

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        :root {
            --primary-blue: #002c76;
            --primary-blue-light: #0041a0;
            --secondary-blue: #0066cc;
            --text-light: rgba(255, 255, 255, 0.95);
            --text-muted: rgba(255, 255, 255, 0.78);
            --app-font-sans: 'Google Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: var(--app-font-sans);
            min-height: 100vh;
            color: #ffffff;
            background: linear-gradient(315deg, #001a40 0%, #002c6c 50%, #0052cc 100%);
        }

        .error-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .error-shell::before,
        .error-shell::after {
            content: '';
            position: absolute;
            border-radius: 999px;
            filter: blur(2px);
            pointer-events: none;
        }

        .error-shell::before {
            width: 420px;
            height: 420px;
            right: -140px;
            top: -120px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.22) 0%, rgba(255, 255, 255, 0) 72%);
        }

        .error-shell::after {
            width: 380px;
            height: 380px;
            left: -150px;
            bottom: -170px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.16) 0%, rgba(255, 255, 255, 0) 70%);
        }

        .error-nav {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 24px;
            background: rgba(255, 255, 255, 0.96);
            border-bottom: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 10px 24px rgba(0, 20, 58, 0.18);
        }

        .error-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--primary-blue);
            text-decoration: none;
            min-width: 0;
        }

        .error-brand img {
            width: 44px;
            height: 44px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .error-brand-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .error-brand-text strong {
            font-size: 13px;
            line-height: 1.25;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .error-brand-text span {
            font-size: 11px;
            opacity: 0.85;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .error-nav-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 999px;
            border: 1px solid rgba(0, 44, 118, 0.2);
            background: linear-gradient(180deg, #ffffff 0%, #f3f7ff 100%);
            box-shadow: 0 2px 10px rgba(0, 44, 118, 0.12);
            color: var(--primary-blue);
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            padding: 8px 14px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, color 0.2s ease;
        }

        .error-nav-action:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #002c6c 0%, #0052cc 100%);
            color: #ffffff;
            box-shadow: 0 10px 22px rgba(0, 44, 118, 0.28);
        }

        .error-main {
            position: relative;
            z-index: 1;
            width: min(1160px, 100%);
            margin: 0 auto;
            padding: 48px 22px 64px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(250px, 420px);
            gap: 28px;
            align-items: center;
            flex: 1 0 auto;
        }

        .error-content {
            position: relative;
            overflow: hidden;
            background: linear-gradient(315deg, #001a40 0%, #002c6c 50%, #0052cc 100%);
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 20px;
            padding: 34px 30px;
            box-shadow: 0 24px 40px rgba(0, 15, 48, 0.3);
            text-align: center;
        }

        .error-network-canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
            opacity: 0.52;
        }

        .error-content-inner {
            position: relative;
            z-index: 2;
        }

        .error-status {
            display: block;
            color: #ffffff;
            font-size: clamp(64px, 12vw, 132px);
            line-height: 0.9;
            font-weight: 800;
            letter-spacing: 0.04em;
            margin: 0 0 10px;
            text-shadow: 0 8px 22px rgba(0, 0, 0, 0.28);
        }

        .error-title {
            margin: 0;
            text-align: center;
            font-size: clamp(30px, 4.4vw, 50px);
            line-height: 1.15;
            font-weight: 700;
            text-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .error-message {
            margin: 14px auto 0;
            text-align: center;
            font-size: clamp(15px, 2.1vw, 18px);
            line-height: 1.65;
            color: var(--text-light);
            max-width: 52ch;
        }

        .error-meta {
            margin: 14px auto 0;
            text-align: center;
            font-size: 13px;
            line-height: 1.6;
            color: var(--text-muted);
            max-width: 56ch;
        }

        .error-actions {
            margin-top: 26px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
        }

        .error-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 999px;
            border: 2px solid #ffffff;
            min-height: 46px;
            padding: 0 20px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.01em;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, color 0.2s ease;
        }

        .error-btn-primary {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-light) 100%);
            color: #ffffff;
            box-shadow: 0 10px 22px rgba(0, 44, 118, 0.28);
        }

        .error-btn-secondary {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
        }

        .error-btn:hover {
            transform: translateY(-2px);
            color: #ffffff;
        }

        .error-btn-primary:hover {
            box-shadow: 0 14px 28px rgba(0, 44, 118, 0.35);
        }

        .error-btn-secondary:hover {
            background: #ffffff;
            color: var(--primary-blue);
        }

        .error-illustration {
            text-align: center;
        }

        .error-illustration img {
            width: min(100%, 600px);
            max-height: 510px;
            object-fit: contain;
            filter: drop-shadow(0 12px 22px rgba(0, 0, 0, 0.24));
            opacity: 0.97;
        }

        .error-footer {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 0 20px 26px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 12px;
        }

        @media (max-width: 960px) {
            .error-main {
                grid-template-columns: 1fr;
                text-align: center;
                padding-top: 36px;
            }

            .error-content {
                padding: 28px 20px;
            }

            .error-message,
            .error-meta {
                margin-left: auto;
                margin-right: auto;
            }

            .error-actions {
                justify-content: center;
            }
        }

        @media (max-width: 640px) {
            .error-nav {
                padding: 12px 14px;
            }

            .error-brand img {
                width: 36px;
                height: 36px;
            }

            .error-brand-text strong {
                font-size: 11px;
            }

            .error-brand-text span {
                font-size: 10px;
            }

            .error-nav-action {
                font-size: 12px;
                padding: 7px 11px;
            }

            .error-main {
                padding-left: 14px;
                padding-right: 14px;
            }

            .error-btn {
                width: 100%;
            }
        }
    </style>

    @yield('head')
</head>
<body>
    <div class="error-shell">
        <header class="error-nav">
            <a href="{{ url('/') }}" class="error-brand" aria-label="Go to landing page">
                <img src="{{ asset('DILG-Logo.png') }}" alt="DILG Logo">
                <span class="error-brand-text">
                    <strong>DILG CAR PRISM</strong>
                    <span>Project Development and Management Unit</span>
                </span>
            </a>
            @if (Route::has('login'))
                <a href="{{ route('login') }}" class="error-nav-action">
                    <i class="fas fa-right-to-bracket"></i>
                    <span>PRISM Portal</span>
                </a>
            @endif
        </header>

        <main class="error-main">
            <div class="error-content">
                <canvas class="error-network-canvas" data-error-network aria-hidden="true"></canvas>
                <div class="error-content-inner">
                    @hasSection('status')
                        <span class="error-status">@yield('status')</span>
                    @endif
                    <h1 class="error-title">@yield('heading', 'Something went wrong')</h1>
                    <p class="error-message">@yield('message', 'The page could not be loaded at the moment.')</p>

                    @hasSection('meta')
                        <div class="error-meta">@yield('meta')</div>
                    @endif

                    <div class="error-actions">
                        @if (trim($__env->yieldContent('actions')) !== '')
                            @yield('actions')
                        @else
                            <a href="{{ url('/') }}" class="error-btn error-btn-primary">
                                <i class="fas fa-house"></i>
                                <span>Back to Landing Page</span>
                            </a>
                            <button
                                type="button"
                                class="error-btn error-btn-secondary"
                                onclick="window.history.length > 1 ? window.history.back() : window.location.assign('{{ url('/') }}')"
                            >
                                <i class="fas fa-arrow-left"></i>
                                <span>Go Back</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="error-illustration" aria-hidden="true">
                <img src="{{ asset('PRISM.png') }}" alt="PRISM" style="width: 600px;">
            </div>
        </main>

        <footer class="error-footer">
            &copy; {{ date('Y') }} DILG CAR. All rights reserved.
        </footer>
    </div>

    <script>
        (function initializeErrorNetworkCanvas() {
            const canvas = document.querySelector('[data-error-network]');
            if (!canvas) {
                return;
            }

            const card = canvas.closest('.error-content');
            if (!card) {
                return;
            }

            const ctx = canvas.getContext('2d');
            if (!ctx) {
                return;
            }

            const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
            let particles = [];
            let width = 0;
            let height = 0;
            let animationFrame = null;
            let resizeTimer = null;

            function syncCanvasSize() {
                const rect = card.getBoundingClientRect();
                const dpr = window.devicePixelRatio || 1;
                width = Math.max(1, rect.width);
                height = Math.max(1, rect.height);
                canvas.width = Math.floor(width * dpr);
                canvas.height = Math.floor(height * dpr);
                canvas.style.width = width + 'px';
                canvas.style.height = height + 'px';
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            }

            function createParticles() {
                const count = Math.max(24, Math.min(70, Math.floor(width / 18)));
                particles = Array.from({ length: count }, function () {
                    return {
                        x: Math.random() * width,
                        y: Math.random() * height,
                        vx: (Math.random() - 0.5) * 1.1,
                        vy: (Math.random() - 0.5) * 1.1,
                        r: Math.random() * 1.5 + 1
                    };
                });
            }

            function drawNetwork(updatePositions) {
                ctx.clearRect(0, 0, width, height);

                for (let i = 0; i < particles.length; i++) {
                    const particle = particles[i];

                    if (updatePositions) {
                        particle.x += particle.vx;
                        particle.y += particle.vy;

                        if (particle.x <= 0 || particle.x >= width) {
                            particle.vx *= -1;
                        }

                        if (particle.y <= 0 || particle.y >= height) {
                            particle.vy *= -1;
                        }
                    }

                    ctx.beginPath();
                    ctx.arc(particle.x, particle.y, particle.r, 0, Math.PI * 2);
                    ctx.fillStyle = 'rgba(255, 255, 255, 0.66)';
                    ctx.fill();
                }

                const maxDistance = 168;
                for (let i = 0; i < particles.length; i++) {
                    for (let j = i + 1; j < particles.length; j++) {
                        const dx = particles[i].x - particles[j].x;
                        const dy = particles[i].y - particles[j].y;
                        const distance = Math.hypot(dx, dy);

                        if (distance < maxDistance) {
                            const alpha = 1 - distance / maxDistance;
                            ctx.beginPath();
                            ctx.moveTo(particles[i].x, particles[i].y);
                            ctx.lineTo(particles[j].x, particles[j].y);
                            ctx.strokeStyle = 'rgba(255, 255, 255, ' + (alpha * 0.34).toFixed(3) + ')';
                            ctx.lineWidth = 1.1;
                            ctx.stroke();
                        }
                    }
                }
            }

            function stopAnimation() {
                if (animationFrame) {
                    window.cancelAnimationFrame(animationFrame);
                    animationFrame = null;
                }
            }

            function runAnimationFrame() {
                drawNetwork(true);
                animationFrame = window.requestAnimationFrame(runAnimationFrame);
            }

            function refreshNetwork() {
                stopAnimation();
                syncCanvasSize();
                createParticles();

                if (motionQuery.matches) {
                    drawNetwork(false);
                    return;
                }

                runAnimationFrame();
            }

            function handleResize() {
                if (resizeTimer) {
                    window.clearTimeout(resizeTimer);
                }

                resizeTimer = window.setTimeout(refreshNetwork, 120);
            }

            window.addEventListener('resize', handleResize);

            if (typeof motionQuery.addEventListener === 'function') {
                motionQuery.addEventListener('change', refreshNetwork);
            } else if (typeof motionQuery.addListener === 'function') {
                motionQuery.addListener(refreshNetwork);
            }

            refreshNetwork();
        })();
    </script>
</body>
</html>
