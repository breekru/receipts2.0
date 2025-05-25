<?php
// index.php - LogIt Landing Page
require_once 'config.php';

// If user is already logged in, redirect to dashboard
if (Utils::isLoggedIn()) {
    Utils::redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php renderHeader('Welcome to LogIt', 'Modern receipt management and expense tracking made simple'); ?>
    <style>
        /* Landing page specific styles */
        .hero-section {
            background: var(--primary-gradient);
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="%23ffffff" stop-opacity="0.1"/><stop offset="100%" stop-color="%23ffffff" stop-opacity="0"/></radialGradient></defs><circle cx="200" cy="200" r="300" fill="url(%23a)"/><circle cx="800" cy="300" r="200" fill="url(%23a)"/><circle cx="400" cy="700" r="250" fill="url(%23a)"/></svg>');
            opacity: 0.3;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-logo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            box-shadow: var(--shadow-lg);
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .hero-subtitle {
            font-size: 1.4rem;
            opacity: 0.9;
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }
        
        .hero-buttons .btn {
            margin: 0.5rem;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: var(--radius-pill);
            transition: var(--transition-normal);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 180px;
        }
        
        .btn-hero-primary {
            background: var(--warm-gradient);
            border: 2px solid transparent;
            color: white;
        }
        
        .btn-hero-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(253, 126, 20, 0.4);
        }
        
        .btn-hero-secondary {
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.8);
            color: white;
        }
        
        .btn-hero-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: white;
            color: white;
            transform: translateY(-3px);
        }
        
        .features-section {
            padding: 6rem 0;
            background: var(--light-color);
        }
        
        .feature-card {
            background: white;
            border-radius: var(--radius-xl);
            padding: 2.5rem;
            box-shadow: var(--shadow-md);
            transition: var(--transition-normal);
            border: none;
            height: 100%;
            text-align: center;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: var(--accent-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
        }
        
        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }
        
        .stats-section {
            background: var(--accent-gradient);
            color: white;
            padding: 4rem 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 2rem 1rem;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .how-it-works {
            padding: 6rem 0;
            background: white;
        }
        
        .step-card {
            text-align: center;
            padding: 2rem 1rem;
        }
        
        .step-number {
            width: 60px;
            height: 60px;
            background: var(--accent-gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 1.5rem;
            box-shadow: var(--shadow-md);
        }
        
        .cta-section {
            background: var(--light-color);
            padding: 5rem 0;
            text-align: center;
        }
        
        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }
        
        .footer {
            background: var(--dark-color);
            color: white;
            padding: 3rem 0 1rem;
        }
        
        .footer-logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-bottom: 1rem;
        }
        
        .social-links a {
            color: #ccc;
            font-size: 1.5rem;
            margin: 0 1rem;
            transition: var(--transition-normal);
        }
        
        .social-links a:hover {
            color: var(--accent-color);
        }
        
        .install-banner {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            background: var(--accent-gradient);
            color: white;
            padding: 1rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            display: none;
            z-index: 1000;
        }
        
        .install-banner.show {
            display: block;
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .hero-buttons .btn {
                display: block;
                width: 100%;
                margin: 0.5rem 0;
            }
            
            .feature-card {
                margin-bottom: 2rem;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
        }
        
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
        
        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content text-center text-lg-start">
                        <img src="icons/LogIt.png" alt="LogIt" class="hero-logo">
                        <h1 class="hero-title">LogIt</h1>
                        <p class="hero-subtitle">
                            The smart way to manage your receipts. Upload, organize, and track your expenses with our powerful, mobile-friendly platform designed for modern businesses.
                        </p>
                        <div class="hero-buttons">
                            <a href="register.php" class="btn btn-hero-primary">
                                <i class="fas fa-rocket me-2"></i>Get Started Free
                            </a>
                            <a href="login.php" class="btn btn-hero-secondary">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="hero-image mt-5 mt-lg-0">
                        <i class="fas fa-receipt" style="font-size: 15rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-4 fw-bold mb-4">Why Choose LogIt?</h2>
                    <p class="lead text-muted">Streamline your expense management with our comprehensive receipt tracking solution designed for the modern workplace.</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="feature-title">Mobile First</h3>
                        <p class="feature-description">
                            Snap photos of receipts instantly with your phone. Our mobile-optimized interface makes uploading receipts quick and effortless wherever you are.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="feature-title">Team Collaboration</h3>
                        <p class="feature-description">
                            Share receipt boxes with team members, clients, or family. Control access levels and collaborate seamlessly on expense tracking.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3 class="feature-title">Smart Organization</h3>
                        <p class="feature-description">
                            Advanced filtering and search capabilities. Organize by date, category, vendor, or amount to find receipts instantly when you need them.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="feature-title">Secure & Private</h3>
                        <p class="feature-description">
                            Your receipts are encrypted and stored securely. We never share your data with third parties. Your privacy is our priority.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">Expense Tracking</h3>
                        <p class="feature-description">
                            Track spending patterns with built-in analytics. Mark receipts as logged for easy tax preparation and financial reporting.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-download"></i>
                        </div>
                        <h3 class="feature-title">PWA Ready</h3>
                        <p class="feature-description">
                            Install as a Progressive Web App on any device. Works offline and provides a native app experience across all platforms.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item fade-in">
                        <div class="stat-number" data-count="25000">0</div>
                        <div class="stat-label">Receipts Processed</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item fade-in">
                        <div class="stat-number" data-count="1200">0</div>
                        <div class="stat-label">Active Users</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item fade-in">
                        <div class="stat-number" data-count="99.9">0</div>
                        <div class="stat-label">Uptime %</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item fade-in">
                        <div class="stat-number" data-count="24">0</div>
                        <div class="stat-label">Support Hours</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-4 fw-bold mb-4">How LogIt Works</h2>
                    <p class="lead text-muted">Get started with LogIt in just three simple steps and transform your receipt management.</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="step-card fade-in">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Create Account</h3>
                        <p class="step-description">
                            Sign up for free and create your first receipt box. Invite team members to collaborate and start organizing immediately.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="step-card fade-in">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Upload Receipts</h3>
                        <p class="step-description">
                            Use your phone to snap photos of receipts or upload existing files. Add details like amount, category, and vendor information.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="step-card fade-in">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Organize & Track</h3>
                        <p class="step-description">
                            View, filter, and organize your receipts. Mark them as logged for tax purposes and export reports when needed.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container text-center">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <h2 class="cta-title">Ready to Get Organized?</h2>
                    <p class="cta-subtitle lead">
                        Join thousands of users who trust LogIt for their expense management needs. Start your free account today.
                    </p>
                    <div class="hero-buttons mt-4">
                        <a href="register.php" class="btn btn-accent btn-lg">
                            <i class="fas fa-rocket me-2"></i>Start Free Today
                        </a>
                        <a href="#features" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-info-circle me-2"></i>Learn More
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <img src="icons/LogIt.png" alt="LogIt" class="footer-logo">
                    <h5 class="fw-bold">LogIt</h5>
                    <p class="text-muted">Modern receipt management made simple. Organize, track, and collaborate on your expenses with ease and confidence.</p>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Product</h6>
                    <div class="d-flex flex-column">
                        <a href="#features" class="text-decoration-none text-muted mb-2">Features</a>
                        <a href="#pricing" class="text-decoration-none text-muted mb-2">Pricing</a>
                        <a href="#security" class="text-decoration-none text-muted mb-2">Security</a>
                        <a href="#api" class="text-decoration-none text-muted mb-2">API</a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Company</h6>
                    <div class="d-flex flex-column">
                        <a href="#about" class="text-decoration-none text-muted mb-2">About</a>
                        <a href="#contact" class="text-decoration-none text-muted mb-2">Contact</a>
                        <a href="#careers" class="text-decoration-none text-muted mb-2">Careers</a>
                        <a href="#blog" class="text-decoration-none text-muted mb-2">Blog</a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Support</h6>
                    <div class="d-flex flex-column">
                        <a href="#help" class="text-decoration-none text-muted mb-2">Help Center</a>
                        <a href="#docs" class="text-decoration-none text-muted mb-2">Documentation</a>
                        <a href="#status" class="text-decoration-none text-muted mb-2">Status</a>
                        <a href="#feedback" class="text-decoration-none text-muted mb-2">Feedback</a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Legal</h6>
                    <div class="d-flex flex-column">
                        <a href="#privacy" class="text-decoration-none text-muted mb-2">Privacy</a>
                        <a href="#terms" class="text-decoration-none text-muted mb-2">Terms</a>
                        <a href="#cookies" class="text-decoration-none text-muted mb-2">Cookies</a>
                        <a href="#compliance" class="text-decoration-none text-muted mb-2">Compliance</a>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">&copy; 2024 LogIt. All rights reserved. Built with ❤️ for better expense management.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                        <a href="#" aria-label="GitHub"><i class="fab fa-github"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- PWA Install Banner -->
    <div id="installBanner" class="install-banner">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-1">Install LogIt</h6>
                <small>Get the full app experience on your device</small>
            </div>
            <div>
                <button id="installBtn" class="btn btn-light btn-sm me-2">Install</button>
                <button id="dismissBtn" class="btn btn-outline-light btn-sm">&times;</button>
            </div>
        </div>
    </div>

    <?php renderScripts(); ?>
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Fade in animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });

        // Animated counters
        function animateCounter(element) {
            const target = parseFloat(element.getAttribute('data-count'));
            const increment = target / 100;
            let current = 0;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = target % 1 === 0 ? target : target.toFixed(1);
                    clearInterval(timer);
                } else {
                    element.textContent = current % 1 === 0 ? Math.ceil(current) : current.toFixed(1);
                }
            }, 20);
        }

        // Trigger counters when stats section is visible
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.querySelectorAll('[data-count]').forEach(counter => {
                        animateCounter(counter);
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        const statsSection = document.querySelector('.stats-section');
        if (statsSection) {
            statsObserver.observe(statsSection);
        }

        // PWA Install functionality
        let deferredPrompt;
        const installBanner = document.getElementById('installBanner');
        const installBtn = document.getElementById('installBtn');
        const dismissBtn = document.getElementById('dismissBtn');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            installBanner.classList.add('show');
        });

        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log(`User ${outcome} the install prompt`);
                deferredPrompt = null;
                installBanner.classList.remove('show');
            }
        });

        dismissBtn.addEventListener('click', () => {
            installBanner.classList.remove('show');
        });

        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then((registration) => {
                        console.log('SW registered: ', registration);
                    })
                    .catch((registrationError) => {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }

        // Parallax effect for hero section
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallax = document.querySelector('.hero-section');
            const speed = scrolled * 0.3;
            
            if (parallax && scrolled < window.innerHeight) {
                parallax.style.transform = `translateY(${speed}px)`;
            }
        });

        // Add some interactive hover effects
        document.querySelectorAll('.feature-card, .step-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.altKey) {
                switch(e.key) {
                    case 'r':
                        e.preventDefault();
                        window.location.href = 'register.php';
                        break;
                    case 'l':
                        e.preventDefault();
                        window.location.href = 'login.php';
                        break;
                }
            }
        });
    </script>
</body>
</html>