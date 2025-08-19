<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/notifications.php';
require_once 'includes/navigation.php';

// Require admin authentication
require_admin();

// Set timezone to India
date_default_timezone_set('Asia/Kolkata');

$message = '';
$error = '';

// Handle form submission for adding new holiday
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_holiday') {
        $name = trim($_POST['name']);
        $date = $_POST['date'];
        $state = trim($_POST['state']);
        
        if (empty($name) || empty($date) || empty($state)) {
            $error = "All fields are required.";
        } else {
            // Check if holiday already exists for this date and state
            $stmt = $conn->prepare("SELECT id FROM holidays WHERE date = ? AND state = ?");
            $stmt->bind_param("ss", $date, $state);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $error = "A holiday already exists for this date in the selected state.";
            } else {
                $stmt = $conn->prepare("INSERT INTO holidays (name, date, state) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $date, $state);
                
                if ($stmt->execute()) {
                    $message = "Holiday added successfully!";
                } else {
                    $error = "Error adding holiday: " . $conn->error;
                }
            }
        }
    } elseif ($_POST['action'] === 'delete_holiday') {
        $holiday_id = $_POST['holiday_id'];
        
        $stmt = $conn->prepare("DELETE FROM holidays WHERE id = ?");
        $stmt->bind_param("i", $holiday_id);
        
        if ($stmt->execute()) {
            $message = "Holiday deleted successfully!";
        } else {
            $error = "Error deleting holiday: " . $conn->error;
        }
    } elseif ($_POST['action'] === 'import_holidays') {
        $year = $_POST['year'];
        $state = $_POST['import_state'];
        
        if (empty($year) || empty($state)) {
            $error = "Year and state are required for import.";
        } else {
            $imported_count = 0;
            $skipped_count = 0;
            
            // Get government holidays for the specified year and state
            $holidays_data = getGovernmentHolidays($year, $state);
            
            foreach ($holidays_data as $holiday) {
                // Check if holiday already exists
                $stmt = $conn->prepare("SELECT id FROM holidays WHERE date = ? AND state = ?");
                $stmt->bind_param("ss", $holiday['date'], $state);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows == 0) {
                    // Insert new holiday
                    $stmt = $conn->prepare("INSERT INTO holidays (name, date, state) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $holiday['name'], $holiday['date'], $state);
                    
                    if ($stmt->execute()) {
                        $imported_count++;
                    }
                } else {
                    $skipped_count++;
                }
            }
            
            if ($imported_count > 0) {
                $message = "Successfully imported $imported_count holidays for $state ($year).";
                if ($skipped_count > 0) {
                    $message .= " Skipped $skipped_count existing holidays.";
                }
            } else {
                $message = "No new holidays imported. All holidays for $state ($year) already exist.";
            }
        }
    } elseif ($_POST['action'] === 'bulk_delete') {
        $year = $_POST['delete_year'];
        $state = $_POST['delete_state'];
        
        if (empty($year) || empty($state)) {
            $error = "Year and state are required for bulk delete.";
        } else {
            $stmt = $conn->prepare("DELETE FROM holidays WHERE YEAR(date) = ? AND state = ?");
            $stmt->bind_param("ss", $year, $state);
            
            if ($stmt->execute()) {
                $deleted_count = $stmt->affected_rows;
                $message = "Successfully deleted $deleted_count holidays for $state ($year).";
            } else {
                $error = "Error deleting holidays: " . $conn->error;
            }
        }
    }
}

// Function to get government holidays from calendar
function getGovernmentHolidays($year, $state) {
    $holidays = [];
    
    // National holidays (all states)
    $national_holidays = [
        ['name' => 'Republic Day', 'date' => "$year-01-26"],
        ['name' => 'Independence Day', 'date' => "$year-08-15"],
        ['name' => 'Gandhi Jayanti', 'date' => "$year-10-02"],
        ['name' => 'Christmas Day', 'date' => "$year-12-25"]
    ];
    
    // Add national holidays
    foreach ($national_holidays as $holiday) {
        $holidays[] = $holiday;
    }
    
    // State-specific holidays
    $state_holidays = getStateSpecificHolidays($year, $state);
    foreach ($state_holidays as $holiday) {
        $holidays[] = $holiday;
    }
    
    return $holidays;
}

