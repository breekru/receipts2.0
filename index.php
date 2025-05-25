<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Logger - Modern Receipt Management</title>
    <link rel="icon" href="icons/ReceiptLogger.png" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <meta name="description" content="Modern receipt management system for businesses and individuals. Upload, organize, and track your receipts with ease.">
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6610f2;
            --success-color: #28a745;
            --gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        /* Hero Section */
        .hero-section {
            background: var(--gradient);
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
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
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
            border-radius: 50px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-hero-primary {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            backdrop-filter: blur(10px);
        }
        
        .btn-hero-primary:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .btn-hero-secondary {
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.5);
            color: white;
        }
        
        .btn-hero-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: white;
            color: white;
            transform: translateY(-3px);
        }
        
        /* Features Section */
        .features-section {
            padding: 6rem 0;
            background: #f8f9fa;
        }
        
        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: none;
            height: 100%;
            text-align: center;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: var(--gradient);
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
            color: #333;
        }
        
        .feature-description {
            color: #666;
            line-height: 1.6;
        }
        
        /* Stats Section */
        .stats-section {
            background: var(--gradient);
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
        
        /* How It Works Section */
        .how-it-works {
            padding: 6rem 0;
            background: white;
        }
        
        .step-card {
            text-align: center;
            padding: 2rem 1rem;
            position: relative;
        }
        
        .step-number {
            width: 60px;
            height: 60px;
            background: var(--gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .step-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .step-description {
            color: #666;
            line-height: 1.6;
        }
        
        /* CTA Section */
        .cta-section {
            background: #f8f9fa;
            padding: 5rem 0;
            text-align: center;
        }
        
        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .cta-subtitle {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2.5rem;
        }
        
        /* Footer */
        .footer {
            background: #333;
            color: white;
            padding: 3rem 0 1rem;
        }
        
        .footer-logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-bottom: 1rem;
        }
        
        .footer-links a {
            color: #ccc;
            text-decoration: none;
            margin-bottom: 0.5rem;
            display: block;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        .social-links a {
            color: #ccc;
            font-size: 1.5rem;
            margin: 0 1rem;
            transition: color 0.3s ease;
        }
        
        .social-links a:hover {
            color: var(--primary-color);
        }
        
        /* Responsive Design */
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
        
        /* Scroll animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
        
        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* PWA Install Banner */
        .install-banner {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            background: var(--gradient);
            color: white;
            padding: 1rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
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
    </style>
</head>
<body>
    <?php
    require_once 'config.php';
    
    // If user is already logged in, redirect to dashboard
    if (Utils::isLoggedIn()) {
        Utils::redirect('dashboard.php');
    }
    ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content text-center text-lg-start">
                        <img src="icons/ReceiptLogger.png" alt="Receipt Logger" class="hero-logo">
                        <h1 class="hero-title">Receipt Logger</h1>
                        <p class="hero-subtitle">
                            The modern way to manage your receipts. Upload, organize, and track your expenses with our powerful, mobile-friendly platform.
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
                    <h2 class="display-4 fw-bold mb-4">Why Choose Receipt Logger?</h2>
                    <p class="lead text-muted">Streamline your expense management with our comprehensive receipt tracking solution.</p>
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
                            Snap photos of receipts instantly with your phone. Our mobile-optimized interface makes uploading receipts quick and effortless.
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
                            Share receipt boxes with team members, clients, or family. Control access levels and collaborate seamlessly.
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
                            Advanced filtering and search capabilities. Organize by date, category, vendor, or amount to find receipts instantly.
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
                            Your receipts are encrypted and stored securely. We never share your data with third parties.
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
                            Track spending patterns with built-in analytics. Mark receipts as logged for easy tax preparation.
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
                            Install as a Progressive Web App on any device. Works offline and provides a native app experience.
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
                        <div class="stat-number" data-count="10000">0</div>
                        <div class="stat-label">Receipts Processed</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item fade-in">
                        <div class="stat-number" data-count="500">0</div>
                        <div class="stat-label">Active Users</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item fade-in">
                        <div class="stat-number" data-count="99">0</div>
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
                    <h2 class="display-4 fw-bold mb-4">How It Works</h2>
                    <p class="lead text-muted">Get started with Receipt Logger in just three simple steps.</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="step-card fade-in">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Create Account</h3>
                        <p class="step-description">
                            Sign up for free and create your first receipt box. Invite team members to collaborate.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="step-card fade-in">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Upload Receipts</h3>
                        <p class="step-description">
                            Use your phone to snap photos of receipts or upload existing files. Add details like amount and category.
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="step-card fade-in">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Organize & Track</h3>
                        <p class="step-description">
                            View, filter, and organize your receipts. Mark them as logged for tax purposes and export when needed.
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
                    <p class="cta-subtitle">
                        Join thousands of users who trust Receipt Logger for their expense management needs.
                    </p>
                    <div class="hero-buttons">
                        <a href="register.php" class="btn btn-primary btn-lg">
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
                    <img src="icons/ReceiptLogger.png" alt="Receipt Logger" class="footer-logo">
                    <h5 class="fw-bold">Receipt Logger</h5>
                    <p class="text-muted">Modern receipt management made simple. Organize, track, and collaborate on your expenses with ease.</p>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Product</h6>
                    <div class="footer-links">
                        <a href="#features">Features</a>
                        <a href="#pricing">Pricing</a>
                        <a href="#security">Security</a>
                        <a href="#api">API</a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Company</h6>
                    <div class="footer-links">
                        <a href="#about">About</a>
                        <a href="#contact">Contact</a>
                        <a href="#careers">Careers</a>
                        <a href="#blog">Blog</a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Support</h6>
                    <div class="footer-links">
                        <a href="#help">Help Center</a>
                        <a href="#docs">Documentation</a>
                        <a href="#status">Status</a>
                        <a href="#feedback">Feedback</a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Legal</h6>
                    <div class="footer-links">
                        <a href="#privacy">Privacy</a>
                        <a href="#terms">Terms</a>
                        <a href="#cookies">Cookies</a>
                        <a href="#compliance">Compliance</a>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">&copy; 2024 Receipt Logger. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-github"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- PWA Install Banner -->
    <div id="installBanner" class="install-banner">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-1">Install Receipt Logger</h6>
                <small>Get the full app experience on your device</small>
            </div>
            <div>
                <button id="installBtn" class="btn btn-light btn-sm me-2">Install</button>
                <button id="dismissBtn" class="btn btn-outline-light btn-sm">&times;</button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
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
            const target = parseInt(element.getAttribute('data-count'));
            const increment = target / 100;
            let current = 0;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = target;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.ceil(current);
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
            const speed = scrolled * 0.5;
            
            if (parallax) {
                parallax.style.transform = `translateY(${speed}px)`;
            }
        });
    </script>
</body>
</html>