<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'PDMUOMS') }}</title>
    <link rel="icon" href="{{ asset('DILG-Logo.png') }}" type="image/png">
    
    @include('partials.google-sans-font')
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-blue: #002C76;
            --primary-blue-light: #0041A0;
            --secondary-blue: #0066CC;
            --accent-gold: #D4AF37;
            --success-green: #28a745;
            --warning-orange: #fd7e14;
            --danger-red: #dc3545;
            --light-gray: #f8f9fa;
            --medium-gray: #6c757d;
            --dark-gray: #343a40;
            --app-font-sans: 'Google Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        body {
            font-family: var(--app-font-sans);
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .landing-hero {
            min-height: 70vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
            position: relative;
            overflow: hidden;
        }

        .landing-hero .container {
            position: relative;
            z-index: 2;
        }

        .hero-network-canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
            opacity: 0.5;
        }
        
        .hero-content h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
        }
        
        .hero-content p.lead {
            font-size: 1.25rem;
            color: var(--dark-gray);
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .btn-landing-primary {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-light));
            border: 2px solid #ffffff;
            padding: 1rem 2.5rem;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 50px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 44, 118, 0.3);
        }
        
        .btn-landing-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0, 44, 118, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .btn-landing-secondary {
            background: transparent;
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue);
            padding: 0.9rem 2.5rem;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-landing-secondary:hover {
            background: #ffffff;
            color: var(--primary-blue);
            border-color: #ffffff;
            transform: translateY(-2px);
            text-decoration: none;
        }
        
        .features-grid {
            background: white;
            padding: 4rem 0;
            margin-top: 1rem;
        }

        .navbar-brand img {
            transition: transform 0.3s ease;
        }

        .navbar-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            line-height: 1.2;
        }

        .navbar-logo {
            height: 90px;
            width: auto;
            margin-right: 10px;
        }

        .navbar-brand-text {
            display: flex;
            flex-direction: column;
            color: var(--primary-blue);
        }

        .navbar-brand-text .line-1 {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .navbar-brand-text .line-2 {
            font-size: 0.8rem;
            font-weight: 600;
            padding-bottom: 0.15rem;
            margin-bottom: 0.15rem;
            border-bottom: 1px solid rgba(0, 44, 118, 0.35);
        }

        .navbar-brand-text .line-3 {
            font-size: 0.76rem;
            font-weight: 600;
        }

        .navbar-brand:hover img {
            transform: scale(1.05);
        }

        .navbar-nav {
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link.btn {
            padding: 0.52rem 1rem;
            border-radius: 999px;
            font-weight: 600;
            letter-spacing: 0.01em;
            border: 1px solid rgba(0, 44, 118, 0.2);
            color: var(--primary-blue) !important;
            background: linear-gradient(180deg, #ffffff 0%, #f3f7ff 100%);
            box-shadow: 0 2px 8px rgba(0, 44, 118, 0.12);
            transition: transform 0.2s ease, box-shadow 0.25s ease, background 0.25s ease, color 0.25s ease;
        }

        .nav-link.btn i {
            color: var(--secondary-blue);
            transition: color 0.25s ease;
        }

        .nav-link.btn:focus-visible {
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(0, 102, 204, 0.25), 0 4px 14px rgba(0, 44, 118, 0.2);
        }

        .nav-link.btn:hover {
            transform: translateY(-2px);
            color: #ffffff !important;
            background: linear-gradient(135deg, #002c6c 0%, #0052cc 100%);
            border-color: transparent;
            box-shadow: 0 10px 22px rgba(0, 44, 118, 0.28);
        }

        .nav-link.btn:hover i {
            color: #ffffff;
        }

        .nav-link.btn:active {
            transform: translateY(0);
        }
        
        .feature-card {
            text-align: center;
            padding: 2rem;
            border-radius: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            border: none;
            background: #fff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
        }
        
        .footer-section {
            background: var(--primary-blue);
            color: white;
            padding: 3rem 0 2rem;
            margin-top: 4rem;
        }
        
        @media (max-width: 768px) {
            .hero-buttons {
                flex-direction: column;
                gap: 1rem;
            }
            
            .hero-buttons .btn {
                width: 100%;
            }
        }

        @media (max-width: 991.98px) {
            .navbar-logo {
                height: 64px;
            }

            .navbar-brand-text .line-1,
            .navbar-brand-text .line-2,
            .navbar-brand-text .line-3 {
                font-size: 0.66rem;
            }

            .navbar-collapse {
                width: 100%;
                margin-top: 0.75rem;
            }

            .navbar-nav {
                align-items: stretch;
                width: 100%;
                gap: 0.5rem;
            }

            .navbar-nav .nav-link.btn {
                width: 100%;
                margin-right: 0 !important;
                text-align: center;
            }

            .landing-hero {
                min-height: auto;
                padding-top: 7rem !important;
                padding-bottom: 2.5rem;
                text-align: center;
            }

            .hero-content p.lead {
                font-size: 1.05rem;
            }

            .hero-buttons {
                justify-content: center;
            }

            .hero-buttons .btn {
                width: 100%;
                max-width: 320px;
            }

            .hero-illustration {
                max-height: 300px !important;
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-lg fixed-top" style="z-index: 1050;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/" style="font-size: 1.5rem;">
                <img src="{{ asset('DILG-Logo.png') }}" alt="DILG" class="navbar-logo">
                <span class="navbar-brand-text">
                    <span class="line-1">DEPARTMENT OF THE INTERIOR AND LOCAL GOVERNMENT</span>
                    <span class="line-2">Cordillera Administrative Region</span>
                    <span class="line-3">Project Development and Management Unit</span>
                </span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbar" aria-controls="topNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="topNavbar">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link btn btn-outline-primary btn-sm me-2" href="https://subaybayan.dilg.gov.ph" target="_blank" rel="noopener">
                        <i class="fas fa-globe me-1"></i> SubayBAYAN Portal
                    </a>
                    <a class="nav-link btn btn-outline-primary btn-sm me-2" href="https://rssa.dilg.gov.ph" target="_blank" rel="noopener">
                        <i class="fas fa-chart-line me-1"></i> RSSA Portal
                    </a>
                    <a class="nav-link btn btn-outline-primary btn-sm me-2" href="https://sglgif.dilg.gov.ph" target="_blank" rel="noopener">
                        <i class="fas fa-award me-1"></i> SGLGIF Portal
                    </a>
                    <a class="nav-link btn btn-outline-primary btn-sm" href="{{ route('login') }}" rel="noopener">
                        <i class="fas fa-rocket me-1"></i> PRISM Portal
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section with padding-top for fixed navbar -->
    <section class="landing-hero" style="padding-top: 80px; background: linear-gradient(315deg, #001a40 0%, #002c6c 50%, #0052cc 100%);">
        <canvas id="hero-network" class="hero-network-canvas" aria-hidden="true"></canvas>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1 style="color: white !important; text-shadow: 0 4px 8px rgba(0,0,0,0.3);">PRISM</h1>
                        <p class="lead fw-bold" style="color: rgba(255,255,255,0.95);">
                            <strong>PDMU REPORTING, INSPECTION, AND MONITORING SYSTEM</strong><br>
                            <small class="text-light d-block mt-2 fw-bold">Streamlined Project Monitoring, Reporting, and Collaboration for Cordillerans</small>
                        </p>
                        <div class="hero-buttons d-flex gap-3 flex-wrap mb-4">
                            <a href="{{ route('login') }}" class="btn btn-landing-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Get Started
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="{{ asset('PRISM.png') }}" alt="PRISM" class="img-fluid hero-illustration" style="max-height: 400px; opacity: 0.95; filter: drop-shadow(0 8px 16px rgba(0,0,0,0.3));">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-grid">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col">
                    <h2 class="display-5 fw-bold text-dark mb-3">Core Features</h2>
                    <p class="lead text-muted">Everything you need for effective project monitoring</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <h4>Project Monitoring</h4>
                        <p>Real-time tracking of locally funded projects, SGLGIF, RLIP-LIME, and SubayBAYAN data.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon">
                            <i class="fas fa-file-upload"></i>
                        </div>
                        <h4>Document Management</h4>
                        <p>Secure upload, approval workflow, and storage for MOVs, FDP, written notices, and compliance reports.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h4>Ticketing System</h4>
                        <p>Streamlined support tickets with escalation, assignment, and comprehensive workflow management.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-section">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-building me-2"></i>DILG CAR PRISM</h5>
                    <p class="mb-0 opacity-75">Department of the Interior and Local Government<br>Cordillera Administrative Region</p>
                </div>
                <div class="col-md-3">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('login') }}" class="text-white-50 text-decoration-none">Login</a></li>
                        <li><a href="{{ route('home') }}" class="text-white-50 text-decoration-none">Dashboard</a></li>
                        <li><a href="{{ url('/projects/locally-funded') }}" class="text-white-50 text-decoration-none">Projects</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Contact</h6>
                    <p class="mb-0 opacity-75">
                        <i class="fas fa-envelope me-2"></i>pdmucordillera@gmail.com<br>
                        <i class="fas fa-phone me-2"></i>(074) 123-4567
                    </p>
                </div>
            </div>
            <hr class="my-4 opacity-25">
            <div class="text-center">
                <p class="mb-0 opacity-75">&copy; {{ date('Y') }} DILG CAR. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const canvas = document.getElementById('hero-network');
            if (!canvas) {
                return;
            }

            const hero = canvas.closest('.landing-hero');
            const ctx = canvas.getContext('2d');
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            let particles = [];
            let width = 0;
            let height = 0;
            let animationId = null;

            function resizeCanvas() {
                const rect = hero.getBoundingClientRect();
                const dpr = window.devicePixelRatio || 1;
                width = rect.width;
                height = rect.height;
                canvas.width = Math.floor(width * dpr);
                canvas.height = Math.floor(height * dpr);
                canvas.style.width = width + 'px';
                canvas.style.height = height + 'px';
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            }

            function createParticles() {
                const baseCount = Math.max(48, Math.min(120, Math.floor(width / 18)));
                particles = Array.from({ length: baseCount }, function () {
                    return {
                        x: Math.random() * width,
                        y: Math.random() * height,
                        vx: (Math.random() - 0.5) * 1.4,
                        vy: (Math.random() - 0.5) * 1.4,
                        r: Math.random() * 1.8 + 1
                    };
                });
            }

            function drawFrame() {
                ctx.clearRect(0, 0, width, height);

                for (let i = 0; i < particles.length; i++) {
                    const p = particles[i];
                    p.x += p.vx;
                    p.y += p.vy;

                    if (p.x < 0 || p.x > width) {
                        p.vx *= -1;
                    }
                    if (p.y < 0 || p.y > height) {
                        p.vy *= -1;
                    }

                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                    ctx.fillStyle = 'rgba(255, 255, 255, 0.65)';
                    ctx.fill();
                }

                const maxDistance = 180;
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
                            ctx.strokeStyle = 'rgba(255, 255, 255, ' + (alpha * 0.38).toFixed(3) + ')';
                            ctx.lineWidth = 1.2;
                            ctx.stroke();
                        }
                    }
                }

                animationId = window.requestAnimationFrame(drawFrame);
            }

            function setup() {
                resizeCanvas();
                createParticles();

                if (!prefersReducedMotion) {
                    if (animationId) {
                        window.cancelAnimationFrame(animationId);
                    }
                    drawFrame();
                } else {
                    drawFrame();
                    if (animationId) {
                        window.cancelAnimationFrame(animationId);
                        animationId = null;
                    }
                }
            }

            window.addEventListener('resize', setup);
            setup();
        })();
    </script>
</body>
</html>

