<?php
// index.php - Simple landing page
require_once 'config.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('dashboard.php');
}

$page_title = 'Welcome';
include 'header.php';
?>

<style>
.hero-section {
    background: linear-gradient(135deg, var(--primary-color) 0%, #6610f2 100%);
    color: white;
    padding: 5rem 0;
    margin: -2rem -15px 3rem -15px;
}

.feature-icon {
    width: 60px;
    height: 60px;
    background: var(--accent-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    margin: 0 auto 1rem;
}
</style>

<div class="hero-section">
    <div class="container text-center">
        <i class="fas fa-receipt fa-4x mb-4 opacity-75"></i>
        <h1 class="display-4 fw-bold mb-4">LogIt</h1>
        <p class="lead mb-4">Simple, efficient receipt management for individuals and small businesses</p>
        <div class="d-flex gap-3 justify-content-center">
            <a href="register.php" class="btn btn-accent btn-lg">
                <i class="fas fa-user-plus me-2"></i>Get Started Free
            </a>
            <a href="login.php" class="btn btn-outline-light btn-lg">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In
            </a>
        </div>
    </div>
</div>

<div class="row mb-5">
    <div class="col-lg-8 mx-auto text-center">
        <h2 class="fw-bold mb-4">Why Choose LogIt?</h2>
        <p class="lead text-muted">Keep your receipts organized, searchable, and always accessible from any device.</p>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-4 text-center">
        <div class="feature-icon">
            <i class="fas fa-mobile-alt"></i>
        </div>
        <h5 class="fw-bold">Mobile Friendly</h5>
        <p class="text-muted">Upload receipts instantly from your phone or tablet. Works great on all devices.</p>
    </div>
    
    <div class="col-md-4 text-center">
        <div class="feature-icon">
            <i class="fas fa-search"></i>
        </div>
        <h5 class="fw-bold">Easy Search</h5>
        <p class="text-muted">Find any receipt quickly by searching title, vendor, category, or description.</p>
    </div>
    
    <div class="col-md-4 text-center">
        <div class="feature-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        <h5 class="fw-bold">Secure Storage</h5>
        <p class="text-muted">Your receipts are stored securely and backed up. Never lose important documents again.</p>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-4 text-center">
        <div class="feature-icon">
            <i class="fas fa-box"></i>
        </div>
        <h5 class="fw-bold">Organize by Box</h5>
        <p class="text-muted">Create separate boxes for different projects, clients, or categories.</p>
    </div>
    
    <div class="col-md-4 text-center">
        <div class="feature-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h5 class="fw-bold">Track Status</h5>
        <p class="text-muted">Mark receipts as logged for easy tax preparation and expense reporting.</p>
    </div>
    
    <div class="col-md-4 text-center">
        <div class="feature-icon">
            <i class="fas fa-download"></i>
        </div>

        <p class="text-muted">Download receipts individually or export data for accounting software.</p>
    </div>
</div>

<div class="text-center mb-5">
    <h3 class="fw-bold mb-4">Ready to Get Organized?</h3>
    <p class="lead text-muted mb-4">Join LogIt today and take control of your receipts</p>
    <a href="register.php" class="btn btn-accent btn-lg">
        <i class="fas fa-rocket me-2"></i>Start Free Today
    </a>
</div>



<?php include 'footer.php'; ?>