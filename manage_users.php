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

// Handle success/error messages
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'user_deleted') {
        $message = "User '" . htmlspecialchars($_GET['name'] ?? '') . "' has been successfully deleted.";
        $message_type = 'success';
    } elseif ($_GET['success'] == 'user_updated') {
        $message = "User has been successfully updated.";
        $message_type = 'success';
    }
}

if (isset($_GET['error'])) {
    if ($_GET['error'] == 'invalid_user') {
        $message = "Invalid user ID provided.";
        $message_type = 'danger';
    } elseif ($_GET['error'] == 'user_not_found') {
        $message = "User not found.";
        $message_type = 'danger';
    } elseif ($_GET['error'] == 'cannot_delete_self') {
        $message = "You cannot delete your own account.";
        $message_type = 'danger';
    } elseif ($_GET['error'] == 'delete_failed') {
        $message = "Failed to delete user. Please try again.";
        $message_type = 'danger';
    }
}

// Handle search and filters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize_input($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

// Build query
$where_conditions = [];
$params = [];
$param_types = '';

if ($search) {
    $where_conditions[] = "(u.name LIKE ? OR u.mobile LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ss';
}

if ($role_filter) {
    $where_conditions[] = "u.role = ?";
    $params[] = $role_filter;
    $param_types .= 's';
}

if ($status_filter) {
    if ($status_filter == 'assigned') {
        $where_conditions[] = "u.site_id IS NOT NULL";
    } elseif ($status_filter == 'unassigned') {
        $where_conditions[] = "u.site_id IS NULL AND u.role != 'admin'";
    }
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get users with filters
$query = "SELECT u.*, s.name as site_name, s.state as site_state 
          FROM users u 
          LEFT JOIN sites s ON u.site_id = s.id 
          $where_clause 
          ORDER BY u.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_users,
    COUNT(CASE WHEN role = 'worker' THEN 1 END) as total_workers,
    COUNT(CASE WHEN role = 'staff' THEN 1 END) as total_staff,
    COUNT(CASE WHEN role = 'admin' THEN 1 END) as total_admins,
    COUNT(CASE WHEN site_id IS NOT NULL THEN 1 END) as assigned_users,
    COUNT(CASE WHEN site_id IS NULL AND role != 'admin' THEN 1 END) as unassigned_users
FROM users";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Sunny Polymers Employee Portal</title>
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
                
                <?php echo getNavigationMenu('manage_users'); ?>
                
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
                <h1 class="page-title">Manage Users</h1>
                <p class="page-subtitle">View, edit, and manage all users in the system</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users" style="color: #667eea;"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-tie" style="color: #28a745;"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['total_workers']; ?></div>
                    <div class="stat-label">Workers</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-cog" style="color: #ffc107;"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['total_staff']; ?></div>
                    <div class="stat-label">Staff</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-shield" style="color: #dc3545;"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['total_admins']; ?></div>
                    <div class="stat-label">Admins</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-map-marker-alt" style="color: #17a2b8;"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['assigned_users']; ?></div>
                    <div class="stat-label">Assigned</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle" style="color: #fd7e14;"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['unassigned_users']; ?></div>
                    <div class="stat-label">Unassigned</div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-search"></i>
                        Search & Filters
                    </h3>
                </div>
                
                <div class="card-body">
                    <form method="GET" class="search-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="search">Search</label>
                                <input type="text" id="search" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or mobile">
                            </div>
                            
                            <div class="form-group">
                                <label for="role">Role</label>
                                <select id="role" name="role" class="form-control">
                                    <option value="">All Roles</option>
                                    <option value="worker" <?php echo $role_filter == 'worker' ? 'selected' : ''; ?>>Worker</option>
                                    <option value="staff" <?php echo $role_filter == 'staff' ? 'selected' : ''; ?>>Staff</option>
                                    <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="assigned" <?php echo $status_filter == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                    <option value="unassigned" <?php echo $status_filter == 'unassigned' ? 'selected' : ''; ?>>Unassigned</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                        Search
                                    </button>
                                    <a href="manage_users.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i>
                                        Clear
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i>
                        Users List (<?php echo $users->num_rows; ?> users found)
                    </h3>
                    <a href="add_user.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i>
                        Add New User
                    </a>
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
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users->num_rows > 0): ?>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                            <small><?php echo htmlspecialchars($user['state']); ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['mobile']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'staff' ? 'warning' : 'primary'); ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['site_name']): ?>
                                            <div class="site-info">
                                                <strong><?php echo htmlspecialchars($user['site_name']); ?></strong>
                                                <small><?php echo htmlspecialchars($user['site_state']); ?></small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Not Assigned</span>
                                        <?php endif; ?>
                                    </td>
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
                                    <td><?php echo date('d M Y', strtotime($user['date_of_joining'])); ?></td>
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
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        <i class="fas fa-users" style="font-size: 3rem; color: #dee2e6; margin-bottom: 10px;"></i>
                                        <p>No users found matching your criteria.</p>
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
        .search-form {
            margin-bottom: 0;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
        }
        
        .user-info, .site-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-info small, .site-info small {
            color: #6c757d;
            font-size: 0.8rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 0.75rem;
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
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .status-admin {
            background-color: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
    <?php echo getNotificationScripts(); ?>
</body>
</html> 