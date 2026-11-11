import { Head } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import '../../css/landing.css';

const features = [
    {
        icon: 'fa-project-diagram',
        title: 'Project Monitoring',
        description: 'Real-time tracking of locally funded projects, SGLGIF, RLIP-LIME, and SubayBAYAN data.',
    },
    {
        icon: 'fa-file-upload',
        title: 'Document Management',
        description: 'Secure upload, approval workflow, and storage for MOVs, FDP, written notices, and compliance reports.',
    },
    {
        icon: 'fa-headset',
        title: 'Ticketing System',
        description: 'Streamlined support tickets with escalation, assignment, and comprehensive workflow management.',
    },
];

function NetworkCanvas() {
    const canvasRef = useRef(null);

    useEffect(() => {
        const canvas = canvasRef.current;
        const hero = canvas?.closest('.landing-hero');
        const context = canvas?.getContext('2d');

        if (!canvas || !hero || !context) {
            return undefined;
        }

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let particles = [];
        let width = 0;
        let height = 0;
        let animationId = null;

        function resizeCanvas() {
            const rect = hero.getBoundingClientRect();
            const pixelRatio = window.devicePixelRatio || 1;
            width = rect.width;
            height = rect.height;
            canvas.width = Math.floor(width * pixelRatio);
            canvas.height = Math.floor(height * pixelRatio);
            canvas.style.width = `${width}px`;
            canvas.style.height = `${height}px`;
            context.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);
        }

        function createParticles() {
            const count = Math.max(48, Math.min(120, Math.floor(width / 18)));
            particles = Array.from({ length: count }, () => ({
                x: Math.random() * width,
                y: Math.random() * height,
                vx: (Math.random() - 0.5) * 1.4,
                vy: (Math.random() - 0.5) * 1.4,
                radius: Math.random() * 1.8 + 1,
            }));
        }

        function drawFrame() {
            context.clearRect(0, 0, width, height);

            particles.forEach((particle) => {
                if (!prefersReducedMotion) {
                    particle.x += particle.vx;
                    particle.y += particle.vy;

                    if (particle.x < 0 || particle.x > width) particle.vx *= -1;
                    if (particle.y < 0 || particle.y > height) particle.vy *= -1;
                }

                context.beginPath();
                context.arc(particle.x, particle.y, particle.radius, 0, Math.PI * 2);
                context.fillStyle = 'rgba(255, 255, 255, 0.65)';
                context.fill();
            });

            for (let first = 0; first < particles.length; first += 1) {
                for (let second = first + 1; second < particles.length; second += 1) {
                    const dx = particles[first].x - particles[second].x;
                    const dy = particles[first].y - particles[second].y;
                    const distance = Math.hypot(dx, dy);

                    if (distance < 180) {
                        context.beginPath();
                        context.moveTo(particles[first].x, particles[first].y);
                        context.lineTo(particles[second].x, particles[second].y);
                        context.strokeStyle = `rgba(255, 255, 255, ${((1 - distance / 180) * 0.38).toFixed(3)})`;
                        context.lineWidth = 1.2;
                        context.stroke();
                    }
                }
            }

            if (!prefersReducedMotion) {
                animationId = window.requestAnimationFrame(drawFrame);
            }
        }

        function setup() {
            if (animationId !== null) {
                window.cancelAnimationFrame(animationId);
            }

            resizeCanvas();
            createParticles();
            drawFrame();
        }

        window.addEventListener('resize', setup);
        setup();

        return () => {
            window.removeEventListener('resize', setup);

            if (animationId !== null) {
                window.cancelAnimationFrame(animationId);
            }
        };
    }, []);

    return <canvas ref={canvasRef} className="hero-network-canvas" aria-hidden="true" />;
}

