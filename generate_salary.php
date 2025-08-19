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

// Handle salary generation and deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['generate_salary'])) {
        $user_id = $_POST['user_id'];
        $month = $_POST['month'];
        $year = $_POST['year'];
        
        // Get user details
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            // Calculate attendance
            $start_date = $year . '-' . $month . '-01';
            $end_date = date('Y-m-t', strtotime($start_date));
            
            // Get attendance count
            $stmt = $conn->prepare("SELECT COUNT(*) as present_days FROM attendance WHERE user_id = ? AND date BETWEEN ? AND ? AND status = 'present'");
            $stmt->bind_param("iss", $user_id, $start_date, $end_date);
            $stmt->execute();
            $attendance = $stmt->get_result()->fetch_assoc();
            $present_days = $attendance['present_days'];
            
            // Get total working days in month
            $total_days = date('t', strtotime($start_date));
            
            // Get approved leaves
            $stmt = $conn->prepare("SELECT COUNT(*) as leave_days FROM leave_requests WHERE user_id = ? AND status = 'approved' AND start_date BETWEEN ? AND ?");
            $stmt->bind_param("iss", $user_id, $start_date, $end_date);
            $stmt->execute();
            $leaves = $stmt->get_result()->fetch_assoc();
            $leave_days = $leaves['leave_days'];
            
            // Get advances
            $stmt = $conn->prepare("SELECT SUM(amount) as total_advance FROM advances WHERE user_id = ? AND date_given BETWEEN ? AND ? AND is_repaid = 0");
            $stmt->bind_param("iss", $user_id, $start_date, $end_date);
            $stmt->execute();
            $advances = $stmt->get_result()->fetch_assoc();
            $total_advance = $advances['total_advance'] ?? 0;
            
            // Calculate salary
            $basic_salary = $user['salary'];
            $per_day_salary = $basic_salary / $total_days;
            $earned_salary = $per_day_salary * ($present_days + $leave_days);
            $deductions = $total_advance;
            $net_salary = $earned_salary - $deductions; // Allow negative values for accurate accounting
            
            // Check if payslip already exists
            $stmt = $conn->prepare("SELECT id FROM payslips WHERE user_id = ? AND month = ? AND year = ?");
            $stmt->bind_param("iss", $user_id, $month, $year);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows == 0) {
                // Insert payslip
                $stmt = $conn->prepare("INSERT INTO payslips (user_id, month, year, basic_salary, present_days, leave_days, total_days, earned_salary, advances, deductions, net_salary, generated_by, generated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("issiiiiidddi", $user_id, $month, $year, $basic_salary, $present_days, $leave_days, $total_days, $earned_salary, $total_advance, $deductions, $net_salary, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    // Create notification for the user
                    $notification_title = "Salary Slip Generated";
                    $notification_message = "Your salary slip for " . date('F Y', strtotime($start_date)) . " has been generated. Net salary: ₹" . number_format($net_salary, 2);
                    
                    createNotification(
                        $user_id,
                        $notification_title,
                        $notification_message,
                        'success',
                        'view_payslip.php'
                    );
                    
                    // Redirect to prevent form resubmission
                    header("Location: generate_salary.php?success=salary_generated&user=" . urlencode($user['name']) . "&month=" . urlencode(date('F Y', strtotime($start_date))));
                    exit();
                } else {
                    $error_message = "Error generating salary slip.";
                }
            } else {
                $error_message = "Salary slip already exists for this month.";
            }
        }
    }
    
    // Handle payslip deletion
    if (isset($_POST['delete_payslip'])) {
        $payslip_id = $_POST['payslip_id'];
        
        // Get payslip details for confirmation message
        $stmt = $conn->prepare("SELECT p.*, u.name as user_name FROM payslips p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
        $stmt->bind_param("i", $payslip_id);
        $stmt->execute();
        $payslip = $stmt->get_result()->fetch_assoc();
        
        if ($payslip) {
            $stmt = $conn->prepare("DELETE FROM payslips WHERE id = ?");
            $stmt->bind_param("i", $payslip_id);
            
            if ($stmt->execute()) {
                // Redirect to prevent form resubmission
                header("Location: generate_salary.php?success=payslip_deleted&user=" . urlencode($payslip['user_name']) . "&month=" . urlencode(date('F Y', mktime(0, 0, 0, $payslip['month'], 1, $payslip['year']))));
                exit();
            } else {
                $error_message = "Error deleting payslip.";
            }
        } else {
            $error_message = "Payslip not found.";
        }
    }
}

