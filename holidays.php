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

// Debug: Check session variables
echo "<!-- Debug Info: -->\n";
echo "<!-- Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . " -->\n";
echo "<!-- Session user_date_of_joining: " . ($_SESSION['user_date_of_joining'] ?? 'NOT SET') . " -->\n";

// Get user data directly from database to verify
$stmt = $conn->prepare("SELECT date_of_joining FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user_result = $stmt->get_result()->fetch_assoc();
echo "<!-- Database date_of_joining: " . ($user_result['date_of_joining'] ?? 'NOT FOUND') . " -->\n";

$user_state = $_SESSION['user_state'];
$current_year = date('Y');

// Get holidays for user's state (including All India holidays)
$stmt = $conn->prepare("SELECT * FROM holidays WHERE (state = ? OR state = 'All India') AND YEAR(date) = ? ORDER BY date ASC");
$stmt->bind_param("ss", $user_state, $current_year);
$stmt->execute();
$holidays = $stmt->get_result();

// Check if user is eligible for holidays (more than 1 year tenure)
$is_eligible = false;
if (isset($_SESSION['user_date_of_joining']) && $_SESSION['user_date_of_joining']) {
    $joining_date = new DateTime($_SESSION['user_date_of_joining']);
    $current_date = new DateTime();
    $tenure = $current_date->diff($joining_date);
    $is_eligible = $tenure->y >= 1;
} else {
    // Fallback: Get from database directly
    if ($user_result && $user_result['date_of_joining']) {
        $joining_date = new DateTime($user_result['date_of_joining']);
        $current_date = new DateTime();
        $tenure = $current_date->diff($joining_date);
        $is_eligible = $tenure->y >= 1;
        // Update session
        $_SESSION['user_date_of_joining'] = $user_result['date_of_joining'];
    }
}

// Get upcoming holidays (including All India holidays)
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT * FROM holidays WHERE (state = ? OR state = 'All India') AND date >= ? AND YEAR(date) = ? ORDER BY date ASC LIMIT 5");
$stmt->bind_param("sss", $user_state, $today, $current_year);
$stmt->execute();
$upcoming_holidays = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holidays - Sunny Polymers Employee Portal</title>
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
                
                <?php echo getNavigationMenu('holidays'); ?>
                
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
                <h1 class="page-title">Government Holidays</h1>
                <p class="page-subtitle">Holidays for <?php echo htmlspecialchars($user_state); ?> (including All India holidays) - <?php echo $current_year; ?></p>
            </div>

            <?php if (!$is_eligible): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Note:</strong> You need at least 1 year of tenure to be eligible for government holidays. 
                Your joining date: <?php 
                    $display_date = 'Not available';
                    if (isset($_SESSION['user_date_of_joining']) && $_SESSION['user_date_of_joining']) {
                        $display_date = date('d M Y', strtotime($_SESSION['user_date_of_joining']));
                    } elseif ($user_result && $user_result['date_of_joining']) {
                        $display_date = date('d M Y', strtotime($user_result['date_of_joining']));
                    }
                    echo $display_date;
                ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-2">
                <!-- Upcoming Holidays -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-alt"></i>
                            Upcoming Holidays
                        </h3>
                    </div>
                    
                    <div class="upcoming-holidays">
                        <?php if ($upcoming_holidays->num_rows > 0): ?>
                            <?php while ($holiday = $upcoming_holidays->fetch_assoc()): ?>
                                <div class="holiday-item">
                                    <div class="holiday-date">
                                        <div class="date-number"><?php echo date('d', strtotime($holiday['date'])); ?></div>
                                        <div class="date-month"><?php echo date('M', strtotime($holiday['date'])); ?></div>
                                    </div>
                                    <div class="holiday-details">
                                        <h4><?php echo htmlspecialchars($holiday['name']); ?></h4>
                                        <p><?php echo date('l, d M Y', strtotime($holiday['date'])); ?></p>
                                        <div class="holiday-badges">
                                            <?php if ($holiday['state'] == 'All India'): ?>
                                                <span class="badge badge-info">All India</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary"><?php echo htmlspecialchars($holiday['state']); ?></span>
                                            <?php endif; ?>
                                            <?php 
                                            $days_until = (strtotime($holiday['date']) - strtotime($today)) / (60 * 60 * 24);
                                            if ($days_until == 0): ?>
                                                <span class="badge badge-success">Today</span>
                                            <?php elseif ($days_until == 1): ?>
                                                <span class="badge badge-warning">Tomorrow</span>
                                            <?php else: ?>
                                                <span class="badge badge-info"><?php echo $days_until; ?> days away</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-calendar-times" style="font-size: 3rem; color: #dee2e6;"></i>
                                <p>No upcoming holidays</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- All Holidays -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list"></i>
                            All Holidays (<?php echo $current_year; ?>)
                        </h3>
                    </div>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Holiday Name</th>
                                    <th>Type</th>
                                    <th>Day</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($holidays->num_rows > 0): ?>
                                    <?php while ($holiday = $holidays->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo date('d M Y', strtotime($holiday['date'])); ?></td>
                                            <td><?php echo htmlspecialchars($holiday['name']); ?></td>
                                            <td>
                                                <?php if ($holiday['state'] == 'All India'): ?>
                                                    <span class="badge badge-info">All India</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary"><?php echo htmlspecialchars($holiday['state']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('l', strtotime($holiday['date'])); ?></td>
                                            <td>
                                                <?php 
                                                if ($holiday['date'] < $today): ?>
                                                    <span class="badge badge-secondary">Passed</span>
                                                <?php elseif ($holiday['date'] == $today): ?>
                                                    <span class="badge badge-success">Today</span>
                                                <?php else: ?>
                                                    <span class="badge badge-primary">Upcoming</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            No holidays found for <?php echo htmlspecialchars($user_state); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Holiday Information -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i>
                        Holiday Information
                    </h3>
                </div>
                
                <div class="holiday-info">
                    <div class="info-grid">
                        <div class="info-item">
                            <i class="fas fa-user-check"></i>
                            <div>
                                <h4>Eligibility</h4>
                                <p><?php echo $is_eligible ? 'You are eligible for government holidays' : 'You need 1+ year tenure to be eligible'; ?></p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <h4>State</h4>
                                <p><?php echo htmlspecialchars($user_state); ?></p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-calendar-check"></i>
                            <div>
                                <h4>Total Holidays</h4>
                                <p><?php echo $holidays->num_rows; ?> holidays in <?php echo $current_year; ?></p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h4>Next Holiday</h4>
                                <?php 
                                $upcoming_holidays->data_seek(0);
                                $next_holiday = $upcoming_holidays->fetch_assoc();
                                if ($next_holiday): ?>
                                    <p><?php echo htmlspecialchars($next_holiday['name']); ?> on <?php echo date('d M Y', strtotime($next_holiday['date'])); ?></p>
                                <?php else: ?>
                                    <p>No upcoming holidays</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .upcoming-holidays {
            padding: 10px 0;
        }
        
        .holiday-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .holiday-item:last-child {
            border-bottom: none;
        }
        
        .holiday-date {
            text-align: center;
            min-width: 60px;
        }
        
        .date-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
        }
        
        .date-month {
            font-size: 0.8rem;
            color: #6c757d;
            text-transform: uppercase;
        }
        
        .holiday-details h4 {
            margin: 0 0 5px 0;
            font-size: 1rem;
        }
        
        .holiday-details p {
            margin: 0 0 5px 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .holiday-info {
            padding: 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-item i {
            font-size: 1.5rem;
            color: #007bff;
            margin-top: 5px;
        }
        
        .info-item h4 {
            margin: 0 0 5px 0;
            font-size: 1rem;
        }
        
        .info-item p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9rem;
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
        
        .badge-info {
            background: #17a2b8;
            color: white;
        }
        
        .badge-primary {
            background: #007bff;
            color: white;
        }
        
        .badge-secondary {
            background: #6c757d;
            color: white;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        .holiday-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        
        .holiday-badges .badge {
            font-size: 0.7rem;
            padding: 3px 6px;
        }
    </style>
    <?php echo getNotificationScripts(); ?>
</body>
</html> 