// Function to get state-specific holidays
function getStateSpecificHolidays($year, $state) {
    $holidays = [];
    
    switch ($state) {
        case 'Maharashtra':
            $holidays = [
                ['name' => 'Maharashtra Day', 'date' => "$year-05-01"],
                ['name' => 'Ganesh Chaturthi', 'date' => getGaneshChaturthiDate($year)],
                ['name' => 'Diwali', 'date' => getDiwaliDate($year)],
                ['name' => 'Gudhi Padwa', 'date' => getGudhiPadwaDate($year)],
                ['name' => 'Raksha Bandhan', 'date' => getRakshaBandhanDate($year)],
                ['name' => 'Janmashtami', 'date' => getJanmashtamiDate($year)]
            ];
            break;
            
        case 'Gujarat':
            $holidays = [
                ['name' => 'Gujarat Day', 'date' => "$year-05-01"],
                ['name' => 'Ganesh Chaturthi', 'date' => getGaneshChaturthiDate($year)],
                ['name' => 'Diwali', 'date' => getDiwaliDate($year)],
                ['name' => 'Navratri', 'date' => getNavratriDate($year)],
                ['name' => 'Raksha Bandhan', 'date' => getRakshaBandhanDate($year)],
                ['name' => 'Janmashtami', 'date' => getJanmashtamiDate($year)]
            ];
            break;
            
        case 'Karnataka':
            $holidays = [
                ['name' => 'Karnataka Rajyotsava', 'date' => "$year-11-01"],
                ['name' => 'Ganesh Chaturthi', 'date' => getGaneshChaturthiDate($year)],
                ['name' => 'Diwali', 'date' => getDiwaliDate($year)],
                ['name' => 'Ugadi', 'date' => getUgadiDate($year)],
                ['name' => 'Raksha Bandhan', 'date' => getRakshaBandhanDate($year)],
                ['name' => 'Janmashtami', 'date' => getJanmashtamiDate($year)]
            ];
            break;
            
        case 'Tamil Nadu':
            $holidays = [
                ['name' => 'Pongal', 'date' => getPongalDate($year)],
                ['name' => 'Tamil New Year', 'date' => getTamilNewYearDate($year)],
                ['name' => 'Diwali', 'date' => getDiwaliDate($year)],
                ['name' => 'Vinayagar Chaturthi', 'date' => getGaneshChaturthiDate($year)],
                ['name' => 'Raksha Bandhan', 'date' => getRakshaBandhanDate($year)],
                ['name' => 'Janmashtami', 'date' => getJanmashtamiDate($year)]
            ];
            break;
            
        case 'Kerala':
            $holidays = [
                ['name' => 'Onam', 'date' => getOnamDate($year)],
                ['name' => 'Vishu', 'date' => getVishuDate($year)],
                ['name' => 'Diwali', 'date' => getDiwaliDate($year)],
                ['name' => 'Vinayagar Chaturthi', 'date' => getGaneshChaturthiDate($year)],
                ['name' => 'Raksha Bandhan', 'date' => getRakshaBandhanDate($year)],
                ['name' => 'Janmashtami', 'date' => getJanmashtamiDate($year)]
            ];
            break;
            
        default:
            // For other states, add common festivals
            $holidays = [
                ['name' => 'Diwali', 'date' => getDiwaliDate($year)],
                ['name' => 'Ganesh Chaturthi', 'date' => getGaneshChaturthiDate($year)],
                ['name' => 'Raksha Bandhan', 'date' => getRakshaBandhanDate($year)],
                ['name' => 'Janmashtami', 'date' => getJanmashtamiDate($year)],
                ['name' => 'Holi', 'date' => getHoliDate($year)],
                ['name' => 'Eid al-Fitr', 'date' => getEidAlFitrDate($year)],
                ['name' => 'Eid al-Adha', 'date' => getEidAlAdhaDate($year)]
            ];
            break;
    }
    
    return $holidays;
}

// Helper functions for calculating festival dates (simplified approximations)
function getGaneshChaturthiDate($year) {
    // Ganesh Chaturthi typically falls in August-September
    return "$year-09-05"; // Approximate date
}

function getDiwaliDate($year) {
    // Diwali typically falls in October-November
    return "$year-11-01"; // Approximate date
}

