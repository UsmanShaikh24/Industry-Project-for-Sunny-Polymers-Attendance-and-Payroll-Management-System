<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Test PDF generation
function testPDFGeneration() {
    global $conn;
    
    // Get a sample payslip
    $stmt = $conn->prepare("SELECT id FROM payslips LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $payslip = $result->fetch_assoc();
        $payslip_id = $payslip['id'];
        
        echo "<h2>PDF Generation Test</h2>";
        echo "<p>Testing PDF generation for payslip ID: " . $payslip_id . "</p>";
        
        // Test the PDF generation function
        require_once 'generate_pdf_payslip.php';
        
        // Simulate admin access
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'admin';
        
        // Test PDF generation
        $dompdf = generatePDFPayslip($payslip_id, 1);
        
        if ($dompdf) {
            echo "<p style='color: green;'>✅ PDF generation successful!</p>";
            echo "<p>You can now:</p>";
            echo "<ul>";
            echo "<li><a href='generate_pdf_payslip.php?payslip_id=" . $payslip_id . "&action=view' target='_blank'>View PDF in Browser</a></li>";
            echo "<li><a href='generate_pdf_payslip.php?payslip_id=" . $payslip_id . "&action=download'>Download PDF</a></li>";
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>❌ PDF generation failed!</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No payslips found in database. Please generate a payslip first.</p>";
    }
}

// Check if DOMPDF is installed
if (class_exists('Dompdf\Dompdf')) {
    echo "<h1>PDF Library Test</h1>";
    echo "<p style='color: green;'>✅ DOMPDF library is installed and working!</p>";
    testPDFGeneration();
} else {
    echo "<h1>PDF Library Test</h1>";
    echo "<p style='color: red;'>❌ DOMPDF library is not installed!</p>";
    echo "<p>Please run: <code>composer require dompdf/dompdf</code></p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Test - Sunny Polymers</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f8f9fa;
        }
        h1, h2 {
            color: #2563eb;
        }
        .test-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            color: #22c55e;
            font-weight: bold;
        }
        .error {
            color: #ef4444;
            font-weight: bold;
        }
        .warning {
            color: #f59e0b;
            font-weight: bold;
        }
        a {
            color: #2563eb;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        ul {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="test-section">
        <h1>PDF Generation System Test</h1>
        <p>This page tests the PDF generation functionality for payslips.</p>
        
        <h2>Features Implemented:</h2>
        <ul>
            <li>✅ Professional PDF payslip generation</li>
            <li>✅ View PDF in browser</li>
            <li>✅ Download PDF file</li>
            <li>✅ Role-based access control</li>
            <li>✅ Complete payslip details</li>
            <li>✅ Professional styling</li>
        </ul>
        
        <h2>How to Use:</h2>
        <ol>
            <li><strong>For Workers:</strong> Go to "View Payslips" and click "View PDF" or "Download PDF"</li>
            <li><strong>For Admins:</strong> Go to "Generate Salary Slip" and use the "View" or "PDF" buttons</li>
            <li><strong>Direct Access:</strong> Use the links below to test PDF generation</li>
        </ol>
        
        <h2>Test Results:</h2>
        <?php
        // Check if DOMPDF is installed
        if (class_exists('Dompdf\Dompdf')) {
            echo "<p class='success'>✅ DOMPDF library is installed and working!</p>";
            testPDFGeneration();
        } else {
            echo "<p class='error'>❌ DOMPDF library is not installed!</p>";
            echo "<p>Please run: <code>composer require dompdf/dompdf</code></p>";
        }
        ?>
        
        <h2>Next Steps:</h2>
        <ul>
            <li>Generate some payslips using the admin panel</li>
            <li>Test PDF viewing and downloading</li>
            <li>Verify the professional appearance of generated PDFs</li>
        </ul>
    </div>
</body>
</html>
