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
    $user_id = sanitize_input($_POST['user_id']);
    $site_id = sanitize_input($_POST['site_id']);
    
    if (empty($user_id) || empty($site_id)) {
        $message = 'Please select both user and site.';
        $message_type = 'danger';
    } else {
        $stmt = $conn->prepare("UPDATE users SET site_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $site_id, $user_id);
        
        if ($stmt->execute()) {
            $message = "Site assigned successfully!";
            $message_type = 'success';
        } else {
            $message = "Error assigning site. Please try again.";
            $message_type = 'danger';
        }
    }
}

// Get all users (workers and staff only)
$stmt = $conn->prepare("SELECT u.*, s.name as site_name FROM users u LEFT JOIN sites s ON u.site_id = s.id WHERE u.role IN ('worker', 'staff') ORDER BY u.name");
$stmt->execute();
$users = $stmt->get_result();

// Get all sites
$stmt = $conn->prepare("SELECT * FROM sites ORDER BY name");
$stmt->execute();
$sites = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Site - Sunny Polymers Employee Portal</title>
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
            <?php echo getNavigationMenu('assign_site'); ?>
        </nav>
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Assign Site to Workers</h1>
                <p class="page-subtitle">Assign work sites to workers for attendance tracking</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-2">
                <!-- Assign Site Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Assign Site</h3>
                    </div>
                    
                    <!-- Search and Filters for Assignment Form -->
                    <div class="assignment-filters">
                        <div class="filter-row">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="userSearchInput" placeholder="Search workers/staff by name or mobile..." class="search-input">
                            </div>
                            
                            <div class="filter-controls">
                                <select id="userRoleFilter" class="filter-select">
                                    <option value="">All Roles</option>
                                    <option value="worker">Worker</option>
                                    <option value="staff">Staff</option>
                                </select>
                                
                                <select id="userStatusFilter" class="filter-select">
                                    <option value="">All Status</option>
                                    <option value="assigned">Currently Assigned</option>
                                    <option value="unassigned">Not Assigned</option>
                                </select>
                                
                                <button id="clearUserFilters" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                        
                        <div class="filter-summary">
                            <span id="userFilterSummary">Showing all workers and staff</span>
                        </div>
                    </div>
                    
                    <!-- Search Instructions -->
                    <div class="search-instructions">
                        <div class="instructions-header">
                            <i class="fas fa-info-circle"></i>
                            <h4>How to Use Search & Filters</h4>
                        </div>
                        <div class="instructions-content">
                            <div class="instruction-item main-tip">
                                <strong>🎯 Easy Peasy Steps:</strong> First search for the worker's name or mobile number, then select them from the filtered dropdown menu!
                            </div>
                            <div class="instruction-item">
                                <strong>🔍 Step 1 - Search:</strong> Type any part of a worker's name or mobile number in the search box
                            </div>
                            <div class="instruction-item">
                                <strong>📋 Step 2 - Select:</strong> Pick the worker from the filtered dropdown menu below
                            </div>
                            <div class="instruction-item">
                                <strong>⚡ Quick Filters:</strong> Use "Role" or "Status" filters to narrow down your search even more
                            </div>
                            <div class="instruction-item">
                                <strong>💡 Example:</strong> Type "John" → See only Johns in dropdown → Select the right John → Easy peasy!
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="user_id">Select Worker/Staff *</label>
                            <select id="user_id" name="user_id" class="form-control" required>
                                <option value="">Select Worker/Staff</option>
                                <?php 
                                $users->data_seek(0); // Reset pointer
                                while ($user = $users->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                            data-name="<?php echo strtolower(htmlspecialchars($user['name'])); ?>"
                                            data-mobile="<?php echo htmlspecialchars($user['mobile']); ?>"
                                            data-role="<?php echo $user['role']; ?>"
                                            data-status="<?php echo $user['site_name'] ? 'assigned' : 'unassigned'; ?>">
                                        <?php echo htmlspecialchars($user['name']); ?> 
                                        (<?php echo ucfirst($user['role']); ?>) 
                                        - <?php echo htmlspecialchars($user['mobile']); ?>
                                        <?php if ($user['site_name']): ?>
                                            - Currently: <?php echo htmlspecialchars($user['site_name']); ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="site_id">Select Site *</label>
                            <select id="site_id" name="site_id" class="form-control" required>
                                <option value="">Select Site</option>
                                <?php 
                                $sites->data_seek(0); // Reset pointer
                                while ($site = $sites->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $site['id']; ?>">
                                        <?php echo htmlspecialchars($site['name']); ?> 
                                        (<?php echo htmlspecialchars($site['state']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-link"></i>
                            Assign Site
                        </button>
                    </form>
                </div>

                <!-- Current Assignments -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Current Site Assignments</h3>
                    </div>
                    
                    <!-- Search and Filters -->
                    <div class="filters-section">
                        <div class="search-filters">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchInput" placeholder="Search by name, mobile, or site..." class="search-input">
                            </div>
                            
                            <div class="filter-controls">
                                <select id="roleFilter" class="filter-select">
                                    <option value="">All Roles</option>
                                    <option value="worker">Worker</option>
                                    <option value="staff">Staff</option>
                                </select>
                                
                                <select id="statusFilter" class="filter-select">
                                    <option value="">All Status</option>
                                    <option value="assigned">Assigned</option>
                                    <option value="unassigned">Unassigned</option>
                                </select>
                                
                                <select id="siteFilter" class="filter-select">
                                    <option value="">All Sites</option>
                                    <?php 
                                    $sites->data_seek(0); // Reset pointer
                                    while ($site = $sites->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars($site['name']); ?>">
                                            <?php echo htmlspecialchars($site['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                
                                <button id="clearFilters" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                        
                        <div class="filter-summary">
                            <span id="filterSummary">Showing all workers and staff</span>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <table class="table" id="assignmentsTable">
                            <thead>
                                <tr>
                                    <th>Worker/Staff</th>
                                    <th>Role</th>
                                    <th>Mobile</th>
                                    <th>Assigned Site</th>
                                    <th>State</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $users->data_seek(0); // Reset pointer
                                while ($user = $users->fetch_assoc()): 
                                ?>
                                <tr class="user-row" 
                                    data-name="<?php echo strtolower(htmlspecialchars($user['name'])); ?>"
                                    data-mobile="<?php echo htmlspecialchars($user['mobile']); ?>"
                                    data-role="<?php echo $user['role']; ?>"
                                    data-site="<?php echo strtolower(htmlspecialchars($user['site_name'] ?? '')); ?>"
                                    data-status="<?php echo $user['site_name'] ? 'assigned' : 'unassigned'; ?>">
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['role'] == 'staff' ? 'warning' : 'primary'; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['mobile']); ?></td>
                                    <td>
                                        <?php if ($user['site_name']): ?>
                                            <?php echo htmlspecialchars($user['site_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not Assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['state']); ?></td>
                                    <td>
                                        <?php if ($user['site_name']): ?>
                                            <span class="status-present">Assigned</span>
                                        <?php else: ?>
                                            <span class="status-absent">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        
                        <div id="noResults" class="no-results" style="display: none;">
                            <i class="fas fa-search"></i>
                            <p>No results found for your search criteria</p>
                            <button id="resetSearch" class="btn btn-primary btn-sm">Reset Search</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-primary {
            background: #667eea;
            color: white;
        }
        
        .badge-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .text-muted {
            color: #6c757d;
            font-style: italic;
        }
        
        .assignment-filters {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
        }
        
        .filter-row {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .search-instructions {
            padding: 20px;
            background: #e8f4fd;
            border-left: 4px solid #17a2b8;
            margin: 20px 0;
            border-radius: 6px;
        }
        
        .instructions-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: #0c5460;
        }
        
        .instructions-header i {
            font-size: 20px;
            color: #17a2b8;
        }
        
        .instructions-header h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #0c5460;
        }
        
        .instructions-content {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .instruction-item {
            font-size: 14px;
            color: #495057;
            line-height: 1.4;
        }
        
        .instruction-item strong {
            color: #0c5460;
        }
        
        .instruction-item.main-tip {
            background: #d1ecf1;
            padding: 12px;
            border-radius: 4px;
            border: 1px solid #bee5eb;
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        .instruction-item.main-tip strong {
            color: #0c5460;
            font-size: 15px;
        }
        
        .filters-section {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .search-filters {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .search-box {
            position: relative;
            max-width: 400px;
        }
        
        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .search-input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .filter-controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            min-width: 120px;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .filter-summary {
            margin-top: 10px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .no-results i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .no-results p {
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        .user-row.hidden {
            display: none;
        }
        
        @media (max-width: 768px) {
            .filter-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-select {
                min-width: auto;
            }
            
            .search-box {
                max-width: 100%;
            }
        }
    </style>
    
    <style>
        .nav-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
            margin-left: 5px;
            font-weight: bold;
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Assignment form filters
            const userSearchInput = document.getElementById('userSearchInput');
            const userRoleFilter = document.getElementById('userRoleFilter');
            const userStatusFilter = document.getElementById('userStatusFilter');
            const clearUserFiltersBtn = document.getElementById('clearUserFilters');
            const userFilterSummary = document.getElementById('userFilterSummary');
            const userSelect = document.getElementById('user_id');
            
            // Table filters
            const searchInput = document.getElementById('searchInput');
            const roleFilter = document.getElementById('roleFilter');
            const statusFilter = document.getElementById('statusFilter');
            const siteFilter = document.getElementById('siteFilter');
            const clearFiltersBtn = document.getElementById('clearFilters');
            const resetSearchBtn = document.getElementById('resetSearch');
            const userRows = document.querySelectorAll('.user-row');
            const noResults = document.getElementById('noResults');
            const filterSummary = document.getElementById('filterSummary');
            
            function filterAssignmentForm() {
                const searchTerm = userSearchInput.value.toLowerCase();
                const roleValue = userRoleFilter.value;
                const statusValue = userStatusFilter.value;
                
                let visibleCount = 0;
                let hiddenCount = 0;
                
                // Filter options in the user select dropdown
                Array.from(userSelect.options).forEach(option => {
                    if (option.value === '') return; // Skip placeholder option
                    
                    const name = option.dataset.name || '';
                    const mobile = option.dataset.mobile || '';
                    const role = option.dataset.role || '';
                    const status = option.dataset.status || '';
                    
                    let shouldShow = true;
                    
                    // Search filter
                    if (searchTerm && !name.includes(searchTerm) && !mobile.includes(searchTerm)) {
                        shouldShow = false;
                    }
                    
                    // Role filter
                    if (roleValue && role !== roleValue) {
                        shouldShow = false;
                    }
                    
                    // Status filter
                    if (statusValue && status !== statusValue) {
                        shouldShow = false;
                    }
                    
                    if (shouldShow) {
                        option.style.display = '';
                        visibleCount++;
                    } else {
                        option.style.display = 'none';
                        hiddenCount++;
                    }
                });
                
                // Update assignment form filter summary
                updateAssignmentFilterSummary(visibleCount, hiddenCount);
            }
            
            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const roleValue = roleFilter.value;
                const statusValue = statusFilter.value;
                const siteValue = siteFilter.value.toLowerCase();
                
                let visibleCount = 0;
                let hiddenCount = 0;
                
                userRows.forEach(row => {
                    const name = row.dataset.name;
                    const mobile = row.dataset.mobile;
                    const role = row.dataset.role;
                    const site = row.dataset.site;
                    const status = row.dataset.status;
                    
                    let shouldShow = true;
                    
                    // Search filter
                    if (searchTerm && !name.includes(searchTerm) && !mobile.includes(searchTerm) && !site.includes(searchTerm)) {
                        shouldShow = false;
                    }
                    
                    // Role filter
                    if (roleValue && role !== roleValue) {
                        shouldShow = false;
                    }
                    
                    // Status filter
                    if (statusValue && status !== statusValue) {
                        shouldShow = false;
                    }
                    
                    // Site filter
                    if (siteValue && site !== siteValue) {
                        shouldShow = false;
                    }
                    
                    if (shouldShow) {
                        row.classList.remove('hidden');
                        visibleCount++;
                    } else {
                        row.classList.add('hidden');
                        hiddenCount++;
                    }
                });
                
                // Show/hide no results message
                if (visibleCount === 0) {
                    noResults.style.display = 'block';
                } else {
                    noResults.style.display = 'none';
                }
                
                // Update filter summary
                updateFilterSummary(visibleCount, hiddenCount);
            }
            
            function updateAssignmentFilterSummary(visible, hidden) {
                const total = visible + hidden;
                let summary = `Showing ${visible} of ${total} workers and staff`;
                
                if (userRoleFilter.value) {
                    summary += ` (Role: ${userRoleFilter.value})`;
                }
                if (userStatusFilter.value) {
                    summary += ` (Status: ${userStatusFilter.value})`;
                }
                if (userSearchInput.value) {
                    summary += ` (Search: "${userSearchInput.value}")`;
                }
                
                userFilterSummary.textContent = summary;
            }
            
            function updateFilterSummary(visible, hidden) {
                const total = visible + hidden;
                let summary = `Showing ${visible} of ${total} workers and staff`;
                
                if (roleFilter.value) {
                    summary += ` (Role: ${roleFilter.value})`;
                }
                if (statusFilter.value) {
                    summary += ` (Status: ${statusFilter.value})`;
                }
                if (siteFilter.value) {
                    summary += ` (Site: ${siteFilter.value})`;
                }
                if (searchInput.value) {
                    summary += ` (Search: "${searchInput.value}")`;
                }
                
                filterSummary.textContent = summary;
            }
            
            function clearAssignmentFilters() {
                userSearchInput.value = '';
                userRoleFilter.value = '';
                userStatusFilter.value = '';
                filterAssignmentForm();
            }
            
            function clearAllFilters() {
                searchInput.value = '';
                roleFilter.value = '';
                statusFilter.value = '';
                siteFilter.value = '';
                filterTable();
            }
            
            // Event listeners for assignment form
            userSearchInput.addEventListener('input', filterAssignmentForm);
            userRoleFilter.addEventListener('change', filterAssignmentForm);
            userStatusFilter.addEventListener('change', filterAssignmentForm);
            clearUserFiltersBtn.addEventListener('click', clearAssignmentFilters);
            
            // Event listeners for table
            searchInput.addEventListener('input', filterTable);
            roleFilter.addEventListener('change', filterTable);
            statusFilter.addEventListener('change', filterTable);
            siteFilter.addEventListener('change', filterTable);
            clearFiltersBtn.addEventListener('click', clearAllFilters);
            resetSearchBtn.addEventListener('click', clearAllFilters);
            
            // Initial filter summaries
            filterAssignmentForm();
            updateFilterSummary(userRows.length, 0);
        });
    </script>
    
    <?php echo getNotificationScripts(); ?>
</body>
</html> 