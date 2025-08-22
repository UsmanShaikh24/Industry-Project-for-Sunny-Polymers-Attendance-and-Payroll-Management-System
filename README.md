# 🏢 Sunny Polymers - Attendance & Payroll Management System

A comprehensive, web-based employee portal for managing attendance, payroll, leaves, and workforce operations. Built with PHP, MySQL, and modern web technologies.

## 🌟 Features

### 👥 **User Management**
- **Multi-role System**: Admin, Staff, and Worker roles with different permissions
- **User Registration**: Add new employees with role assignment
- **Profile Management**: Edit user details, assign sites, manage salaries
- **Password Management**: Admin-controlled password reset system
- **Bank Details**: Store employee banking information for salary processing

### 📍 **Site Management**
- **Geolocation-based**: GPS coordinates for each work site
- **Address Management**: Complete address fields (line 1, line 2, city, pincode, state, country)
- **Auto-fill Location**: Use current GPS location to auto-populate address fields
- **Site Assignment**: Advanced worker assignment with comprehensive search and filtering
- **Manage Sites**: Dedicated site management page with edit, delete, and overview features
- **Smart Search**: Real-time search and filtering for efficient site assignments
- **Timestamp Tracking**: Automatic creation and update timestamps for all sites

### 📅 **Attendance System**
- **GPS Verification**: Check-in/out with location tracking
- **Real-time Monitoring**: Live attendance status for admins
- **Comprehensive View**: Admin can view all attendance records across all dates
- **Status Tracking**: Present, Absent, Late, Half-day statuses
- **Overtime Tracking**: Automatic overtime calculation on check-out
- **Statistics**: Attendance rates, present/absent counts, daily averages

### 💰 **Payroll Management**
- **Salary Calculation**: Automatic salary computation based on attendance
- **Overtime Management**: Complete overtime tracking and rate management
- **Overtime Reports**: Comprehensive overtime analytics with PDF export
- **Advance Management**: Track and manage employee advances
- **Deduction Handling**: Support for negative net salary (advance repayment)
- **PDF Generation**: Professional payslip generation using DOMPDF
- **Download/View**: PDF payslips can be viewed or downloaded

### 🏖️ **Leave Management**
- **Leave Requests**: Employee leave application system
- **Approval Workflow**: Admin approval for leave requests
- **Status Tracking**: Pending, Approved, Rejected statuses
- **Leave Balance**: Track leave days and earned salary calculations

### 🎉 **Holiday System**
- **State-specific Holidays**: Different holidays for different states
- **All India Holidays**: National holidays applicable to all workers
- **Holiday Management**: Admin can add/edit holiday calendars
- **Visual Indicators**: Badge system to distinguish holiday types

### 🔔 **Notification System**
- **Real-time Alerts**: Instant notifications for various events
- **Unread Count**: Track unread notifications
- **Mark as Read**: Individual and bulk notification management
- **Contextual Links**: Direct navigation to relevant pages

### 📱 **Responsive Design**
- **Mobile-First**: Optimized for all device sizes
- **Modern UI**: Clean, intuitive interface with animated dropdown navigation
- **Touch-Friendly**: Optimized for mobile and tablet use
- **Icon-Only Navigation**: Consistent icon-only mobile navigation
- **Smooth Animations**: Animated arrow flips and smooth transitions
- **Cross-Browser**: Compatible with all modern browsers

## 🛠️ **Technology Stack**

### **Backend**
- **PHP 7.4+**: Core application logic
- **MySQL**: Database management
- **Composer**: Dependency management
- **DOMPDF**: PDF generation library

### **Frontend**
- **HTML5**: Semantic markup
- **CSS3**: Modern styling with Flexbox and Grid
- **JavaScript (ES6+)**: Interactive functionality
- **Font Awesome**: Icon library
- **Google Fonts**: Typography (Inter font family)

### **Libraries & Dependencies**
- **dompdf/dompdf**: ^3.1 - Professional PDF generation
- **Leaflet.js**: Interactive maps for location management
- **Nominatim API**: Reverse geocoding for address auto-fill

## 📁 **Project Structure**

