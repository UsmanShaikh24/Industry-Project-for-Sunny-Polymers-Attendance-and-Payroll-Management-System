<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Require authentication
require_auth();

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Function to generate PDF payslip
function generatePDFPayslip($payslip_id) {
    global $conn;
    
    // Get payslip data
    $stmt = $conn->prepare("
        SELECT p.*, u.name as user_name, u.mobile, u.state, u.role, u.designation, u.pf_uan_number, u.bank_name, u.account_number, 
               u.ifsc_code, u.branch_name, s.name as site_name, g.name as generated_by_name, p.generated_at 
        FROM payslips p 
        JOIN users u ON p.user_id = u.id 
        LEFT JOIN sites s ON u.site_id = s.id 
        LEFT JOIN users g ON p.generated_by = g.id 
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $payslip_id);
    $stmt->execute();
    $payslip = $stmt->get_result()->fetch_assoc();
    
    if (!$payslip) {
        return false;
    }
    
    // Convert logo to base64 for PDF embedding
    $logo_path = __DIR__ . '/assets/payslip logo.jpg';
    $logo_base64 = '';
    if (file_exists($logo_path)) {
        $logo_data = file_get_contents($logo_path);
        $logo_base64 = 'data:image/jpeg;base64,' . base64_encode($logo_data);
    }
    
    // Convert signature to base64 for PDF embedding
    $signature_path = __DIR__ . '/assets/sign.png';
    $signature_base64 = '';
    if (file_exists($signature_path)) {
        $signature_data = file_get_contents($signature_path);
        $signature_base64 = 'data:image/png;base64,' . base64_encode($signature_data);
    }
    
    // Calculate allowances from user data
    $stmt = $conn->prepare("SELECT dearness_allowance, medical_allowance, house_rent_allowance, conveyance_allowance FROM users WHERE id = ?");
    $stmt->bind_param("i", $payslip['user_id']);
    $stmt->execute();
    $user_allowances = $stmt->get_result()->fetch_assoc();
    
    // Calculate total earnings
    $basic_pay = $payslip['basic_salary'];
    $dearness_allowance = $user_allowances['dearness_allowance'] ?? 0;
    $medical_allowance = $user_allowances['medical_allowance'] ?? 0;
    $house_rent_allowance = $user_allowances['house_rent_allowance'] ?? 0;
    $conveyance_allowance = $user_allowances['conveyance_allowance'] ?? 0;
    $total_earnings = $basic_pay + $dearness_allowance + $medical_allowance + $house_rent_allowance + $conveyance_allowance;
    
    // Use designation from database or fallback to role if not set
    $designation = !empty($payslip['designation']) ? strtoupper($payslip['designation']) : strtoupper($payslip['role']);
    
    // Create HTML content for PDF - exact replica of the image
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Pay Slip - ' . htmlspecialchars($payslip['user_name']) . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 15px;
                font-size: 12px;
                line-height: 1.3;
            }
            .payslip-container {
                border: 2px solid #000;
                width: 100%;
                max-width: 750px;
                margin: 0 auto;
            }
            .company-header {
                text-align: center;
                padding: 10px;
                border-bottom: 1px solid #000;
            }
            .company-logo {
                width: 100%;
                height: 80px;
                object-fit: cover;
                margin: 5px 0;
                display: block;
            }
            .company-details {
                font-size: 11px;
                margin: 15px 0;
                line-height: 1.4;
                padding: 0 20px;
                text-align: center;
                font-weight: normal;
            }
            .payslip-title {
                text-align: center;
                font-weight: bold;
                padding: 8px;
                border-bottom: 1px solid #000;
                background-color: #f5f5f5;
            }
            .employee-info {
                display: table;
                width: 100%;
                border-bottom: 1px solid #000;
            }
            .employee-row {
                display: table-row;
            }
            .employee-cell {
                display: table-cell;
                padding: 6px 8px;
                vertical-align: top;
                text-align: left;
                width: 25%;
            }
            .employee-cell:nth-child(2) {
                text-align: left;
            }
            .employee-cell:nth-child(4) {
                text-align: right;
            }
            .label {
                font-weight: bold;
            }
            .value {
                
            }
            .salary-section {
                display: table;
                width: 100%;
            }
            .salary-row {
                display: table-row;
            }
            .earning-cell, .deduction-cell {
                display: table-cell;
                padding: 6px 8px;
                border-right: 1px solid #000;
                border-bottom: 1px solid #000;
                width: 50%;
                vertical-align: top;
            }
            .deduction-cell {
                border-right: none;
            }
            .section-header {
                font-weight: bold;
                text-align: center;
                background-color: #f5f5f5;
                padding: 8px;
                border-bottom: 1px solid #000;
            }
            .amount-cell {
                text-align: right;
                font-weight: bold;
                min-width: 80px;
            }
            .total-row {
                font-weight: bold;
                background-color: #f0f0f0;
            }
            .net-pay-section {
                border-top: 2px solid #000;
                padding: 8px;
                text-align: center;
                font-weight: bold;
                background-color: #f5f5f5;
            }
            .signature-section {
                display: table;
                width: 100%;
                margin-top: 30px;
            }
            .signature-cell {
                display: table-cell;
                text-align: center;
                padding: 20px;
                width: 50%;
                vertical-align: bottom;
                 position: relative;
            }
            .signature-image {
                height: 100px;
                max-width: 250px;
                margin-bottom: 0px;
                display: block;
                margin-left: auto;
                margin-right: auto;
            }
            .signature-line {
                border-top: 1px solid #000;
                margin-top: 2px;
                padding-top: 2px;
                font-size: 10px;
            }
            .signature-line.employer {
                border-top: none;
            }
            .system-footer {
                margin-top: 20px;
                padding: 12px 15px;
                border-top: 1px solid #ccc;
                text-align: center;
                background-color: #f9f9f9;
                border-radius: 0 0 5px 5px;
            }
            .footer-text {
                font-size: 10px;
                color: #666;
                line-height: 1.5;
            }
            .generation-info {
                font-size: 9px;
                color: #888;
                margin-top: 8px;
                display: block;
                border-top: 1px solid #eee;
                padding-top: 8px;
            }
        </style>
    </head>
    <body>
        <div class="payslip-container">
            <!-- Company Header -->
            <div class="company-header">' . 
                ($logo_base64 ? '<img src="' . $logo_base64 . '" alt="Company Logo" class="company-logo">' : '') . '
                <div class="company-details">
                    FABRICATORS OF P.P., H.D.P.E., F.R.P., P.V.C., P.V.D.F., PLASTICS<br>
                    OFFICE: - C-3 / 3 ARIHANT GARDEN, LAXMI NAGAR, KHOPOLI - 410 203<br>
                    PHONE: - 02192-265491 TEL FAX: 277354
                </div>
            </div>
            
            <!-- Payslip Title -->
            <div class="payslip-title">
                Pay Slip for the Period of ' . date('M Y', mktime(0,0,0,$payslip['month'],1,$payslip['year'])) . '
            </div>
            
            <!-- Employee Information -->
            <div class="employee-info">
                <div class="employee-row">
                    <div class="employee-cell label">Name</div>
                    <div class="employee-cell value">: ' . strtoupper(htmlspecialchars($payslip['user_name'])) . '</div>
                    <div class="employee-cell label">Designation</div>
                    <div class="employee-cell value">: ' . $designation . '</div>
                </div>
                <div class="employee-row">
                    <div class="employee-cell label">PHONE NO.</div>
                    <div class="employee-cell value">: ' . htmlspecialchars($payslip['mobile']) . '</div>
                    <div class="employee-cell label">Days in ' . date('M', mktime(0,0,0,$payslip['month'],1,$payslip['year'])) . '</div>
                    <div class="employee-cell value amount-cell">' . number_format($payslip['total_days'], 1) . '</div>
                </div>
                <div class="employee-row">
                    <div class="employee-cell label">PF UAN NO.</div>
                    <div class="employee-cell value">: ' . htmlspecialchars($payslip['pf_uan_number'] ?? '') . '</div>
                    <div class="employee-cell label">Working days</div>
                    <div class="employee-cell value amount-cell">' . number_format($payslip['present_days'] + $payslip['leave_days'], 1) . '</div>
                </div>
                <div class="employee-row">
                    <div class="employee-cell label">Salary</div>
                    <div class="employee-cell value amount-cell">' . number_format($basic_pay) . '</div>
                    <div class="employee-cell label">Absent days</div>
                    <div class="employee-cell value amount-cell">' . number_format($payslip['total_days'] - ($payslip['present_days'] + $payslip['leave_days']), 1) . '</div>
                </div>
                <div class="employee-row">
                    <div class="employee-cell label">Rate per day</div>
                    <div class="employee-cell value amount-cell">' . number_format($basic_pay / $payslip['total_days']) . '</div>
                    <div class="employee-cell label">working days counted</div>
                    <div class="employee-cell value amount-cell">' . number_format($payslip['present_days'] + $payslip['leave_days'], 1) . '</div>
                </div>
            </div>
            
            <!-- Salary Section -->
            <div class="salary-section">
                <!-- Section Headers -->
                <div class="salary-row">
                    <div class="earning-cell section-header">EARNING</div>
                    <div class="earning-cell section-header">AMOUNT</div>
                    <div class="deduction-cell section-header">DEDUCTION</div>
                    <div class="deduction-cell section-header">AMOUNT</div>
                </div>
                
                <!-- Basic Pay Row -->
                <div class="salary-row">
                    <div class="earning-cell">Basic Pay</div>
                    <div class="earning-cell amount-cell">' . number_format($basic_pay) . '</div>
                    <div class="deduction-cell">PF</div>
                    <div class="deduction-cell amount-cell">' . number_format($payslip['pf_amount'] ?? 0) . '</div>
                </div>
                
                <!-- Allowances -->
                <div class="salary-row">
                    <div class="earning-cell">Dearness Allowance</div>
                    <div class="earning-cell amount-cell">' . number_format($dearness_allowance) . '</div>
                    <div class="deduction-cell">Professional Tax</div>
                    <div class="deduction-cell amount-cell">' . number_format($payslip['professional_tax'] ?? 0) . '</div>
                </div>
                
                <div class="salary-row">
                    <div class="earning-cell">Medical allowance</div>
                    <div class="earning-cell amount-cell">' . number_format($medical_allowance) . '</div>
                    <div class="deduction-cell">Advance</div>
                    <div class="deduction-cell amount-cell">' . number_format($payslip['advances']) . '</div>
                </div>
                
                <div class="salary-row">
                    <div class="earning-cell">House Rent Allowance</div>
                    <div class="earning-cell amount-cell">' . number_format($house_rent_allowance) . '</div>
                    <div class="deduction-cell"></div>
                    <div class="deduction-cell amount-cell"></div>
                </div>
                
                <div class="salary-row">
                    <div class="earning-cell">Conveyance Allowance</div>
                    <div class="earning-cell amount-cell">' . number_format($conveyance_allowance) . '</div>
                    <div class="deduction-cell"></div>
                    <div class="deduction-cell amount-cell"></div>
                </div>
                
                <!-- Overtime Row -->
                <div class="salary-row">
                    <div class="earning-cell">Overtime (' . number_format($payslip['overtime_hours'] ?? 0, 1) . ' hrs)</div>
                    <div class="earning-cell amount-cell">' . number_format($payslip['overtime_pay'] ?? 0, 2) . '</div>
                    <div class="deduction-cell"></div>
                    <div class="deduction-cell amount-cell"></div>
                </div>
                
                <!-- Total Row -->
                <div class="salary-row total-row">
                    <div class="earning-cell">TOTAL EARNINGS</div>
                    <div class="earning-cell amount-cell">' . number_format($payslip['earned_salary'], 2) . '</div>
                    <div class="deduction-cell">TOTAL DEDUCTIONS</div>
                    <div class="deduction-cell amount-cell">' . number_format($payslip['deductions'], 2) . '</div>
                </div>
                
                <!-- Previous Balance & PF Given -->
                <div class="salary-row">
                    <div class="earning-cell">PREVIOUS PF BALANCE</div>
                    <div class="earning-cell amount-cell">' . ($payslip['pf_previous_balance'] > 0 ? number_format($payslip['pf_previous_balance'], 2) : 'NIL') . '</div>
                    <div class="deduction-cell">NET PAY (ROUNDED)</div>
                    <div class="deduction-cell amount-cell">' . number_format($payslip['net_salary'], 2) . '</div>
                </div>
                
                <div class="salary-row">
                    <div class="earning-cell">PF GIVEN THIS MONTH</div>
                    <div class="earning-cell amount-cell">' . number_format($payslip['pf_amount'], 2) . '</div>
                    <div class="deduction-cell">Total Salary</div>
                    <div class="deduction-cell amount-cell">' . number_format($payslip['net_salary'], 2) . '</div>
                </div>
            </div>
            
            <!-- Signature Section -->
            <div class="signature-section">
                <div class="signature-cell">' . 
                    ($signature_base64 ? '<img src="' . $signature_base64 . '" alt="Employer Signature" class="signature-image">' : '') . '
                    <div class="signature-line employer">EMPLOYERS SIGNATURE</div>
                </div>
                <div class="signature-cell">
                    <div class="signature-line">EMPLOYEES SIGNATURE</div>
                </div>
            </div>
            
            <!-- System Generated Footer -->
            <div class="system-footer">
                <div class="footer-text">
                    <strong>This is a system generated slip</strong><br>
                    <span class="generation-info">
                        Payslip Period: ' . date('M Y', mktime(0,0,0,$payslip['month'],1,$payslip['year'])) . '<br>
                        Created on: ' . ($payslip['generated_at'] ? date('d-m-Y h:i A', strtotime($payslip['generated_at'])) . ' (Creation Time)' : 'Date not available') . '<br>
                        Generated by: ' . htmlspecialchars($payslip['generated_by_name'] ?? $_SESSION['user_name'] ?? 'System') . '
                    </span>
                </div>
            </div>
        </div>
    </body>
    </html>';
    
    // Configure DOMPDF
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('defaultPaperSize', 'A4');
    $options->set('defaultMediaType', 'screen');
    $options->set('isFontSubsettingEnabled', true);
    
    // Create DOMPDF instance
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    return $dompdf;
}