export default function Landing() {
    return (
        <>
            <Head title="PRISM | Project Monitoring" />
            <main className="landing-page">
                <nav className="navbar navbar-expand-lg navbar-light bg-white shadow-lg fixed-top landing-navbar">
                    <div className="container">
                        <a className="navbar-brand fw-bold" href="/" aria-label="PRISM home">
                            <img src="/DILG-Logo.png" alt="DILG" className="navbar-logo" />
                            <span className="navbar-brand-text">
                                <span className="line-1">Department of the Interior and Local Government</span>
                                <span className="line-2">Cordillera Administrative Region</span>
                                <span className="line-3">Project Development and Management Unit</span>
                            </span>
                        </a>
                        <button
                            className="navbar-toggler"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#topNavbar"
                            aria-controls="topNavbar"
                            aria-expanded="false"
                            aria-label="Toggle navigation"
                        >
                            <span className="navbar-toggler-icon" />
                        </button>
                        <div className="collapse navbar-collapse" id="topNavbar">
                            <div className="navbar-nav ms-auto">
                                <a className="nav-link btn btn-outline-primary btn-sm me-2" href="https://subaybayan.dilg.gov.ph" target="_blank" rel="noopener noreferrer">
                                    <i className="fas fa-globe me-1" /> SubayBAYAN Portal
                                </a>
                                <a className="nav-link btn btn-outline-primary btn-sm me-2" href="https://rssa.dilg.gov.ph" target="_blank" rel="noopener noreferrer">
                                    <i className="fas fa-chart-line me-1" /> RSSA Portal
                                </a>
                                <a className="nav-link btn btn-outline-primary btn-sm me-2" href="https://sglgif.dilg.gov.ph" target="_blank" rel="noopener noreferrer">
                                    <i className="fas fa-award me-1" /> SGLGIF Portal
                                </a>
                                <a className="nav-link btn btn-outline-primary btn-sm" href="/login">
                                    <i className="fas fa-rocket me-1" /> PRISM Portal
                                </a>
                            </div>
                        </div>
                    </div>
                </nav>

                <section className="landing-hero">
                    <NetworkCanvas />
                    <div className="container">
                        <div className="row align-items-center">
                            <div className="col-lg-6">
                                <div className="hero-content">
                                    <h1>PRISM</h1>
                                    <p className="lead fw-bold">
                                        <strong>PDMU REPORTING, INSPECTION, AND MONITORING SYSTEM</strong>
                                        <small className="text-light d-block mt-2 fw-bold">
                                            Streamlined Project Monitoring, Reporting, and Collaboration for Cordillerans
                                        </small>
                                    </p>
                                    <div className="hero-buttons d-flex gap-3 flex-wrap mb-4">
                                        <a href="/login" className="btn btn-landing-primary">
                                            <i className="fas fa-sign-in-alt me-2" /> Get Started
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div className="col-lg-6 text-center">
                                <img src="/PRISM.png" alt="PRISM" className="img-fluid hero-illustration" />
                            </div>
                        </div>
                    </div>
                </section>

                <section className="features-grid">
                    <div className="container">
                        <div className="row text-center mb-5">
                            <div className="col">
                                <h2 className="display-5 fw-bold text-dark mb-3">Core Features</h2>
                                <p className="lead text-muted">Everything you need for effective project monitoring</p>
                            </div>
                        </div>
                        <div className="row g-4">
                            {features.map((feature) => (
                                <div className="col-md-4" key={feature.title}>
                                    <article className="feature-card">
                                        <div className="feature-icon">
                                            <i className={`fas ${feature.icon}`} aria-hidden="true" />
                                        </div>
                                        <h3>{feature.title}</h3>
                                        <p>{feature.description}</p>
                                    </article>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                <footer className="footer-section">
                    <div className="container">
                        <div className="row">
                            <div className="col-md-6">
                                <h2 className="h5"><i className="fas fa-building me-2" />DILG CAR PRISM</h2>
                                <p className="mb-0 opacity-75">
                                    Department of the Interior and Local Government<br />
                                    Cordillera Administrative Region
                                </p>
                            </div>
                            <div className="col-md-3">
                                <h3 className="h6">Quick Links</h3>
                                <ul className="list-unstyled">
                                    <li><a href="/login" className="text-white-50 text-decoration-none">Login</a></li>
                                    <li><a href="/home" className="text-white-50 text-decoration-none">Dashboard</a></li>
                                    <li><a href="/projects/locally-funded" className="text-white-50 text-decoration-none">Projects</a></li>
                                </ul>
                            </div>
                            <div className="col-md-3">
                                <h3 className="h6">Contact</h3>
                                <p className="mb-0 opacity-75">
                                    <i className="fas fa-envelope me-2" />pdmucordillera@gmail.com<br />
                                    <i className="fas fa-phone me-2" />(074) 123-4567
                                </p>
                            </div>
                        </div>
                        <hr className="my-4 opacity-25" />
                        <p className="text-center mb-0 opacity-75">
                            &copy; {new Date().getFullYear()} DILG CAR. All rights reserved.
                        </p>
                    </div>
                </footer>
            </main>
        </>
    );
}
