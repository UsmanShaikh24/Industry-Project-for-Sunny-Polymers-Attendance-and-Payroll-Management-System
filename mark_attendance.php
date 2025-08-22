<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/notifications.php';
require_once 'includes/navigation.php';

// Set timezone to India
date_default_timezone_set('Asia/Kolkata');

// Require authentication
require_auth();

// Get user's assigned site
$user_site = get_user_site($_SESSION['user_id']);

if (!$user_site) {
    header("Location: dashboard.php?error=no_site_assigned");
    exit();
}

$message = '';
$message_type = '';

// Handle attendance marking
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action']; // 'check_in' or 'check_out'
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $current_time = date('H:i:s');
    $current_date = date('Y-m-d');
    
    // Calculate distance from assigned site
    $distance = calculate_distance($latitude, $longitude, $user_site['latitude'], $user_site['longitude']);
    $distance_meters = $distance * 1000; // Convert to meters
    
    // Check if within 100 meters
    if ($distance_meters > 1500) {
        $message = "You are too far from your assigned site. Distance: " . round($distance_meters, 2) . " meters (must be within 1.5 km)";
        $message_type = 'danger';
    } else {
        // Check if attendance already exists for today
        $stmt = $conn->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ?");
        $stmt->bind_param("is", $_SESSION['user_id'], $current_date);
        $stmt->execute();
        $existing_attendance = $stmt->get_result()->fetch_assoc();
        
        if ($action == 'check_in') {
            if ($existing_attendance && $existing_attendance['check_in_time']) {
                $message = "You have already checked in today!";
                $message_type = 'warning';
            } else {
                if ($existing_attendance) {
                    // Update existing record
                    $stmt = $conn->prepare("UPDATE attendance SET check_in_time = ?, check_in_lat = ?, check_in_lng = ?, status = 'present' WHERE user_id = ? AND date = ?");
                    $stmt->bind_param("sddss", $current_time, $latitude, $longitude, $_SESSION['user_id'], $current_date);
                } else {
                    // Create new record
                    $stmt = $conn->prepare("INSERT INTO attendance (user_id, date, check_in_time, check_in_lat, check_in_lng, status) VALUES (?, ?, ?, ?, ?, 'present')");
                    $stmt->bind_param("issdd", $_SESSION['user_id'], $current_date, $current_time, $latitude, $longitude);
                }
                
                if ($stmt->execute()) {
                    $message = "Check-in successful! Time: " . $current_time;
                    $message_type = 'success';
                } else {
                    $message = "Error marking attendance. Please try again.";
                    $message_type = 'danger';
                }
            }
        } elseif ($action == 'check_out') {
            if (!$existing_attendance || !$existing_attendance['check_in_time']) {
                $message = "You need to check in first!";
                $message_type = 'warning';
            } elseif ($existing_attendance['check_out_time']) {
                $message = "You have already checked out today!";
                $message_type = 'warning';
            } else {
                // Calculate overtime hours
                $check_in_time = strtotime($existing_attendance['check_in_time']);
                $check_out_time = strtotime($current_time);
                $total_seconds = $check_out_time - $check_in_time;
                $total_hours = $total_seconds / 3600;
                
                // Standard working hours (8 hours with 1 hour break)
                $standard_hours = 8;
                $overtime_hours = max(0, $total_hours - $standard_hours);
                
                // Get user's overtime rate
                $stmt = $conn->prepare("SELECT overtime_rate FROM users WHERE id = ?");
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $user_result = $stmt->get_result()->fetch_assoc();
                $overtime_rate = $user_result['overtime_rate'] ?? 0;
                
                // Update attendance with overtime calculation
                $stmt = $conn->prepare("UPDATE attendance SET check_out_time = ?, check_out_lat = ?, check_out_lng = ?, overtime_hours = ?, overtime_rate = ? WHERE user_id = ? AND date = ?");
                $stmt->bind_param("sddddss", $current_time, $latitude, $longitude, $overtime_hours, $overtime_rate, $_SESSION['user_id'], $current_date);
                
                if ($stmt->execute()) {
                    $overtime_message = "";
                    if ($overtime_hours > 0) {
                        $overtime_message = " Overtime: " . number_format($overtime_hours, 2) . " hours";
                    }
                    $message = "Check-out successful! Time: " . $current_time . $overtime_message;
                    $message_type = 'success';
                } else {
                    $message = "Error marking attendance. Please try again.";
                    $message_type = 'danger';
                }
            }
        }
    }
}

