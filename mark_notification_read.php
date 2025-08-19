<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Require authentication
require_auth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['notification_id'])) {
    $notification_id = (int)$_POST['notification_id'];
    $user_id = $_SESSION['user_id'];
    
    if (markNotificationAsRead($notification_id, $user_id)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to mark notification as read']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?> 