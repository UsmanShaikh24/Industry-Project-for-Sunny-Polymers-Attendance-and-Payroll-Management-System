<?php
// Authentication helper functions

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check if user has specific role
function has_role($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Check if user is admin
function is_admin() {
    return has_role('admin');
}

// Check if user is staff
function is_staff() {
    return has_role('staff');
}

// Check if user is worker
function is_worker() {
    return has_role('worker');
}

// Require authentication
function require_auth() {
    if (!is_logged_in()) {
        header("Location: index.php");
        exit();
    }
}

// Require specific role
function require_role($role) {
    require_auth();
    if (!has_role($role)) {
        header("Location: dashboard.php?error=unauthorized");
        exit();
    }
}

// Require admin access
function require_admin() {
    require_role('admin');
}

// Logout function
function logout() {
    session_start();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Get current user data
function get_current_user_data() {
    if (!is_logged_in()) {
        return null;
    }
    
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Get user's assigned site
function get_user_site($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT s.* FROM sites s 
                           INNER JOIN users u ON s.id = u.site_id 
                           WHERE u.id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?> 