// Get today's attendance status
$today_date = date('Y-m-d');
$stmt = $conn->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ?");
$stmt->bind_param("is", $_SESSION['user_id'], $today_date);
$stmt->execute();
$today_attendance = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Sunny Polymers Employee Portal</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php echo getNotificationStyles(); ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation -->
        <nav class="navbar">
            <?php echo getNavigationMenu('mark_attendance'); ?>
        </nav>
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Mark Attendance</h1>
                <p class="page-subtitle">
                    Assigned Site: <?php echo htmlspecialchars($user_site['name']); ?>
                    (<?php echo htmlspecialchars($user_site['state']); ?>)
                </p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-2">
                <!-- Attendance Status Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Today's Attendance Status</h3>
                    </div>
                    
                    <div class="attendance-status">
                        <div class="status-item">
                            <i class="fas fa-calendar-day"></i>
                            <span>Date: <?php echo date('d M Y'); ?></span>
                        </div>
                        
                        <div class="status-item">
                            <i class="fas fa-clock"></i>
                            <span>Current Time: <span id="current-time"></span></span>
                        </div>
                        
                        <div class="status-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Site: <?php echo htmlspecialchars($user_site['name']); ?></span>
                        </div>
                        
                        <div class="status-item">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Check In: 
                                <?php if ($today_attendance && $today_attendance['check_in_time']): ?>
                                    <span class="status-present"><?php echo date('H:i', strtotime($today_attendance['check_in_time'])); ?></span>
                                <?php else: ?>
                                    <span class="status-absent">Not checked in</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <div class="status-item">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Check Out: 
                                <?php if ($today_attendance && $today_attendance['check_out_time']): ?>
                                    <span class="status-present"><?php echo date('H:i', strtotime($today_attendance['check_out_time'])); ?></span>
                                <?php else: ?>
                                    <span class="status-absent">Not checked out</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="attendance-actions">
                        <?php if (!$today_attendance || !$today_attendance['check_in_time']): ?>
                            <button onclick="markAttendance('check_in')" class="btn btn-primary btn-block">
                                <i class="fas fa-sign-in-alt"></i>
                                Check In
                            </button>
                        <?php elseif (!$today_attendance['check_out_time']): ?>
                            <button onclick="markAttendance('check_out')" class="btn btn-success btn-block">
                                <i class="fas fa-sign-out-alt"></i>
                                Check Out
                            </button>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-check-circle"></i>
                                Attendance completed for today!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Map Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Location Validation</h3>
                    </div>
                    
                    <div id="map">
                        <div id="map-loading" style="display: flex; align-items: center; justify-content: center; height: 300px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <div style="text-align: center; color: #6b7280;">
                                <i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 10px;"></i>
                                <div>Loading map...</div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="location-info">
                        <h4 style="margin: 0 0 15px 0; color: #374151; font-size: 16px; font-weight: 600;">
                            <i class="fas fa-info-circle" style="color: #667eea; margin-right: 8px;"></i>
                            Location Information
                        </h4>
                        <div class="info-item">
                            <i class="fas fa-crosshairs"></i>
                            <span>Your Location: <span id="user-location">Detecting...</span></span>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Site Location: <span style="font-family: monospace;"><?php echo number_format($user_site['latitude'], 6); ?>, <?php echo number_format($user_site['longitude'], 6); ?></span></span>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-ruler"></i>
                            <span>Distance: <span id="distance">Calculating...</span></span>
                        </div>
                        <div id="attendance-warning" class="alert alert-danger" style="display:none; margin-top:10px;"><i class="fas fa-exclamation-triangle"></i> You are outside the attendance area. Please move closer to your assigned site (within 1.5 km) to mark attendance.</div>
                        
                        <div class="location-actions" style="margin-top: 15px;">
                            <button type="button" id="get-location-btn" class="btn btn-secondary" style="width: 100%;">
                                <i class="fas fa-location-arrow"></i>
                                Get My Location
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize map when page is fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Initializing map...');
            
            try {
                const map = L.map('map').setView([<?php echo $user_site['latitude']; ?>, <?php echo $user_site['longitude']; ?>], 16);
                
                // Add tile layer with loading event
                const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                });
                
                tileLayer.on('loading', function() {
                    console.log('Tiles loading...');
                });
                
                tileLayer.on('load', function() {
                    console.log('Tiles loaded successfully');
                    // Hide loading indicator after tiles are loaded
                    const loadingElement = document.getElementById('map-loading');
                    if (loadingElement) {
                        loadingElement.style.display = 'none';
                    }
                });
                
                tileLayer.addTo(map);
                
                // Add site marker with custom icon
                const siteIcon = L.divIcon({
                    className: 'site-marker',
                    html: '<i class="fas fa-building" style="color: #dc2626; font-size: 24px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);"></i>',
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });
                
                const siteMarker = L.marker([<?php echo $user_site['latitude']; ?>, <?php echo $user_site['longitude']; ?>], {
                    icon: siteIcon
                })
                .addTo(map)
                .bindPopup('<strong><?php echo htmlspecialchars($user_site['name']); ?></strong><br><small>Work Site Location</small>')
                .openPopup();
                
                // Add 100m radius circle with better styling
                const radiusCircle = L.circle([<?php echo $user_site['latitude']; ?>, <?php echo $user_site['longitude']; ?>], {
                    color: '#dc2626',
                    weight: 3,
                    fillColor: '#dc2626',
                    fillOpacity: 0.1,
                    radius: 1500 // 1.5 km
                }).addTo(map);
                
                // Add radius label
                radiusCircle.bindTooltip('1.5km Attendance Zone', {
                    permanent: false,
                    direction: 'center',
                    className: 'radius-tooltip'
                });
        
        let userMarker = null;
        window.userPosition = null;
        let connectionLine = null;
        
        // Update current time
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleTimeString('en-IN', { timeZone: 'Asia/Kolkata' });
        }
        updateTime();
        setInterval(updateTime, 1000);
        
        // Function to get user location
        function getUserLocation() {
            if (!navigator.geolocation) {
                document.getElementById('user-location').textContent = 'Geolocation not supported';
                document.getElementById('distance').textContent = 'Not available';
                updateAttendanceWarning(null); // Hide warning and disable buttons
                return;
            }
            
            // Update button state
            const locationBtn = document.getElementById('get-location-btn');
            locationBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting Location...';
            locationBtn.disabled = true;
            
            console.log('Getting user location...');
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    console.log('User location obtained:', position.coords);
                    window.userPosition = position.coords;
                    
                    // Update user location display
                    document.getElementById('user-location').textContent = 
                        position.coords.latitude.toFixed(6) + ', ' + position.coords.longitude.toFixed(6);
                    
                    // Remove existing user marker if any
                    if (userMarker) {
                        map.removeLayer(userMarker);
                    }
                    
                    // Add user marker with custom icon
                    const userIcon = L.divIcon({
                        className: 'user-marker',
                        html: '<i class="fas fa-user-circle" style="color: #059669; font-size: 28px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);"></i>',
                        iconSize: [32, 32],
                        iconAnchor: [16, 16]
                    });
                    
                    userMarker = L.marker([position.coords.latitude, position.coords.longitude], {
                        icon: userIcon
                    })
                    .addTo(map)
                    .bindPopup('<strong>Your Location</strong><br><small>Current Position</small>');
                    
                    // Calculate and display distance
                    const distance = calculateDistance(
                        position.coords.latitude, position.coords.longitude,
                        <?php echo $user_site['latitude']; ?>, <?php echo $user_site['longitude']; ?>
                    );
                    
                    const distanceText = distance < 1000 ? 
                        distance.toFixed(1) + ' meters' : 
                        (distance/1000).toFixed(2) + ' km';
                    document.getElementById('distance').textContent = distanceText;
                    
                    // Remove existing connection line if any
                    if (connectionLine) {
                        map.removeLayer(connectionLine);
                    }
                    
                    // Add connection line between user and site
                    connectionLine = L.polyline([
                        [position.coords.latitude, position.coords.longitude],
                        [<?php echo $user_site['latitude']; ?>, <?php echo $user_site['longitude']; ?>]
                    ], {
                        color: '#667eea',
                        weight: 2,
                        opacity: 0.7,
                        dashArray: '5, 10'
                    }).addTo(map);
                    
                    // Fit map to show both markers with padding
                    const bounds = L.latLngBounds([
                        [position.coords.latitude, position.coords.longitude],
                        [<?php echo $user_site['latitude']; ?>, <?php echo $user_site['longitude']; ?>]
                    ]);
                    map.fitBounds(bounds, { padding: [30, 30] });
                    
                    // Reset button
                    locationBtn.innerHTML = '<i class="fas fa-location-arrow"></i> Get My Location';
                    locationBtn.disabled = false;

                    // Show/hide warning and enable/disable attendance buttons
                    updateAttendanceWarning(distance);
                },
                function(error) {
                    console.error('Geolocation error:', error);
                    let errorMessage = 'Location access denied';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'Location access denied by user';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Location information unavailable';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'Location request timed out';
                            break;
                    }
                    document.getElementById('user-location').textContent = errorMessage;
                    document.getElementById('distance').textContent = 'Unable to calculate';
                    updateAttendanceWarning(null); // Hide warning and disable buttons
                    
                    // Reset button
                    locationBtn.innerHTML = '<i class="fas fa-location-arrow"></i> Get My Location';
                    locationBtn.disabled = false;
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000
                }
            );
        }
        
        // Initial location detection
        getUserLocation();
        
        // Manual location button click handler
        document.getElementById('get-location-btn').addEventListener('click', getUserLocation);
        
            } catch (error) {
                console.error('Map initialization error:', error);
                const loadingElement = document.getElementById('map-loading');
                if (loadingElement) {
                    loadingElement.innerHTML = '<div style="text-align: center; color: #dc2626;"><i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 10px;"></i><br><strong>Map failed to load</strong><br><small style="color: #991b1b;">' + error.message + '</small><br><button onclick="location.reload()" style="margin-top: 10px; padding: 5px 10px; background: #667eea; color: white; border: none; border-radius: 4px; cursor: pointer;">Retry</button></div>';
                }
            }
        });
    </script>
    
    <script>
    // Calculate distance between two points (global)
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371e3; // Earth's radius in meters
        const φ1 = lat1 * Math.PI / 180;
        const φ2 = lat2 * Math.PI / 180;
        const Δφ = (lat2 - lat1) * Math.PI / 180;
        const Δλ = (lon2 - lon1) * Math.PI / 180;
        
        const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                  Math.cos(φ1) * Math.cos(φ2) *
                  Math.sin(Δλ/2) * Math.sin(Δλ/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        
        return R * c;
    }
    
    // Mark attendance (global)
    function markAttendance(action) {
        console.log('markAttendance called with action:', action);
        console.log('window.userPosition:', window.userPosition);
        
        if (!window.userPosition) {
            alert('Please allow location access to mark attendance.');
            return;
        }
        
        const distance = calculateDistance(
            window.userPosition.latitude, window.userPosition.longitude,
            <?php echo $user_site['latitude']; ?>, <?php echo $user_site['longitude']; ?>
        );
        
        console.log('Distance calculated:', distance);
        
        if (distance > 1500) {
            alert('You are too far from your assigned site. Please move closer (within 1.5 km).');
            return;
        }
        
        console.log('Creating form for submission...');
        
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="${action}">
            <input type="hidden" name="latitude" value="${window.userPosition.latitude}">
            <input type="hidden" name="longitude" value="${window.userPosition.longitude}">
        `;
        
        console.log('Form HTML:', form.innerHTML);
        console.log('Submitting form...');
        
        document.body.appendChild(form);
        form.submit();
    }

    // Update warning and button state (global)
    function updateAttendanceWarning(distance) {
        const warning = document.getElementById('attendance-warning');
        const checkInBtn = document.querySelector('.attendance-actions .btn-primary');
        const checkOutBtn = document.querySelector('.attendance-actions .btn-success');
        if (distance === null || isNaN(distance)) {
            warning.style.display = 'block';
            warning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Unable to determine your distance from the site.';
            if (checkInBtn) checkInBtn.disabled = true;
            if (checkOutBtn) checkOutBtn.disabled = true;
        } else if (distance > 1500) {
            warning.style.display = 'block';
            warning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> You are outside the attendance area. Please move closer to your assigned site (within 1.5 km) to mark attendance.';
            if (checkInBtn) checkInBtn.disabled = true;
            if (checkOutBtn) checkOutBtn.disabled = true;
        } else {
            warning.style.display = 'none';
            if (checkInBtn) checkInBtn.disabled = false;
            if (checkOutBtn) checkOutBtn.disabled = false;
        }
    }
    </script>
    
    <style>
        #map {
            height: 300px;
            width: 100%;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
        }
        
        #map-loading {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 10;
            border-radius: 8px;
        }
        
        .attendance-status {
            margin-bottom: 20px;
        }
        
        .status-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            padding: 12px 15px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }
        
        .status-item:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }
        
        .status-item i {
            color: #667eea;
            width: 20px;
            font-size: 16px;
            text-align: center;
        }
        
        .status-item span {
            color: #374151;
            font-weight: 500;
        }
        
        .status-present {
            color: #059669 !important;
            font-weight: 600;
        }
        
        .status-absent {
            color: #dc2626 !important;
            font-weight: 500;
        }
        
        .attendance-actions {
            margin-top: 20px;
        }
        
        .location-info {
            margin-top: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            padding: 8px 12px;
            background: #ffffff;
            border-radius: 6px;
            font-size: 0.9rem;
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }
        
        .info-item:last-child {
            margin-bottom: 0;
        }
        
        .info-item:hover {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }
        
        .info-item i {
            color: #667eea;
            width: 18px;
            font-size: 14px;
            text-align: center;
            flex-shrink: 0;
        }
        
        .info-item span {
            color: #374151;
            font-weight: 500;
        }
        
        .info-item #user-location,
        .info-item #distance {
            color: #059669;
            font-weight: 600;
        }
        
        .user-marker,
        .site-marker {
            background: transparent !important;
            border: none !important;
        }
        
        .radius-tooltip {
            background: rgba(220, 38, 38, 0.9) !important;
            color: white !important;
            border: none !important;
            border-radius: 4px !important;
            font-size: 12px !important;
            padding: 4px 8px !important;
        }
        
        .radius-tooltip::before {
            border-top-color: rgba(220, 38, 38, 0.9) !important;
        }
        
        /* Card improvements */
        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .card > *:not(.card-header):not(#map) {
            padding: 20px;
        }
        
        .card #map {
            margin: 20px;
            margin-bottom: 0;
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 20px;
            border-bottom: none;
        }
        
        .card-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        /* Button improvements */
        .btn-block {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
        }
        
        .btn-secondary:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.4);
        }
        
        .btn-secondary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Alert improvements */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-info {
            background: #dbeafe;
            border: 1px solid #93c5fd;
            color: #1e40af;
        }
        
        /* Grid improvements */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
            
            .card {
                margin: 10px;
            }
            
            .card > *:not(.card-header):not(#map) {
                padding: 15px;
            }
            
            .card #map {
                margin: 15px;
                margin-bottom: 0;
            }
            
            #map {
                height: 250px;
            }
            
            .location-info {
                padding: 12px;
            }
            
            .info-item {
                padding: 6px 10px;
                font-size: 0.85rem;
            }
            
            .status-item {
                padding: 10px 12px;
            }
        }
    </style>
    <?php echo getNotificationScripts(); ?>
</body>
</html> 