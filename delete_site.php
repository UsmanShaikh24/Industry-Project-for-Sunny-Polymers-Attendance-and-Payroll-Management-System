<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/notifications.php';
require_once 'includes/navigation.php';

// Require admin access
require_admin();

// Get site ID from URL
$site_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$site_id) {
    header("Location: add_site.php?error=invalid_site");
    exit();
}

// Get site details
$stmt = $conn->prepare("SELECT s.*, COUNT(u.id) as assigned_workers FROM sites s LEFT JOIN users u ON s.id = u.site_id WHERE s.id = ? GROUP BY s.id");
$stmt->bind_param("i", $site_id);
$stmt->execute();
$site = $stmt->get_result()->fetch_assoc();

if (!$site) {
    header("Location: add_site.php?error=site_not_found");
    exit();
}

// Check if site has assigned workers
if ($site['assigned_workers'] > 0) {
    header("Location: add_site.php?error=site_in_use");
    exit();
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['confirm_delete'])) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Delete the site
            $stmt = $conn->prepare("DELETE FROM sites WHERE id = ?");
            $stmt->bind_param("i", $site_id);
            
            if ($stmt->execute()) {
                // Commit transaction
                $conn->commit();
                
                header("Location: add_site.php?success=site_deleted&name=" . urlencode($site['name']));
                exit();
            } else {
                throw new Exception("Failed to delete site");
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            header("Location: add_site.php?error=delete_failed");
            exit();
        }
    } else {
        // User cancelled deletion
        header("Location: add_site.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Site - Sunny Polymers Employee Portal</title>
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
            <?php echo getNavigationMenu('delete_site'); ?>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Delete Site</h1>
                <p class="page-subtitle">Confirm site deletion</p>
            </div>

            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>
                        Confirm Site Deletion
                    </h3>
                </div>
                
                <div class="card-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> This action cannot be undone. The site will be permanently deleted.
                    </div>
                    
                    <div class="site-details">
                        <h4>Site Information</h4>
                        <div class="detail-item">
                            <label>Site Name:</label>
                            <span><?php echo htmlspecialchars($site['name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>State:</label>
                            <span><?php echo htmlspecialchars($site['state']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Coordinates:</label>
                            <span><?php echo number_format($site['latitude'], 6); ?>, <?php echo number_format($site['longitude'], 6); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Created:</label>
                            <span><?php echo date('d M Y H:i', strtotime($site['created_at'])); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Assigned Workers:</label>
                            <span class="badge badge-success"><?php echo $site['assigned_workers']; ?> workers</span>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> This site has no assigned workers, so it can be safely deleted.
                    </div>
                </div>
                
                <div class="card-footer">
                    <form method="POST" style="display: flex; gap: 10px; justify-content: center;">
                        <button type="submit" name="cancel_delete" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="submit" name="confirm_delete" class="btn btn-danger">
                            <i class="fas fa-trash"></i>
                            Yes, Delete Site
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .site-details {
            margin: 20px 0;
        }
        
        .site-details h4 {
            margin-bottom: 15px;
            color: #495057;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-item label {
            font-weight: 600;
            color: #495057;
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
    </style>
    <?php echo getNotificationScripts(); ?>
</body>
</html> 