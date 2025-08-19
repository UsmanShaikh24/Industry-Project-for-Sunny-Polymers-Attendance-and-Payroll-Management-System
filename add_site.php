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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize_input($_POST['name']);
    $address_line1 = sanitize_input($_POST['address_line1']);
    $address_line2 = sanitize_input($_POST['address_line2'] ?? '');
    $city = sanitize_input($_POST['city']);
    $pincode = sanitize_input($_POST['pincode']);
    $state = sanitize_input($_POST['state']);
    $country = sanitize_input($_POST['country'] ?? 'India');
    $latitude = sanitize_input($_POST['latitude']);
    $longitude = sanitize_input($_POST['longitude']);
    
    if (empty($name) || empty($address_line1) || empty($city) || empty($pincode) || empty($state) || empty($latitude) || empty($longitude)) {
        $message = 'Please fill all required fields.';
        $message_type = 'danger';
    } else {
        $stmt = $conn->prepare("INSERT INTO sites (name, address_line1, address_line2, city, pincode, state, country, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssdd", $name, $address_line1, $address_line2, $city, $pincode, $state, $country, $latitude, $longitude);
        
        if ($stmt->execute()) {
            $message = "Site added successfully!";
            $message_type = 'success';
            
            // Clear form data
            $_POST = array();
        } else {
            $message = "Error adding site. Please try again.";
            $message_type = 'danger';
        }
    }
}

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

