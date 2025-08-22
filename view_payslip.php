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

// Check if this is a print view request
if (isset($_GET['payslip_id']) && isset($_GET['view']) && $_GET['view'] === 'print') {
    $payslip_id = $_GET['payslip_id'];
    
    // Get specific payslip for print view
    $stmt = $conn->prepare("SELECT p.*, u.name as user_name, u.mobile, u.state, u.bank_name, u.account_number, u.ifsc_code, u.branch_name, s.name as site_name, g.name as generated_by_name FROM payslips p JOIN users u ON p.user_id = u.id LEFT JOIN sites s ON u.site_id = s.id LEFT JOIN users g ON p.generated_by = g.id WHERE p.id = ? AND p.user_id = ?");
    $stmt->bind_param("ii", $payslip_id, $_SESSION['user_id']);
    $stmt->execute();
    $payslip = $stmt->get_result()->fetch_assoc();
    
    if (!$payslip) {
        die('Payslip not found or access denied.');
    }
    
    // Display print view
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Salary Slip - Print View</title>
        <style>
            body { font-family: 'Inter', Arial, sans-serif; margin: 0; background: #eaf6ff !important; color: #222 !important; }
            .payslip-main, .payslip-card { max-width: 800px; margin: 30px auto; background: #fff !important; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 40px; color: #222 !important; }
            .company-header { text-align: center; margin-bottom: 30px; color: #2563eb !important; }
            .company-header h2 { margin: 0; font-size: 2rem; color: #2563eb !important; }
            .company-header .org-info { color: #555 !important; font-size: 1rem; margin-top: 5px; }
            .divider { border-top: 2px solid #2563eb !important; margin: 25px 0; }
            .details-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
            .details-table td { padding: 4px 8px; font-size: 1rem; color: #222 !important; background: #fff !important; }
            .details-table .label { color: #2563eb !important; font-weight: 700 !important; width: 180px; }
            .details-table .value { color: #222 !important; font-weight: 700 !important; }
            .section-title { font-size: 1.1rem; color: #2563eb !important; font-weight: 700 !important; margin: 20px 0 10px 0; }
            .pay-table, .earnings-deductions-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .pay-table th, .pay-table td, .earnings-deductions-table th, .earnings-deductions-table td { border: 1px solid #e2e8f0 !important; padding: 8px 10px; text-align: left; font-size: 1rem; color: #222 !important; background: #fff !important; }
            .pay-table th, .earnings-deductions-table th { background: #f1f5f9 !important; color: #2563eb !important; font-weight: 700 !important; }
            .pay-table .right, .earnings-deductions-table .right { text-align: right; }
            .net-pay { background: #22c55e !important; color: #fff !important; font-size: 1.3rem; font-weight: bold !important; text-align: center; padding: 18px; border-radius: 8px; margin-top: 25px; }
            .footer { text-align: right; color: #888 !important; font-size: 0.95rem; margin-top: 30px; }
            .payslip-actions { display: flex !important; gap: 10px; margin-bottom: 10px; }
            .btn-download { background: #2563eb !important; color: #fff !important; border: none !important; border-radius: 5px !important; padding: 8px 18px !important; font-size: 1rem !important; font-weight: 600 !important; cursor: pointer !important; transition: background 0.2s !important; display: inline-block !important; }
            .btn-download:hover { background: #1d4ed8 !important; }
            @media print { body { background: #fff !important; } .payslip-main, .payslip-card { box-shadow: none !important; padding: 20px !important; } .payslip-actions { display: none !important; } }
            @media (max-width: 900px) { .payslip-main, .payslip-card { padding: 20px; } }
            @media (max-width: 600px) { .payslip-main, .payslip-card { padding: 8px; } .details-table td { font-size: 0.95rem; } }
        </style>
    </head>
    <body>
        <div class="payslip-main">
            <div class="company-header">
                <h2>Sunny Polymers Pvt. Ltd.</h2>
                <div class="org-info">Organization number: 1234567890<br>Address: 123, Industrial Area, City, State, 123456</div>
            </div>
            <div class="divider"></div>
            <table class="details-table" style="width: 100%; margin: 10px 0 0 0;">
                <tr><td class="label" style="color:#2563eb!important;font-weight:700!important;">Employee Name:</td><td class="value" style="color:#222!important;font-weight:700!important;"><?php echo htmlspecialchars($payslip['user_name']) ?></td><td class="label" style="color:#2563eb!important;font-weight:700!important;">Pay Period:</td><td class="value" style="color:#222!important;font-weight:700!important;"><?php echo date('F Y', mktime(0,0,0,$payslip['month'],1,$payslip['year'])) ?></td></tr>
                <tr><td class="label" style="color:#2563eb!important;font-weight:700!important;">Mobile:</td><td class="value" style="color:#222!important;font-weight:700!important;"><?php echo htmlspecialchars($payslip['mobile']) ?></td><td class="label" style="color:#2563eb!important;font-weight:700!important;">Pay Date:</td><td class="value" style="color:#222!important;font-weight:700!important;"><?php echo date('d M Y', strtotime($payslip['generated_at'])) ?></td></tr>
                <tr><td class="label" style="color:#2563eb!important;font-weight:700!important;">Site:</td><td class="value" style="color:#222!important;font-weight:700!important;"><?php echo htmlspecialchars($payslip['site_name'] ?? 'Not Assigned') ?></td><td class="label" style="color:#2563eb!important;font-weight:700!important;">State:</td><td class="value" style="color:#222!important;font-weight:700!important;"><?php echo htmlspecialchars($payslip['state']) ?></td></tr>
                <tr><td class="label" style="color:#2563eb!important;font-weight:700!important;">Bank Name:</td><td class="value" style="color:#222!important;font-weight:700!important;"><?php echo htmlspecialchars(isset($payslip['bank_name']) ? $payslip['bank_name'] : '') ?></td><td class="label" style="color:#2563eb!important;font-weight:700!important;">Account Number:</td><td class="value" style="color:#222!important;font-weight:700!important;"><?php echo htmlspecialchars(isset($payslip['account_number']) ? $payslip['account_number'] : '') ?></td></tr>
            </table>
            <div class="section-title">Earnings & Deductions</div>
            <table class="earnings-deductions-table">
                <tr>
                    <th>Earnings</th><th class="right">Amount (₹)</th><th>Deductions</th><th class="right">Amount (₹)</th>
                </tr>
                <tr>
                    <td>Basic Salary</td><td class="right">₹<?php echo number_format($payslip['basic_salary'], 2); ?></td>
                    <td>Advances</td><td class="right">₹<?php echo number_format($payslip['advances'], 2); ?></td>
                </tr>
                <tr>
                    <td>Per Day Salary</td><td class="right">₹<?php echo number_format($payslip['basic_salary'] / $payslip['total_days'], 2); ?></td>
                    <td>Other Deductions</td><td class="right">₹<?php echo number_format($payslip['deductions'] - $payslip['advances'], 2); ?></td>
                </tr>
                <tr>
                    <td>Present Days</td><td class="right"><?php echo $payslip['present_days']; ?> days</td>
                    <td></td><td></td>
                </tr>
                <tr>
                    <td>Leave Days</td><td class="right"><?php echo $payslip['leave_days']; ?> days</td>
                    <td></td><td></td>
                </tr>
                <tr style="background:#f1f5f9;">
                    <td><b>Total Earned</b></td><td class="right"><b>₹<?php echo number_format($payslip['earned_salary'], 2); ?></b></td>
                    <td><b>Total Deductions</b></td><td class="right"><b>₹<?php echo number_format($payslip['deductions'], 2); ?></b></td>
                </tr>
            </table>
            <div class="net-pay">Net Salary: ₹<?php echo number_format($payslip['net_salary'], 2); ?></div>
            <div class="footer">Generated by: <?php echo htmlspecialchars($payslip['generated_by_name'] ?? 'Admin'); ?> | Generated on: <?php echo date('d M Y H:i', strtotime($payslip['generated_at'])); ?></div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Get user's payslips
$stmt = $conn->prepare("
    SELECT p.*, u.name as user_name, u.mobile, u.state, s.name as site_name, g.name as generated_by_name
    FROM payslips p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN sites s ON u.site_id = s.id
    LEFT JOIN users g ON p.generated_by = g.id
    WHERE p.user_id = ? 
    ORDER BY p.year DESC, p.month DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$payslips = $stmt->get_result();

// Get user details
$stmt = $conn->prepare("SELECT u.*, s.name as site_name FROM users u LEFT JOIN sites s ON u.site_id = s.id WHERE u.id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get current month attendance
$current_month = date('m');
$current_year = date('Y');
$start_date = $current_year . '-' . $current_month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

$stmt = $conn->prepare("SELECT COUNT(*) as present_days FROM attendance WHERE user_id = ? AND date BETWEEN ? AND ? AND status = 'present'");
$stmt->bind_param("iss", $_SESSION['user_id'], $start_date, $end_date);
$stmt->execute();
$current_attendance = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Payslips - Sunny Polymers Employee Portal</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php echo getNotificationStyles(); ?>
    
    <script>
        // Define functions immediately when page loads
        function printPayslip(payslipId) {
            console.log('printPayslip called with ID:', payslipId);
            const payslipElement = document.getElementById('payslip-' + payslipId);
            console.log('Payslip element found:', payslipElement);
            
            if (!payslipElement) {
                alert('Payslip element not found!');
                return;
            }
            
            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                alert('Pop-up blocked! Please allow pop-ups for this site.');
                return;
            }
            
            printWindow.document.write(`
                <html>
                <head>
                    <title>Salary Slip</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .payslip-header { text-align: center; margin-bottom: 20px; }
                        .payslip-content { border: 1px solid #ccc; padding: 20px; }
                        .detail-row { display: flex; justify-content: space-between; }
                        .detail-group { flex: 1; margin: 0 10px; }
                        .detail-item { display: flex; justify-content: space-between; margin: 5px 0; }
                        .total { font-weight: bold; border-top: 1px solid #ccc; padding-top: 5px; }
                        .net-salary { text-align: center; margin-top: 20px; font-size: 1.2em; }
                        @media print { body { margin: 0; } }
                    </style>
                </head>
                <body>
                    ${payslipElement.innerHTML}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        function downloadPayslip(payslipId) {
            console.log('downloadPayslip called with ID:', payslipId);
            const payslipElement = document.getElementById('payslip-' + payslipId);
            console.log('Payslip element found:', payslipElement);
            
            if (!payslipElement) {
                alert('Payslip element not found!');
                return;
            }
            
            const newWindow = window.open('view_payslip.php?payslip_id=' + payslipId + '&view=print', '_blank');
            if (!newWindow) {
                alert('Pop-up blocked! Please allow pop-ups for this site.');
                return;
            }
            
            newWindow.document.write(`
                <html>
                <head>
                    <title>Salary Slip</title>
                    <style>
                        body { 
                            font-family: Arial, sans-serif; 
                            margin: 20px; 
                            background: #f8f9fa;
                            color: #333;
                        }
                        .payslip-container {
                            background: white;
                            padding: 30px;
                            border-radius: 8px;
                            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                            max-width: 800px;
                            margin: 0 auto;
                        }
                        .payslip-header { 
                            text-align: center; 
                            margin-bottom: 30px;
                            border-bottom: 2px solid #007bff;
                            padding-bottom: 20px;
                        }
                        .payslip-header h1 {
                            color: #007bff;
                            margin: 0 0 10px 0;
                        }
                        .payslip-content { 
                            border: 1px solid #dee2e6; 
                            padding: 20px; 
                            border-radius: 5px;
                        }
                        .detail-row { 
                            display: flex; 
                            justify-content: space-between; 
                            gap: 30px;
                        }
                        .detail-group { 
                            flex: 1; 
                            margin: 0 10px; 
                        }
                        .detail-group h3 {
                            color: #007bff;
                            border-bottom: 1px solid #dee2e6;
                            padding-bottom: 10px;
                            margin-bottom: 15px;
                        }
                        .detail-item { 
                            display: flex; 
                            justify-content: space-between; 
                            margin: 8px 0; 
                            padding: 5px 0;
                        }
                        .total { 
                            font-weight: bold; 
                            border-top: 2px solid #dee2e6; 
                            padding-top: 10px; 
                            margin-top: 10px;
                            color: #28a745;
                        }
                        .net-salary { 
                            text-align: center; 
                            margin-top: 30px; 
                            font-size: 1.5em;
                            background: #28a745;
                            color: white;
                            padding: 20px;
                            border-radius: 8px;
                        }
                        .net-salary h2 {
                            margin: 0;
                        }
                        .print-button {
                            text-align: center;
                            margin-top: 20px;
                        }
                        .print-button button {
                            background: #007bff;
                            color: white;
                            border: none;
                            padding: 10px 20px;
                            border-radius: 5px;
                            cursor: pointer;
                            font-size: 16px;
                        }
                        .print-button button:hover {
                            background: #0056b3;
                        }
                        @media print { 
                            body { margin: 0; background: white; }
                            .print-button { display: none; }
                        }
                        .payslip-actions {
                            display: none;
                        }
                    </style>
                </head>
                <body>
                    <div class="payslip-container">
                        ${payslipElement.innerHTML}
                        <div class="print-button">
                            <button onclick="window.print()">
                                <i class="fas fa-print"></i> Print / Save as PDF
                            </button>
                        </div>
                    </div>
                </body>
                </html>
            `);
            newWindow.document.close();
        }
        
        // Debug: Check if functions are available
        console.log('printPayslip function available:', typeof printPayslip);
        console.log('downloadPayslip function available:', typeof downloadPayslip);
    </script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation -->
        <nav class="navbar">
            <?php echo getNavigationMenu('view_payslip'); ?>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">My Salary Slips</h1>
                <p class="page-subtitle">View and download your monthly salary slips</p>
            </div>

            <!-- Current Month Summary -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i>
                        Current Month Summary
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
                            <i class="fas fa-calendar-check" style="color: #28a745;"></i>
                        </div>
                        <div class="summary-content">
                            <h4>Current Month Attendance</h4>
                            <p><strong>Present Days:</strong> <?php echo $current_attendance['present_days']; ?></p>
                            <p><strong>Month:</strong> <?php echo date('F Y'); ?></p>
                            <p><strong>Basic Salary:</strong> ₹<?php echo number_format($user['salary'], 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payslips List -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-invoice"></i>
                        Salary Slips History
                    </h3>
                </div>
                
                <div class="payslips-list">
                    <?php if ($payslips->num_rows > 0): ?>
                        <?php while ($payslip = $payslips->fetch_assoc()): ?>
                            <div class="payslip-card" id="payslip-<?php echo $payslip['id']; ?>" style="background: #fff !important; color: #222 !important; font-weight: 700 !important;">
                                <div class="company-header" style="color: #2563eb !important;">
                                    <h2 style="color: #2563eb !important;">Sunny Polymers Pvt. Ltd.</h2>
                                    <div class="org-info" style="color: #555 !important;">Organization number: 1234567890<br>Address: 123, Industrial Area, City, State, 123456</div>
                                </div>
                                <div class="divider" style="border-top: 2px solid #2563eb !important;"></div>
                                <table class="details-table" style="width: 100%; margin: 10px 0 0 0; color: #222 !important; background: #fff !important; font-weight: 700 !important;">
                                    <tr><td class="label" style="color:#2563eb!important;font-weight:700!important;">Employee Name:</td><td class="value" style="color:#222!important;font-weight:700!important;"> <?php echo htmlspecialchars($payslip['user_name']) ?> </td><td class="label" style="color:#2563eb!important;font-weight:700!important;">Pay Period:</td><td class="value" style="color:#222!important;font-weight:700!important;"> <?php echo date('F Y', mktime(0,0,0,$payslip['month'],1,$payslip['year'])) ?> </td></tr>
                                    <tr><td class="label" style="color:#2563eb!important;font-weight:700!important;">Mobile:</td><td class="value" style="color:#222!important;font-weight:700!important;"> <?php echo htmlspecialchars($payslip['mobile']) ?> </td><td class="label" style="color:#2563eb!important;font-weight:700!important;">Pay Date:</td><td class="value" style="color:#222!important;font-weight:700!important;"> <?php echo date('d M Y', strtotime($payslip['generated_at'])) ?> </td></tr>
                                    <tr><td class="label" style="color:#2563eb!important;font-weight:700!important;">Site:</td><td class="value" style="color:#222!important;font-weight:700!important;"> <?php echo htmlspecialchars($payslip['site_name'] ?? 'Not Assigned') ?> </td><td class="label" style="color:#2563eb!important;font-weight:700!important;">State:</td><td class="value" style="color:#222!important;font-weight:700!important;"> <?php echo htmlspecialchars($payslip['state']) ?> </td></tr>
                                    <tr><td class="label" style="color:#2563eb!important;font-weight:700!important;">Bank Name:</td><td class="value" style="color:#222!important;font-weight:700!important;"> <?php echo htmlspecialchars(isset($payslip['bank_name']) ? $payslip['bank_name'] : '') ?> </td><td class="label" style="color:#2563eb!important;font-weight:700!important;">Account Number:</td><td class="value" style="color:#222!important;font-weight:700!important;"> <?php echo htmlspecialchars(isset($payslip['account_number']) ? $payslip['account_number'] : '') ?> </td></tr>
                                </table>
                                <div class="section-title" style="color: #2563eb !important; font-weight: 700 !important;">Earnings & Deductions</div>
                                <table class="earnings-deductions-table" style="color: #222 !important; background: #fff !important; font-weight: 700 !important;">
                                    <tr>
                                        <th style="color: #2563eb !important;">Earnings</th><th class="right" style="color: #2563eb !important;">Amount (₹)</th><th style="color: #2563eb !important;">Deductions</th><th class="right" style="color: #2563eb !important;">Amount (₹)</th>
                                    </tr>
                                    <tr>
                                        <td>Basic Salary</td><td class="right">₹<?php echo number_format($payslip['basic_salary'], 2); ?></td>
                                        <td>Advances</td><td class="right">₹<?php echo number_format($payslip['advances'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Per Day Salary</td><td class="right">₹<?php echo number_format($payslip['basic_salary'] / $payslip['total_days'], 2); ?></td>
                                        <td>Other Deductions</td><td class="right">₹<?php echo number_format($payslip['deductions'] - $payslip['advances'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Present Days</td><td class="right"><?php echo $payslip['present_days']; ?> days</td>
                                        <td></td><td></td>
                                    </tr>
                                    <tr>
                                        <td>Leave Days</td><td class="right"><?php echo $payslip['leave_days']; ?> days</td>
                                        <td></td><td></td>
                                    </tr>
                                    <tr style="background:#f1f5f9;">
                                        <td><b>Total Earned</b></td><td class="right"><b>₹<?php echo number_format($payslip['earned_salary'], 2); ?></b></td>
                                        <td><b>Total Deductions</b></td><td class="right"><b>₹<?php echo number_format($payslip['deductions'], 2); ?></b></td>
                                    </tr>
                                </table>
                                <div class="net-pay" style="background: #22c55e !important; color: #fff !important; font-size: 1.3rem; font-weight: bold !important; text-align: center; padding: 18px; border-radius: 8px; margin-top: 25px;">Net Salary: ₹<?php echo number_format($payslip['net_salary'], 2); ?></div>
                                <div class="footer" style="color: #888 !important;">Generated by: <?php echo htmlspecialchars($payslip['generated_by_name'] ?? 'Admin'); ?> | Generated on: <?php echo date('d M Y H:i', strtotime($payslip['generated_at'])); ?></div>
                                <div class="payslip-actions" style="display: flex !important; gap: 10px; margin-bottom: 10px;">
                                    <a href="generate_pdf_payslip.php?payslip_id=<?php echo $payslip['id']; ?>&action=view" target="_blank" class="btn-download" style="background: #2563eb !important; color: #fff !important; border: none !important; border-radius: 5px !important; padding: 8px 18px !important; font-size: 1rem !important; font-weight: 600 !important; cursor: pointer !important; display: inline-block !important; text-decoration: none !important;">
                                        <i class="fas fa-eye"></i> View PDF
                                    </a>
                                    <a href="generate_pdf_payslip.php?payslip_id=<?php echo $payslip['id']; ?>&action=download" class="btn-download" style="background: #22c55e !important; color: #fff !important; border: none !important; border-radius: 5px !important; padding: 8px 18px !important; font-size: 1rem !important; font-weight: 600 !important; cursor: pointer !important; display: inline-block !important; text-decoration: none !important;">
                                        <i class="fas fa-download"></i> Download PDF
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-file-invoice" style="font-size: 3rem; color: #dee2e6;"></i>
                            <p>No salary slips found</p>
                            <small>Salary slips will appear here once generated by admin</small>
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
        
        .payslips-list {
            padding: 20px;
        }
        
        .payslip-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 20px;
            background: white;
        }
        
        .payslip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
        }
        
        .payslip-title h4 {
            margin: 0;
            color: #212529;
        }
        
        .payslip-subtitle {
            margin: 5px 0 0 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .payslip-actions {
            display: flex;
            gap: 10px;
        }
        
        .payslip-content {
            padding: 20px;
        }
        
        .payslip-details {
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
        
        .detail-item.total {
            font-weight: bold;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .net-salary {
            text-align: center;
            padding: 20px;
            background: #28a745;
            color: white;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .net-salary h3 {
            margin: 0;
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
            
            .payslip-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
    <?php echo getNotificationScripts(); ?>
</body>
</html> 