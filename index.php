<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sunny Polymers - Employee Portal</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Home Page Specific Styles */
        .hero-section {
            background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 50%, #2563eb 100%);
            color: white;
            padding: 120px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g fill="%23ffffff" fill-opacity="0.08"><circle cx="30" cy="30" r="2"/></g></g></svg>') repeat;
            pointer-events: none;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-content h1 {
            font-size: 4.5rem;
            font-weight: 900;
            margin-bottom: 30px;
            text-shadow: 3px 3px 6px rgba(0,0,0,0.6);
            letter-spacing: -0.02em;
            color: #ffffff;
            background: none;
            -webkit-background-clip: unset;
            -webkit-text-fill-color: unset;
            background-clip: unset;
            animation: fadeInUp 1s ease-out;
            position: relative;
            display: inline-block;
        }
        
        .hero-content h1::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            right: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #ffffff, #fbbf24);
            border-radius: 3px;
            box-shadow: 0 2px 10px rgba(251, 191, 36, 0.5);
        }
        
        .hero-content p {
            font-size: 1.5rem;
            margin-bottom: 60px;
            opacity: 1;
            color: #ffffff;
            font-weight: 500;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.4);
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
            animation: fadeInUp 1s ease-out 0.2s both;
        }
        
        .cta-buttons {
            display: flex;
            gap: 30px;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease-out 0.4s both;
        }
        
        .btn-hero {
            padding: 20px 40px;
            font-size: 1.2rem;
            font-weight: 700;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            border: 3px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .btn-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-hero:hover::before {
            left: 100%;
        }
        
        .btn-primary-hero {
            background: #ffffff;
            color: #1e40af;
            border-color: #ffffff;
        }
        
        .btn-primary-hero:hover {
            background: transparent;
            color: #ffffff;
            border-color: #ffffff;
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        }
        
        .btn-secondary-hero {
            background: transparent;
            color: #ffffff;
            border-color: #ffffff;
        }
        
        .btn-secondary-hero:hover {
            background: #ffffff;
            color: #1e40af;
            border-color: #ffffff;
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .features-section {
            padding: 100px 0;
            background: #f8f9fa;
            position: relative;
        }
        
        .features-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, #e2e8f0 50%, transparent 100%);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 80px;
            animation: fadeInUp 1s ease-out;
        }
        
        .section-title h2 {
            font-size: 2.8rem;
            color: #1e293b;
            margin-bottom: 20px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        
        .section-title p {
            font-size: 1.3rem;
            color: #64748b;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.7;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 40px;
            margin-bottom: 60px;
        }
        
        .feature-card {
            background: white;
            padding: 45px 35px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #f1f5f9;
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2563eb, #1d4ed8);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            border-color: #e2e8f0;
        }
        
        .feature-card:hover::before {
            transform: scaleX(1);
        }
        
        .feature-icon {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            color: white;
            font-size: 2.2rem;
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1);
            box-shadow: 0 12px 35px rgba(37, 99, 235, 0.4);
        }
        
        .feature-card h3 {
            font-size: 1.6rem;
            color: #1e293b;
            margin-bottom: 18px;
            font-weight: 600;
        }
        
        .feature-card p {
            color: #64748b;
            line-height: 1.7;
            font-size: 1rem;
        }
        
        .how-it-works {
            padding: 100px 0;
            background: white;
            position: relative;
        }
        
        .how-it-works::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, #e2e8f0 50%, transparent 100%);
        }
        
        .steps-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .step {
            display: flex;
            align-items: center;
            margin-bottom: 60px;
            position: relative;
            animation: fadeInLeft 1s ease-out;
        }
        
        .step:nth-child(even) {
            animation: fadeInRight 1s ease-out;
        }
        
        .step:last-child {
            margin-bottom: 0;
        }
        
        .step-number {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            margin-right: 35px;
            flex-shrink: 0;
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
            position: relative;
        }
        
        .step-number::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border: 2px solid #2563eb;
            border-radius: 50%;
            opacity: 0.3;
            animation: pulse 2s infinite;
        }
        
        .step-content h3 {
            font-size: 1.5rem;
            color: #1e293b;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .step-content p {
            color: #64748b;
            line-height: 1.7;
            font-size: 1.1rem;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 35px;
            top: 70px;
            width: 2px;
            height: 50px;
            background: linear-gradient(to bottom, #2563eb, #1d4ed8);
            opacity: 0.6;
        }
        
        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.3;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.1;
            }
            100% {
                transform: scale(1);
                opacity: 0.3;
            }
        }
        
        .cta-section {
            padding: 80px 0;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            text-align: center;
        }
        
        .cta-section h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .cta-section p {
            font-size: 1.2rem;
            margin-bottom: 40px;
            opacity: 1;
            color: #ffffff;
            font-weight: 500;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        .footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 40px 0;
        }
        
        .footer p {
            margin: 0;
            opacity: 1;
            color: #ffffff;
            font-weight: 500;
            font-size: 1rem;
        }
        
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .hero-content p {
                font-size: 1.1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-hero {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
            
            .step {
                flex-direction: column;
                text-align: center;
            }
            
            .step-number {
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .step:not(:last-child)::after {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1>Sunny Polymers</h1>
                <p>Employee Portal - Attendance & Payroll Management System Demo</p>
                <div class="cta-buttons">
                    <a href="login.php" class="btn-hero btn-primary-hero">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </a>
                    <a href="#how-it-works" class="btn-hero btn-secondary-hero">
                        <i class="fas fa-info-circle"></i>
                        Learn More
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="section-title">
                <h2>Why Choose Our Portal?</h2>
                <p>Streamline your workforce management with our comprehensive employee portal</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Smart Attendance</h3>
                    <p>Track attendance with GPS location, manage leaves, and monitor work hours efficiently</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3>Payroll Management</h3>
                    <p>Automated salary calculations, advance management, and professional payslip generation</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>User Management</h3>
                    <p>Comprehensive user profiles, role-based access, and centralized employee database</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3>Site Management</h3>
                    <p>Manage multiple work sites, track locations, and assign workers efficiently</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Holiday Calendar</h3>
                    <p>State-wise and national holiday management with automatic notifications</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Analytics & Reports</h3>
                    <p>Comprehensive reports on attendance, payroll, and workforce performance</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <div class="section-title">
                <h2>How It Works</h2>
                <p>Get started with our employee portal in just a few simple steps</p>
            </div>
            
            <div class="steps-container">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Account Creation</h3>
                        <p>Administrators can create new user accounts with specific roles (Admin, Staff, Worker) and assign them to work sites. Each user gets a unique mobile number and secure password.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Login & Authentication</h3>
                        <p>Users log in using their mobile number and password. The system provides secure authentication and role-based access control to different features.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Attendance Management</h3>
                        <p>Workers mark their attendance with GPS location verification. Admins can view attendance records, manage leaves, and track work hours across all sites.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h3>Payroll Processing</h3>
                        <p>System automatically calculates salaries based on attendance, advances, and deductions. Generate professional PDF payslips for all employees.</p>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">5</div>
                    <div class="step-content">
                        <h3>Site & User Management</h3>
                        <p>Admins can manage multiple work sites, assign workers, and maintain comprehensive user profiles with state-wise holiday management.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Get Started?</h2>
            <p>Join Hundreds of employees already using our portal</p>
            <div class="cta-buttons">
                <a href="login.php" class="btn-hero btn-primary-hero">
                    <i class="fas fa-sign-in-alt"></i>
                    Login Now
                </a>
                <a href="#how-it-works" class="btn-hero btn-secondary-hero">
                    <i class="fas fa-info-circle"></i>
                    Learn More
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>© 2025 Sunny Polymers. All rights reserved.</p>
    </div>
    </footer>
</body>
</html> 