// Get all sites for display
$stmt = $conn->prepare("SELECT s.*, COUNT(u.id) as assigned_workers FROM sites s LEFT JOIN users u ON s.id = u.site_id GROUP BY s.id ORDER BY s.created_at DESC");
$stmt->execute();
$sites = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Site - Sunny Polymers Employee Portal</title>
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
                    <i class="fas fa-users-cog"></i>
                    Sunny Polymers
                </a>
                
                <?php echo getNavigationMenu('add_site'); ?>
                
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
                <h1 class="page-title">Add New Site</h1>
                <p class="page-subtitle">Add work sites with GPS coordinates for attendance tracking</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-2">
                <!-- Add Site Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Add New Site</h3>
                    </div>
                    
                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="name">Site Name *</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" placeholder="e.g., Sunny Polymers - Ahmedabad" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="address_line1">Address Line 1 *</label>
                            <input type="text" id="address_line1" name="address_line1" class="form-control" value="<?php echo isset($_POST['address_line1']) ? htmlspecialchars($_POST['address_line1']) : ''; ?>" placeholder="e.g., 123, Industrial Area" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="address_line2">Address Line 2</label>
                            <input type="text" id="address_line2" name="address_line2" class="form-control" value="<?php echo isset($_POST['address_line2']) ? htmlspecialchars($_POST['address_line2']) : ''; ?>" placeholder="e.g., Near Railway Station">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City *</label>
                                <input type="text" id="city" name="city" class="form-control" value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>" placeholder="e.g., Ahmedabad" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="pincode">Pincode *</label>
                                <input type="text" id="pincode" name="pincode" class="form-control" value="<?php echo isset($_POST['pincode']) ? htmlspecialchars($_POST['pincode']) : ''; ?>" placeholder="e.g., 380001" required>
                            </div>
                        </div>
                        
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
                            <label for="country">Country</label>
                            <input type="text" id="country" name="country" class="form-control" value="<?php echo isset($_POST['country']) ? htmlspecialchars($_POST['country']) : 'India'; ?>" placeholder="e.g., India">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="latitude">Latitude *</label>
                                <input type="number" id="latitude" name="latitude" class="form-control" value="<?php echo isset($_POST['latitude']) ? htmlspecialchars($_POST['latitude']) : ''; ?>" step="any" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="longitude">Longitude *</label>
                                <input type="number" id="longitude" name="longitude" class="form-control" value="<?php echo isset($_POST['longitude']) ? htmlspecialchars($_POST['longitude']) : ''; ?>" step="any" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Map Picker</label>
                            <div id="map" style="height: 300px; margin-bottom: 15px;"></div>
                            <button type="button" id="use-current-location" class="btn btn-secondary" style="margin-bottom: 10px;"><i class="fas fa-location-arrow"></i> Use Current Location</button>
                            <p class="text-muted">Click on the map to set the site location</p>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-map-marker-alt"></i>
                            Add Site
                        </button>
                    </form>
                </div>

                <!-- Sites List -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Sites</h3>
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($site = $sites->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($site['name']); ?></td>
                                <td>
                                    <div style="font-size: 0.9rem;">
                                        <div><?php echo htmlspecialchars($site['address_line1'] ?? ''); ?></div>
                                        <?php if (!empty($site['address_line2'])): ?>
                                            <div><?php echo htmlspecialchars($site['address_line2']); ?></div>
                                        <?php endif; ?>
                                        <div><?php echo htmlspecialchars($site['city'] ?? ''); ?>, <?php echo htmlspecialchars($site['pincode'] ?? ''); ?></div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($site['state']); ?></td>
                                <td>
                                    <small>
                                        <?php echo number_format($site['latitude'], 6); ?>, 
                                        <?php echo number_format($site['longitude'], 6); ?>
                                    </small>
                                </td>
                                    <td>
                                        <span class="badge badge-<?php echo $site['assigned_workers'] > 0 ? 'success' : 'secondary'; ?>">
                                            <?php echo $site['assigned_workers']; ?> workers
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($site['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_site.php?id=<?php echo $site['id']; ?>" class="btn btn-sm btn-primary" title="Edit Site">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($site['assigned_workers'] == 0): ?>
                                                <a href="delete_site.php?id=<?php echo $site['id']; ?>" class="btn btn-sm btn-danger" title="Delete Site" onclick="return confirm('Are you sure you want to delete this site? This action cannot be undone.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="btn btn-sm btn-secondary" title="Cannot delete site with assigned workers" style="cursor: not-allowed; opacity: 0.6;">
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

    <script>
        // Initialize map
        const map = L.map('map').setView([23.0225, 72.5714], 10); // Default to Ahmedabad
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        let marker = null;
        
        // Function to reverse geocode and fill form fields
        function reverseGeocodeAndFillForm(lat, lng) {
            fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&addressdetails=1`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.address) {
                        const address = data.address;
                        
                        // Fill address fields
                        if (address.road && address.house_number) {
                            document.getElementById('address_line1').value = `${address.house_number}, ${address.road}`;
                        } else if (address.road) {
                            document.getElementById('address_line1').value = address.road;
                        } else if (address.suburb) {
                            document.getElementById('address_line1').value = address.suburb;
                        }
                        
                        // Fill address line 2
                        if (address.neighbourhood || address.suburb) {
                            const line2 = [];
                            if (address.neighbourhood) line2.push(address.neighbourhood);
                            if (address.suburb && address.suburb !== address.neighbourhood) line2.push(address.suburb);
                            if (line2.length > 0) {
                                document.getElementById('address_line2').value = line2.join(', ');
                            }
                        }
                        
                        // Fill city
                        if (address.city) {
                            document.getElementById('city').value = address.city;
                        } else if (address.town) {
                            document.getElementById('city').value = address.town;
                        } else if (address.village) {
                            document.getElementById('city').value = address.village;
                        }
                        
                        // Fill pincode
                        if (address.postcode) {
                            document.getElementById('pincode').value = address.postcode;
                        }
                        
                        // Fill state
                        if (address.state) {
                            const stateSelect = document.getElementById('state');
                            for (let i = 0; i < stateSelect.options.length; i++) {
                                if (stateSelect.options[i].text.toLowerCase() === address.state.toLowerCase()) {
                                    stateSelect.selectedIndex = i;
                                    break;
                                }
                            }
                        }
                        
                        // Fill country
                        if (address.country) {
                            document.getElementById('country').value = address.country;
                        }
                    }
                })
                .catch(error => {
                    console.log('Error fetching address details:', error);
                });
        }
        
        // Handle map click
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            
            // Update form fields
            document.getElementById('latitude').value = lat.toFixed(6);
            document.getElementById('longitude').value = lng.toFixed(6);
            
            // Reverse geocode and fill form
            reverseGeocodeAndFillForm(lat, lng);
            
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

        // Use Current Location button
        document.getElementById('use-current-location').addEventListener('click', function() {
            if (navigator.geolocation) {
                // Show loading state
                const button = document.getElementById('use-current-location');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting Location...';
                button.disabled = true;
                
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    // Update form fields
                    document.getElementById('latitude').value = lat.toFixed(6);
                    document.getElementById('longitude').value = lng.toFixed(6);
                    
                    // Move map and marker
                    map.setView([lat, lng], 15);
                    if (marker) {
                        map.removeLayer(marker);
                    }
                    marker = L.marker([lat, lng]).addTo(map);
                    
                    // Reverse geocode and fill all form fields
                    reverseGeocodeAndFillForm(lat, lng);
                    
                    // Restore button
                    button.innerHTML = originalText;
                    button.disabled = false;
                    
                    // Show success message
                    showMessage('Location detected and form auto-filled!', 'success');
                }, function(error) {
                    // Restore button
                    button.innerHTML = originalText;
                    button.disabled = false;
                    
                    let errorMessage = 'Unable to retrieve your location.';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'Location access denied. Please allow location access in your browser settings.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Location information unavailable. Please try again.';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'Location request timed out. Please try again.';
                            break;
                    }
                    showMessage(errorMessage, 'error');
                });
            } else {
                showMessage('Geolocation is not supported by your browser.', 'error');
            }
        });
        
        // Function to show messages
        function showMessage(message, type) {
            // Create message element
            const messageDiv = document.createElement('div');
            messageDiv.className = `alert alert-${type}`;
            messageDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;
            
            // Insert after the form
            const form = document.querySelector('form');
            form.parentNode.insertBefore(messageDiv, form.nextSibling);
            
            // Remove message after 5 seconds
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }
    </script>

    <style>
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
        
        .badge-secondary {
            background: #6c757d;
            color: white;
        }
        
        .text-muted {
            color: #6c757d;
            font-size: 0.875rem;
        }
    </style>
    <?php echo getNotificationScripts(); ?>
</body>
</html> 