```
attendance-system/
├── 📄 Core Files
│   ├── index.php                 # Home page (landing page)
│   ├── login.php                 # User authentication
│   ├── dashboard.php             # Main dashboard
│   └── logout.php                # Session termination
│
├── 👥 User Management
│   ├── add_user.php              # Add new employees
│   ├── manage_users.php          # User administration
│   ├── edit_user.php             # Edit user details
│   ├── delete_user.php           # Remove users
│   └── admin_reset_password.php  # Password reset system
│
├── 📍 Site Management
│   ├── add_site.php              # Add new work sites
│   ├── manage_sites.php          # Comprehensive site management
│   ├── edit_site.php             # Modify site details with location features
│   ├── delete_site.php           # Remove sites
│   └── assign_site.php           # Advanced worker assignment with search
│
├── 📅 Attendance & Leave
│   ├── mark_attendance.php       # Check-in/out system
│   ├── view_attendance.php       # Attendance records
│   ├── apply_leave.php           # Leave applications
│   └── manage_leaves.php         # Leave administration
│
├── 💰 Payroll System
│   ├── generate_salary.php       # Salary generation
│   ├── view_payslip.php          # Payslip viewing
│   ├── generate_pdf_payslip.php  # PDF generation
│   ├── overtime_management.php   # Overtime rate management
│   ├── overtime_report.php       # Overtime analytics & reports
│   ├── manage_advances.php       # Advance management
│   └── view_advances.php         # Advance records
│
├── 🎉 Holiday Management
│   ├── upload_holidays.php       # Holiday administration
│   └── holidays.php              # Holiday display
│
├── 🔔 Notifications
│   ├── get_notification_count.php
│   ├── mark_notification_read.php
│   └── mark_all_notifications_read.php
│
├── 📁 Includes
│   ├── db.php                    # Database connection
│   ├── auth.php                  # Authentication functions
│   ├── navigation.php            # Navigation template
│   └── notifications.php         # Notification functions
│
├── 🎨 Assets
│   ├── style.css                 # Main stylesheet
│   ├── navigation.css            # Navigation and mobile styles
│   ├── script.js                 # JavaScript functions
│   └── favicon.png               # Site icon
│
├── 📊 Database
│   ├── database_schema.sql       # Complete database structure
│   └── composer.json             # PHP dependencies
│
└── 📚 Documentation
    └── README.md                 # This file
```

## 🚀 **Installation**

### **Prerequisites**
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (for dependency management)

### **Step-by-Step Setup**

1. **Clone/Download Project**
   ```bash
   # Download and extract to your web server directory
   # Example: /var/www/html/ or /htdocs/ for XAMPP
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Database Setup**
   ```bash
   # Create MySQL database
   mysql -u root -p
   CREATE DATABASE attendance_system;
   
   # Import schema
   mysql -u root -p attendance_system < database_schema.sql
   ```

4. **Configure Database Connection**
```php
   # Edit includes/db.php
