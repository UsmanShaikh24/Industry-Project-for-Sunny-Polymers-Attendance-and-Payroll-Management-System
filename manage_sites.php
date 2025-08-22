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
    if ($_GET['success'] == 'site_updated') {
        $message = "Site has been successfully updated.";
        $message_type = 'success';
    } elseif ($_GET['success'] == 'site_deleted') {
        $message = "Site '" . htmlspecialchars($_GET['name'] ?? '') . "' has been successfully deleted.";
        $message_type = 'success';
    }
}

if (isset($_GET['error'])) {
    if ($_GET['error'] == 'invalid_site') {
        $message = "Invalid site ID provided.";
        $message_type = 'danger';
    } elseif ($_GET['error'] == 'site_not_found') {
        $message = "Site not found.";
        $message_type = 'danger';
    } elseif ($_GET['error'] == 'delete_failed') {
        $message = "Failed to delete site. Please try again.";
        $message_type = 'danger';
    } elseif ($_GET['error'] == 'site_in_use') {
        $message = "Cannot delete site. It has assigned workers.";
        $message_type = 'danger';
    }
}

// Get all sites with assigned workers count
$stmt = $conn->prepare("SELECT s.*, COUNT(u.id) as assigned_workers FROM sites s LEFT JOIN users u ON s.id = u.site_id GROUP BY s.id ORDER BY s.created_at DESC");
$stmt->execute();
$sites = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sites - Sunny Polymers Employee Portal</title>
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
            <?php echo getNavigationMenu('manage_sites'); ?>
        </nav>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Manage Sites</h1>
                <p class="page-subtitle">View, edit, and manage all work sites</p>
                <div class="page-actions">
                    <a href="add_site.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Site
                    </a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Sites Management Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Sites</h3>
                    <div class="card-actions">
                        <span class="text-muted"><?php echo $sites->num_rows; ?> sites found</span>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Site Name</th>
                                <th>Address</th>
                                <th>State</th>
                                <th>Coordinates</th>
                                <th>Workers</th>
                                <th>Created</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($sites->num_rows > 0): ?>
                                <?php while ($site = $sites->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="site-name">
                                            <strong><?php echo htmlspecialchars($site['name']); ?></strong>
                                            <small class="text-muted">ID: <?php echo $site['id']; ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="site-address">
                                            <?php if (!empty($site['address_line1'])): ?>
                                                <div><?php echo htmlspecialchars($site['address_line1']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($site['address_line2'])): ?>
                                                <div><?php echo htmlspecialchars($site['address_line2']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($site['city']) || !empty($site['pincode'])): ?>
                                                <div>
                                                    <?php if (!empty($site['city'])): ?>
                                                        <?php echo htmlspecialchars($site['city']); ?>
                                                    <?php endif; ?>
                                                    <?php if (!empty($site['pincode'])): ?>
                                                        <?php echo !empty($site['city']) ? ', ' : ''; ?>
                                                        <?php echo htmlspecialchars($site['pincode']); ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($site['state'])): ?>
                                                <div class="text-muted"><?php echo htmlspecialchars($site['state']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo htmlspecialchars($site['state']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="coordinates">
                                            <small>
                                                <?php echo number_format($site['latitude'], 6); ?>, 
                                                <?php echo number_format($site['longitude'], 6); ?>
                                            </small>
                                            <a href="https://maps.google.com/?q=<?php echo $site['latitude']; ?>,<?php echo $site['longitude']; ?>" 
                                               target="_blank" class="btn btn-sm btn-outline-secondary" title="View on Google Maps">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $site['assigned_workers'] > 0 ? 'success' : 'secondary'; ?>">
                                            <?php echo $site['assigned_workers']; ?> workers
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo date('d M Y', strtotime($site['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo date('d M Y', strtotime($site['updated_at'] ?? $site['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_site.php?id=<?php echo $site['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Edit Site">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($site['assigned_workers'] == 0): ?>
                                                <a href="delete_site.php?id=<?php echo $site['id']; ?>" 
                                                   class="btn btn-sm btn-danger" title="Delete Site" 
                                                   onclick="return confirm('Are you sure you want to delete this site? This action cannot be undone.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="btn btn-sm btn-secondary" 
                                                      title="Cannot delete site with assigned workers" 
                                                      style="cursor: not-allowed; opacity: 0.6;">
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
                                        <div class="empty-state">
                                            <i class="fas fa-map-marker-alt" style="font-size: 3rem; color: #dee2e6;"></i>
                                            <p>No sites found</p>
                                            <a href="add_site.php" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Add Your First Site
                                            </a>
                                        </div>
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
        .page-actions {
            margin-top: 1rem;
        }
        
        .site-name {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .site-name small {
            font-size: 0.75rem;
        }
        
        .site-address {
            font-size: 0.875rem;
            line-height: 1.4;
        }
        
        .coordinates {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            align-items: flex-start;
        }
        
        .coordinates small {
            font-family: monospace;
            background: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }
        
        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-secondary {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
        }
        
        .empty-state i {
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            margin-bottom: 1.5rem;
            color: #6c757d;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        .card-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .table-container {
                overflow-x: auto;
            }
            
            .site-address {
                min-width: 200px;
            }
            
            .coordinates {
                min-width: 150px;
            }
        }
    </style>
    
    <?php echo getNotificationScripts(); ?>
</body>
</html>
