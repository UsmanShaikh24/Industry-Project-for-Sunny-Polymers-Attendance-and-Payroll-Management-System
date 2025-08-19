<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/notifications.php';
require_once 'includes/navigation.php';

// Require admin authentication
require_admin();

// Handle leave approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && isset($_POST['leave_id'])) {
        $leave_id = $_POST['leave_id'];
        $action = $_POST['action'];
        
        if ($action == 'approve' || $action == 'reject') {
            $status = ($action == 'approve') ? 'approved' : 'rejected';
            
            // Get leave request details first
            $leave_stmt = $conn->prepare("SELECT lr.*, u.name as user_name FROM leave_requests lr JOIN users u ON lr.user_id = u.id WHERE lr.id = ?");
            $leave_stmt->bind_param("i", $leave_id);
            $leave_stmt->execute();
            $leave_request = $leave_stmt->get_result()->fetch_assoc();
            
            if ($leave_request) {
                $stmt = $conn->prepare("UPDATE leave_requests SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
                $stmt->bind_param("sii", $status, $_SESSION['user_id'], $leave_id);
                
                if ($stmt->execute()) {
                    $success_message = "Leave request " . ucfirst($status) . " successfully!";
                    
                    // Create notification for the user
                    $notification_title = "Leave Request " . ucfirst($status);
                    $notification_message = "Your leave request for " . date('M j', strtotime($leave_request['start_date'])) . " to " . date('M j', strtotime($leave_request['end_date'])) . " has been " . $status . ".";
                    $notification_type = ($status == 'approved') ? 'success' : 'warning';
                    
                    createNotification(
                        $leave_request['user_id'],
                        $notification_title,
                        $notification_message,
                        $notification_type,
                        'apply_leave.php'
                    );
                } else {
                    $error_message = "Error updating leave request.";
                }
            } else {
                $error_message = "Leave request not found.";
            }
        }
    }
}

// Get all leave requests
$stmt = $conn->prepare("
    SELECT lr.*, u.name as user_name, u.mobile, u.state, u.salary, s.name as site_name
    FROM leave_requests lr 
    JOIN users u ON lr.user_id = u.id 
    LEFT JOIN sites s ON u.site_id = s.id
    ORDER BY lr.created_at DESC
");
$stmt->execute();
$leave_requests = $stmt->get_result();

// Get statistics
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM leave_requests GROUP BY status");
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = [];
while ($row = $stats_result->fetch_assoc()) {
    $stats[$row['status']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Leave Requests - Sunny Polymers Employee Portal</title>
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
                
                <?php echo getNavigationMenu('manage_leaves'); ?>
                
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
                <h1 class="page-title">Manage Leave Requests</h1>
                <p class="page-subtitle">Review and approve/reject worker leave requests</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock" style="color: #ffc107;"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['pending'] ?? 0; ?></div>
                    <div class="stat-label">Pending Requests</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle" style="color: #28a745;"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['approved'] ?? 0; ?></div>
                    <div class="stat-label">Approved</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle" style="color: #dc3545;"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['rejected'] ?? 0; ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt" style="color: #667eea;"></i>
                    </div>
                    <div class="stat-number"><?php echo $leave_requests->num_rows; ?></div>
                    <div class="stat-label">Total Requests</div>
                </div>
            </div>

            <!-- Leave Requests Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i>
                        All Leave Requests
                    </h3>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Worker</th>
                                <th>Site</th>
                                <th>Leave Period</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Applied On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($leave_requests->num_rows > 0): ?>
                                <?php while ($request = $leave_requests->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <strong><?php echo htmlspecialchars($request['user_name']); ?></strong>
                                                <small><?php echo htmlspecialchars($request['mobile']); ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($request['site_name'] ?? 'Not Assigned'); ?></td>
                                        <td>
                                            <div class="date-range">
                                                <div><?php echo date('d M Y', strtotime($request['start_date'])); ?></div>
                                                <div class="text-muted">to</div>
                                                <div><?php echo date('d M Y', strtotime($request['end_date'])); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="reason-text">
                                                <?php echo htmlspecialchars($request['reason']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $status_class = '';
                                            $status_icon = '';
                                            switch($request['status']) {
                                                case 'pending':
                                                    $status_class = 'badge-warning';
                                                    $status_icon = 'fas fa-clock';
                                                    break;
                                                case 'approved':
                                                    $status_class = 'badge-success';
                                                    $status_icon = 'fas fa-check-circle';
                                                    break;
                                                case 'rejected':
                                                    $status_class = 'badge-danger';
                                                    $status_icon = 'fas fa-times-circle';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <i class="<?php echo $status_icon; ?>"></i>
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y H:i', strtotime($request['created_at'])); ?></td>
                                        <td>
                                            <?php if ($request['status'] == 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="leave_id" value="<?php echo $request['id']; ?>">
                                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">Already <?php echo $request['status']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        <i class="fas fa-calendar-times" style="font-size: 3rem; color: #dee2e6;"></i>
                                        <p>No leave requests found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-info small {
            color: #6c757d;
            font-size: 0.8rem;
        }
        
        .date-range {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .reason-text {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .badge-success {
            background: #28a745;
            color: white;
        }
        
        .badge-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 0.8rem;
            margin: 2px;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        .text-center {
            text-align: center;
        }
    </style>
    <?php echo getNotificationScripts(); ?>
</body>
</html> 