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
        SELECT p.*, u.name as user_name, u.mobile, u.state, u.bank_name, u.account_number, 
               u.ifsc_code, u.branch_name, s.name as site_name, g.name as generated_by_name 
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
    
    // Create HTML content for PDF
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Salary Slip - ' . htmlspecialchars($payslip['user_name']) . '</title>
                 <style>
             @font-face {
                 font-family: "DejaVu Sans";
                 src: url("data:font/ttf;base64,") format("truetype");
             }
             body {
                 font-family: "DejaVu Sans", Arial, sans-serif;
                 margin: 0;
                 padding: 20px;
                 color: #333;
                 background: #fff;
             }
            .header {
                text-align: center;
                border-bottom: 3px solid #2563eb;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }
            .company-name {
                font-size: 24px;
                font-weight: bold;
                color: #2563eb;
                margin: 0;
            }
            .company-info {
                font-size: 12px;
                color: #666;
                margin: 5px 0;
            }
            .payslip-title {
                font-size: 18px;
                font-weight: bold;
                text-align: center;
                margin: 20px 0;
                color: #2563eb;
            }
            .employee-details {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
            }
            .employee-details td {
                padding: 8px;
                border: 1px solid #ddd;
                font-size: 12px;
            }
            .employee-details .label {
                background: #f8f9fa;
                font-weight: bold;
                color: #2563eb;
                width: 150px;
            }
            .section-title {
                font-size: 14px;
                font-weight: bold;
                color: #2563eb;
                margin: 20px 0 10px 0;
                border-bottom: 2px solid #2563eb;
                padding-bottom: 5px;
            }
            .salary-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            .salary-table th,
            .salary-table td {
                border: 1px solid #ddd;
                padding: 10px;
                text-align: left;
                font-size: 12px;
            }
            .salary-table th {
                background: #f8f9fa;
                color: #2563eb;
                font-weight: bold;
            }
            .salary-table .right {
                text-align: right;
            }
            .net-salary {
                background: #22c55e;
                color: white;
                font-size: 16px;
                font-weight: bold;
                text-align: center;
                padding: 15px;
                border-radius: 5px;
                margin-top: 20px;
            }
            .footer {
                margin-top: 40px;
                text-align: right;
                font-size: 10px;
                color: #666;
            }
            .signature-section {
                margin-top: 40px;
                display: flex;
                justify-content: space-between;
            }
            .signature-box {
                text-align: center;
                width: 200px;
            }
            .signature-line {
                border-top: 1px solid #333;
                margin-top: 30px;
                padding-top: 5px;
                font-size: 10px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1 class="company-name">Sunny Polymers Pvt. Ltd.</h1>
            <div class="company-info">
                Organization Number: 1234567890<br>
                Address: 123, Industrial Area, City, State, 123456<br>
                Phone: +91-1234567890 | Email: hr@sunnypolymers.com
            </div>
        </div>
        
        <div class="payslip-title">SALARY SLIP</div>
        
        <table class="employee-details">
            <tr>
                <td class="label">Employee Name:</td>
                <td>' . htmlspecialchars($payslip['user_name']) . '</td>
                <td class="label">Pay Period:</td>
                <td>' . date('F Y', mktime(0,0,0,$payslip['month'],1,$payslip['year'])) . '</td>
            </tr>
            <tr>
                <td class="label">Mobile Number:</td>
                <td>' . htmlspecialchars($payslip['mobile']) . '</td>
                <td class="label">Pay Date:</td>
                <td>' . date('d M Y', strtotime($payslip['generated_at'])) . '</td>
            </tr>
            <tr>
                <td class="label">Work Site:</td>
                <td>' . htmlspecialchars($payslip['site_name'] ?? 'Not Assigned') . '</td>
                <td class="label">State:</td>
                <td>' . htmlspecialchars($payslip['state']) . '</td>
            </tr>
            <tr>
                <td class="label">Bank Name:</td>
                <td>' . htmlspecialchars($payslip['bank_name'] ?? 'Not Provided') . '</td>
                <td class="label">Account Number:</td>
                <td>' . htmlspecialchars($payslip['account_number'] ?? 'Not Provided') . '</td>
            </tr>
        </table>
        
        <div class="section-title">Earnings & Deductions</div>
        
        <table class="salary-table">
            <tr>
                                 <th>Earnings</th>
                 <th class="right">Amount (Rs.)</th>
                 <th>Deductions</th>
                 <th class="right">Amount (Rs.)</th>
            </tr>
                         <tr>
                 <td>Basic Salary</td>
                 <td class="right">Rs. ' . number_format($payslip['basic_salary'], 2) . '</td>
                 <td>Advances</td>
                 <td class="right">Rs. ' . number_format($payslip['advances'], 2) . '</td>
             </tr>
             <tr>
                 <td>Per Day Salary</td>
                 <td class="right">Rs. ' . number_format($payslip['basic_salary'] / $payslip['total_days'], 2) . '</td>
                 <td>Other Deductions</td>
                 <td class="right">Rs. ' . number_format($payslip['deductions'] - $payslip['advances'], 2) . '</td>
             </tr>
            <tr>
                <td>Present Days</td>
                <td class="right">' . $payslip['present_days'] . ' days</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>Leave Days</td>
                <td class="right">' . $payslip['leave_days'] . ' days</td>
                <td></td>
                <td></td>
            </tr>
                         <tr style="background:#f8f9fa;">
                 <td><strong>Total Earned</strong></td>
                 <td class="right"><strong>Rs. ' . number_format($payslip['earned_salary'], 2) . '</strong></td>
                 <td><strong>Total Deductions</strong></td>
                 <td class="right"><strong>Rs. ' . number_format($payslip['deductions'], 2) . '</strong></td>
             </tr>
        </table>
        
                 <div class="net-salary">
             Net Salary: Rs. ' . number_format($payslip['net_salary'], 2) . '
         </div>
         ' . ($payslip['net_salary'] == 0 ? '<div style="text-align: center; color: #dc3545; font-size: 12px; margin-top: 10px; font-weight: bold;">Note: Net salary is zero due to advances exceeding earned salary</div>' : '') . '
        
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">Employee Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Authorized Signature</div>
            </div>
        </div>
        
        <div class="footer">
            Generated on: ' . date('d M Y H:i:s') . '<br>
            Generated by: ' . htmlspecialchars($payslip['generated_by_name'] ?? 'System') . '
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
