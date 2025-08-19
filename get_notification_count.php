<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Require authentication
require_auth();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$count = getUnreadNotificationCount($user_id);

echo json_encode(['count' => $count]);
?> 