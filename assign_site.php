<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/notifications.php';
require_once 'includes/navigation.php';

// Require admin access
require_admin();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = sanitize_input($_POST['user_id']);
    $site_id = sanitize_input($_POST['site_id']);
    
    if (empty($user_id) || empty($site_id)) {
        $message = 'Please select both user and site.';
        $message_type = 'danger';
    } else {
        $stmt = $conn->prepare("UPDATE users SET site_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $site_id, $user_id);
        
        if ($stmt->execute()) {
            $message = "Site assigned successfully!";
            $message_type = 'success';
        } else {
            $message = "Error assigning site. Please try again.";
            $message_type = 'danger';
        }
    }
}

// Get all users (workers and staff only)
$stmt = $conn->prepare("SELECT u.*, s.name as site_name FROM users u LEFT JOIN sites s ON u.site_id = s.id WHERE u.role IN ('worker', 'staff') ORDER BY u.name");
$stmt->execute();
$users = $stmt->get_result();

// Get all sites
$stmt = $conn->prepare("SELECT * FROM sites ORDER BY name");
$stmt->execute();
$sites = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Site - Sunny Polymers Employee Portal</title>
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
                
                <?php echo getNavigationMenu('assign_site'); ?>
                
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
                <h1 class="page-title">Assign Site to Workers</h1>
                <p class="page-subtitle">Assign work sites to workers for attendance tracking</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-2">
                <!-- Assign Site Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Assign Site</h3>
                    </div>
                    
                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="user_id">Select Worker/Staff *</label>
                            <select id="user_id" name="user_id" class="form-control" required>
                                <option value="">Select Worker/Staff</option>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['name']); ?> 
                                        (<?php echo ucfirst($user['role']); ?>) 
                                        - <?php echo htmlspecialchars($user['mobile']); ?>
                                        <?php if ($user['site_name']): ?>
                                            - Currently: <?php echo htmlspecialchars($user['site_name']); ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="site_id">Select Site *</label>
                            <select id="site_id" name="site_id" class="form-control" required>
                                <option value="">Select Site</option>
                                <?php 
                                $sites->data_seek(0); // Reset pointer
                                while ($site = $sites->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $site['id']; ?>">
                                        <?php echo htmlspecialchars($site['name']); ?> 
                                        (<?php echo htmlspecialchars($site['state']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-link"></i>
                            Assign Site
                        </button>
                    </form>
                </div>

                <!-- Current Assignments -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Current Site Assignments</h3>
                    </div>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Worker/Staff</th>
                                    <th>Role</th>
                                    <th>Mobile</th>
                                    <th>Assigned Site</th>
                                    <th>State</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $users->data_seek(0); // Reset pointer
                                while ($user = $users->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['role'] == 'staff' ? 'warning' : 'primary'; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['mobile']); ?></td>
                                    <td>
                                        <?php if ($user['site_name']): ?>
                                            <?php echo htmlspecialchars($user['site_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not Assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['state']); ?></td>
                                    <td>
                                        <?php if ($user['site_name']): ?>
                                            <span class="status-present">Assigned</span>
                                        <?php else: ?>
                                            <span class="status-absent">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
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
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-primary {
            background: #667eea;
            color: white;
        }
        
        .badge-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .text-muted {
            color: #6c757d;
            font-style: italic;
        }
    </style>
    <style>
        .nav-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
            margin-left: 5px;
            font-weight: bold;
        }
    </style>
    <?php echo getNotificationScripts(); ?>
</body>
</html> 