<?php
// header.php - Common header for all pages
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, maximum-scale=5.0">
    <title><?php echo $page_title ?? 'LogIt'; ?> - Receipt Manager</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#fd7e14">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="LogIt">
    <link rel="apple-touch-icon" href="icons/LogIt-152.png">
    <link rel="icon" type="image/png" sizes="32x32" href="icons/LogIt-72.png">
    <link rel="icon" type="image/png" sizes="96x96" href="icons/LogIt-96.png">
    <link rel="icon" type="image/png" sizes="192x192" href="icons/LogIt-192.png">
    <link rel="shortcut icon" type="image/png" href="icons/LogIt-72.png">
        <style>
        :root {
            --primary-color: #0d6efd;
            --accent-color: #fd7e14;
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 80px; /* Space for mobile bottom nav */
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, #6610f2 100%) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
        }
        
        .btn-accent {
            background: var(--accent-color);
            border: none;
            color: white;
            border-radius: 8px;
        }
        
        .btn-accent:hover {
            background: #e67e22;
            color: white;
        }
        
        .alert {
            border: none;
            border-radius: 8px;
            border-left: 4px solid;
        }
        
        .alert-success { border-left-color: #28a745; }
        .alert-danger { border-left-color: #dc3545; }
        .alert-info { border-left-color: #17a2b8; }
        .alert-warning { border-left-color: #ffc107; }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        /* Mobile-first responsive design */
        @media (max-width: 768px) {
            .container {
                padding-left: 15px;
                padding-right: 15px;
            }
            
            .card {
                margin-bottom: 1rem;
                border-radius: 8px;
            }
            
            .btn {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
            
            .navbar-collapse {
                margin-top: 1rem;
                padding: 1rem;
                background: rgba(255,255,255,0.1);
                border-radius: 8px;
            }
            
            /* Hide desktop navbar on mobile - we'll use bottom nav */
            .navbar {
                display: none;
            }
            
            body {
                margin-top: 0;
                padding-top: 1rem;
            }
        }

        /* Mobile bottom navigation */
        .mobile-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, var(--primary-color) 0%, #6610f2 100%);
            padding: 0.75rem 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 1030;
        }
        
        .mobile-nav-item {
            flex: 1;
            text-align: center;
        }
        
        .mobile-nav-link {
            display: block;
            color: white;
            text-decoration: none;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background-color 0.2s;
            font-size: 0.75rem;
        }
        
        .mobile-nav-link:hover,
        .mobile-nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .mobile-nav-link i {
            display: block;
            font-size: 1.2rem;
            margin-bottom: 0.25rem;
        }

        @media (max-width: 768px) {
            .mobile-bottom-nav {
                display: flex;
            }
        }

        /* PWA install prompt improvements */
        .pwa-install-prompt {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 1rem;
            position: fixed;
            bottom: 80px; /* Above mobile nav */
            left: 1rem;
            right: 1rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            z-index: 1040;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (min-width: 769px) {
            .pwa-install-prompt {
                bottom: 20px;
                max-width: 400px;
                left: auto;
                right: 20px;
            }
        }

        /* Touch-friendly UI improvements */
        .btn {
            min-height: 44px; /* Apple's recommended touch target size */
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .form-control, .form-select {
            min-height: 44px;
            font-size: 16px; /* Prevents iOS zoom on focus */
        }

        /* Improved spacing for mobile */
        @media (max-width: 768px) {
            .mb-3 {
                margin-bottom: 1.5rem !important;
            }
            
            .mt-4 {
                margin-top: 2rem !important;
            }
            
            h1, h2 {
                font-size: 1.75rem;
            }
            
            .display-6 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php if (is_logged_in()): ?>
    <!-- Desktop Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark d-none d-md-block">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-receipt me-2"></i>LogIt
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="upload.php">
                            <i class="fas fa-upload me-1"></i>Upload
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="boxes.php">
                            <i class="fas fa-box me-1"></i>Boxes
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav d-md-none">
        <div class="mobile-nav-item">
            <a href="dashboard.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
        </div>
        <div class="mobile-nav-item">
            <a href="upload.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'upload.php') ? 'active' : ''; ?>">
                <i class="fas fa-plus"></i>
                Upload
            </a>
        </div>
        <div class="mobile-nav-item">
            <a href="boxes.php" class="mobile-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'boxes.php') ? 'active' : ''; ?>">
                <i class="fas fa-box"></i>
                Boxes
            </a>
        </div>
        <div class="mobile-nav-item">
            <a href="#" class="mobile-nav-link" onclick="showMobileMenu(event)">
                <i class="fas fa-user"></i>
                <?php echo substr($_SESSION['username'], 0, 8); ?>
            </a>
        </div>
    </div>

    <!-- Mobile User Menu Modal -->
    <div class="modal fade" id="mobileUserModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user me-2"></i><?php echo $_SESSION['username']; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-grid gap-2">
                        <a href="logout.php" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="container mt-4">

<script>
// Mobile menu handler
function showMobileMenu(event) {
    event.preventDefault();
    const modal = new bootstrap.Modal(document.getElementById('mobileUserModal'));
    modal.show();
}
</script>