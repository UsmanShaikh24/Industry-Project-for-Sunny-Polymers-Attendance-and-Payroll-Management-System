<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/notifications.php';

// Require admin access
require_admin();

$message = '';
$message_type = '';

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$user_id) {
    header("Location: add_user.php?error=invalid_user");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize_input($_POST['name']);
    $mobile = sanitize_input($_POST['mobile']);
    $state = sanitize_input($_POST['state']);
    $date_of_joining = sanitize_input($_POST['date_of_joining']);
    $salary = sanitize_input($_POST['salary']);
    $role = sanitize_input($_POST['role']);
    $site_id = !empty($_POST['site_id']) ? sanitize_input($_POST['site_id']) : null;
    
    // Validate mobile number
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $message = 'Please enter a valid 10-digit mobile number.';
        $message_type = 'danger';
    } else {
        // Check if mobile already exists for other users
        $stmt = $conn->prepare("SELECT id FROM users WHERE mobile = ? AND id != ?");
        $stmt->bind_param("si", $mobile, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $message = 'Mobile number already exists for another user.';
            $message_type = 'danger';
        } else {
            // Update user
            $stmt = $conn->prepare("UPDATE users SET name = ?, mobile = ?, role = ?, state = ?, date_of_joining = ?, salary = ?, site_id = ? WHERE id = ?");
            $stmt->bind_param("sssssdii", $name, $mobile, $role, $state, $date_of_joining, $salary, $site_id, $user_id);
            
            if ($stmt->execute()) {
                $message = "User updated successfully!";
                $message_type = 'success';
                
                // Create notification for the user
                createNotification(
                    $user_id,
                    "Profile Updated",
                    "Your profile information has been updated by admin.",
                    'info',
                    'change_password.php'
                );
            } else {
                $message = "Error updating user. Please try again.";
                $message_type = 'danger';
            }
        }
    }
}

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: add_user.php?error=user_not_found");
    exit();
}

