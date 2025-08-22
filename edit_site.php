<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/notifications.php';
require_once 'includes/navigation.php';

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Set MySQL timezone to match PHP timezone
$conn->query("SET time_zone = '+05:30'");

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
        $stmt = $conn->prepare("UPDATE sites SET name = ?, address_line1 = ?, address_line2 = ?, city = ?, pincode = ?, state = ?, country = ?, latitude = ?, longitude = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("sssssssddi", $name, $address_line1, $address_line2, $city, $pincode, $state, $country, $latitude, $longitude, $site_id);
        
        if ($stmt->execute()) {
            $message = "Site updated successfully!";
            $message_type = 'success';
            
            // Update site data for display
            $site['name'] = $name;
            $site['address_line1'] = $address_line1;
            $site['address_line2'] = $address_line2;
            $site['city'] = $city;
            $site['pincode'] = $pincode;
            $site['state'] = $state;
            $site['country'] = $country;
            $site['latitude'] = $latitude;
            $site['longitude'] = $longitude;
            
            // Get the actual updated timestamp from database
            $stmt = $conn->prepare("SELECT updated_at FROM sites WHERE id = ?");
            $stmt->bind_param("i", $site_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $site['updated_at'] = $row['updated_at'];
            }
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
            <?php echo getNavigationMenu('edit_site'); ?>
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
                            <label for="address_line1">Address Line 1 *</label>
                            <input type="text" id="address_line1" name="address_line1" class="form-control" value="<?php echo htmlspecialchars($site['address_line1'] ?? ''); ?>" placeholder="e.g., 123, Industrial Area" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="address_line2">Address Line 2</label>
                            <input type="text" id="address_line2" name="address_line2" class="form-control" value="<?php echo htmlspecialchars($site['address_line2'] ?? ''); ?>" placeholder="e.g., Near Railway Station">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City *</label>
                                <input type="text" id="city" name="city" class="form-control" value="<?php echo htmlspecialchars($site['city'] ?? ''); ?>" placeholder="e.g., Ahmedabad" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="pincode">Pincode *</label>
                                <input type="text" id="pincode" name="pincode" class="form-control" value="<?php echo htmlspecialchars($site['pincode'] ?? ''); ?>" placeholder="e.g., 380001" required>
                            </div>
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
                        
                        <div class="form-group">
                            <label for="country">Country</label>
                            <input type="text" id="country" name="country" class="form-control" value="<?php echo htmlspecialchars($site['country'] ?? 'India'); ?>" placeholder="e.g., India">
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
                            <button type="button" id="use-current-location" class="btn btn-secondary" style="margin-bottom: 10px;"><i class="fas fa-location-arrow"></i> Use Current Location</button>
                            <p class="text-muted">Click on the map to update the site location</p>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Update Site
                            </button>
                            <a href="manage_sites.php" class="btn btn-secondary">
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
                                <span><?php echo date('d M Y H:i A', strtotime($site['created_at'] . ' UTC')); ?></span>
                                <small class="text-muted">(Raw: <?php echo $site['created_at']; ?>)</small>
                            </div>
                            <div class="info-item">
                                <label>Last Updated:</label>
                                <span><?php echo date('d M Y H:i A', strtotime($site['updated_at'] ?? $site['created_at'] . ' UTC')); ?></span>
                                <small class="text-muted">(Raw: <?php echo $site['updated_at'] ?? $site['created_at']; ?>)</small>
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