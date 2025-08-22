<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/notifications.php';
require_once 'includes/navigation.php';

// Require authentication
require_auth();

// Set timezone to India
date_default_timezone_set('Asia/Kolkata');

$message = '';
$message_type = '';

// Handle leave application
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $start_date = sanitize_input($_POST['start_date']);
    $end_date = sanitize_input($_POST['end_date']);
    $reason = sanitize_input($_POST['reason']);
    
    // Validation
    $today = date('Y-m-d');
    $start_timestamp = strtotime($start_date);
    $end_timestamp = strtotime($end_date);
    $min_start_date = date('Y-m-d', strtotime('+7 days')); // 7 days advance notice
    
    if (empty($start_date) || empty($end_date) || empty($reason)) {
        $message = 'Please fill all required fields.';
        $message_type = 'danger';
    } elseif ($start_date < $today) {
        $message = 'Start date cannot be in the past.';
        $message_type = 'danger';
    } elseif ($start_date < $min_start_date) {
        $message = 'Leave must be applied at least 7 days in advance.';
        $message_type = 'danger';
    } elseif ($end_date < $start_date) {
        $message = 'End date cannot be before start date.';
        $message_type = 'danger';
    } else {
        // Calculate number of days
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        $days = $interval->days + 1;
        
        if ($days > 4) {
            $message = 'Maximum 4 consecutive days allowed for leave.';
            $message_type = 'danger';
        } else {
            // Check if user has enough leaves
            $current_year = date('Y');
            $stmt = $conn->prepare("SELECT COUNT(*) as used_leaves FROM leave_requests WHERE user_id = ? AND YEAR(start_date) = ? AND status = 'approved'");
            $stmt->bind_param("is", $_SESSION['user_id'], $current_year);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $used_leaves = $result['used_leaves'];
            
            if ($used_leaves + $days > 12) {
                $message = "You have already used {$used_leaves} leaves this year. Maximum 12 leaves allowed.";
                $message_type = 'danger';
            } else {
                // Check for overlapping leaves
                $stmt = $conn->prepare("SELECT COUNT(*) as overlapping FROM leave_requests WHERE user_id = ? AND status IN ('pending', 'approved') AND ((start_date BETWEEN ? AND ?) OR (end_date BETWEEN ? AND ?) OR (start_date <= ? AND end_date >= ?))");
                $stmt->bind_param("issssss", $_SESSION['user_id'], $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                if ($result['overlapping'] > 0) {
                    $message = 'You have overlapping leave requests for these dates.';
                    $message_type = 'danger';
                } else {
                    // Insert leave request
                    $stmt = $conn->prepare("INSERT INTO leave_requests (user_id, start_date, end_date, reason) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isss", $_SESSION['user_id'], $start_date, $end_date, $reason);
                    
                    if ($stmt->execute()) {
                        $message = "Leave application submitted successfully! Your request is pending approval.";
                        $message_type = 'success';
                        
                        // Create notification for admin
                        $user_name = $_SESSION['user_name'];
                        $admin_notification_title = "New Leave Request";
                        $admin_notification_message = "$user_name has submitted a leave request for " . date('M j', strtotime($start_date)) . " to " . date('M j', strtotime($end_date)) . ".";
                        
                        // Get all admin users
                        $admin_stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin'");
                        $admin_stmt->execute();
                        $admins = $admin_stmt->get_result();
                        
                        while ($admin = $admins->fetch_assoc()) {
                            createNotification(
                                $admin['id'],
                                $admin_notification_title,
                                $admin_notification_message,
                                'info',
                                'manage_leaves.php'
                            );
                        }
                        
                        // Clear form data
                        $_POST = array();
                    } else {
                        $message = "Error submitting leave request. Please try again.";
                        $message_type = 'danger';
                    }
                }
            }
        }
    }
}

// Get user's leave history
$stmt = $conn->prepare("SELECT * FROM leave_requests WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$leave_history = $stmt->get_result();

// Get current year's leave count
$current_year = date('Y');
$stmt = $conn->prepare("SELECT COUNT(*) as used_leaves FROM leave_requests WHERE user_id = ? AND YEAR(start_date) = ? AND status = 'approved'");
$stmt->bind_param("is", $_SESSION['user_id'], $current_year);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$used_leaves = $result['used_leaves'];
$remaining_leaves = 12 - $used_leaves;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Leave - Sunny Polymers Employee Portal</title>
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
            <?php echo getNavigationMenu('apply_leave'); ?>
        </nav>
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Apply Leave</h1>
                <p class="page-subtitle">Submit leave requests for approval</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-2">
                <!-- Leave Application Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Apply for Leave</h3>
                    </div>
                    
                    <form method="POST" class="form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_date">Start Date *</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" 
                                       value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>" 
                                       min="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                                <small class="text-muted">Minimum 7 days advance notice required</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="end_date">End Date *</label>
                                <input type="date" id="end_date" name="end_date" class="form-control" 
                                       value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>" 
                                       min="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required>
                                <small class="text-muted">Maximum 4 consecutive days</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="reason">Reason *</label>
                            <textarea id="reason" name="reason" class="form-control" rows="4" 
                                      placeholder="Please provide a detailed reason for your leave request" required><?php echo isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : ''; ?></textarea>
                        </div>
                        
                        <div class="leave-info">
                            <div class="info-item">
                                <i class="fas fa-calendar-check"></i>
                                <span>Used Leaves: <?php echo $used_leaves; ?>/12</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-calendar-times"></i>
                                <span>Remaining: <?php echo $remaining_leaves; ?> leaves</span>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                            Submit Leave Request
                        </button>
                    </form>
                </div>

                <!-- Leave History -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Leave History</h3>
                    </div>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date Range</th>
                                    <th>Days</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Applied On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($leave = $leave_history->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php echo date('d M Y', strtotime($leave['start_date'])); ?> - 
                                        <?php echo date('d M Y', strtotime($leave['end_date'])); ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $start = new DateTime($leave['start_date']);
                                        $end = new DateTime($leave['end_date']);
                                        $interval = $start->diff($end);
                                        echo $interval->days + 1;
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($leave['reason']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $leave['status'] == 'approved' ? 'success' : 
                                                ($leave['status'] == 'rejected' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($leave['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($leave['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .leave-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }
        
        .info-item:last-child {
            margin-bottom: 0;
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