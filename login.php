<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mobile = trim($_POST['mobile']);
    $password = $_POST['password'];
    
    if (empty($mobile) || empty($password)) {
        $error = 'Please enter both mobile number and password';
    } else {
        $stmt = $conn->prepare("SELECT id, name, mobile, password, role, state, salary, site_id, date_of_joining FROM users WHERE mobile = ?");
        $stmt->bind_param("s", $mobile);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_mobile'] = $user['mobile'];
                $_SESSION['user_state'] = $user['state'];
                $_SESSION['user_salary'] = $user['salary'];
                $_SESSION['user_site_id'] = $user['site_id'];
                $_SESSION['user_date_of_joining'] = $user['date_of_joining'];
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'Mobile number not found';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sunny Polymers Employee Portal</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            color: white;
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="80" height="80" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g fill="%23ffffff" fill-opacity="0.06"><circle cx="40" cy="40" r="1.5"/></g></g></svg>') repeat;
            pointer-events: none;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            position: relative;
            z-index: 2;
        }
        
        .login-content {
            text-align: center;
            max-width: 450px;
            width: 100%;
            padding: 0.5rem 0;
        }
        
        .login-logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            display: block;
            animation: fadeInUp 1s ease-out;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }
        
        .login-logo:hover {
            transform: scale(1.05);
            filter: brightness(1.1) drop-shadow(0 0 20px rgba(251, 191, 36, 0.3));
        }
        
        .login-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        
        .login-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.5);
            color: #ffffff;
            animation: fadeInUp 1s ease-out 0.2s both;
        }
        

        
        .login-subtitle {
            font-size: 1rem;
            margin-bottom: 20px;
            color: #e2e8f0;
            opacity: 0.9;
            animation: fadeInUp 1s ease-out 0.3s both;
        }
        
        .login-form {
            background: rgba(255, 255, 255, 0.05);
            padding: 25px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            animation: fadeInUp 1s ease-out 0.4s both;
        }
        
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #ffffff;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .form-group label i {
            margin-right: 10px;
            color: #fbbf24;
            width: 16px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .form-control:focus {
            outline: none;
            border-color: #fbbf24;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 20px rgba(251, 191, 36, 0.3);
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .password-field {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            font-size: 16px;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: #fbbf24;
        }
        
        .password-field input[type="password"],
        .password-field input[type="text"] {
            padding-right: 50px;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px 24px;
            background: #fbbf24;
            color: #0f172a;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(251, 191, 36, 0.3);
        }
        
        .btn-login:hover {
            background: #f59e0b;
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(251, 191, 36, 0.4);
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        .back-to-home {
            text-align: center;
            margin-top: 20px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .back-to-home a {
            color: #fbbf24;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
        }
        
        .back-to-home a:hover {
            color: #ffffff;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .login-footer p {
            color: #94a3b8;
            font-size: 0.9rem;
            opacity: 0.8;
            margin: 0;
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
        
        @media (max-width: 768px) {
            .login-logo {
                width: 100px;
                height: 100px;
                margin-bottom: 20px;
            }
            
            .login-title {
                font-size: 1.5rem;
                margin-bottom: 8px;
            }
            
            .login-subtitle {
                font-size: 0.9rem;
                margin-bottom: 20px;
            }
            
            .login-form {
                padding: 20px 15px;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
            
            .form-control {
                padding: 8px 12px;
            }
            
            .btn-login {
                padding: 10px 20px;
            }
            
            .back-to-home {
                margin-top: 15px;
                padding: 10px;
            }
            
            .login-footer {
                margin-top: 15px;
                padding-top: 12px;
            }
        }
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .back-to-home a:hover {
            color: #764ba2;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-content">
            <div class="login-logo" onclick="window.location.reload()">
                <img src="assets/SUNNY_POLYMERS logo.jpg" alt="Sunny Polymers Logo">
            </div>
            
            <h1 class="login-title" style="position: relative;">Employee Portal</h1>
            <p class="login-subtitle">Attendance & Payroll Management System</p>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="mobile">
                        <i class="fas fa-mobile-alt"></i>
                        Mobile Number
                    </label>
                    <input type="text" id="mobile" name="mobile" class="form-control" placeholder="Enter mobile number" required>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="password-icon"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </button>
            </form>
            
            <div class="back-to-home">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i>
                    Back to Home
                </a>
            </div>
            
            <div class="login-footer">
                <p>© 2025 Sunny Polymers. All rights reserved.</p>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>
