<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/notifications.php';
require_once 'includes/navigation.php';

// Require authentication
require_auth();

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Get user's advances
$stmt = $conn->prepare("
    SELECT * FROM advances 
    WHERE user_id = ? 
    ORDER BY date_given DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$advances = $stmt->get_result();

// Get user details
$stmt = $conn->prepare("SELECT u.*, s.name as site_name FROM users u LEFT JOIN sites s ON u.site_id = s.id WHERE u.id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Calculate advance statistics
$total_advances = 0;
$total_repaid = 0;
$pending_amount = 0;

$advances->data_seek(0);
while ($advance = $advances->fetch_assoc()) {
    $total_advances += $advance['amount'];
    if ($advance['is_repaid']) {
        $total_repaid += $advance['repaid_amount'];
    } else {
        $pending_amount += $advance['amount'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Advances - Sunny Polymers Employee Portal</title>
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
            <?php echo getNavigationMenu('view_advances'); ?>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">My Advances</h1>
                <p class="page-subtitle">View your advance history and repayment status</p>
            </div>

            <!-- Advance Summary -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i>
                        Advance Summary
                    </h3>
                </div>
                
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-icon">
                            <i class="fas fa-user" style="color: #667eea;"></i>
                        </div>
                        <div class="summary-content">
                            <h4>Employee Details</h4>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                            <p><strong>Mobile:</strong> <?php echo htmlspecialchars($user['mobile']); ?></p>
                            <p><strong>Site:</strong> <?php echo htmlspecialchars($user['site_name'] ?? 'Not Assigned'); ?></p>
                        </div>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-icon">
                            <i class="fas fa-money-bill-wave" style="color: #28a745;"></i>
                        </div>
                        <div class="summary-content">
                            <h4>Advance Statistics</h4>
                            <p><strong>Total Advances:</strong> ₹<?php echo number_format($total_advances, 2); ?></p>
                            <p><strong>Total Repaid:</strong> ₹<?php echo number_format($total_repaid, 2); ?></p>
                            <p><strong>Pending Amount:</strong> ₹<?php echo number_format($pending_amount, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advances List -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i>
                        Advance History
                    </h3>
                </div>
                
                <div class="advances-list">
                    <?php if ($advances->num_rows > 0): ?>
                        <?php while ($advance = $advances->fetch_assoc()): ?>
                            <div class="advance-card">
                                <div class="advance-header">
                                    <div class="advance-title">
                                        <h4>Advance - <?php echo date('d M Y', strtotime($advance['date_given'])); ?></h4>
                                        <p class="advance-subtitle">Reason: <?php echo htmlspecialchars($advance['reason']); ?></p>
                                    </div>
                                    <div class="advance-amount">
                                        <span class="amount">₹<?php echo number_format($advance['amount'], 2); ?></span>
                                        <?php if ($advance['is_repaid']): ?>
                                            <span class="status repaid">Repaid</span>
                                        <?php else: ?>
                                            <span class="status pending">Pending</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="advance-content">
                                    <div class="advance-details">
                                        <div class="detail-row">
                                            <div class="detail-group">
                                                <h5>Advance Details</h5>
                                                <div class="detail-item">
                                                    <span>Amount Given:</span>
                                                    <span>₹<?php echo number_format($advance['amount'], 2); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span>Date Given:</span>
                                                    <span><?php echo date('d M Y', strtotime($advance['date_given'])); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span>Reason:</span>
                                                    <span><?php echo htmlspecialchars($advance['reason']); ?></span>
                                                </div>
                                            </div>
                                            
                                            <div class="detail-group">
                                                <h5>Repayment Status</h5>
                                                <div class="detail-item">
                                                    <span>Status:</span>
                                                    <span>
                                                        <?php if ($advance['is_repaid']): ?>
                                                            <span class="badge badge-success">Repaid</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-warning">Pending</span>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                                <div class="detail-item">
                                                    <span>Repaid Amount:</span>
                                                    <span>₹<?php echo number_format($advance['repaid_amount'], 2); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span>Remaining:</span>
                                                    <span>₹<?php echo number_format($advance['amount'] - $advance['repaid_amount'], 2); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if (!$advance['is_repaid']): ?>
                                            <div class="pending-notice">
                                                <i class="fas fa-info-circle"></i>
                                                <strong>Note:</strong> This advance will be deducted from your salary when the payslip is generated.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-hand-holding-usd" style="font-size: 3rem; color: #dee2e6;"></i>
                            <p>No advances found</p>
                            <small>Advances will appear here when given by admin</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .summary-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .summary-icon {
            font-size: 2rem;
            margin-top: 5px;
        }
        
        .summary-content h4 {
            margin: 0 0 10px 0;
            color: #212529;
        }
        
        .summary-content p {
            margin: 5px 0;
            color: #6c757d;
        }
        
        .advances-list {
            padding: 20px;
        }
        
        .advance-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 20px;
            background: white;
        }
        
        .advance-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
        }
        
        .advance-title h4 {
            margin: 0;
            color: #212529;
        }
        
        .advance-subtitle {
            margin: 5px 0 0 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .advance-amount {
            text-align: right;
        }
        
        .advance-amount .amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
            display: block;
        }
        
        .advance-amount .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status.repaid {
            background: #28a745;
            color: white;
        }
        
        .status.pending {
            background: #ffc107;
            color: #212529;
        }
        
        .advance-content {
            padding: 20px;
        }
        
        .advance-details {
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .detail-group h5 {
            margin: 0 0 15px 0;
            color: #212529;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            padding: 5px 0;
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
        
        .pending-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .detail-row {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .advance-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
    <?php echo getNotificationScripts(); ?>
</body>
</html> 