<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/notifications.php';
require_once 'includes/navigation.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Require admin authentication
require_admin();

// Set timezone
date_default_timezone_set('Asia/Kolkata');

$success_message = '';
$error_message = '';

// Get filter parameters
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');
$employee_filter = $_GET['employee'] ?? '';

// Get all employees with overtime details
$where_clause = "WHERE 1=1";
$params = [];
$types = "";

if ($employee_filter) {
    $where_clause .= " AND u.name LIKE ?";
    $params[] = "%$employee_filter%";
    $types .= "s";
}

$sql = "
    SELECT 
        u.id,
        u.name,
        u.mobile,
        u.role,
        u.salary,
        u.overtime_rate,
        COALESCE(SUM(a.overtime_hours), 0) as total_overtime_hours,
        COALESCE(SUM(a.overtime_hours * a.overtime_rate), 0) as total_overtime_pay,
        COUNT(CASE WHEN a.overtime_hours > 0 THEN 1 END) as days_with_overtime,
        COUNT(a.id) as total_attendance_days
    FROM users u
    LEFT JOIN attendance a ON u.id = a.user_id 
        AND MONTH(a.date) = ? 
        AND YEAR(a.date) = ?
    $where_clause
    GROUP BY u.id, u.name, u.mobile, u.role, u.salary, u.overtime_rate
    ORDER BY total_overtime_hours DESC, u.name
";

$params = array_merge([$month, $year], $params);
$types = "ss" . $types;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get monthly summary
$summary_sql = "
    SELECT 
        COUNT(DISTINCT a.user_id) as employees_with_overtime,
        SUM(a.overtime_hours) as total_overtime_hours,
        SUM(a.overtime_hours * a.overtime_rate) as total_overtime_pay,
        AVG(a.overtime_hours) as avg_overtime_per_day
    FROM attendance a
    WHERE MONTH(a.date) = ? AND YEAR(a.date) = ? AND a.overtime_hours > 0
";

$stmt = $conn->prepare($summary_sql);
$stmt->bind_param("ss", $month, $year);
$stmt->execute();
$monthly_summary = $stmt->get_result()->fetch_assoc();

// Get top overtime earners
$top_earners_sql = "
    SELECT 
        u.name,
        SUM(a.overtime_hours) as total_hours,
        SUM(a.overtime_hours * a.overtime_rate) as total_pay
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    WHERE MONTH(a.date) = ? AND YEAR(a.date) = ? AND a.overtime_hours > 0
    GROUP BY u.id, u.name
    ORDER BY total_pay DESC
    LIMIT 5
";

$stmt = $conn->prepare($top_earners_sql);
$stmt->bind_param("ss", $month, $year);
$stmt->execute();
$top_earners = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $download = isset($_GET['download']) && $_GET['download'] === '1';
    generateOvertimePDF($employees, $monthly_summary, $top_earners, $month, $year, $employee_filter, $download);
    exit;
}

// Function to generate overtime report PDF
function generateOvertimePDF($employees, $monthly_summary, $top_earners, $month, $year, $employee_filter, $download = false) {
    // Create new DOMPDF instance
    $dompdf = new Dompdf();
    
    // Set options
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', true);
    $dompdf->setOptions($options);
    
    // Generate HTML content
    $html = generateOvertimeHTML($employees, $monthly_summary, $top_earners, $month, $year, $employee_filter);
    
    // Load HTML into DOMPDF
    $dompdf->loadHtml($html);
    
    // Set paper size and orientation
    $dompdf->setPaper('A4', 'landscape');
    
    // Render PDF
    $dompdf->render();
    
    // Generate filename
    $filename = 'overtime_report_' . date('F_Y', mktime(0, 0, 0, $month, 1, $year));
    if ($employee_filter) {
        $filename .= '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $employee_filter);
    }
    $filename .= '.pdf';
    
    // Output PDF
    $dompdf->stream($filename, array('Attachment' => $download));
}