$host = 'localhost';
$username = 'your_db_username';
$password = 'your_db_password';
$database = 'attendance_system';
```

5. **Set Permissions**
   ```bash
   # Ensure web server can write to necessary directories
   chmod 755 -R /path/to/project
   ```

6. **Access Application**
   ```
   http://localhost/your-project-folder/
   ```

## 🔐 **Default Login Credentials**

### **Admin Account**
- **Mobile**: 9999999999
- **Password**: admin123
- **Role**: Admin
- **State**: Gujarat

### **Sample Worker Accounts**
- **Rajesh Kumar**: 9876543210 / admin123
- **Priya Sharma**: 9876543211 / admin123
- **Amit Patel**: 9876543212 / admin123

## 📊 **Database Schema**

### **Core Tables**
- **users**: Employee information, roles, salaries, overtime rates
- **sites**: Work location details with GPS coordinates
- **attendance**: Daily check-in/out records with overtime tracking
- **leave_requests**: Leave applications and approvals
- **holidays**: State-specific and national holidays
- **advances**: Employee advance tracking
- **payslips**: Salary generation records with overtime pay
- **notifications**: System notifications

### **Key Features**
- **Foreign Key Constraints**: Maintains data integrity
- **Indexed Fields**: Optimized for performance
- **Audit Trails**: Created/updated timestamps
- **Soft Deletes**: Safe data removal

## 🎯 **User Roles & Permissions**

### **👑 Admin**
- Full system access
- User management (add/edit/delete)
- Site management
- Attendance monitoring
- Salary generation
- Overtime management and reporting
- Leave approval
- Holiday management
- Password reset for all users

### **👔 Staff**
- Limited administrative access
- View attendance records
- Apply for leaves
- View payslips
- Manage personal information

### **👷 Worker**
- Mark attendance
- Apply for leaves
- View personal payslips
- Check holiday calendar
- Update personal details

## 📱 **Key Features in Detail**

### **Advanced Site Assignment System**
- **Smart Search**: Real-time search by worker name, mobile, or current site
- **Multi-Filter Options**: Filter by role (Worker/Staff), assignment status, specific sites
- **Dropdown Filtering**: Live filtering of assignment dropdown options
- **Easy Workflow**: Search first, then select from filtered results - easy peasy!
- **Visual Instructions**: Built-in help guide for using search and filter features
- **Filter Summary**: Real-time display of current search results count
- **Responsive Design**: Mobile-optimized search and filter interface

### **GPS-Based Attendance**
- **Location Verification**: Ensures workers are at assigned sites
- **Real-time Tracking**: Live location capture during check-in/out
- **Map Integration**: Visual site locations using Leaflet.js
- **Address Auto-fill**: Reverse geocoding for site management

### **Smart Payroll System**
- **Automatic Calculations**: Salary based on attendance and leaves
- **Overtime Integration**: Automatic overtime calculation and pay
- **Advance Integration**: Tracks and deducts advances from salary
- **Negative Salary Support**: Handles cases where advances exceed earnings
- **Professional PDFs**: Company-branded payslips with overtime details

### **Comprehensive Overtime Management**
- **Automatic Calculation**: Overtime hours calculated on check-out (>8 hours = overtime)
- **Flexible Rates**: Individual and global overtime rate management
- **Real-time Tracking**: Live overtime statistics and summaries
- **Detailed Reports**: Monthly overtime analytics with filtering options
- **PDF Export**: Professional overtime reports with employee details
- **Top Earners**: Rankings of highest overtime earners
- **Integration**: Seamless integration with payroll and payslips

### **Responsive Navigation**
- **Dropdown Menus**: Organized, clutter-free navigation with animated arrows
- **Mobile Optimization**: Hamburger menu with touch-friendly interactions
- **Role-based Access**: Different menus for different user types
- **Consistent Layout**: Unified navigation across all pages
- **Smart Toggle**: Click to open/close dropdowns on mobile
- **Icon-Only Mobile**: Clean icon-only notifications and navigation

## 🔧 **Configuration Options**

### **System Settings**
- **Timezone**: Asia/Kolkata (configurable)
- **Currency**: Indian Rupees (₹)
- **Date Format**: Y-m-d (ISO standard)
- **Language**: English

### **PDF Generation**
- **Paper Size**: A4
- **Font**: DejaVu Sans (Unicode support)
- **Orientation**: Portrait
- **Quality**: High resolution

## 📱 **Mobile Features**

### **Responsive Design**
- **Mobile-First Approach**: Optimized for small screens
- **Touch-Friendly Interface**: Large buttons and touch targets
- **Swipe Gestures**: Mobile-optimized interactions
- **Offline Capability**: Basic functionality without internet

### **GPS Integration**
- **Location Services**: Browser-based GPS detection
- **Permission Handling**: Graceful fallback for location access
- **Accuracy Settings**: Configurable location precision
- **Battery Optimization**: Efficient location tracking

## 🚨 **Security Features**

### **Authentication & Authorization**
- **Session Management**: Secure user sessions
- **Password Hashing**: bcrypt encryption
- **Role-based Access**: Granular permission system
- **SQL Injection Prevention**: Prepared statements

### **Data Protection**
- **Input Validation**: Server-side data validation
- **XSS Prevention**: Output escaping
- **CSRF Protection**: Form token validation
- **Secure Headers**: Security-focused HTTP headers

## 🧪 **Testing & Quality Assurance**

### **Browser Compatibility**
- **Chrome**: 90+ (Full support)
- **Firefox**: 88+ (Full support)
- **Safari**: 14+ (Full support)
- **Edge**: 90+ (Full support)

### **Device Testing**
- **Desktop**: Windows, macOS, Linux
- **Mobile**: iOS 14+, Android 8+
- **Tablet**: iPad, Android tablets
- **Responsive**: All screen sizes

## 📈 **Performance Optimization**

### **Database Optimization**
- **Indexed Queries**: Fast data retrieval
- **Connection Pooling**: Efficient database connections
- **Query Optimization**: Minimal database calls
- **Caching**: Session-based caching

### **Frontend Optimization**
- **Minified CSS/JS**: Reduced file sizes
- **Image Optimization**: Compressed assets
- **Lazy Loading**: On-demand content loading
- **CDN Integration**: Fast resource delivery

## 🔄 **Maintenance & Updates**

### **Regular Tasks**
- **Database Backups**: Daily automated backups
- **Log Rotation**: Manage log files
- **Security Updates**: Regular dependency updates
- **Performance Monitoring**: Track system metrics

### **Update Procedures**
- **Backup First**: Always backup before updates
- **Test Environment**: Verify changes in staging
- **Rollback Plan**: Quick recovery procedures
- **User Communication**: Notify users of changes

## 🆘 **Troubleshooting**

### **Common Issues**

#### **PDF Generation Errors**
```bash
# Check DOMPDF installation
composer require dompdf/dompdf

