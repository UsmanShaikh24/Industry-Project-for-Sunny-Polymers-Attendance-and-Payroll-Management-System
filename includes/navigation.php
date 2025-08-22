<?php
// Navigation template for consistent menu across all pages
function getNavigationMenu($current_page = '') {
    $is_admin = is_admin();
    ob_start();
    ?>
    <link rel="stylesheet" href="assets/navigation.css">
    
    <!-- Navigation Header -->
    <div class="navbar-content">
        <a href="dashboard.php" class="navbar-brand">
            <img src="assets/SUNNY_POLYMERS logo.jpg" alt="Sunny Polymers Logo" class="navbar-logo">
        </a>
        
        <!-- Navigation Menu -->
        <ul class="navbar-nav" id="mobile-nav">
            <li><a href="dashboard.php" <?php echo ($current_page == 'dashboard') ? 'class="active"' : ''; ?>>
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a></li>
            
            <?php if ($is_admin): ?>
                <!-- User Management Dropdown -->
                <li class="nav-dropdown">
                    <a href="#" class="nav-dropdown-toggle">
                        <i class="fas fa-users"></i> Users <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="nav-dropdown-menu">
                        <li><a href="add_user.php" <?php echo ($current_page == 'add_user') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-user-plus"></i> Add User
                        </a></li>
                        <li><a href="manage_users.php" <?php echo ($current_page == 'manage_users') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-users"></i> Manage Users
                        </a></li>
                        <li><a href="admin_reset_password.php" <?php echo ($current_page == 'admin_reset_password') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-key"></i> Reset Staff and Workers Password
                        </a></li>
                    </ul>
                </li>
                
                <!-- Site Management Dropdown -->
                <li class="nav-dropdown">
                    <a href="#" class="nav-dropdown-toggle">
                        <i class="fas fa-map-marker-alt"></i> Sites <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="nav-dropdown-menu">
                        <li><a href="add_site.php" <?php echo ($current_page == 'add_site') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-plus"></i> Add Site
                        </a></li>
                        <li><a href="manage_sites.php" <?php echo ($current_page == 'manage_sites') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-list"></i> Manage Sites
                        </a></li>
                        <li><a href="assign_site.php" <?php echo ($current_page == 'assign_site') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-link"></i> Assign Site
                        </a></li>
                    </ul>
                </li>
                
                <!-- Attendance & Leave -->
                <li class="nav-dropdown">
                    <a href="#" class="nav-dropdown-toggle">
                        <i class="fas fa-calendar"></i> Attendance <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="nav-dropdown-menu">
                        <li><a href="view_attendance.php" <?php echo ($current_page == 'view_attendance') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-calendar-check"></i> View Attendance
                        </a></li>
                        <li><a href="manage_leaves.php" <?php echo ($current_page == 'manage_leaves') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-calendar-times"></i> Manage Leaves
                        </a></li>
                    </ul>
                </li>
                
                <!-- Payroll -->
                <li class="nav-dropdown">
                    <a href="#" class="nav-dropdown-toggle">
                        <i class="fas fa-money-bill-wave"></i> Payroll <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="nav-dropdown-menu">
                        <li><a href="generate_salary.php" <?php echo ($current_page == 'generate_salary') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-file-invoice"></i> Generate Salary Slip
                        </a></li>
                        <li><a href="overtime_management.php" <?php echo ($current_page == 'overtime_management') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-clock"></i> Overtime Management
                        </a></li>
                        <li><a href="overtime_report.php" <?php echo ($current_page == 'overtime_report') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-chart-bar"></i> Overtime Report
                        </a></li>
                        <li><a href="manage_advances.php" <?php echo ($current_page == 'manage_advances') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-hand-holding-usd"></i> Advances
                        </a></li>
                    </ul>
                </li>
                
                <!-- Settings -->
                <li class="nav-dropdown">
                    <a href="#" class="nav-dropdown-toggle">
                        <i class="fas fa-cog"></i> Settings <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="nav-dropdown-menu">
                        <li><a href="upload_holidays.php" <?php echo ($current_page == 'upload_holidays') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-calendar-day"></i> Holidays
                        </a></li>
                        <li><a href="change_password.php" <?php echo ($current_page == 'change_password') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-key"></i> Change Password
                        </a></li>
                        <li><a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a></li>
                    </ul>
                </li>
            <?php else: ?>
                <!-- Worker/Staff Navigation -->
                <!-- Attendance & Leave -->
                <li class="nav-dropdown">
                    <a href="#" class="nav-dropdown-toggle">
                        <i class="fas fa-calendar"></i> Attendance <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="nav-dropdown-menu">
                        <li><a href="mark_attendance.php" <?php echo ($current_page == 'mark_attendance') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-user-check"></i> Mark Attendance
                        </a></li>
                        <li><a href="view_attendance.php" <?php echo ($current_page == 'view_attendance') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-calendar-check"></i> View Attendance
                        </a></li>
                        <li><a href="apply_leave.php" <?php echo ($current_page == 'apply_leave') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-calendar-plus"></i> Apply Leave
                        </a></li>
                    </ul>
                </li>
                
                <!-- Payroll -->
                <li class="nav-dropdown">
                    <a href="#" class="nav-dropdown-toggle">
                        <i class="fas fa-money-bill-wave"></i> Payroll <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="nav-dropdown-menu">
                        <li><a href="view_payslip.php" <?php echo ($current_page == 'view_payslip') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-file-invoice"></i> Payslips
                        </a></li>
                        <li><a href="view_advances.php" <?php echo ($current_page == 'view_advances') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-hand-holding-usd"></i> Advances
                        </a></li>
                    </ul>
                </li>
                
                <!-- Information -->
                <li class="nav-dropdown">
                    <a href="#" class="nav-dropdown-toggle">
                        <i class="fas fa-info-circle"></i> Information <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="nav-dropdown-menu">
                        <li><a href="holidays.php" <?php echo ($current_page == 'holidays') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-calendar-day"></i> Holidays
                        </a></li>
                    </ul>
                </li>
                
                <!-- Settings -->
                <li class="nav-dropdown">
                    <a href="#" class="nav-dropdown-toggle">
                        <i class="fas fa-cog"></i> Settings <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="nav-dropdown-menu">
                        <li><a href="change_password.php" <?php echo ($current_page == 'change_password') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-key"></i> Change Password
                        </a></li>
                        <li><a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a></li>
                    </ul>
                </li>
            <?php endif; ?>
        </ul>
        
        <!-- Right side container for notifications and mobile menu -->
        <div class="navbar-right">
            <!-- Notification Section -->
            <div class="navbar-notifications">
                <div class="notification-container">
                    <div class="notification-trigger" onclick="toggleNotifications()">
                        <i class="fas fa-bell"></i>
                        <span class="notification-label">Notifications</span>
                        <?php echo getNotificationBadge($_SESSION['user_id'] ?? 0); ?>
                    </div>
                    <?php echo getNotificationDropdown($_SESSION['user_id'] ?? 0); ?>
                </div>
            </div>
            
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
    
    <script>
        function toggleMobileMenu() {
            const nav = document.getElementById('mobile-nav');
            nav.classList.toggle('show');
        }
        
        function toggleNotifications() {
            const dropdown = document.querySelector('.notification-dropdown');
            dropdown.classList.toggle('show');
        }
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const nav = document.getElementById('mobile-nav');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (!nav.contains(event.target) && !toggle.contains(event.target)) {
                nav.classList.remove('show');
            }
        });
        
        // Handle dropdown toggles on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const dropdowns = document.querySelectorAll('.nav-dropdown-toggle');
            
            dropdowns.forEach(function(dropdown) {
                dropdown.addEventListener('click', function(e) {
                    if (window.innerWidth <= 768) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const parent = this.parentElement;
                        const isCurrentlyActive = parent.classList.contains('active');
                        
                        // Close all other dropdowns first
                        const allDropdowns = document.querySelectorAll('.nav-dropdown');
                        allDropdowns.forEach(function(dd) {
                            dd.classList.remove('active');
                        });
                        
                        // Toggle current dropdown - if it wasn't active, make it active
                        if (!isCurrentlyActive) {
                            parent.classList.add('active');
                        }
                        // If it was active, it's now closed due to the removeAll above
                    }
                });
            });
            
            // Close dropdown when clicking on a menu item
            const menuItems = document.querySelectorAll('.nav-dropdown-menu a');
            menuItems.forEach(function(item) {
                item.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        // Close the parent dropdown after a short delay
                        setTimeout(function() {
                            const parent = this.parentElement.parentElement;
                            parent.classList.remove('active');
                        }.bind(this), 100);
                    }
                });
            });
            
            // Close all dropdowns when clicking outside
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 768) {
                    const isDropdownToggle = event.target.closest('.nav-dropdown-toggle');
                    const isDropdownMenu = event.target.closest('.nav-dropdown-menu');
                    
                    if (!isDropdownToggle && !isDropdownMenu) {
                        // Close all dropdowns if clicking outside
                        const allDropdowns = document.querySelectorAll('.nav-dropdown');
                        allDropdowns.forEach(function(dd) {
                            dd.classList.remove('active');
                        });
                    }
                }
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
?>
