-- Attendance & Payroll Management System Database Schema
-- For Hostinger MySQL Database

-- Create database
CREATE DATABASE IF NOT EXISTS attendance_system;
USE attendance_system;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    mobile VARCHAR(15) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'worker') DEFAULT 'worker',
    designation VARCHAR(100) DEFAULT NULL,
    state VARCHAR(50) NOT NULL,
    date_of_joining DATE NOT NULL,
    salary DECIMAL(10,2) NOT NULL,
    dearness_allowance DECIMAL(10,2) DEFAULT 0.00,
    medical_allowance DECIMAL(10,2) DEFAULT 0.00,
    house_rent_allowance DECIMAL(10,2) DEFAULT 0.00,
    conveyance_allowance DECIMAL(10,2) DEFAULT 0.00,
    pf_uan_number VARCHAR(30) DEFAULT NULL,
    overtime_rate DECIMAL(10,2) DEFAULT 0.00,
    bank_name VARCHAR(100) DEFAULT NULL,
    account_number VARCHAR(30) DEFAULT NULL,
    ifsc_code VARCHAR(20) DEFAULT NULL,
    branch_name VARCHAR(100) DEFAULT NULL,
    site_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_mobile (mobile),
    INDEX idx_role (role),
    INDEX idx_site (site_id)
);

-- Sites table
CREATE TABLE sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NOT NULL,
    pincode VARCHAR(10) NOT NULL,
    state VARCHAR(50) NOT NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'India',
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_state (state),
    INDEX idx_location (latitude, longitude),
    INDEX idx_city (city)
);

-- Attendance table
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    check_in_time TIME NULL,
    check_out_time TIME NULL,
    check_in_lat DECIMAL(10,8) NULL,
    check_in_lng DECIMAL(11,8) NULL,
    check_out_lat DECIMAL(10,8) NULL,
    check_out_lng DECIMAL(11,8) NULL,
    status ENUM('present', 'absent', 'late', 'half_day') DEFAULT 'absent',
    overtime_hours DECIMAL(4,2) DEFAULT 0.00,
    overtime_rate DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, date),
    INDEX idx_date (date),
    INDEX idx_user_date (user_id, date)
);

-- Leave requests table
CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
);

-- Holidays table
CREATE TABLE holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    state VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_state_date (state, date),
    INDEX idx_state_date (state, date)
);

-- Advances table
CREATE TABLE advances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reason TEXT NOT NULL,
    date_given DATE NOT NULL,
    is_repaid BOOLEAN DEFAULT FALSE,
    repaid_amount DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_date (date_given)
);

-- Payslips table
CREATE TABLE payslips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    month VARCHAR(2) NOT NULL,
    year VARCHAR(4) NOT NULL,
    basic_salary DECIMAL(10,2) NOT NULL,
    present_days INT NOT NULL,
    leave_days INT DEFAULT 0,
    total_days INT NOT NULL,
    earned_salary DECIMAL(10,2) NOT NULL,
    overtime_hours DECIMAL(6,2) DEFAULT 0.00,
    overtime_pay DECIMAL(10,2) DEFAULT 0.00,
    advances DECIMAL(10,2) DEFAULT 0.00,
    professional_tax DECIMAL(10,2) DEFAULT 0.00,
    pf_amount DECIMAL(10,2) DEFAULT 0.00,
    pf_previous_balance DECIMAL(10,2) DEFAULT 0.00,
    deductions DECIMAL(10,2) DEFAULT 0.00,
    net_salary DECIMAL(10,2) NOT NULL,
    generated_by INT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_month_year (user_id, month, year),
    INDEX idx_month_year (month, year)
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    link VARCHAR(255) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created_at (created_at)
);

-- Insert default admin user
-- Password: admin123 (hashed with password_hash)
INSERT INTO users (name, mobile, password, role, state, date_of_joining, salary) VALUES 
('Admin User', '9999999999', '$2y$10$BsjfhQeufJC3xSwS/MGbkOdThmp4JWMRrDioOc3g63TmElV/sP7GW', 'admin', 'Gujarat', '2024-01-01', 50000.00);

-- Insert sample sites
INSERT INTO sites (name, state, latitude, longitude) VALUES 
('Sunny Polymers - Ahmedabad', 'Gujarat', 23.0225, 72.5714),
('Sunny Polymers - Mumbai', 'Maharashtra', 19.0760, 72.8777),
('Sunny Polymers - Pune', 'Maharashtra', 18.5204, 73.8567);

-- Insert sample workers
INSERT INTO users (name, mobile, password, role, state, date_of_joining, salary, site_id) VALUES 
('Rajesh Kumar', '9876543210', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'worker', 'Gujarat', '2024-01-15', 15000.00, 1),
('Priya Sharma', '9876543211', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'worker', 'Maharashtra', '2024-02-01', 18000.00, 2),
('Amit Patel', '9876543212', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'worker', 'Gujarat', '2024-01-20', 16000.00, 1);

-- Insert sample holidays for 2024
INSERT INTO holidays (name, date, state) VALUES 
('Republic Day', '2024-01-26', 'Gujarat'),
('Republic Day', '2024-01-26', 'Maharashtra'),
('Independence Day', '2024-08-15', 'Gujarat'),
('Independence Day', '2024-08-15', 'Maharashtra'),
('Gandhi Jayanti', '2024-10-02', 'Gujarat'),
('Gandhi Jayanti', '2024-10-02', 'Maharashtra');

