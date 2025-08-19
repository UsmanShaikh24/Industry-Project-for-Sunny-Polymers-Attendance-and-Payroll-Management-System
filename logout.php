<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/notifications.php';
require_once 'includes/navigation.php';

// If user confirms logout
if (isset($_POST['confirm_logout'])) {
session_destroy();
    header("Location: index.php?message=logged_out");
    exit();
}

// If user cancels logout
if (isset($_POST['cancel_logout'])) {
    header("Location: dashboard.php");
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
header("Location: index.php");
exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Logout - Sunny Polymers Employee Portal</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php echo getNotificationStyles(); ?>
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="navbar-content">
                <a href="dashboard.php" class="navbar-brand">
                    <i class="fas fa-users-cog"></i>
                    Sunny Polymers
                </a>
                
                <?php echo getNavigationMenu('logout'); ?>
                
                <!-- Right side container for notifications and mobile menu -->
                <div class="navbar-right">
                    <!-- Notification Section -->
                    <div class="navbar-notifications">
                        <div class="notification-container">
                            <div class="notification-trigger" onclick="toggleNotifications()">
                                <i class="fas fa-bell"></i>
                                <span class="notification-label">Notifications</span>
                                <?php echo getNotificationBadge($_SESSION['user_id']); ?>
                            </div>
                            <?php echo getNotificationDropdown($_SESSION['user_id']); ?>
                        </div>
                    </div>
                    
                    <!-- Mobile Menu Toggle -->
                    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Confirm Logout</h1>
                <p class="page-subtitle">Are you sure you want to logout?</p>
            </div>

            <div class="card" style="max-width: 500px; margin: 0 auto;">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i>
                        Logout Confirmation
                    </h3>
                </div>
                
                <div class="card-body">
                    <p>You are about to logout from the Attendance & Payroll System.</p>
                    <p><strong>Current User:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                    <p><strong>Role:</strong> <?php echo ucfirst(htmlspecialchars($_SESSION['user_role'])); ?></p>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> Make sure you have saved any unsaved work before logging out.
                    </div>
                </div>
                
                <div class="card-footer">
                    <form method="POST" style="display: flex; gap: 10px; justify-content: center;">
                        <button type="submit" name="cancel_logout" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="submit" name="confirm_logout" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt"></i>
                            Yes, Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php echo getNotificationScripts(); ?>
</body>
</html> 