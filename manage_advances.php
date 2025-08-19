<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/notifications.php';
require_once 'includes/navigation.php';

// Require admin authentication
require_admin();

// Set timezone
date_default_timezone_set('Asia/Kolkata');

$success_message = '';
$error_message = '';

// Handle advance operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['give_advance'])) {
        $user_id = $_POST['user_id'];
        $amount = $_POST['amount'];
        $reason = $_POST['reason'];
        $date_given = $_POST['date_given'];
        
        if ($amount > 0) {
            $stmt = $conn->prepare("INSERT INTO advances (user_id, amount, reason, date_given) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("idss", $user_id, $amount, $reason, $date_given);
            
            if ($stmt->execute()) {
                // Create notification for the user
                $notification_title = "Advance Given";
                $notification_message = "You have been given an advance of ₹" . number_format($amount, 2) . " for: " . $reason;
                
                createNotification(
                    $user_id,
                    $notification_title,
                    $notification_message,
                    'info',
                    'view_advances.php'
                );
                
                // Redirect to prevent form resubmission
                header("Location: manage_advances.php?success=advance_added&amount=" . urlencode($amount));
                exit();
            } else {
                $error_message = "Error giving advance.";
            }
        } else {
            $error_message = "Amount must be greater than 0.";
        }
    }
    
    // Handle repayment marking
    if (isset($_POST['mark_repaid'])) {
        $advance_id = $_POST['advance_id'];
        $repaid_amount = $_POST['repaid_amount'];
        
        $stmt = $conn->prepare("UPDATE advances SET is_repaid = 1, repaid_amount = ? WHERE id = ?");
        $stmt->bind_param("di", $repaid_amount, $advance_id);
        
        if ($stmt->execute()) {
            // Redirect to prevent form resubmission
            header("Location: manage_advances.php?success=advance_repaid");
            exit();
        } else {
            $error_message = "Error updating advance.";
        }
    }
}

// Handle success messages from redirects
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'advance_added') {
        $amount = isset($_GET['amount']) ? $_GET['amount'] : '';
        $success_message = "Advance of ₹" . number_format($amount, 2) . " given successfully!";
    } elseif ($_GET['success'] == 'advance_repaid') {
        $success_message = "Advance marked as repaid successfully!";
    }
}

// Get all workers
$stmt = $conn->prepare("SELECT u.*, s.name as site_name FROM users u LEFT JOIN sites s ON u.site_id = s.id WHERE u.role = 'worker' ORDER BY u.name");
$stmt->execute();
$workers = $stmt->get_result();

