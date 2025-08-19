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

// Check for session messages (for redirect after successful form submission)
if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    $message_type = $_SESSION['admin_message_type'];
    unset($_SESSION['admin_message']);
    unset($_SESSION['admin_message_type']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = (int)$_POST['user_id'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($user_id) || empty($new_password) || empty($confirm_password)) {
        $message = 'Please fill all fields.';
        $message_type = 'danger';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $message_type = 'danger';
    } elseif (strlen($new_password) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $message_type = 'danger';
    } else {
        // Get user details
        $stmt = $conn->prepare("SELECT name, mobile FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
                         if ($stmt->execute()) {
                 // Create notification for the user
                 createNotification(
                     $user_id,
                     "Password Reset",
                     "Your password has been reset by admin. New password: " . $new_password,
                     'warning',
                     'change_password.php'
                 );
                 
                 // Redirect with success message to prevent form resubmission
                 $_SESSION['admin_message'] = "Password reset successfully for " . htmlspecialchars($user['name']) . " (Mobile: " . htmlspecialchars($user['mobile']) . "). New password: " . $new_password;
                 $_SESSION['admin_message_type'] = 'success';
                 header("Location: admin_reset_password.php");
                 exit();
             } else {
                 $message = 'Error resetting password. Please try again.';
                 $message_type = 'danger';
             }
        } else {
            $message = 'User not found.';
            $message_type = 'danger';
        }
    }
}

// Get all users for dropdown
$stmt = $conn->prepare("SELECT id, name, mobile, role FROM users WHERE role IN ('worker', 'staff') ORDER BY name");
$stmt->execute();
$users = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reset Password - Sunny Polymers Employee Portal</title>
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
                
                                <?php echo getNavigationMenu('admin_reset_password'); ?>
                
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
                <h1 class="page-title">Admin Reset Password</h1>
                <p class="page-subtitle">Reset user passwords and notify them</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-2">
                <!-- Reset Password Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Reset User Password</h3>
                    </div>
                    
                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="user_id">Select User *</label>
                            <select id="user_id" name="user_id" class="form-control" required>
                                <option value="">Choose a user</option>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['name']); ?> 
                                        (<?php echo htmlspecialchars($user['mobile']); ?>) - 
                                        <?php echo ucfirst($user['role']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password *</label>
                            <div style="display: flex; gap: 10px; align-items: end;">
                                <input type="text" id="new_password" name="new_password" class="form-control" placeholder="Enter new password" required style="flex: 1;">
                                <button type="button" onclick="generatePassword()" class="btn btn-secondary" style="white-space: nowrap;">
                                    <i class="fas fa-dice"></i>
                                    Generate
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password *</label>
                            <input type="text" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm new password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key"></i>
                            Reset Password
                        </button>
                    </form>
                </div>

                <!-- Instructions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Instructions</h3>
                    </div>
                    
                    <div class="card-body">
                        <h4>How to Reset User Passwords:</h4>
                        <ol>
                            <li><strong>Select User:</strong> Choose the user whose password needs to be reset</li>
                            <li><strong>Enter New Password:</strong> Create a new password for the user</li>
                            <li><strong>Confirm Password:</strong> Re-enter the password to confirm</li>
                            <li><strong>Submit:</strong> Click "Reset Password" to update</li>
                        </ol>
                        
                        <h4>What Happens:</h4>
                        <ul>
                            <li>✅ User's password is updated in the database</li>
                            <li>✅ User receives a notification about the password reset</li>
                            <li>✅ New password is displayed for admin reference</li>
                            <li>✅ User can login with the new password immediately</li>
                        </ul>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Security Note:</strong> The new password will be visible to the admin. 
                            Advise users to change their password after first login.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Generate password button
        function generatePassword() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let password = '';
            for (let i = 0; i < 8; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('new_password').value = password;
            document.getElementById('confirm_password').value = password;
        }
    </script>
    
    <?php echo getNotificationScripts(); ?>
</body>
</html>
