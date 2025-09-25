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
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            color: white;
            padding: 70px 0 70px;
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
            background: url('data:image/svg+xml,<svg width="80" height="80" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g fill="%23ffffff" fill-opacity="0.06"><circle cx="40" cy="40" r="1.5"/></g></g></svg>') repeat;
            pointer-events: none;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 700px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .hero-logo {
            width: 320px;
            height: 320px;
            margin: 0 auto 50px;
            display: block;
            animation: fadeInUp 1s ease-out;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }
        
        .hero-logo:hover {
            transform: scale(1.05);
            filter: brightness(1.1) drop-shadow(0 0 20px rgba(251, 191, 36, 0.3));
        }
        
        .hero-logo:active {
            transform: scale(1.02);
        }
        

        
        .hero-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        
        .hero-content p {
            font-size: 1.4rem;
            margin-bottom: 50px;
            color: #ffffff;
            font-weight: 500;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.5);
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.4;
            animation: fadeInUp 1s ease-out 0.2s both;
            opacity: 1;
            position: relative;
            display: inline-block;
            white-space: nowrap;
        }
        
        .hero-content p::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            right: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #fbbf24 0%, #ffffff 50%, #fbbf24 100%);
            border-radius: 3px;
            box-shadow: 0 3px 12px rgba(251, 191, 36, 0.7);
        }
        
        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease-out 0.4s both;
            margin-top: 10px;
        }
        
        .btn-hero {
            padding: 16px 32px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            min-width: 150px;
            justify-content: center;
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
            background: #fbbf24;
            color: #0f172a;
            border-color: #fbbf24;
        }
        
        .btn-primary-hero:hover {
            background: #f59e0b;
            color: #0f172a;
            border-color: #f59e0b;
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(251, 191, 36, 0.4);
        }
        
        .btn-secondary-hero {
            background: transparent;
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.6);
        }
        
        .btn-secondary-hero:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border-color: #ffffff;
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(255, 255, 255, 0.2);
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
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            position: relative;
            color: white;
        }
        
        .features-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, #fbbf24 50%, transparent 100%);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Enhanced section spacing and visual hierarchy */
        .section-title {
            text-align: center;
            margin-bottom: 80px;
            animation: fadeInUp 1s ease-out;
        }
        
        .section-title h2 {
            font-size: 2.8rem;
            color: #ffffff;
            margin-bottom: 20px;
            font-weight: 700;
            letter-spacing: -0.02em;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.4);
        }
        
        .section-title p {
            font-size: 1.3rem;
            color: #e2e8f0;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.7;
            opacity: 0.9;
            white-space: nowrap;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 40px;
            margin-bottom: 60px;
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 45px 35px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #fbbf24, #f59e0b);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            border-color: rgba(251, 191, 36, 0.3);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .feature-card:hover::before {
            transform: scaleX(1);
        }
        
        .feature-icon {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            color: #0f172a;
            font-size: 2.2rem;
            box-shadow: 0 8px 25px rgba(251, 191, 36, 0.3);
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1);
            box-shadow: 0 12px 35px rgba(251, 191, 36, 0.4);
        }
        
        .feature-card h3 {
            font-size: 1.6rem;
            color: #ffffff;
            margin-bottom: 18px;
            font-weight: 600;
        }
        
        .feature-card p {
            color: #cbd5e1;
            line-height: 1.7;
            font-size: 1rem;
        }
        
        .how-it-works {
            padding: 100px 0;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            position: relative;
            color: white;
        }
        
        .how-it-works::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, #fbbf24 50%, transparent 100%);
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
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0f172a;
            font-size: 1.8rem;
            font-weight: 700;
            margin-right: 35px;
            flex-shrink: 0;
            box-shadow: 0 8px 25px rgba(251, 191, 36, 0.3);
            position: relative;
        }
        
        .step-number::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border: 2px solid #fbbf24;
            border-radius: 50%;
            opacity: 0.3;
            animation: pulse 2s infinite;
        }
        
        .step-content h3 {
            font-size: 1.5rem;
            color: #ffffff;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .step-content p {
            color: #cbd5e1;
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
            background: linear-gradient(to bottom, #fbbf24, #f59e0b);
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
        
        /* Enhanced animations for better visual appeal */
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
        
        /* Step animations */
        .step:nth-child(odd) {
            animation: fadeInLeft 1s ease-out;
        }
        
        .step:nth-child(even) {
            animation: fadeInRight 1s ease-out;
        }
        
        .cta-section {
            padding: 60px 0;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            text-align: center;
            position: relative;
        }
        
        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, #fbbf24 50%, transparent 100%);
        }
        
        .cta-section h2 {
            font-size: 2.8rem;
            margin-bottom: 25px;
            text-shadow: 2px 2px 10px rgba(0,0,0,0.5);
            color: #ffffff;
            font-weight: 700;
        }
        
        .cta-section p {
            font-size: 1.3rem;
            margin-bottom: 40px;
            opacity: 1;
            color: #ffffff;
            font-weight: 500;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.4);
        }
        
        .footer {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            text-align: center;
            padding: 25px 0;
            position: relative;
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, #fbbf24 50%, transparent 100%);
        }
        
        .footer p {
            margin: 0;
            opacity: 0.7;
            color: #94a3b8;
            font-weight: 400;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .hero-logo {
                width: 260px;
                height: 260px;
                margin-bottom: 40px;
            }
            
            .hero-content p {
                font-size: 1.1rem;
                margin-bottom: 35px;
                white-space: normal;
                max-width: 90%;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
                gap: 18px;
            }
            
            .btn-hero {
                width: 100%;
                max-width: 260px;
                justify-content: center;
                padding: 14px 28px;
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
                <div class="hero-logo" onclick="window.location.reload()">
                    <img src="assets/SUNNY_POLYMERS logo.jpg" alt="Sunny Polymers Logo">
                </div>
                <p>Employee Portal - Attendance & Payroll Management System</p>
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