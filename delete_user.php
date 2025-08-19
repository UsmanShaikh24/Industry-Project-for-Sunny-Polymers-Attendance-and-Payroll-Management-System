<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/notifications.php';

// Require admin access
require_admin();

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$user_id) {
    header("Location: add_user.php?error=invalid_user");
    exit();
}

// Check if user exists
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: add_user.php?error=user_not_found");
    exit();
}

// Prevent deleting the current admin user
if ($user_id == $_SESSION['user_id']) {
    header("Location: add_user.php?error=cannot_delete_self");
    exit();
}

// Handle deletion confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete related records first (due to foreign key constraints)
        
        // Delete notifications
        $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Delete advances
        $stmt = $conn->prepare("DELETE FROM advances WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Delete leave requests
        $stmt = $conn->prepare("DELETE FROM leave_requests WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Delete payslips
        $stmt = $conn->prepare("DELETE FROM payslips WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Delete attendance records
        $stmt = $conn->prepare("DELETE FROM attendance WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Finally, delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        header("Location: add_user.php?success=user_deleted&name=" . urlencode($user['name']));
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        header("Location: add_user.php?error=delete_failed");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete User - Sunny Polymers Employee Portal</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                
                <ul class="navbar-nav">
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    
                    <?php if (is_admin()): ?>
                        <li><a href="add_user.php"><i class="fas fa-user-plus"></i> Add User</a></li>
                        <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                        <li><a href="add_site.php"><i class="fas fa-map-marker-alt"></i> Add Site</a></li>
                        <li><a href="assign_site.php"><i class="fas fa-link"></i> Assign Site</a></li>
                        <li><a href="view_attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
                        <li><a href="manage_leaves.php"><i class="fas fa-calendar-times"></i> Manage Leaves</a></li>
                        <li><a href="generate_salary.php"><i class="fas fa-money-bill-wave"></i> Generate Salary</a></li>
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
                    
                    <li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
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
                <h1 class="page-title">Delete User</h1>
                <p class="page-subtitle">Confirm user deletion</p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>
                        Confirm User Deletion
                    </h3>
                </div>
                
                <div class="card-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> This action cannot be undone. All user data will be permanently deleted.
                    </div>
                    
                    <div class="user-details">
                        <h4>User Information</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Name:</label>
                                <span><?php echo htmlspecialchars($user['name']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Mobile:</label>
                                <span><?php echo htmlspecialchars($user['mobile']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Role:</label>
                                <span class="badge badge-<?php echo $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'staff' ? 'warning' : 'primary'); ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <label>State:</label>
                                <span><?php echo htmlspecialchars($user['state']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Date of Joining:</label>
                                <span><?php echo date('d M Y', strtotime($user['date_of_joining'])); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Salary:</label>
                                <span>₹<?php echo number_format($user['salary'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="deletion-warning">
                        <h4>What will be deleted:</h4>
                        <ul>
                            <li>User profile and account</li>
                            <li>All attendance records</li>
                            <li>All leave requests</li>
                            <li>All salary slips</li>
                            <li>All advances and repayments</li>
                            <li>All notifications</li>
                        </ul>
                    </div>
                    
                    <div class="form-actions">
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="confirm_delete" class="btn btn-danger" onclick="return confirm('Are you absolutely sure you want to delete this user? This action cannot be undone.')">
                                <i class="fas fa-trash"></i>
                                Delete User
                            </button>
                        </form>
                        <a href="add_user.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .user-details {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .user-details h4 {
            margin-bottom: 15px;
            color: #495057;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-item label {
            font-weight: 600;
            color: #495057;
        }
        
        .deletion-warning {
            margin: 20px 0;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
        }
        
        .deletion-warning h4 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .deletion-warning ul {
            margin: 0;
            padding-left: 20px;
            color: #856404;
        }
        
        .deletion-warning li {
            margin-bottom: 5px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-primary {
            background: #667eea;
            color: white;
        }
        
        .badge-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .badge-danger {
            background: #dc3545;
            color: white;
        }
    </style>
    <?php echo getNotificationScripts(); ?>
</body>
</html> 