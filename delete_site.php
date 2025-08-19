<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/notifications.php';

// Require admin access
require_admin();

// Get site ID from URL
$site_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$site_id) {
    header("Location: add_site.php?error=invalid_site");
    exit();
}

// Get site details
$stmt = $conn->prepare("SELECT s.*, COUNT(u.id) as assigned_workers FROM sites s LEFT JOIN users u ON s.id = u.site_id WHERE s.id = ? GROUP BY s.id");
$stmt->bind_param("i", $site_id);
$stmt->execute();
$site = $stmt->get_result()->fetch_assoc();

if (!$site) {
    header("Location: add_site.php?error=site_not_found");
    exit();
}

// Check if site has assigned workers
if ($site['assigned_workers'] > 0) {
    header("Location: add_site.php?error=site_in_use");
    exit();
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['confirm_delete'])) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Delete the site
            $stmt = $conn->prepare("DELETE FROM sites WHERE id = ?");
            $stmt->bind_param("i", $site_id);
            
            if ($stmt->execute()) {
                // Commit transaction
                $conn->commit();
                
                header("Location: add_site.php?success=site_deleted&name=" . urlencode($site['name']));
                exit();
            } else {
                throw new Exception("Failed to delete site");
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            header("Location: add_site.php?error=delete_failed");
            exit();
        }
    } else {
        // User cancelled deletion
        header("Location: add_site.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Site - Sunny Polymers Employee Portal</title>
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
                    <i class="fas fa-industry"></i>
                    Sunny Polymers
                </a>
                
                <ul class="navbar-nav">
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    
                    <?php if (is_admin()): ?>
                        <li><a href="add_user.php"><i class="fas fa-user-plus"></i> Add User</a></li>
                        <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                        <li><a href="add_site.php"><i class="fas fa-map-marker-alt"></i> Add Site</a></li>
                        <li><a href="assign_site.php"><i class="fas fa-link"></i> Assign Site</a></li>
                        <li><a href="view_attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
                        <li><a href="manage_leaves.php"><i class="fas fa-calendar-times"></i> Manage Leaves</a></li>
                        <li><a href="generate_salary.php"><i class="fas fa-money-bill-wave"></i> Generate Salary Slip</a></li>
                        <li><a href="manage_advances.php"><i class="fas fa-hand-holding-usd"></i> Advances</a></li>
                        <li><a href="upload_holidays.php"><i class="fas fa-calendar-day"></i> Holidays</a></li>
                    <?php else: ?>
                        <li><a href="mark_attendance.php"><i class="fas fa-clock"></i> Mark Attendance</a></li>
                        <li><a href="apply_leave.php"><i class="fas fa-calendar-plus"></i> Apply Leave</a></li>
                        <li><a href="holidays.php"><i class="fas fa-calendar-day"></i> Holidays</a></li>
                        <li><a href="view_payslip.php"><i class="fas fa-file-invoice"></i> Payslips</a></li>
                        <li><a href="view_advances.php"><i class="fas fa-hand-holding-usd"></i> Advances</a></li>
                        <li><a href="view_attendance.php"><i class="fas fa-history"></i> History</a></li>
                    <?php endif; ?>
                    
                    <!-- Notification Icon --><li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
                
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
                <h1 class="page-title">Delete Site</h1>
                <p class="page-subtitle">Confirm site deletion</p>
            </div>

            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>
                        Confirm Site Deletion
                    </h3>
                </div>
                
                <div class="card-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> This action cannot be undone. The site will be permanently deleted.
                    </div>
                    
                    <div class="site-details">
                        <h4>Site Information</h4>
                        <div class="detail-item">
                            <label>Site Name:</label>
                            <span><?php echo htmlspecialchars($site['name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>State:</label>
                            <span><?php echo htmlspecialchars($site['state']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Coordinates:</label>
                            <span><?php echo number_format($site['latitude'], 6); ?>, <?php echo number_format($site['longitude'], 6); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Created:</label>
                            <span><?php echo date('d M Y H:i', strtotime($site['created_at'])); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Assigned Workers:</label>
                            <span class="badge badge-success"><?php echo $site['assigned_workers']; ?> workers</span>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> This site has no assigned workers, so it can be safely deleted.
                    </div>
                </div>
                
                <div class="card-footer">
                    <form method="POST" style="display: flex; gap: 10px; justify-content: center;">
                        <button type="submit" name="cancel_delete" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="submit" name="confirm_delete" class="btn btn-danger">
                            <i class="fas fa-trash"></i>
                            Yes, Delete Site
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .site-details {
            margin: 20px 0;
        }
        
        .site-details h4 {
            margin-bottom: 15px;
            color: #495057;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-item label {
            font-weight: 600;
            color: #495057;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #28a745;
            color: white;
        }
    </style>
    <?php echo getNotificationScripts(); ?>
</body>
</html> 