// Handle PDF generation request
if (isset($_GET['payslip_id']) && isset($_GET['action'])) {
    $payslip_id = (int)$_GET['payslip_id'];
    $action = $_GET['action'];
    
    // Check if user has permission to view this payslip
    $user_id = $_SESSION['user_id'];
    $is_admin = is_admin();
    
    if ($is_admin) {
        // Admin can view any payslip
        $stmt = $conn->prepare("SELECT user_id FROM payslips WHERE id = ?");
        $stmt->bind_param("i", $payslip_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 0) {
            die('Payslip not found.');
        }
    } else {
        // Workers can only view their own payslips
        $stmt = $conn->prepare("SELECT user_id FROM payslips WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $payslip_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 0) {
            die('Payslip not found or access denied.');
        }
    }
    
    // Generate PDF
    $dompdf = generatePDFPayslip($payslip_id);
    
    if ($dompdf) {
        if ($action === 'download') {
            // Download PDF
            $dompdf->stream("payslip_" . $payslip_id . ".pdf", array("Attachment" => true));
        } elseif ($action === 'view') {
            // View PDF in browser
            $dompdf->stream("payslip_" . $payslip_id . ".pdf", array("Attachment" => false));
        } else {
            die('Invalid action.');
        }
    } else {
        die('Error generating PDF.');
    }
    exit();
}

// If no valid request, redirect to dashboard
header("Location: dashboard.php");
exit();
?>