// Handle success messages from redirects
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'salary_generated') {
        $user = isset($_GET['user']) ? $_GET['user'] : '';
        $month = isset($_GET['month']) ? $_GET['month'] : '';
        $success_message = "Salary slip generated successfully for " . $user . " - " . $month;
    } elseif ($_GET['success'] == 'payslip_deleted') {
        $user = isset($_GET['user']) ? $_GET['user'] : '';
        $month = isset($_GET['month']) ? $_GET['month'] : '';
        $success_message = "Payslip deleted successfully for " . $user . " - " . $month;
    }
}

// Get all workers
$stmt = $conn->prepare("SELECT u.*, s.name as site_name FROM users u LEFT JOIN sites s ON u.site_id = s.id WHERE u.role = 'worker' ORDER BY u.name");
$stmt->execute();
$workers = $stmt->get_result();

// Get recent payslips
$stmt = $conn->prepare("
    SELECT p.*, u.name as user_name, u.mobile 
    FROM payslips p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.generated_at DESC 
    LIMIT 10
");
$stmt->execute();
$recent_payslips = $stmt->get_result();

// Get all payslips for the table
$stmt = $conn->prepare("
    SELECT p.*, u.name as user_name, u.mobile, u.salary,
           g.name as generated_by_name
    FROM payslips p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN users g ON p.generated_by = g.id
    ORDER BY p.generated_at DESC
");
$stmt->execute();
$all_payslips = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Salary - Sunny Polymers Employee Portal</title>
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
                
                <?php echo getNavigationMenu('generate_salary'); ?>
                
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
                <h1 class="page-title">Generate Salary Slips</h1>
                <p class="page-subtitle">Generate monthly salary slips for workers</p>
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

            <div class="grid grid-2">
                <!-- Generate Salary Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calculator"></i>
                            Generate New Salary Slip
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
                                            <?php if ($worker['site_name']): ?>
                                                - <?php echo htmlspecialchars($worker['site_name']); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="month">Month</label>
                                    <select name="month" id="month" required class="form-control">
                                        <option value="">Select Month</option>
                                        <option value="01">January</option>
                                        <option value="02">February</option>
                                        <option value="03">March</option>
                                        <option value="04">April</option>
                                        <option value="05">May</option>
                                        <option value="06">June</option>
                                        <option value="07">July</option>
                                        <option value="08">August</option>
                                        <option value="09">September</option>
                                        <option value="10">October</option>
                                        <option value="11">November</option>
                                        <option value="12">December</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="year">Year</label>
                                    <select name="year" id="year" required class="form-control">
                                        <option value="">Select Year</option>
                                        <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                                            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="submit" name="generate_salary" class="btn btn-primary btn-block">
                                <i class="fas fa-calculator"></i>
                                Generate Salary Slip
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Recent Payslips -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history"></i>
                            Recent Salary Slips
                        </h3>
                    </div>
                    
                    <div class="recent-payslips">
                        <?php if ($recent_payslips->num_rows > 0): ?>
                            <?php while ($payslip = $recent_payslips->fetch_assoc()): ?>
                                <div class="payslip-item">
                                    <div class="payslip-header">
                                        <div class="worker-info">
                                            <strong><?php echo htmlspecialchars($payslip['user_name']); ?></strong>
                                            <small><?php echo htmlspecialchars($payslip['mobile']); ?></small>
                                        </div>
                                        <div class="payslip-date">
                                            <?php echo date('F Y', mktime(0, 0, 0, $payslip['month'], 1, $payslip['year'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="payslip-details">
                                        <div class="detail-item">
                                            <span class="label">Net Salary:</span>
                                            <span class="value">₹<?php echo number_format($payslip['net_salary'], 2); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="label">Present Days:</span>
                                            <span class="value"><?php echo $payslip['present_days']; ?>/<?php echo $payslip['total_days']; ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="label">Generated:</span>
                                            <span class="value"><?php echo date('d M Y', strtotime($payslip['generated_at'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-file-invoice" style="font-size: 3rem; color: #dee2e6;"></i>
                                <p>No salary slips generated yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- All Payslips Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i>
                        All Salary Slips
                    </h3>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Worker</th>
                                <th>Month/Year</th>
                                <th>Basic Salary</th>
                                <th>Present Days</th>
                                <th>Leave Days</th>
                                <th>Earned Salary</th>
                                <th>Advances</th>
                                <th>Net Salary</th>
                                <th>Generated By</th>
                                <th>Generated On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($all_payslips->num_rows > 0): ?>
                                <?php while ($payslip = $all_payslips->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <strong><?php echo htmlspecialchars($payslip['user_name']); ?></strong>
                                                <small><?php echo htmlspecialchars($payslip['mobile']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo date('F Y', mktime(0, 0, 0, $payslip['month'], 1, $payslip['year'])); ?>
                                            </span>
                                        </td>
                                        <td>₹<?php echo number_format($payslip['basic_salary'], 2); ?></td>
                                        <td>
                                            <span class="badge badge-success">
                                                <?php echo $payslip['present_days']; ?>/<?php echo $payslip['total_days']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($payslip['leave_days'] > 0): ?>
                                                <span class="badge badge-warning"><?php echo $payslip['leave_days']; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>₹<?php echo number_format($payslip['earned_salary'], 2); ?></td>
                                        <td>
                                            <?php if ($payslip['advances'] > 0): ?>
                                                <span class="badge badge-danger">₹<?php echo number_format($payslip['advances'], 2); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">₹0.00</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong>₹<?php echo number_format($payslip['net_salary'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($payslip['generated_by_name'] ?? 'System'); ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo date('d M Y', strtotime($payslip['generated_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <a href="generate_pdf_payslip.php?payslip_id=<?php echo $payslip['id']; ?>&action=view" target="_blank" class="btn btn-primary btn-sm" style="text-decoration: none;">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="generate_pdf_payslip.php?payslip_id=<?php echo $payslip['id']; ?>&action=download" class="btn btn-success btn-sm" style="text-decoration: none;">
                                                    <i class="fas fa-download"></i> PDF
                                                </a>
                                                <form method="POST" action="" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to delete this payslip for <?php echo htmlspecialchars($payslip['user_name']); ?> - <?php echo date('F Y', mktime(0, 0, 0, $payslip['month'], 1, $payslip['year'])); ?>? This action cannot be undone.')">
                                                    <input type="hidden" name="payslip_id" value="<?php echo $payslip['id']; ?>">
                                                    <button type="submit" name="delete_payslip" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="text-center text-muted">
                                        <i class="fas fa-file-invoice" style="font-size: 3rem; color: #dee2e6;"></i>
                                        <p>No salary slips found</p>
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
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .recent-payslips {
            padding: 10px 0;
        }
        
        .payslip-item {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .payslip-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .payslip-header {
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
        
        .payslip-date {
            font-weight: bold;
            color: #007bff;
        }
        
        .payslip-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
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
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-info {
            background: #17a2b8;
            color: white;
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
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 0.8rem;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-info small {
            color: #6c757d;
            font-size: 0.8rem;
        }
    </style>
    <?php echo getNotificationScripts(); ?>
</body>
</html> 