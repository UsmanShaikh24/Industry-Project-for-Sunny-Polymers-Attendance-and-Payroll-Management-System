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

// Check for session messages (from redirect after successful form submission)
if (isset($_SESSION['add_user_message'])) {
    $message = $_SESSION['add_user_message'];
    $message_type = $_SESSION['add_user_message_type'];
    unset($_SESSION['add_user_message']);
    unset($_SESSION['add_user_message_type']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize_input($_POST['name']);
    $mobile = sanitize_input($_POST['mobile']);
    $state = sanitize_input($_POST['state']);
    $date_of_joining = sanitize_input($_POST['date_of_joining']);
    $salary = sanitize_input($_POST['salary']);
    $role = sanitize_input($_POST['role']);
    $site_id = !empty($_POST['site_id']) ? sanitize_input($_POST['site_id']) : null;
    $bank_name = sanitize_input($_POST['bank_name']);
    $account_number = sanitize_input($_POST['account_number']);
    $ifsc_code = sanitize_input($_POST['ifsc_code']);
    $branch_name = sanitize_input($_POST['branch_name']);
    
    // Validate mobile number
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $message = 'Please enter a valid 10-digit mobile number.';
        $message_type = 'danger';
    } else {
        // Check if mobile already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE mobile = ?");
        $stmt->bind_param("s", $mobile);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $message = 'Mobile number already exists.';
            $message_type = 'danger';
        } else {
            // Generate secure default password
            $default_password = generateSecurePassword();
            $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (name, mobile, password, role, state, date_of_joining, salary, site_id, bank_name, account_number, ifsc_code, branch_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssdissss", $name, $mobile, $hashed_password, $role, $state, $date_of_joining, $salary, $site_id, $bank_name, $account_number, $ifsc_code, $branch_name);
            
            if ($stmt->execute()) {
                // Store success message in session and redirect
                $_SESSION['add_user_message'] = "User added successfully! Default password: $default_password. Please share this password securely with the user and ask them to change it on first login.";
                $_SESSION['add_user_message_type'] = 'success';
                header("Location: add_user.php");
                exit();
            } else {
                $message = "Error adding user. Please try again.";
                $message_type = 'danger';
            }
        }
    }
}

// Get all sites for dropdown
$stmt = $conn->prepare("SELECT id, name, state FROM sites ORDER BY name");
$stmt->execute();
$sites = $stmt->get_result();