function getNavratriDate($year) {
    // Navratri typically falls in September-October
    return "$year-10-01"; // Approximate date
}

function getRakshaBandhanDate($year) {
    // Raksha Bandhan typically falls in August
    return "$year-08-15"; // Approximate date
}

function getJanmashtamiDate($year) {
    // Janmashtami typically falls in August-September
    return "$year-09-01"; // Approximate date
}

function getUgadiDate($year) {
    // Ugadi typically falls in March-April
    return "$year-04-01"; // Approximate date
}

function getPongalDate($year) {
    // Pongal typically falls in January
    return "$year-01-15"; // Approximate date
}

function getTamilNewYearDate($year) {
    // Tamil New Year typically falls in April
    return "$year-04-14"; // Approximate date
}

function getOnamDate($year) {
    // Onam typically falls in August-September
    return "$year-09-01"; // Approximate date
}

function getVishuDate($year) {
    // Vishu typically falls in April
    return "$year-04-14"; // Approximate date
}

function getHoliDate($year) {
    // Holi typically falls in March
    return "$year-03-15"; // Approximate date
}

function getEidAlFitrDate($year) {
    // Eid al-Fitr typically falls in April-May
    return "$year-05-01"; // Approximate date
}

function getEidAlAdhaDate($year) {
    // Eid al-Adha typically falls in July-August
    return "$year-07-20"; // Approximate date
}

function getGudhiPadwaDate($year) {
    // Gudhi Padwa typically falls in March-April
    return "$year-04-01"; // Approximate date
}

// Get all holidays for the current year
$current_year = date('Y');
$stmt = $conn->prepare("SELECT * FROM holidays WHERE YEAR(date) = ? ORDER BY date ASC");
$stmt->bind_param("s", $current_year);
$stmt->execute();
$holidays = $stmt->get_result();

// Get unique states
$stmt = $conn->prepare("SELECT DISTINCT state FROM holidays ORDER BY state");
$stmt->execute();
$states = $stmt->get_result();

