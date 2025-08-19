<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/notifications.php';
require_once 'includes/navigation.php';

// Require authentication
require_auth();

// Get current user data
$user = get_current_user_data();
$user_site = get_user_site($_SESSION['user_id']);

// Get dashboard stats based on user role
$stats = [];

if (is_admin()) {
    // Admin stats
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role IN ('worker', 'staff')");
    $stmt->execute();
    $stats['total_workers'] = $stmt->get_result()->fetch_assoc()['total'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM sites");
    $stmt->execute();
    $stats['total_sites'] = $stmt->get_result()->fetch_assoc()['total'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE DATE(date) = CURDATE()");
    $stmt->execute();
    $stats['today_attendance'] = $stmt->get_result()->fetch_assoc()['total'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM leave_requests WHERE status = 'pending'");
    $stmt->execute();
    $stats['pending_leaves'] = $stmt->get_result()->fetch_assoc()['total'];
} else {
    // Worker/Staff stats
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE user_id = ? AND DATE(date) = CURDATE()");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stats['today_attendance'] = $stmt->get_result()->fetch_assoc()['total'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE user_id = ? AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stats['month_attendance'] = $stmt->get_result()->fetch_assoc()['total'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM leave_requests WHERE user_id = ? AND status = 'pending'");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stats['pending_leaves'] = $stmt->get_result()->fetch_assoc()['total'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM leave_requests WHERE user_id = ? AND status = 'approved' AND YEAR(start_date) = YEAR(CURDATE())");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stats['leaves_taken'] = $stmt->get_result()->fetch_assoc()['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sunny Polymers Employee Portal</title>
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
                
                <?php echo getNavigationMenu('dashboard'); ?>
                
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
                <h1 class="page-title">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
                <p class="page-subtitle">
                    <?php echo ucfirst($_SESSION['user_role']); ?> Dashboard
                    <?php if ($user_site): ?>
                        - Assigned to: <?php echo htmlspecialchars($user_site['name']); ?>
                    <?php endif; ?>
                </p>
            </div>

                         <!-- Dismissible Security Reminder -->
             <div id="security-reminder" class="alert alert-warning dismissible-alert" style="display: <?php echo isset($_COOKIE['hide_security_reminder']) ? 'none' : 'flex'; ?>;">
                 <div class="alert-content">
                     <i class="fas fa-shield-alt"></i>
                     <div class="alert-text">
                         <strong>Security Reminder:</strong> 
                        For your account security, please change your password every 15 days. 
                         <a href="change_password.php" class="alert-link">Change Password Now</a>
                     </div>
                 </div>
                 <button type="button" class="alert-close" onclick="dismissSecurityReminder()">
                     <i class="fas fa-times"></i>
                 </button>
             </div>

            <?php if (isset($_GET['error']) && $_GET['error'] == 'unauthorized'): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    You don't have permission to access that page.
                </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <?php if (is_admin()): ?>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users" style="color: #667eea;"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['total_workers']; ?></div>
                        <div class="stat-label">Total Workers & Staff</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-map-marker-alt" style="color: #28a745;"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['total_sites']; ?></div>
                        <div class="stat-label">Total Sites</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check" style="color: #ffc107;"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['today_attendance']; ?></div>
                        <div class="stat-label">Today's Attendance</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock" style="color: #dc3545;"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['pending_leaves']; ?></div>
                        <div class="stat-label">Pending Leave Requests</div>
                    </div>
                <?php else: ?>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check" style="color: #667eea;"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['today_attendance']; ?></div>
                        <div class="stat-label">Today's Attendance</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-alt" style="color: #28a745;"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['month_attendance']; ?></div>
                        <div class="stat-label">This Month's Attendance</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock" style="color: #ffc107;"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['pending_leaves']; ?></div>
                        <div class="stat-label">Pending Leave Requests</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-times" style="color: #dc3545;"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['leaves_taken']; ?></div>
                        <div class="stat-label">Leaves Taken This Year</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-2">
                <?php if (is_admin()): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Quick Actions</h3>
                        </div>
                        <div class="grid grid-2">
                            <a href="add_user.php" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i>
                                Add New User
                            </a>
                            <a href="add_site.php" class="btn btn-success">
                                <i class="fas fa-map-marker-alt"></i>
                                Add New Site
                            </a>
                            <a href="view_attendance.php" class="btn btn-warning">
                                <i class="fas fa-calendar-check"></i>
                                View Attendance
                            </a>
                            <a href="generate_salary.php" class="btn btn-secondary">
                                <i class="fas fa-money-bill-wave"></i>
                                Generate Salary Slip
                            </a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Activity</h3>
                        </div>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Worker</th>
                                        <th>Site</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $conn->prepare("SELECT a.*, u.name as worker_name, s.name as site_name 
                                                          FROM attendance a 
                                                          INNER JOIN users u ON a.user_id = u.id 
                                                          LEFT JOIN sites s ON u.site_id = s.id 
                                                          ORDER BY a.date DESC, a.check_in_time DESC 
                                                          LIMIT 5");
                                    $stmt->execute();
                                    $recent_attendance = $stmt->get_result();
                                    
                                    while ($row = $recent_attendance->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['worker_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['site_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('H:i', strtotime($row['check_in_time'])); ?></td>
                                        <td>
                                            <span class="status-present">Present</span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Quick Actions</h3>
                        </div>
                        <div class="grid grid-2">
                            <a href="mark_attendance.php" class="btn btn-primary">
                                <i class="fas fa-clock"></i>
                                Mark Attendance
                            </a>
                            <a href="apply_leave.php" class="btn btn-success">
                                <i class="fas fa-calendar-plus"></i>
                                Apply Leave
                            </a>
                            <a href="view_payslip.php" class="btn btn-warning">
                                <i class="fas fa-file-invoice"></i>
                                View Payslips
                            </a>
                            <a href="view_attendance.php" class="btn btn-secondary">
                                <i class="fas fa-history"></i>
                                Attendance History
                            </a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Your Recent Attendance</h3>
                        </div>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $conn->prepare("SELECT * FROM attendance WHERE user_id = ? ORDER BY date DESC LIMIT 5");
                                    $stmt->bind_param("i", $_SESSION['user_id']);
                                    $stmt->execute();
                                    $recent_attendance = $stmt->get_result();
                                    
                                    while ($row = $recent_attendance->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($row['date'])); ?></td>
                                        <td><?php echo $row['check_in_time'] ? date('H:i', strtotime($row['check_in_time'])) : '-'; ?></td>
                                        <td><?php echo $row['check_out_time'] ? date('H:i', strtotime($row['check_out_time'])) : '-'; ?></td>
                                        <td>
                                            <?php if ($row['check_in_time'] && $row['check_out_time']): ?>
                                                <span class="status-present">Complete</span>
                                            <?php elseif ($row['check_in_time']): ?>
                                                <span class="status-warning">Checked In</span>
                                            <?php else: ?>
                                                <span class="status-absent">Absent</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php echo getNotificationScripts(); ?>
    
    <style>
        /* Dismissible Alert Styles */
        .dismissible-alert {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            position: relative;
        }
        
        .alert-content {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            flex: 1;
        }
        
        .alert-text {
            flex: 1;
        }
        
        .alert-close {
            background: none;
            border: none;
            color: #92400e;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: var(--radius-sm);
            transition: all 0.2s ease;
            flex-shrink: 0;
            margin-left: 1rem;
        }
        
        .alert-close:hover {
            background: rgba(146, 64, 14, 0.1);
            color: #78350f;
        }
        
        .alert-link {
            color: #92400e;
            text-decoration: underline;
            font-weight: 600;
        }
        
        .alert-link:hover {
            color: #78350f;
        }
        
        /* Navbar Notifications */
        .navbar-notifications {
            display: flex;
            align-items: center;
            margin-left: 1rem;
        }
        
        .notification-trigger {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.2s ease;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            position: relative;
        }
        
        .notification-trigger:hover {
            background: var(--bg-tertiary);
            color: var(--primary-color);
        }
        
        .notification-label {
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .notification-badge {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.625rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            border: 2px solid var(--bg-primary);
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .navbar-notifications {
                margin-left: 0.5rem;
            }
            
            .notification-label {
                display: none;
            }
            
            .notification-trigger {
                padding: 0.5rem;
            }
            
            .alert-content {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .alert-close {
                margin-left: 0;
                margin-top: 0.5rem;
                align-self: flex-start;
            }
        }
    </style>
    
    <script>
        function dismissSecurityReminder() {
            const reminder = document.getElementById('security-reminder');
            reminder.style.display = 'none';
            
            // Set cookie to remember dismissal for 7 days
            const date = new Date();
            date.setTime(date.getTime() + (7 * 24 * 60 * 60 * 1000));
            document.cookie = "hide_security_reminder=1; expires=" + date.toUTCString() + "; path=/";
        }
    </script>
</body>
</html> 