// Get all advances with worker details
$stmt = $conn->prepare("
    SELECT a.*, u.name as user_name, u.mobile, u.salary, s.name as site_name
    FROM advances a 
    JOIN users u ON a.user_id = u.id 
    LEFT JOIN sites s ON u.site_id = s.id
    ORDER BY a.date_given DESC
");
$stmt->execute();
$advances = $stmt->get_result();

// Get statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_advances,
        SUM(amount) as total_given,
        SUM(CASE WHEN is_repaid = 1 THEN repaid_amount ELSE 0 END) as total_repaid,
        SUM(CASE WHEN is_repaid = 0 THEN amount ELSE 0 END) as pending_amount
    FROM advances
");
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Advances - Sunny Polymers Employee Portal</title>
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
                
                <?php echo getNavigationMenu('manage_advances'); ?>
                
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
                <h1 class="page-title">Manage Worker Advances</h1>
                <p class="page-subtitle">Give advances and track repayments</p>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hand-holding-usd" style="color: #667eea;"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['total_advances']; ?></div>
                    <div class="stat-label">Total Advances</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave" style="color: #28a745;"></i>
                    </div>
                    <div class="stat-number">₹<?php echo number_format($stats['total_given'], 2); ?></div>
                    <div class="stat-label">Total Given</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle" style="color: #ffc107;"></i>
                    </div>
                    <div class="stat-number">₹<?php echo number_format($stats['total_repaid'], 2); ?></div>
                    <div class="stat-label">Total Repaid</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock" style="color: #dc3545;"></i>
                    </div>
                    <div class="stat-number">₹<?php echo number_format($stats['pending_amount'], 2); ?></div>
                    <div class="stat-label">Pending Amount</div>
                </div>
            </div>

            <div class="grid grid-2">
                <!-- Give Advance Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-plus-circle"></i>
                            Give New Advance
                        </h3>
                    </div>
                    
                    <div class="card-body">
                        <form method="POST" class="form">
                            <div class="form-group">
                                <label for="user_id">Select Worker</label>
                                <select name="user_id" id="user_id" required class="form-control">
                                    <option value="">Choose a worker...</option>
                                    <?php while ($worker = $workers->fetch_assoc()): ?>
                                        <option value="<?php echo $worker['id']; ?>">
                                            <?php echo htmlspecialchars($worker['name']); ?> 
                                            (<?php echo htmlspecialchars($worker['mobile']); ?>)
                                            - ₹<?php echo number_format($worker['salary'], 2); ?>
                                            <?php if ($worker['site_name']): ?>
                                                - <?php echo htmlspecialchars($worker['site_name']); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="amount">Amount (₹)</label>
                                    <input type="number" name="amount" id="amount" step="0.01" min="0" required class="form-control" placeholder="Enter amount">
                                </div>
                                
                                <div class="form-group">
                                    <label for="date_given">Date Given</label>
                                    <input type="date" name="date_given" id="date_given" required class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="reason">Reason</label>
                                <textarea name="reason" id="reason" required class="form-control" rows="3" placeholder="Enter reason for advance"></textarea>
                            </div>
                            
                            <button type="submit" name="give_advance" class="btn btn-primary btn-block">
                                <i class="fas fa-hand-holding-usd"></i>
                                Give Advance
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Recent Advances -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history"></i>
                            Recent Advances
                        </h3>
                    </div>
                    
                    <div class="recent-advances">
                        <?php 
                        $advances->data_seek(0);
                        $count = 0;
                        while ($advance = $advances->fetch_assoc()): 
                            if ($count >= 5) break;
                            $count++;
                        ?>
                            <div class="advance-item">
                                <div class="advance-header">
                                    <div class="worker-info">
                                        <strong><?php echo htmlspecialchars($advance['user_name']); ?></strong>
                                        <small><?php echo htmlspecialchars($advance['mobile']); ?></small>
                                    </div>
                                    <div class="advance-amount">
                                        ₹<?php echo number_format($advance['amount'], 2); ?>
                                    </div>
                                </div>
                                
                                <div class="advance-details">
                                    <div class="detail-item">
                                        <span class="label">Reason:</span>
                                        <span class="value"><?php echo htmlspecialchars($advance['reason']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Date:</span>
                                        <span class="value"><?php echo date('d M Y', strtotime($advance['date_given'])); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Status:</span>
                                        <span class="value">
                                            <?php if ($advance['is_repaid']): ?>
                                                <span class="badge badge-success">Repaid</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Pending</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        
                        <?php if ($count == 0): ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-hand-holding-usd" style="font-size: 3rem; color: #dee2e6;"></i>
                                <p>No advances given yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- All Advances Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i>
                        All Advances
                    </h3>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Worker</th>
                                <th>Amount</th>
                                <th>Reason</th>
                                <th>Date Given</th>
                                <th>Status</th>
                                <th>Repaid Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $advances->data_seek(0);
                            if ($advances->num_rows > 0): 
                            ?>
                                <?php while ($advance = $advances->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <strong><?php echo htmlspecialchars($advance['user_name']); ?></strong>
                                                <small><?php echo htmlspecialchars($advance['mobile']); ?></small>
                                            </div>
                                        </td>
                                        <td>₹<?php echo number_format($advance['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($advance['reason']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($advance['date_given'])); ?></td>
                                        <td>
                                            <?php if ($advance['is_repaid']): ?>
                                                <span class="badge badge-success">Repaid</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>₹<?php echo number_format($advance['repaid_amount'], 2); ?></td>
                                        <td>
                                            <?php if (!$advance['is_repaid']): ?>
                                                <button onclick="markRepaid(<?php echo $advance['id']; ?>, <?php echo $advance['amount']; ?>)" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i> Mark Repaid
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">Already repaid</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        No advances found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Repayment Modal -->
    <div id="repaymentModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Mark Advance as Repaid</h3>
                <span class="close">&times;</span>
            </div>
            <form method="POST" id="repaymentForm">
                <div class="modal-body">
                    <input type="hidden" name="advance_id" id="advance_id">
                    <div class="form-group">
                        <label for="repaid_amount">Repaid Amount (₹)</label>
                        <input type="number" name="repaid_amount" id="repaid_amount" step="0.01" min="0" required class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="mark_repaid" class="btn btn-success">Mark as Repaid</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function markRepaid(advanceId, amount) {
            document.getElementById('advance_id').value = advanceId;
            document.getElementById('repaid_amount').value = amount;
            document.getElementById('repaymentModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('repaymentModal').style.display = 'none';
        }

        // Close modal when clicking on X or outside
        document.querySelector('.close').onclick = closeModal;
        window.onclick = function(event) {
            const modal = document.getElementById('repaymentModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>

    <style>
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .recent-advances {
            padding: 10px 0;
        }
        
        .advance-item {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .advance-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .advance-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .worker-info {
            display: flex;
            flex-direction: column;
        }
        
        .worker-info small {
            color: #6c757d;
            font-size: 0.8rem;
        }
        
        .advance-amount {
            font-weight: bold;
            color: #28a745;
            font-size: 1.1rem;
        }
        
        .advance-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-item .label {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .detail-item .value {
            font-weight: bold;
            color: #212529;
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
        
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 0;
            border-radius: 8px;
            width: 400px;
            max-width: 90%;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: #6c757d;
        }
        
        .close:hover {
            color: #000;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: #6c757d;
        }
    </style>
    <?php echo getNotificationScripts(); ?>
</body>
</html> 