// Function to generate HTML for PDF
function generateOvertimeHTML($employees, $monthly_summary, $top_earners, $month, $year, $employee_filter) {
    $month_name = date('F Y', mktime(0, 0, 0, $month, 1, $year));
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Overtime Report - ' . $month_name . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 15px;
                font-size: 10px;
                line-height: 1.2;
            }
            .header {
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
            }
            .header h1 {
                margin: 0;
                color: #333;
                font-size: 24px;
            }
            .header p {
                margin: 5px 0;
                color: #666;
                font-size: 14px;
            }
            .summary-grid {
                display: table;
                width: 100%;
                margin-bottom: 20px;
                border-collapse: collapse;
            }
            .summary-card {
                display: table-cell;
                width: 25%;
                padding: 10px;
                text-align: center;
                border: 1px solid #ddd;
                background: #f8f9fa;
            }
            .summary-number {
                font-size: 18px;
                font-weight: bold;
                color: #667eea;
                margin-bottom: 5px;
            }
            .summary-label {
                color: #666;
                font-size: 12px;
            }
            .top-earners {
                margin-bottom: 20px;
            }
            .top-earners h3 {
                margin: 0 0 10px 0;
                color: #333;
                font-size: 16px;
                border-bottom: 1px solid #ddd;
                padding-bottom: 5px;
            }
            .earner-list {
                display: table;
                width: 100%;
                border-collapse: collapse;
            }
            .earner-item {
                display: table-cell;
                width: 20%;
                padding: 8px;
                text-align: center;
                border: 1px solid #ddd;
                background: #f8f9fa;
            }
            .earner-rank {
                font-size: 14px;
                font-weight: bold;
                color: #667eea;
                margin-bottom: 3px;
            }
            .earner-name {
                font-weight: 600;
                color: #333;
                margin-bottom: 3px;
            }
            .earner-hours {
                color: #e74c3c;
                font-weight: 600;
            }
            .earner-pay {
                color: #28a745;
                font-weight: 600;
            }
            .employees-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            .employees-table th,
            .employees-table td {
                padding: 6px;
                text-align: left;
                border: 1px solid #ddd;
                font-size: 9px;
            }
            .employees-table th {
                background: #f8f9fa;
                font-weight: 600;
                color: #333;
            }
            .overtime-hours {
                font-weight: 600;
                color: #e74c3c;
            }
            .overtime-pay {
                font-weight: 600;
                color: #28a745;
            }
            .no-overtime {
                color: #999;
                font-style: italic;
            }
            .filters-info {
                margin-bottom: 15px;
                padding: 10px;
                background: #f8f9fa;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .filters-info p {
                margin: 2px 0;
                font-size: 11px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>📊 Overtime Report</h1>
            <p>Sunny Polymers Employee Portal</p>
            <p>Period: ' . $month_name . '</p>';
    
    if ($employee_filter) {
        $html .= '<p>Filtered by: ' . htmlspecialchars($employee_filter) . '</p>';
    }
    
    $html .= '</div>';
    
    // Summary Grid
    $html .= '
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-number">' . ($monthly_summary['employees_with_overtime'] ?? 0) . '</div>
                <div class="summary-label">Employees with Overtime</div>
            </div>
            <div class="summary-card">
                <div class="summary-number">' . number_format($monthly_summary['total_overtime_hours'] ?? 0, 1) . '</div>
                <div class="summary-label">Total Overtime Hours</div>
            </div>
            <div class="summary-card">
                <div class="summary-number">₹' . number_format($monthly_summary['total_overtime_pay'] ?? 0, 2) . '</div>
                <div class="summary-label">Total Overtime Pay</div>
            </div>
            <div class="summary-card">
                <div class="summary-number">' . number_format($monthly_summary['avg_overtime_per_day'] ?? 0, 1) . '</div>
                <div class="summary-label">Avg Hours per Day</div>
            </div>
        </div>';
    
    // Top Earners
    if (!empty($top_earners)) {
        $html .= '
        <div class="top-earners">
            <h3>🏆 Top Overtime Earners - ' . $month_name . '</h3>
            <div class="earner-list">';
        
        foreach ($top_earners as $index => $earner) {
            $html .= '
                <div class="earner-item">
                    <div class="earner-rank">#' . ($index + 1) . '</div>
                    <div class="earner-name">' . htmlspecialchars($earner['name']) . '</div>
                    <div class="earner-hours">' . number_format($earner['total_hours'], 1) . ' hrs</div>
                    <div class="earner-pay">₹' . number_format($earner['total_pay'], 2) . '</div>
                </div>';
        }
        
        $html .= '</div></div>';
    }
    
    // Employees Table
    $html .= '
        <div class="employees-table">
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Role</th>
                        <th>Basic Salary</th>
                        <th>Overtime Rate</th>
                        <th>Days with Overtime</th>
                        <th>Total Overtime Hours</th>
                        <th>Total Overtime Pay</th>
                    </tr>
                </thead>
                <tbody>';
    
    if (empty($employees)) {
        $html .= '
                    <tr>
                        <td colspan="7" style="text-align: center; color: #666; padding: 20px;">
                            No employees found for the selected criteria
                        </td>
                    </tr>';
    } else {
        foreach ($employees as $employee) {
            $html .= '
                    <tr>
                        <td>
                            <strong>' . htmlspecialchars($employee['name']) . '</strong><br>
                            <small>' . htmlspecialchars($employee['mobile']) . '</small>
                        </td>
                        <td>' . ucfirst($employee['role']) . '</td>
                        <td>₹' . number_format($employee['salary'], 2) . '</td>
                        <td>₹' . number_format($employee['overtime_rate'], 2) . '</td>
                        <td>';
            
            if ($employee['days_with_overtime'] > 0) {
                $html .= '<span class="overtime-hours">' . $employee['days_with_overtime'] . ' days</span>';
            } else {
                $html .= '<span class="no-overtime">0 days</span>';
            }
            
            $html .= '</td>
                        <td>';
            
            if ($employee['total_overtime_hours'] > 0) {
                $html .= '<span class="overtime-hours">' . number_format($employee['total_overtime_hours'], 1) . ' hrs</span>';
            } else {
                $html .= '<span class="no-overtime">0 hrs</span>';
            }
            
            $html .= '</td>
                        <td>';
            
            if ($employee['total_overtime_pay'] > 0) {
                $html .= '<span class="overtime-pay">₹' . number_format($employee['total_overtime_pay'], 2) . '</span>';
            } else {
                $html .= '<span class="no-overtime">₹0.00</span>';
            }
            
            $html .= '</td>
                    </tr>';
        }
    }
    
    $html .= '
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 20px; text-align: center; font-size: 10px; color: #666;">
            <p>Generated on: ' . date('d M Y, h:i A') . '</p>
            <p>Sunny Polymers Employee Portal - Overtime Management System</p>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overtime Report - Sunny Polymers</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/navigation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .report-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .report-header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
            color: white;
        }
        
        .report-header p {
            margin: 10px 0 0 0;
            font-size: 1.1em;
            opacity: 0.9;
            color: white;
        }
        
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 4px solid #667eea;
        }
        
        .summary-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .summary-label {
            color: #666;
            font-size: 1.1em;
        }
        
        .top-earners {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .section-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .section-header h3 {
            margin: 0;
            color: #333;
            font-size: 1.3em;
        }
        
        .section-content {
            padding: 20px;
        }
        
        .earner-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .earner-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 3px solid #28a745;
        }
        
        .earner-rank {
            font-size: 1.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .earner-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .earner-hours {
            color: #e74c3c;
            font-weight: 600;
        }
        
        .earner-pay {
            color: #28a745;
            font-weight: 600;
        }
        
        .employees-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .employees-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .employees-table th,
        .employees-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .employees-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            position: sticky;
            top: 0;
        }
        
        .employees-table tr:hover {
            background: #f8f9fa;
        }
        
        .overtime-hours {
            font-weight: 600;
            color: #e74c3c;
        }
        
        .overtime-pay {
            font-weight: 600;
            color: #28a745;
        }
        
        .no-overtime {
            color: #999;
            font-style: italic;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 600;
        }
        
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        
        .export-section {
            text-align: right;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .filters-form {
                grid-template-columns: 1fr;
            }
            
            .earner-list {
                grid-template-columns: 1fr;
            }
            
            .report-header h1 {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <?php echo getNavigationMenu('overtime_report'); ?>
    </nav>

    <div class="report-container">
        <div class="report-header">
            <h1>📊 Overtime Report</h1>
            <p>Comprehensive overview of employee overtime for <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></p>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="form-group">
                    <label for="month">Month</label>
                    <select name="month" id="month">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" 
                                    <?php echo $month == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="year">Year</label>
                    <select name="year" id="year">
                        <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                            <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="employee">Search Employee</label>
                    <input type="text" name="employee" id="employee" 
                           value="<?php echo htmlspecialchars($employee_filter); ?>" 
                           placeholder="Enter employee name...">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="overtime_report.php" class="btn btn-success">
                        <i class="fas fa-refresh"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Monthly Summary -->
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-number"><?php echo $monthly_summary['employees_with_overtime'] ?? 0; ?></div>
                <div class="summary-label">Employees with Overtime</div>
            </div>
            <div class="summary-card">
                <div class="summary-number"><?php echo number_format($monthly_summary['total_overtime_hours'] ?? 0, 1); ?></div>
                <div class="summary-label">Total Overtime Hours</div>
            </div>
            <div class="summary-card">
                <div class="summary-number">₹<?php echo number_format($monthly_summary['total_overtime_pay'] ?? 0, 2); ?></div>
                <div class="summary-label">Total Overtime Pay</div>
            </div>
            <div class="summary-card">
                <div class="summary-number"><?php echo number_format($monthly_summary['avg_overtime_per_day'] ?? 0, 1); ?></div>
                <div class="summary-label">Avg Hours per Day</div>
            </div>
        </div>

        <!-- Top Overtime Earners -->
        <?php if (!empty($top_earners)): ?>
        <div class="top-earners">
            <div class="section-header">
                <h3>🏆 Top Overtime Earners - <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h3>
            </div>
            <div class="section-content">
                <div class="earner-list">
                    <?php foreach ($top_earners as $index => $earner): ?>
                        <div class="earner-item">
                            <div class="earner-rank">#<?php echo $index + 1; ?></div>
                            <div class="earner-name"><?php echo htmlspecialchars($earner['name']); ?></div>
                            <div class="earner-hours"><?php echo number_format($earner['total_hours'], 1); ?> hrs</div>
                            <div class="earner-pay">₹<?php echo number_format($earner['total_pay'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Export Section -->
        <div class="export-section">
            <a href="overtime_report.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>&employee=<?php echo urlencode($employee_filter); ?>&export=pdf" 
               class="btn btn-success" style="margin-right: 10px;" target="_blank">
                <i class="fas fa-eye"></i> View PDF
            </a>
            <a href="overtime_report.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>&employee=<?php echo urlencode($employee_filter); ?>&export=pdf&download=1" 
               class="btn btn-primary">
                <i class="fas fa-download"></i> Download PDF
            </a>
        </div>

        <!-- Employees Overtime Table -->
        <div class="employees-table">
            <div class="section-header">
                <h3>👥 Employee Overtime Details</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Role</th>
                            <th>Basic Salary</th>
                            <th>Overtime Rate</th>
                            <th>Days with Overtime</th>
                            <th>Total Overtime Hours</th>
                            <th>Total Overtime Pay</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    <i class="fas fa-users" style="font-size: 3rem; color: #dee2e6;"></i>
                                    <p>No employees found for the selected criteria</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($employee['name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($employee['mobile']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $employee['role'] == 'admin' ? 'danger' : ($employee['role'] == 'staff' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($employee['role']); ?>
                                        </span>
                                    </td>
                                    <td>₹<?php echo number_format($employee['salary'], 2); ?></td>
                                    <td>₹<?php echo number_format($employee['overtime_rate'], 2); ?></td>
                                    <td>
                                        <?php if ($employee['days_with_overtime'] > 0): ?>
                                            <span class="overtime-hours"><?php echo $employee['days_with_overtime']; ?> days</span>
                                        <?php else: ?>
                                            <span class="no-overtime">0 days</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($employee['total_overtime_hours'] > 0): ?>
                                            <span class="overtime-hours"><?php echo number_format($employee['total_overtime_hours'], 1); ?> hrs</span>
                                        <?php else: ?>
                                            <span class="no-overtime">0 hrs</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($employee['total_overtime_pay'] > 0): ?>
                                            <span class="overtime-pay">₹<?php echo number_format($employee['total_overtime_pay'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="no-overtime">₹0.00</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="overtime_management.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i> Manage
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Auto-submit form when month/year changes
        document.getElementById('month').addEventListener('change', function() {
            this.form.submit();
        });
        
        document.getElementById('year').addEventListener('change', function() {
            this.form.submit();
        });
        
        // Highlight rows with overtime
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.employees-table tbody tr');
            rows.forEach(row => {
                const overtimeHours = row.querySelector('.overtime-hours');
                if (overtimeHours && overtimeHours.textContent.includes('hrs')) {
                    row.style.backgroundColor = '#f8f9fa';
                }
            });
        });
    </script>
</body>
</html>