# Verify font support
# Ensure DejaVu Sans font is available
```

#### **Database Connection Issues**
```bash
# Check MySQL service
sudo systemctl status mysql

# Verify credentials in includes/db.php
# Test connection manually
```

#### **GPS Location Issues**
```bash
# Check browser permissions
# Ensure HTTPS for location services
# Verify site coordinates in database
```

#### **Search & Filter Issues**
```bash
# Clear browser cache if filters don't respond
# Check JavaScript console for errors
# Verify dropdown options are populated correctly
# Ensure database has workers/staff data
```

### **Error Logs**
- **PHP Errors**: Check web server error logs
- **Database Errors**: MySQL error log
- **Application Logs**: Custom logging system

## 🤝 **Contributing**

### **Development Guidelines**
- **Code Style**: PSR-12 PHP standards
- **Documentation**: Inline code comments
- **Testing**: Test all changes thoroughly
- **Security**: Follow security best practices

### **Bug Reports**
- **Detailed Description**: Clear issue explanation
- **Steps to Reproduce**: Exact reproduction steps
- **Expected vs Actual**: Clear behavior comparison
- **Environment Details**: System specifications

## 📄 **License**

This project is proprietary software developed for Sunny Polymers. All rights reserved.

### **Documentation**
- **User Manual**: [Link to User Guide]
- **Admin Guide**: [Link to Admin Guide]
- **API Documentation**: [Link to API Docs]

## 🔮 **Future Enhancements**

### **Recent Updates & Improvements**

#### **Site Management Enhancements**
- **Intelligent Search**: Type worker names or mobile numbers to instantly filter assignment options
- **Multi-Criteria Filtering**: Combine search with role and status filters for precise results
- **User-Friendly Instructions**: Built-in guidance with "Easy Peasy Steps" for new users
- **Real-Time Feedback**: Live filter summaries showing current results count
- **Professional Interface**: Clean, modern design with intuitive user experience

#### **Database Schema Optimization**
- **Consolidated Schema**: All database updates centralized in main schema file
- **Timezone Consistency**: Proper timezone handling for accurate timestamps
- **Column Safety**: IF NOT EXISTS clauses for safe database migrations
- **Comprehensive Coverage**: All overtime, allowance, and site fields included

### **Planned Features**
- **Email Integration**: Automated email notifications
- **Mobile App**: Native mobile applications
- **API Development**: RESTful API for integrations
- **Advanced Analytics**: Business intelligence dashboard
- **Multi-language Support**: Internationalization
- **Cloud Deployment**: AWS/Azure integration

### **Technology Upgrades**
- **PHP 8.x**: Latest PHP version support
- **Modern Frontend**: React/Vue.js integration
- **Microservices**: Service-oriented architecture
- **Real-time Updates**: WebSocket integration

---

## 📝 **Changelog**

### **Version 2.2.0** (Current)
- 🔍 **NEW**: Advanced search and filtering system for site assignments
- 📍 **NEW**: Dedicated "Manage Sites" page with comprehensive site overview
- 🎯 **NEW**: Real-time dropdown filtering with visual instructions
- ⏰ **NEW**: Improved timestamp tracking for site updates
- 🗺️ **IMPROVED**: Enhanced "Edit Site" page with current location features
- 📱 **IMPROVED**: Mobile-optimized search interface with responsive design
- 🎨 **IMPROVED**: Professional styling for search and filter components

### **Version 2.1.0**
- 🚀 **NEW**: Complete overtime management system
- 📊 **NEW**: Overtime analytics and reporting with PDF export
- 📱 **IMPROVED**: Enhanced mobile navigation with animated arrows
- 🎯 **IMPROVED**: Smart dropdown toggle behavior for mobile
- 🎨 **IMPROVED**: Icon-only mobile navigation for consistency
- ⚡ **IMPROVED**: Automatic overtime calculation on check-out
- 💰 **IMPROVED**: Overtime integration in payroll and payslips

### **Version 2.0.0**
- ✨ Complete UI redesign with modern navigation
- 🎨 Responsive design for all devices
- 📱 Mobile-optimized interface
- 🔐 Enhanced security features
- 📄 Professional PDF generation
- 🗺️ GPS-based attendance system
- 🎉 Holiday management system
- 🔔 Real-time notifications

### **Version 1.0.0** (Initial)
- Basic attendance tracking
- Simple payroll system
- User management
- Basic reporting

---

**Built with ❤️ for Sunny Polymers by Usman Shaikh and his Team** 
