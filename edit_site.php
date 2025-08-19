<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/notifications.php';

// Require admin access
require_admin();

$message = '';
$message_type = '';

// Get site ID from URL
$site_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$site_id) {
    header("Location: add_site.php?error=invalid_site");
    exit();
}

// Get site details
$stmt = $conn->prepare("SELECT * FROM sites WHERE id = ?");
$stmt->bind_param("i", $site_id);
$stmt->execute();
$site = $stmt->get_result()->fetch_assoc();

if (!$site) {
    header("Location: add_site.php?error=site_not_found");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize_input($_POST['name']);
    $state = sanitize_input($_POST['state']);
    $latitude = sanitize_input($_POST['latitude']);
    $longitude = sanitize_input($_POST['longitude']);
    
    if (empty($name) || empty($state) || empty($latitude) || empty($longitude)) {
        $message = 'Please fill all required fields.';
        $message_type = 'danger';
    } else {
        $stmt = $conn->prepare("UPDATE sites SET name = ?, state = ?, latitude = ?, longitude = ? WHERE id = ?");
        $stmt->bind_param("ssddi", $name, $state, $latitude, $longitude, $site_id);
        
        if ($stmt->execute()) {
            $message = "Site updated successfully!";
            $message_type = 'success';
            
            // Update site data for display
            $site['name'] = $name;
            $site['state'] = $state;
            $site['latitude'] = $latitude;
            $site['longitude'] = $longitude;
        } else {
            $message = "Error updating site. Please try again.";
            $message_type = 'danger';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Site - Sunny Polymers Employee Portal</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php echo getNotificationStyles(); ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
                
                <ul class="navbar-nav">
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    
                    <?php if (is_admin()): ?>
                        <li><a href="add_user.php"><i class="fas fa-user-plus"></i> Add User</a></li>
                        <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                        <li><a href="add_site.php"><i class="fas fa-map-marker-alt"></i> Add Site</a></li>
                        <li><a href="assign_site.php"><i class="fas fa-link"></i> Assign Site</a></li>
                        <li><a href="view_attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
                        <li><a href="manage_leaves.php"><i class="fas fa-calendar-times"></i> Manage Leaves</a></li>
                        <li><a href="generate_salary.php"><i class="fas fa-money-bill-wave"></i> Generate Salary Slip</a></li>
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
                    
                    <!-- Notification Icon --><li><a href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
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
                <h1 class="page-title">Edit Site</h1>
                <p class="page-subtitle">Update site information and location</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-2">
                <!-- Edit Site Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Edit Site: <?php echo htmlspecialchars($site['name']); ?></h3>
                    </div>
                    
                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="name">Site Name *</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($site['name']); ?>" placeholder="e.g., Sunny Polymers - Ahmedabad" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="state">State *</label>
                            <select id="state" name="state" class="form-control" required>
                                <option value="">Select State</option>
                                <option value="Gujarat" <?php echo $site['state'] == 'Gujarat' ? 'selected' : ''; ?>>Gujarat</option>
                                <option value="Maharashtra" <?php echo $site['state'] == 'Maharashtra' ? 'selected' : ''; ?>>Maharashtra</option>
                                <option value="Delhi" <?php echo $site['state'] == 'Delhi' ? 'selected' : ''; ?>>Delhi</option>
                                <option value="Karnataka" <?php echo $site['state'] == 'Karnataka' ? 'selected' : ''; ?>>Karnataka</option>
                                <option value="Tamil Nadu" <?php echo $site['state'] == 'Tamil Nadu' ? 'selected' : ''; ?>>Tamil Nadu</option>
                                <option value="Uttar Pradesh" <?php echo $site['state'] == 'Uttar Pradesh' ? 'selected' : ''; ?>>Uttar Pradesh</option>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="latitude">Latitude *</label>
                                <input type="number" id="latitude" name="latitude" class="form-control" value="<?php echo htmlspecialchars($site['latitude']); ?>" step="any" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="longitude">Longitude *</label>
                                <input type="number" id="longitude" name="longitude" class="form-control" value="<?php echo htmlspecialchars($site['longitude']); ?>" step="any" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Map Picker</label>
                            <div id="map" style="height: 300px; margin-bottom: 15px;"></div>
                            <p class="text-muted">Click on the map to update the site location</p>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Update Site
                            </button>
                            <a href="add_site.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                Back to Sites
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Site Information -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Site Information</h3>
                    </div>
                    
                    <div class="card-body">
                        <div class="site-info">
                            <div class="info-item">
                                <label>Site ID:</label>
                                <span><?php echo $site['id']; ?></span>
                            </div>
                            <div class="info-item">
                                <label>Created:</label>
                                <span><?php echo date('d M Y H:i', strtotime($site['created_at'])); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Last Updated:</label>
                                <span><?php echo date('d M Y H:i', strtotime($site['updated_at'] ?? $site['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize map with current site location
        const map = L.map('map').setView([<?php echo $site['latitude']; ?>, <?php echo $site['longitude']; ?>], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        let marker = L.marker([<?php echo $site['latitude']; ?>, <?php echo $site['longitude']; ?>]).addTo(map);
        
        // Handle map click
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            
            // Update form fields
            document.getElementById('latitude').value = lat.toFixed(6);
            document.getElementById('longitude').value = lng.toFixed(6);
            
            // Reverse geocode to get state
            fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.address && data.address.state) {
                        const state = data.address.state;
                        const stateSelect = document.getElementById('state');
                        for (let i = 0; i < stateSelect.options.length; i++) {
                            if (stateSelect.options[i].text.toLowerCase() === state.toLowerCase()) {
                                stateSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }
                })
                .catch(error => {
                    console.log('Error fetching state:', error);
                });
            
            // Update marker
            if (marker) {
                map.removeLayer(marker);
            }
            marker = L.marker([lat, lng]).addTo(map);
        });
        
        // Update map when coordinates are manually entered
        document.getElementById('latitude').addEventListener('change', updateMapFromForm);
        document.getElementById('longitude').addEventListener('change', updateMapFromForm);
        
        function updateMapFromForm() {
            const lat = parseFloat(document.getElementById('latitude').value);
            const lng = parseFloat(document.getElementById('longitude').value);
            
            if (!isNaN(lat) && !isNaN(lng)) {
                map.setView([lat, lng], 15);
                
                if (marker) {
                    map.removeLayer(marker);
                }
                marker = L.marker([lat, lng]).addTo(map);
            }
        }
    </script>

    <style>
        .site-info {
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
        
        .form-actions {
            display: flex;
            gap: 10px;
        }
        
        .text-muted {
            color: #6c757d;
            font-size: 0.875rem;
        }
    </style>
    <?php echo getNotificationScripts(); ?>
</body>
</html> 