-- Sample attendance records
INSERT INTO attendance (user_id, date, check_in_time, check_out_time, check_in_lat, check_in_lng, status) VALUES 
(2, CURDATE(), '09:00:00', '18:00:00', 23.0225, 72.5714, 'present'),
(3, CURDATE(), '08:45:00', '17:30:00', 19.0760, 72.8777, 'present'),
(4, CURDATE(), '09:15:00', NULL, 23.0225, 72.5714, 'present'); 

-- ========================================
-- ALTER TABLE STATEMENTS FOR EXISTING DATABASES
-- ========================================

-- Add allowance fields to existing users table (skip if exists)
-- Run these commands if you already have a database with the old schema

-- ALTER TABLE users ADD COLUMN IF NOT EXISTS dearness_allowance DECIMAL(10,2) DEFAULT 0.00 AFTER salary;
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS medical_allowance DECIMAL(10,2) DEFAULT 0.00 AFTER dearness_allowance;
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS house_rent_allowance DECIMAL(10,2) DEFAULT 0.00 AFTER medical_allowance;
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS conveyance_allowance DECIMAL(10,2) DEFAULT 0.00 AFTER house_rent_allowance;
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS pf_uan_number VARCHAR(30) DEFAULT NULL AFTER conveyance_allowance;
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS overtime_rate DECIMAL(10,2) DEFAULT 0.00 AFTER pf_uan_number;
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS bank_name VARCHAR(100) DEFAULT NULL AFTER overtime_rate;
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS account_number VARCHAR(30) DEFAULT NULL AFTER bank_name;
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS ifsc_code VARCHAR(20) DEFAULT NULL AFTER account_number;
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS branch_name VARCHAR(100) DEFAULT NULL AFTER ifsc_code;

-- Add new payslip fields to existing payslips table (skip if exists)
-- Run these commands if you already have a database with the old schema

-- ALTER TABLE payslips ADD COLUMN IF NOT EXISTS professional_tax DECIMAL(10,2) DEFAULT 0.00 AFTER advances;
-- ALTER TABLE payslips ADD COLUMN IF NOT EXISTS pf_amount DECIMAL(10,2) DEFAULT 0.00 AFTER professional_tax;
-- ALTER TABLE payslips ADD COLUMN IF NOT EXISTS pf_previous_balance DECIMAL(10,2) DEFAULT 0.00 AFTER pf_amount;
-- ALTER TABLE payslips ADD COLUMN IF NOT EXISTS overtime_hours DECIMAL(6,2) DEFAULT 0.00 AFTER earned_salary;
-- ALTER TABLE payslips ADD COLUMN IF NOT EXISTS overtime_pay DECIMAL(10,2) DEFAULT 0.00 AFTER overtime_hours;

-- Add overtime fields to existing attendance table (skip if exists)
-- Run these commands if you already have a database with the old schema

-- ALTER TABLE attendance ADD COLUMN IF NOT EXISTS overtime_hours DECIMAL(4,2) DEFAULT 0.00 AFTER status;
-- ALTER TABLE attendance ADD COLUMN IF NOT EXISTS overtime_rate DECIMAL(10,2) DEFAULT 0.00 AFTER overtime_hours;

-- Add updated_at column to existing sites table (skip if exists)
-- Run this command if you already have a database with the old schema

-- ALTER TABLE sites ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- ========================================
-- NOTES ON ALLOWANCE FIELDS
-- ========================================

-- Dearness Allowance (DA): Cost of living adjustment allowance
-- Medical Allowance: Medical expenses reimbursement
-- House Rent Allowance (HRA): Housing accommodation allowance
-- Conveyance Allowance: Transportation and travel expenses

-- These fields will be used in payslip generation to calculate:
-- 1. Total Earnings = Basic Salary + All Allowances
-- 2. Gross Salary = Total Earnings
-- 3. Net Salary = Gross Salary - Deductions (advances, etc.)

-- ========================================
-- PAYSLIP DEDUCTION FIELDS
-- ========================================

-- Professional Tax: Monthly tax deduction based on salary slabs (varies by state)
-- PF Amount: Provident Fund contribution (usually 12% of basic salary)
-- Total Deductions = Advances + Professional Tax + PF Amount

-- These fields provide comprehensive salary calculation including:
-- 1. All statutory deductions (Professional Tax, PF)
-- 2. Company advances
-- 3. Accurate net salary calculation 

-- ========================================
-- OVERTIME SYSTEM EXPLANATION
-- ========================================

-- Overtime Hours: Decimal field to store overtime hours (e.g., 2.5 hours)
-- Overtime Rate: Hourly rate for overtime (usually 1.5x or 2x normal hourly rate)
-- Overtime Pay: Calculated overtime compensation (overtime_hours * overtime_rate)

-- The system will:
-- 1. Track daily overtime hours based on check-in/check-out times
-- 2. Calculate overtime pay during salary generation
-- 3. Include overtime in payslip calculations
-- 4. Allow admins to set custom overtime rates per employee

-- ========================================
-- BANK ACCOUNT FIELDS
-- ========================================

-- Bank Name: Employee's bank name for salary transfer
-- Account Number: Employee's bank account number
-- IFSC Code: Bank's IFSC code for transfers
-- Branch Name: Bank branch name

-- These fields enable direct bank transfers for salary payments 