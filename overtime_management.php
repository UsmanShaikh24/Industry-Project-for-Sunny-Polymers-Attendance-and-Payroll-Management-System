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

// Handle overtime rate updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_overtime_rate'])) {
        $user_id = $_POST['user_id'];
        $overtime_rate = (float)$_POST['overtime_rate'];
        
        // Update user's overtime rate
        $stmt = $conn->prepare("UPDATE users SET overtime_rate = ? WHERE id = ?");
        $stmt->bind_param("di", $overtime_rate, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Overtime rate updated successfully!";
        } else {
            $error_message = "Error updating overtime rate. Please try again.";
        }
    }
    
    if (isset($_POST['set_global_rate'])) {
        $global_rate = (float)$_POST['global_rate'];
        
        // Update all users with the global rate
        $stmt = $conn->prepare("UPDATE users SET overtime_rate = ? WHERE role IN ('worker', 'staff')");
        $stmt->bind_param("d", $global_rate);
        
        if ($stmt->execute()) {
            $success_message = "Global overtime rate set for all employees!";
        } else {
            $error_message = "Error setting global rate. Please try again.";
        }
    }
}

// Get all users with their overtime rates
$stmt = $conn->prepare("
    SELECT u.id, u.name, u.mobile, u.role, u.salary, u.overtime_rate, 
           COALESCE(u.overtime_rate, 0) as current_rate
    FROM users u 
    WHERE u.role IN ('worker', 'staff') 
    ORDER BY u.name
");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get overtime statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT a.user_id) as employees_with_overtime,
        SUM(a.overtime_hours) as total_overtime_hours,
        AVG(a.overtime_hours) as avg_overtime_hours
    FROM attendance a 
    WHERE a.overtime_hours > 0 
    AND a.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$stmt->execute();
$overtime_stats = $stmt->get_result()->fetch_assoc();

// Get recent overtime records
$stmt = $conn->prepare("
    SELECT 
        a.date,
        a.overtime_hours,
        a.overtime_rate,
        u.name as employee_name,
        u.role
    FROM attendance a
    JOIN users u ON a.user_id = u.id
    WHERE a.overtime_hours > 0
    ORDER BY a.date DESC
    LIMIT 10
");
$stmt->execute();
$recent_overtime = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overtime Management - Sunny Polymers</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/navigation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .overtime-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .overtime-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .overtime-header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
            color: white;
        }
        
        .overtime-header p {
            margin: 10px 0 0 0;
            font-size: 1.1em;
            opacity: 0.9;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 4px solid #667eea;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1em;
        }
        
        .overtime-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .overtime-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
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
        
        .global-rate-form {
            background: #e8f4fd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .global-rate-form h4 {
            margin: 0 0 15px 0;
            color: #2c5aa0;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input {
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
        
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .user-table th,
        .user-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .user-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .user-table tr:hover {
            background: #f8f9fa;
        }
        
        .rate-input {
            width: 80px;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .recent-overtime {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .overtime-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .overtime-table th,
        .overtime-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .overtime-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .overtime-hours {
            font-weight: 600;
            color: #e74c3c;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .overtime-sections {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .overtime-header h1 {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <?php echo getNavigationMenu('overtime_management'); ?>
    </nav>

    <div class="overtime-container">
        <div class="overtime-header">
            <h1>🕒 Overtime Management</h1>
            <p>Manage overtime rates, policies, and track employee overtime hours</p>
            <div style="margin-top: 20px;">
                <a href="overtime_report.php" class="btn btn-success" style="text-decoration: none; margin: 0 10px;">
                    <i class="fas fa-chart-bar"></i> View Overtime Report
                </a>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Overtime Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $overtime_stats['employees_with_overtime'] ?? 0; ?></div>
                <div class="stat-label">Employees with Overtime</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($overtime_stats['total_overtime_hours'] ?? 0, 1); ?></div>
                <div class="stat-label">Total Overtime Hours (30 days)</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($overtime_stats['avg_overtime_hours'] ?? 0, 1); ?></div>
                <div class="stat-label">Average Overtime per Day</div>
            </div>
        </div>

        <div class="overtime-sections">
            <!-- Employee Overtime Rates -->
            <div class="overtime-section">
                <div class="section-header">
                    <h3>👥 Employee Overtime Rates</h3>
                </div>
                <div class="section-content">
                    <div class="global-rate-form">
                        <h4>🌍 Set Global Overtime Rate</h4>
                        <form method="POST">
                            <div class="form-group">
                                <label for="global_rate">Hourly Rate (₹)</label>
                                <input type="number" id="global_rate" name="global_rate" step="0.01" min="0" placeholder="e.g., 150.00" required>
                            </div>
                            <button type="submit" name="set_global_rate" class="btn btn-success">Set for All Employees</button>
                        </form>
                    </div>

                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Role</th>
                                <th>Basic Salary</th>
                                <th>Overtime Rate</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($user['mobile']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'staff' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>₹<?php echo number_format($user['salary'], 2); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="number" name="overtime_rate" value="<?php echo $user['current_rate']; ?>" 
                                                   step="0.01" min="0" class="rate-input" required>
                                            <button type="submit" name="update_overtime_rate" class="btn btn-primary btn-sm">Update</button>
                                        </form>
                                    </td>
                                    <td>
                                        <button type="submit" form="form_<?php echo $user['id']; ?>" class="btn btn-primary btn-sm">Save</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Overtime Policy -->
            <div class="overtime-section">
                <div class="section-header">
                    <h3>📋 Overtime Policy</h3>
                </div>
                <div class="section-content">
                    <h4>🕐 Standard Working Hours</h4>
                    <ul>
                        <li><strong>Regular Shift:</strong> 8 hours per day (9:00 AM - 6:00 PM)</li>
                        <li><strong>Overtime Start:</strong> After 8 hours of work</li>
                        <li><strong>Break Time:</strong> 1 hour lunch break (not counted in overtime)</li>
                    </ul>

                    <h4>💰 Overtime Calculation</h4>
                    <ul>
                        <li><strong>Weekdays:</strong> 1.5x hourly rate after 8 hours</li>
                        <li><strong>Weekends:</strong> 2x hourly rate for all hours</li>
                        <li><strong>Holidays:</strong> 2.5x hourly rate for all hours</li>
                    </ul>

                    <h4>⚡ Automatic Calculation</h4>
                    <ul>
                        <li>System automatically detects overtime based on check-in/check-out times</li>
                        <li>Overtime hours are calculated daily and accumulated monthly</li>
                        <li>Overtime pay is included in salary calculations</li>
                    </ul>

                    <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-top: 20px;">
                        <strong>💡 Tip:</strong> Set appropriate overtime rates based on your company policy and local labor laws.
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Overtime Records -->
        <div class="recent-overtime">
            <div class="section-header">
                <h3>📊 Recent Overtime Records</h3>
            </div>
            <div class="section-content">
                <?php if (empty($recent_overtime)): ?>
                    <p style="text-align: center; color: #666; padding: 20px;">No overtime records found in the last 30 days.</p>
                <?php else: ?>
                    <table class="overtime-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Employee</th>
                                <th>Role</th>
                                <th>Overtime Hours</th>
                                <th>Rate (₹)</th>
                                <th>Overtime Pay (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_overtime as $record): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($record['date'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($record['employee_name']); ?></strong></td>
                                    <td><?php echo ucfirst($record['role']); ?></td>
                                    <td class="overtime-hours"><?php echo $record['overtime_hours']; ?> hrs</td>
                                    <td>₹<?php echo number_format($record['overtime_rate'], 2); ?></td>
                                    <td><strong>₹<?php echo number_format($record['overtime_hours'] * $record['overtime_rate'], 2); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Highlight active form when editing
            const rateInputs = document.querySelectorAll('.rate-input');
            rateInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.borderColor = '#667eea';
                    this.style.boxShadow = '0 0 0 2px rgba(102, 126, 234, 0.2)';
                });
                
                input.addEventListener('blur', function() {
                    this.style.borderColor = '#ddd';
                    this.style.boxShadow = 'none';
                });
            });

            // Auto-save on Enter key
            rateInputs.forEach(input => {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        this.closest('form').submit();
                    }
                });
            });
        });
    </script>
</body>
</html>