// Get statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM holidays WHERE YEAR(date) = ?");
$stmt->bind_param("s", $current_year);
$stmt->execute();
$total_holidays = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(DISTINCT state) as total FROM holidays WHERE YEAR(date) = ?");
$stmt->bind_param("s", $current_year);
$stmt->execute();
$total_states = $stmt->get_result()->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Holidays - Sunny Polymers Employee Portal</title>
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
                
                <?php echo getNavigationMenu('upload_holidays'); ?>
                
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
                <h1 class="page-title">Manage Holidays</h1>
                <p class="page-subtitle">Add and manage government holidays for different states or All India</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-3">
                <!-- Add New Holiday -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-plus-circle"></i>
                            Add New Holiday
                        </h3>
                    </div>
                    
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="add_holiday">
                            
                            <div class="form-group">
                                <label for="name">Holiday Name *</label>
                                <input type="text" id="name" name="name" class="form-control" required 
                                       placeholder="e.g., Republic Day, Independence Day">
                            </div>
                            
                            <div class="form-group">
                                <label for="date">Date *</label>
                                <input type="date" id="date" name="date" class="form-control" required 
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="state">State *</label>
                                <select id="state" name="state" class="form-control" required>
                                    <option value="">Select State</option>
                                    <option value="Maharashtra">Maharashtra</option>
                                    <option value="Delhi">Delhi</option>
                                    <option value="Karnataka">Karnataka</option>
                                    <option value="Tamil Nadu">Tamil Nadu</option>
                                    <option value="Kerala">Kerala</option>
                                    <option value="Andhra Pradesh">Andhra Pradesh</option>
                                    <option value="Telangana">Telangana</option>
                                    <option value="Gujarat">Gujarat</option>
                                    <option value="Rajasthan">Rajasthan</option>
                                    <option value="Madhya Pradesh">Madhya Pradesh</option>
                                    <option value="Uttar Pradesh">Uttar Pradesh</option>
                                    <option value="West Bengal">West Bengal</option>
                                    <option value="Bihar">Bihar</option>
                                    <option value="Odisha">Odisha</option>
                                    <option value="Assam">Assam</option>
                                    <option value="Punjab">Punjab</option>
                                    <option value="Haryana">Haryana</option>
                                    <option value="Jharkhand">Jharkhand</option>
                                    <option value="Chhattisgarh">Chhattisgarh</option>
                                    <option value="Uttarakhand">Uttarakhand</option>
                                    <option value="Himachal Pradesh">Himachal Pradesh</option>
                                    <option value="Jammu and Kashmir">Jammu and Kashmir</option>
                                    <option value="Goa">Goa</option>
                                    <option value="Sikkim">Sikkim</option>
                                    <option value="Arunachal Pradesh">Arunachal Pradesh</option>
                                    <option value="Manipur">Manipur</option>
                                    <option value="Meghalaya">Meghalaya</option>
                                    <option value="Mizoram">Mizoram</option>
                                    <option value="Nagaland">Nagaland</option>
                                    <option value="Tripura">Tripura</option>
                                    <option value="All India">All India</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Holiday
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Import Holidays -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-download"></i>
                            Import Government Holidays
                        </h3>
                    </div>
                    
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="import_holidays">
                            
                            <div class="form-group">
                                <label for="year">Year *</label>
                                <select id="year" name="year" class="form-control" required>
                                    <option value="">Select Year</option>
                                    <?php for ($y = date('Y'); $y <= date('Y') + 5; $y++): ?>
                                        <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="import_state">State *</label>
                                <select id="import_state" name="import_state" class="form-control" required>
                                    <option value="">Select State</option>
                                    <option value="Maharashtra">Maharashtra</option>
                                    <option value="Delhi">Delhi</option>
                                    <option value="Karnataka">Karnataka</option>
                                    <option value="Tamil Nadu">Tamil Nadu</option>
                                    <option value="Kerala">Kerala</option>
                                    <option value="Andhra Pradesh">Andhra Pradesh</option>
                                    <option value="Telangana">Telangana</option>
                                    <option value="Gujarat">Gujarat</option>
                                    <option value="Rajasthan">Rajasthan</option>
                                    <option value="Madhya Pradesh">Madhya Pradesh</option>
                                    <option value="Uttar Pradesh">Uttar Pradesh</option>
                                    <option value="West Bengal">West Bengal</option>
                                    <option value="Bihar">Bihar</option>
                                    <option value="Odisha">Odisha</option>
                                    <option value="Assam">Assam</option>
                                    <option value="Punjab">Punjab</option>
                                    <option value="Haryana">Haryana</option>
                                    <option value="Jharkhand">Jharkhand</option>
                                    <option value="Chhattisgarh">Chhattisgarh</option>
                                    <option value="Uttarakhand">Uttarakhand</option>
                                    <option value="Himachal Pradesh">Himachal Pradesh</option>
                                    <option value="Jammu and Kashmir">Jammu and Kashmir</option>
                                    <option value="Goa">Goa</option>
                                    <option value="Sikkim">Sikkim</option>
                                    <option value="Arunachal Pradesh">Arunachal Pradesh</option>
                                    <option value="Manipur">Manipur</option>
                                    <option value="Meghalaya">Meghalaya</option>
                                    <option value="Mizoram">Mizoram</option>
                                    <option value="Nagaland">Nagaland</option>
                                    <option value="Tripura">Tripura</option>
                                    <option value="All India">All India</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-download"></i> Import Holidays
                            </button>
                        </form>
                        
                        <div class="import-info">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                This will import national and state-specific government holidays including:
                                <ul style="margin: 5px 0; padding-left: 20px;">
                                    <li>National holidays (Republic Day, Independence Day, etc.)</li>
                                    <li>State-specific holidays</li>
                                    <li>Major religious festivals</li>
                                </ul>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-bar"></i>
                            Holiday Statistics
                        </h3>
                    </div>
                    
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $total_holidays; ?></div>
                            <div class="stat-label">Total Holidays (<?php echo $current_year; ?>)</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $total_states; ?></div>
                            <div class="stat-label">States Covered</div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $holidays->num_rows; ?></div>
                            <div class="stat-label">Holidays Listed</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk Operations -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-trash-alt"></i>
                        Bulk Operations
                    </h3>
                </div>
                
                <div class="card-body">
                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete all holidays for the selected year and state? This action cannot be undone.')">
                        <input type="hidden" name="action" value="bulk_delete">
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="delete_year">Year *</label>
                                <select id="delete_year" name="delete_year" class="form-control" required>
                                    <option value="">Select Year</option>
                                    <?php for ($y = date('Y') - 2; $y <= date('Y') + 2; $y++): ?>
                                        <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="form-group col-md-6">
                                <label for="delete_state">State *</label>
                                <select id="delete_state" name="delete_state" class="form-control" required>
                                    <option value="">Select State</option>
                                    <option value="Maharashtra">Maharashtra</option>
                                    <option value="Delhi">Delhi</option>
                                    <option value="Karnataka">Karnataka</option>
                                    <option value="Tamil Nadu">Tamil Nadu</option>
                                    <option value="Kerala">Kerala</option>
                                    <option value="Andhra Pradesh">Andhra Pradesh</option>
                                    <option value="Telangana">Telangana</option>
                                    <option value="Gujarat">Gujarat</option>
                                    <option value="Rajasthan">Rajasthan</option>
                                    <option value="Madhya Pradesh">Madhya Pradesh</option>
                                    <option value="Uttar Pradesh">Uttar Pradesh</option>
                                    <option value="West Bengal">West Bengal</option>
                                    <option value="Bihar">Bihar</option>
                                    <option value="Odisha">Odisha</option>
                                    <option value="Assam">Assam</option>
                                    <option value="Punjab">Punjab</option>
                                    <option value="Haryana">Haryana</option>
                                    <option value="Jharkhand">Jharkhand</option>
                                    <option value="Chhattisgarh">Chhattisgarh</option>
                                    <option value="Uttarakhand">Uttarakhand</option>
                                    <option value="Himachal Pradesh">Himachal Pradesh</option>
                                    <option value="Jammu and Kashmir">Jammu and Kashmir</option>
                                    <option value="Goa">Goa</option>
                                    <option value="Sikkim">Sikkim</option>
                                    <option value="Arunachal Pradesh">Arunachal Pradesh</option>
                                    <option value="Manipur">Manipur</option>
                                    <option value="Meghalaya">Meghalaya</option>
                                    <option value="Mizoram">Mizoram</option>
                                    <option value="Nagaland">Nagaland</option>
                                    <option value="Tripura">Tripura</option>
                                    <option value="All India">All India</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete All Holidays
                        </button>
                        
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-exclamation-triangle"></i>
                            This will delete ALL holidays for the selected year and state. Use with caution.
                        </small>
                    </form>
                </div>
            </div>

            <!-- All Holidays Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i>
                        All Holidays (<?php echo $current_year; ?>)
                    </h3>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Holiday Name</th>
                                <th>State</th>
                                <th>Day</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($holidays->num_rows > 0): ?>
                                <?php while ($holiday = $holidays->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($holiday['date'])); ?></td>
                                        <td><?php echo htmlspecialchars($holiday['name']); ?></td>
                                        <td>
                                            <?php if ($holiday['state'] == 'All India'): ?>
                                                <span class="badge badge-success">All India</span>
                                            <?php else: ?>
                                                <span class="badge badge-info"><?php echo htmlspecialchars($holiday['state']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('l', strtotime($holiday['date'])); ?></td>
                                        <td>
                                            <?php 
                                            $today = date('Y-m-d');
                                            if ($holiday['date'] < $today): ?>
                                                <span class="badge badge-secondary">Passed</span>
                                            <?php elseif ($holiday['date'] == $today): ?>
                                                <span class="badge badge-success">Today</span>
                                            <?php else: ?>
                                                <span class="badge badge-primary">Upcoming</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" action="" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this holiday?')">
                                                <input type="hidden" name="action" value="delete_holiday">
                                                <input type="hidden" name="holiday_id" value="<?php echo $holiday['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        No holidays found for <?php echo $current_year; ?>
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
        .grid.grid-3 {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-info {
            background: #17a2b8;
            color: white;
        }
        
        .badge-success {
            background: #28a745;
            color: white;
        }
        
        .badge-primary {
            background: #007bff;
            color: white;
        }
        
        .badge-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 0.8rem;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        .import-info {
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 3px solid #17a2b8;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .d-block {
            display: block;
        }
        
        .mt-2 {
            margin-top: 10px;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
    </style>
    <?php echo getNotificationScripts(); ?>
</body>
</html> 