// Get all users for display
$stmt = $conn->prepare("SELECT u.*, s.name as site_name FROM users u LEFT JOIN sites s ON u.site_id = s.id ORDER BY u.created_at DESC");
$stmt->execute();
$users = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - Sunny Polymers Employee Portal</title>
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
                
                <?php echo getNavigationMenu('add_user'); ?>
                
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
                <h1 class="page-title">Add New User</h1>
                <p class="page-subtitle">Add workers, staff, or admin users to the system</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <?php if ($_GET['success'] == 'user_deleted'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        User "<?php echo htmlspecialchars($_GET['name'] ?? ''); ?>" has been successfully deleted.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <?php if ($_GET['error'] == 'invalid_user'): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Invalid user ID provided.
                    </div>
                <?php elseif ($_GET['error'] == 'user_not_found'): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        User not found.
                    </div>
                <?php elseif ($_GET['error'] == 'cannot_delete_self'): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        You cannot delete your own account.
                    </div>
                <?php elseif ($_GET['error'] == 'delete_failed'): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Failed to delete user. Please try again.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="grid grid-2">
                <!-- Add User Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Add New User</h3>
                    </div>
                    
                    <form method="POST" class="form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" class="form-control" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="mobile">Mobile Number *</label>
                                <input type="text" id="mobile" name="mobile" class="form-control" value="<?php echo isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile']) : ''; ?>" placeholder="10 digits" maxlength="10" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="state">State *</label>
                                <select id="state" name="state" class="form-control" required>
                                    <option value="">Select State</option>
                                    <option value="Gujarat" <?php echo (isset($_POST['state']) && $_POST['state'] == 'Gujarat') ? 'selected' : ''; ?>>Gujarat</option>
                                    <option value="Maharashtra" <?php echo (isset($_POST['state']) && $_POST['state'] == 'Maharashtra') ? 'selected' : ''; ?>>Maharashtra</option>
                                    <option value="Delhi" <?php echo (isset($_POST['state']) && $_POST['state'] == 'Delhi') ? 'selected' : ''; ?>>Delhi</option>
                                    <option value="Karnataka" <?php echo (isset($_POST['state']) && $_POST['state'] == 'Karnataka') ? 'selected' : ''; ?>>Karnataka</option>
                                    <option value="Tamil Nadu" <?php echo (isset($_POST['state']) && $_POST['state'] == 'Tamil Nadu') ? 'selected' : ''; ?>>Tamil Nadu</option>
                                    <option value="Uttar Pradesh" <?php echo (isset($_POST['state']) && $_POST['state'] == 'Uttar Pradesh') ? 'selected' : ''; ?>>Uttar Pradesh</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="date_of_joining">Date of Joining *</label>
                                <input type="date" id="date_of_joining" name="date_of_joining" class="form-control" value="<?php echo isset($_POST['date_of_joining']) ? htmlspecialchars($_POST['date_of_joining']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="salary">Monthly Salary (₹) *</label>
                                <input type="number" id="salary" name="salary" class="form-control" value="<?php echo isset($_POST['salary']) ? htmlspecialchars($_POST['salary']) : ''; ?>" min="0" step="0.01" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="role">Role *</label>
                                <select id="role" name="role" class="form-control" required>
                                    <option value="">Select Role</option>
                                    <option value="worker" <?php echo (isset($_POST['role']) && $_POST['role'] == 'worker') ? 'selected' : ''; ?>>Worker</option>
                                    <option value="staff" <?php echo (isset($_POST['role']) && $_POST['role'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
                                    <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="site_id">Assign Site (Optional)</label>
                            <select id="site_id" name="site_id" class="form-control">
                                <option value="">No Site Assignment</option>
                                <?php while ($site = $sites->fetch_assoc()): ?>
                                    <option value="<?php echo $site['id']; ?>" <?php echo (isset($_POST['site_id']) && $_POST['site_id'] == $site['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($site['name']); ?> (<?php echo htmlspecialchars($site['state']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="bank_name">Bank Name</label>
                                <input type="text" id="bank_name" name="bank_name" class="form-control" value="<?php echo isset($_POST['bank_name']) ? htmlspecialchars($_POST['bank_name']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="account_number">Account Number</label>
                                <input type="text" id="account_number" name="account_number" class="form-control" value="<?php echo isset($_POST['account_number']) ? htmlspecialchars($_POST['account_number']) : ''; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="ifsc_code">IFSC Code</label>
                                <input type="text" id="ifsc_code" name="ifsc_code" class="form-control" value="<?php echo isset($_POST['ifsc_code']) ? htmlspecialchars($_POST['ifsc_code']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="branch_name">Branch Name</label>
                                <input type="text" id="branch_name" name="branch_name" class="form-control" value="<?php echo isset($_POST['branch_name']) ? htmlspecialchars($_POST['branch_name']) : ''; ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i>
                            Add User
                        </button>
                    </form>
                </div>

                <!-- Users List -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Users</h3>
                    </div>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Mobile</th>
                                    <th>Role</th>
                                    <th>Site</th>
                                    <th>Salary</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['mobile']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'staff' ? 'warning' : 'primary'); ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['site_name'] ?? 'Not Assigned'); ?></td>
                                    <td>₹<?php echo number_format($user['salary'], 2); ?></td>
                                    <td>
                                        <?php if ($user['role'] == 'admin'): ?>
                                            <span class="status-admin">Admin</span>
                                        <?php elseif ($user['site_id']): ?>
                                            <span class="status-present">Assigned</span>
                                        <?php else: ?>
                                            <span class="status-absent">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary" title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" title="Delete User" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="btn btn-sm btn-secondary" title="Cannot delete yourself" style="cursor: not-allowed;">
                                                    <i class="fas fa-trash"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
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
        
        .badge-danger {
            background: #dc3545;
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 0.75rem;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }
    </style>
    <?php echo getNotificationScripts(); ?>
</body>
</html> 