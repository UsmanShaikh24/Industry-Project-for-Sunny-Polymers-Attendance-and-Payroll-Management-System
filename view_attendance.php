<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/notifications.php';
require_once 'includes/navigation.php';

// Require authentication (both admin and worker can access)
require_auth();

// Get filter parameters
if (is_admin()) {
    // Admin sees all attendance by default, unless date filter is applied
    $date_filter = isset($_GET['date']) ? $_GET['date'] : '';
} else {
    // Workers see today's attendance by default
    $date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
}
$user_filter = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$site_filter = isset($_GET['site_id']) ? $_GET['site_id'] : '';

// Build query based on user role
$where_conditions = [];
$params = [];
$types = '';

if (is_admin()) {
    // Admin can see all attendance records
    if ($date_filter) {
        $where_conditions[] = "DATE(a.date) = ?";
        $params[] = $date_filter;
        $types .= 's';
    }

    if ($user_filter) {
        $where_conditions[] = "a.user_id = ?";
        $params[] = $user_filter;
        $types .= 'i';
    }

    if ($site_filter) {
        $where_conditions[] = "u.site_id = ?";
        $params[] = $site_filter;
        $types .= 'i';
    }
} else {
    // Worker can only see their own attendance records
    $where_conditions[] = "a.user_id = ?";
    $params[] = $_SESSION['user_id'];
    $types .= 'i';
    
    // Only apply date filter if explicitly requested (not by default for workers)
    if (isset($_GET['date']) && $_GET['date']) {
        $where_conditions[] = "DATE(a.date) = ?";
        $params[] = $_GET['date'];
        $types .= 's';
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$query = "SELECT 
            a.id,
            a.user_id,
            a.date,
            a.check_in_time,
            a.check_out_time,
            a.check_in_lat,
            a.check_in_lng,
            a.check_out_lat,
            a.check_out_lng,
            a.status,
            u.name as worker_name,
            u.mobile,
            s.name as site_name
          FROM attendance a 
          INNER JOIN users u ON a.user_id = u.id 
          LEFT JOIN sites s ON u.site_id = s.id 
          $where_clause 
          ORDER BY a.date DESC, a.check_in_time DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$attendance_records = $stmt->get_result();

// Get users for filter (admin only)
$users = null;
$sites = null;
if (is_admin()) {
    $stmt = $conn->prepare("SELECT id, name, mobile FROM users WHERE role IN ('worker', 'staff') ORDER BY name");
    $stmt->execute();
    $users = $stmt->get_result();

    $stmt = $conn->prepare("SELECT id, name FROM sites ORDER BY name");
    $stmt->execute();
    $sites = $stmt->get_result();
}

// Get stats based on user role and filters
if (is_admin()) {
    if ($date_filter) {
        // Show stats for specific date
        $stmt = $conn->prepare("SELECT 
            COUNT(*) as total_workers,
            COUNT(CASE WHEN a.check_in_time IS NOT NULL THEN 1 END) as present,
            COUNT(CASE WHEN a.check_in_time IS NULL THEN 1 END) as absent
        FROM users u 
        LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.date) = ?
        WHERE u.role IN ('worker', 'staff')");
        $stmt->bind_param("s", $date_filter);
    } else {
        // Show overall stats when no date filter
        $stmt = $conn->prepare("SELECT 
            COUNT(DISTINCT u.id) as total_workers,
            COUNT(CASE WHEN a.check_in_time IS NOT NULL THEN 1 END) as present,
            COUNT(CASE WHEN a.check_in_time IS NULL THEN 1 END) as absent,
            COUNT(DISTINCT a.date) as total_days
        FROM users u 
        LEFT JOIN attendance a ON u.id = a.user_id
        WHERE u.role IN ('worker', 'staff')");
    }
} else {
    // Show all-time stats for the logged-in worker
    $stmt = $conn->prepare("SELECT 
        COUNT(*) as total_days,
        COUNT(CASE WHEN check_in_time IS NOT NULL THEN 1 END) as present_days,
        COUNT(CASE WHEN check_in_time IS NULL THEN 1 END) as absent_days
    FROM attendance WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
}
$stmt->execute();
$today_stats = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Sunny Polymers Employee Portal</title>
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
            <?php echo getNavigationMenu('view_attendance'); ?>
        </nav>
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">
                    <?php if (is_admin()): ?>
                        <?php echo $date_filter ? 'Attendance Records' : 'All Attendance Records'; ?>
                    <?php else: ?>
                        My Attendance History
                    <?php endif; ?>
                </h1>
                <p class="page-subtitle">
                    <?php if (is_admin()): ?>
                        <?php echo $date_filter ? 'Monitor and track worker attendance for ' . date('d M Y', strtotime($date_filter)) : 'Monitor and track all worker attendance records'; ?>
                    <?php else: ?>
                        View your attendance history and records
                    <?php endif; ?>
                </p>
            </div>

            <!-- Today's Stats -->
            <div class="stats-grid">
                <?php if (is_admin()): ?>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users" style="color: #667eea;"></i>
                        </div>
                        <div class="stat-number"><?php echo $today_stats['total_workers']; ?></div>
                        <div class="stat-label">Total Workers</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle" style="color: #28a745;"></i>
                        </div>
                        <div class="stat-number"><?php echo $today_stats['present']; ?></div>
                        <div class="stat-label"><?php echo $date_filter ? 'Present Today' : 'Total Present'; ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle" style="color: #dc3545;"></i>
                        </div>
                        <div class="stat-number"><?php echo $today_stats['absent']; ?></div>
                        <div class="stat-label"><?php echo $date_filter ? 'Absent Today' : 'Total Absent'; ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-percentage" style="color: #ffc107;"></i>
                        </div>
                        <div class="stat-number">
                            <?php 
                            if ($date_filter) {
                                // For specific date, show attendance rate
                                $percentage = $today_stats['total_workers'] > 0 ? 
                                    round(($today_stats['present'] / $today_stats['total_workers']) * 100, 1) : 0;
                                echo $percentage . '%';
                            } else {
                                // For all dates, show average attendance per day
                                $avg_attendance = $today_stats['total_days'] > 0 ? 
                                    round($today_stats['present'] / $today_stats['total_days'], 1) : 0;
                                echo $avg_attendance;
                            }
                            ?>
                        </div>
                        <div class="stat-label">
                            <?php echo $date_filter ? 'Attendance Rate' : 'Avg Attendance/Day'; ?>
                        </div>
                    </div>
                    
                    <?php if (!$date_filter && isset($today_stats['total_days'])): ?>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar" style="color: #17a2b8;"></i>
                        </div>
                        <div class="stat-number"><?php echo $today_stats['total_days']; ?></div>
                        <div class="stat-label">Total Days</div>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check" style="color: #667eea;"></i>
                        </div>
                        <div class="stat-number"><?php echo $today_stats['total_days']; ?></div>
                        <div class="stat-label">Total Days</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle" style="color: #28a745;"></i>
                        </div>
                        <div class="stat-number"><?php echo $today_stats['present_days']; ?></div>
                        <div class="stat-label">Present Days</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle" style="color: #dc3545;"></i>
                        </div>
                        <div class="stat-number"><?php echo $today_stats['absent_days']; ?></div>
                        <div class="stat-label">Absent Days</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-percentage" style="color: #ffc107;"></i>
                        </div>
                        <div class="stat-number">
                            <?php 
                            $percentage = $today_stats['total_days'] > 0 ? 
                                round(($today_stats['present_days'] / $today_stats['total_days']) * 100, 1) : 0;
                            echo $percentage . '%';
                            ?>
                        </div>
                        <div class="stat-label">My Attendance Rate</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Filters -->
            <?php if (is_admin()): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Filters</h3>
                    </div>
                    
                    <form method="GET" class="form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date">Date</label>
                                <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="user_id">Worker</label>
                                <select id="user_id" name="user_id" class="form-control">
                                    <option value="">All Workers</option>
                                    <?php if ($users): ?>
                                        <?php while ($user = $users->fetch_assoc()): ?>
                                            <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['mobile']); ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_id">Site</label>
                                <select id="site_id" name="site_id" class="form-control">
                                    <option value="">All Sites</option>
                                    <?php if ($sites): ?>
                                        <?php while ($site = $sites->fetch_assoc()): ?>
                                            <option value="<?php echo $site['id']; ?>" <?php echo $site_filter == $site['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($site['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i>
                            Apply Filters
                        </button>
                        
                        <a href="view_attendance.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Clear Filters
                        </a>
                    </form>
                </div>
            <?php else: ?>
                <!-- Simple date filter for workers -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Filter by Date</h3>
                    </div>
                    
                    <form method="GET" class="form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date">Select Date</label>
                                <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i>
                            View Records
                        </button>
                        
                        <a href="view_attendance.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Clear Filter
                        </a>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Attendance Records -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <?php if (is_admin()): ?>
                            Attendance Records
                        <?php else: ?>
                            My Attendance Records
                        <?php endif; ?>
                    </h3>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <?php if (is_admin()): ?>
                                    <th>Worker</th>
                                    <th>Mobile</th>
                                <?php endif; ?>
                                <th>Site</th>
                                <th>Check In Time</th>
                                <th>Check Out Time</th>
                                <th>Location (Coordinates)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($record = $attendance_records->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($record['date'])); ?></td>
                                <?php if (is_admin()): ?>
                                    <td><?php echo htmlspecialchars($record['worker_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['mobile']); ?></td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($record['site_name'] ?? 'Not Assigned'); ?></td>
                                <td>
                                    <?php if ($record['check_in_time']): ?>
                                        <?php echo date('H:i', strtotime($record['check_in_time'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($record['check_out_time']): ?>
                                        <?php echo date('H:i', strtotime($record['check_out_time'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($record['check_in_lat'] && $record['check_in_lng']): ?>
                                        <small>
                                            <?php echo number_format($record['check_in_lat'], 6); ?>, 
                                            <?php echo number_format($record['check_in_lng'], 6); ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($record['check_in_time'] && $record['check_out_time']): ?>
                                        <span class="status-present">Complete</span>
                                    <?php elseif ($record['check_in_time']): ?>
                                        <span class="status-warning">Checked In</span>
                                    <?php else: ?>
                                        <span class="status-absent">Absent</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            
                            <?php if ($attendance_records->num_rows == 0): ?>
                            <tr>
                                <td colspan="<?php echo is_admin() ? '8' : '6'; ?>" class="text-center text-muted">No attendance records found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: #6c757d;
            font-style: italic;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
            text-align: left;
        }
        
        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .status-present {
            color: #28a745;
            font-weight: 600;
        }
        
        .status-warning {
            color: #ffc107;
            font-weight: 600;
        }
        
        .status-absent {
            color: #dc3545;
            font-weight: 600;
        }
        
        .table-container {
            overflow-x: auto;
        }
    </style>
    <?php echo getNotificationScripts(); ?>
</body>
</html> 