// Get all sites for dropdown
$stmt = $conn->prepare("SELECT id, name, state FROM sites ORDER BY name");
$stmt->execute();
$sites = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Sunny Polymers Employee Portal</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                
                <ul class="navbar-nav">
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    
                    <?php if (is_admin()): ?>
                        <li><a href="add_user.php"><i class="fas fa-user-plus"></i> Add User</a></li>
                        <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                        <li><a href="add_site.php"><i class="fas fa-map-marker-alt"></i> Add Site</a></li>
                        <li><a href="assign_site.php"><i class="fas fa-link"></i> Assign Site</a></li>
                        <li><a href="view_attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
                        <li><a href="manage_leaves.php"><i class="fas fa-calendar-times"></i> Manage Leaves</a></li>
                        <li><a href="generate_salary.php"><i class="fas fa-money-bill-wave"></i> Generate Salary</a></li>
                        <li><a href="manage_advances.php"><i class="fas fa-hand-holding-usd"></i> Advances</a></li>
                        <li><a href="upload_holidays.php"><i class="fas fa-calendar-day"></i> Holidays</a></li>
                    <?php else: ?>
                        <li><a href="mark_attendance.php"><i class="fas fa-clock"></i> Mark Attendance</a></li>
                        <li><a href="apply_leave.php"><i class="fas fa-calendar-plus"></i> Apply Leave</a></li>
                        <li><a href="holidays.php"><i class="fas fa-calendar-day"></i> Holidays</a></li>
                        <li><a href="view_payslip.php"><i class="fas fa-file-invoice"></i> Payslips</a></li>
                        <li><a href="view_advances.php"><i class="fas fa-hand-holding-usd"></i> Advances</a></li>
                        <li><a href="view_attendance.php"><i class="fas fa-history"></i> History</a></li>
                    <?php endif; ?>
                    
                    <li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
                
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
                <h1 class="page-title">Edit User</h1>
                <p class="page-subtitle">Update user information and settings</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-2">
                <!-- Edit User Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-edit"></i>
                            Edit User: <?php echo htmlspecialchars($user['name']); ?>
                        </h3>
                    </div>
                    
                    <div class="card-body">
                        <form method="POST" class="form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Full Name *</label>
                                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="mobile">Mobile Number *</label>
                                    <input type="text" id="mobile" name="mobile" class="form-control" value="<?php echo htmlspecialchars($user['mobile']); ?>" placeholder="10 digits" maxlength="10" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="state">State *</label>
                                    <select id="state" name="state" class="form-control" required>
                                        <option value="">Select State</option>
                                        <option value="Gujarat" <?php echo $user['state'] == 'Gujarat' ? 'selected' : ''; ?>>Gujarat</option>
                                        <option value="Maharashtra" <?php echo $user['state'] == 'Maharashtra' ? 'selected' : ''; ?>>Maharashtra</option>
                                        <option value="Delhi" <?php echo $user['state'] == 'Delhi' ? 'selected' : ''; ?>>Delhi</option>
                                        <option value="Karnataka" <?php echo $user['state'] == 'Karnataka' ? 'selected' : ''; ?>>Karnataka</option>
                                        <option value="Tamil Nadu" <?php echo $user['state'] == 'Tamil Nadu' ? 'selected' : ''; ?>>Tamil Nadu</option>
                                        <option value="Uttar Pradesh" <?php echo $user['state'] == 'Uttar Pradesh' ? 'selected' : ''; ?>>Uttar Pradesh</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="date_of_joining">Date of Joining *</label>
                                    <input type="date" id="date_of_joining" name="date_of_joining" class="form-control" value="<?php echo htmlspecialchars($user['date_of_joining']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="salary">Monthly Salary (₹) *</label>
                                    <input type="number" id="salary" name="salary" class="form-control" value="<?php echo htmlspecialchars($user['salary']); ?>" min="0" step="0.01" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="role">Role *</label>
                                    <select id="role" name="role" class="form-control" required>
                                        <option value="">Select Role</option>
                                        <option value="worker" <?php echo $user['role'] == 'worker' ? 'selected' : ''; ?>>Worker</option>
                                        <option value="staff" <?php echo $user['role'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_id">Assign Site (Optional)</label>
                                <select id="site_id" name="site_id" class="form-control">
                                    <option value="">No Site Assignment</option>
                                    <?php while ($site = $sites->fetch_assoc()): ?>
                                        <option value="<?php echo $site['id']; ?>" <?php echo $user['site_id'] == $site['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($site['name']); ?> (<?php echo htmlspecialchars($site['state']); ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Update User
                                </button>
                                <a href="add_user.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i>
                                    Back to Users
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- User Information -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle"></i>
                            User Information
                        </h3>
                    </div>
                    
                    <div class="card-body">
                        <div class="user-info">
                            <div class="info-item">
                                <label>User ID:</label>
                                <span><?php echo $user['id']; ?></span>
                            </div>
                            <div class="info-item">
                                <label>Created:</label>
                                <span><?php echo date('d M Y', strtotime($user['created_at'])); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Last Updated:</label>
                                <span><?php echo date('d M Y H:i', strtotime($user['updated_at'])); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Current Role:</label>
                                <span class="badge badge-<?php echo $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'staff' ? 'warning' : 'primary'); ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <label>Site Assignment:</label>
                                <span>
                                    <?php 
                                    if ($user['site_id']) {
                                        $site_stmt = $conn->prepare("SELECT name FROM sites WHERE id = ?");
                                        $site_stmt->bind_param("i", $user['site_id']);
                                        $site_stmt->execute();
                                        $site_result = $site_stmt->get_result()->fetch_assoc();
                                        echo htmlspecialchars($site_result['name'] ?? 'Unknown Site');
                                    } else {
                                        echo '<span class="text-muted">Not Assigned</span>';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-item label {
            font-weight: 600;
            color: #495057;
        }
        
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
        
        .badge-danger {
            background: #dc3545;
            color: white;
        }
        
        .text-muted {
            color: #6c757d;
            font-style: italic;
        }
    </style>
    <?php echo getNotificationScripts(); ?>
</body>
</html> 