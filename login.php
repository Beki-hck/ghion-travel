<?php
session_start();
require_once 'vendor/autoload.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
        $database = $mongoClient->selectDatabase('ghion_travel');
        $usersCollection = $database->selectCollection('users');
        
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        // Validation
        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password';
        } else {
            // Find user by email
            $user = $usersCollection->findOne(['email' => $email]);
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = (string)$user['_id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'] ?? 'user';
                
                // Update last login time
                $usersCollection->updateOne(
                    ['_id' => $user['_id']],
                    ['$set' => ['last_login' => new MongoDB\BSON\UTCDateTime()]]
                );
                
                // Redirect to appropriate page
                if (isset($_SESSION['redirect_url'])) {
                    $redirect_url = $_SESSION['redirect_url'];
                    unset($_SESSION['redirect_url']);
                    header('Location: ' . $redirect_url);
                } else {
                    // Redirect based on user role
                    if ($_SESSION['user_role'] === 'admin') {
                        header('Location: admin_dashboard.php');
                    } else {
                        header('Location: index.php');
                    }
                }
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        }
    } catch (Exception $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Store redirect URL if coming from protected page
if (isset($_GET['redirect'])) {
    $_SESSION['redirect_url'] = $_GET['redirect'];
}

// Demo credentials for testing
$demo_credentials = [
    'admin' => [
        'email' => 'admin@ghiontours.com',
        'password' => 'admin123'
    ],
    'user' => [
        'email' => 'user@example.com',
        'password' => 'user123'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ghion Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        :root {
            --primary: #2a6496;
            --secondary: #e74c3c;
            --success: #28a745;
            --dark: #2c3e50;
            --light: #f8f9fa;
            --gold: #ffd700;
            --dark-blue: #1e3a5f;
             overflow-y: scroll; /* Always show scrollbar to prevent layout shift */
    scroll-behavior: smooth; /* Smooth scrolling */
        }
        
        body {
            background: linear-gradient(135deg, #1e3a5f 0%, #2a6496 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
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
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .auth-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15), 0 8px 30px rgba(0,0,0,0.12);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .auth-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.2), 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .auth-header {
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .auth-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .auth-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 700;
            letter-spacing: -0.5px;
            position: relative;
            z-index: 1;
        }
        
        .auth-header p {
            opacity: 0.9;
            font-size: 15px;
            font-weight: 400;
            position: relative;
            z-index: 1;
        }
        
        .logo {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
            position: relative;
            z-index: 1;
        }
        
        .auth-body {
            padding: 35px 30px;
        }
        
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e8ecef;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #fafbfc;
            color: var(--dark);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(42, 100, 150, 0.1);
            transform: translateY(-1px);
        }
        
        .form-control::placeholder {
            color: #a0a4a8;
            font-weight: 400;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--dark-blue) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.5px;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(42, 100, 150, 0.3);
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            margin-top: 12px;
        }
        
        .btn-secondary:hover {
            box-shadow: 0 8px 20px rgba(108, 117, 125, 0.3);
        }
        
        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
            font-size: 14px;
            border: 1px solid;
            position: relative;
            padding-left: 50px;
        }
        
        .alert::before {
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
        }
        
        .alert-error {
            background: #fdf2f2;
            color: #c53030;
            border-color: #feb2b2;
        }
        
        .alert-error::before {
            content: '\f06a';
        }
        
        .auth-footer {
            text-align: center;
            padding: 25px 30px;
            background: #f8fafc;
            border-top: 1px solid #e8ecef;
            font-size: 14px;
            color: #6b7280;
        }
        
        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .auth-footer a:hover {
            color: var(--dark-blue);
            text-decoration: underline;
        }
        
        .forgot-password {
            text-align: right;
            margin-top: -12px;
            margin-bottom: 20px;
        }
        
        .forgot-password a {
            color: var(--primary);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .forgot-password a:hover {
            color: var(--dark-blue);
            text-decoration: underline;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 38px;
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 4px;
            transition: color 0.3s ease;
            z-index: 2;
        }
        
        .password-toggle:hover {
            color: var(--primary);
        }
        
        .demo-credentials {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 10px;
            padding: 20px;
            margin-top: 25px;
            font-size: 13px;
        }
        
        .demo-credentials h4 {
            color: var(--primary);
            margin-bottom: 12px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .demo-account {
            background: white;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid #e1e5e9;
        }
        
        .demo-account:last-child {
            margin-bottom: 0;
        }
        
        .demo-account strong {
            color: var(--dark);
            display: block;
            margin-bottom: 5px;
        }
        
        .demo-account .credentials {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #6b7280;
        }
        
        .fill-demo {
            background: var(--success);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 11px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .fill-demo:hover {
            background: #218838;
        }
        
        @media (max-width: 480px) {
            body {
                padding: 15px;
            }
            
            .auth-container {
                margin: 0;
                border-radius: 15px;
                 position: absolute;
            
            }
            
            .auth-header, .auth-body {
                padding: 30px 20px;
            }
            
            .auth-header h1 {
                font-size: 24px;
            }
            
            .auth-footer {
                padding: 20px;
            }
            
            .demo-credentials {
                padding: 15px;
            }
        }
        
        /* Loading animation for button */
        .btn.loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .btn.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .social-login {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .social-btn {
            flex: 1;
            padding: 12px;
            border: 1px solid #e1e5e9;
            border-radius: 10px;
            background: white;
            color: var(--dark);
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .social-btn:hover {
            background: #f8f9fa;
            border-color: var(--primary);
            transform: translateY(-1px);
        }
        
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
            color: #6b7280;
            font-size: 13px;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e1e5e9;
        }
        
        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <i class="fas fa-plane-departure logo"></i>
            <h1>Welcome to Ghion Travel</h1>
            <p>Sign in to your account</p>
        </div>
        
        <div class="auth-body">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           required placeholder="Enter your email">
                    <i class="fas fa-envelope" style="position: absolute; left: 15px; top: 40px; color: #6b7280;"></i>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           required placeholder="Enter your password">
                    <i class="fas fa-lock" style="position: absolute; left: 15px; top: 40px; color: #6b7280;"></i>
                    <button type="button" class="password-toggle" id="passwordToggle">
                        <i class="fas fa-eye"></i>
                    </button>
                    <div class="forgot-password">
                        <a href="forgot_password.php">Forgot Password?</a>
                    </div>
                  </div>
                
                 <button type="submit" class="btn" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
                
                <div class="divider">
                    <span>or continue with</span>
                </div>
                
               
                 <div class="divider">
                <button>
                <a href="register.php" class="btn" id="loginBtn">
                <i class="fas fa-user-plus"></i> Create New Account
                </a>
            </button>
            </div>
            </form>
        <div class="auth-footer">
            Don't have an account? <a href="register.php">Sign Up Now</a>
        </div>
    </div>

    <script>
        // Password toggle functionality
        const passwordToggle = document.getElementById('passwordToggle');
        const passwordInput = document.getElementById('password');
        
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        // Demo credentials filler
        function fillDemoCredentials(type) {
            const credentials = {
                'admin': {
                    email: '<?php echo $demo_credentials['admin']['email']; ?>',
                    password: '<?php echo $demo_credentials['admin']['password']; ?>'
                },
                'user': {
                    email: '<?php echo $demo_credentials['user']['email']; ?>',
                    password: '<?php echo $demo_credentials['user']['password']; ?>'
                }
            };
            
            document.getElementById('email').value = credentials[type].email;
            document.getElementById('password').value = credentials[type].password;
            
            // Show success message
            alert(`${type.charAt(0).toUpperCase() + type.slice(1)} credentials filled! Click Sign In to continue.`);
        }

        // Form validation and loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const loginBtn = document.getElementById('loginBtn');
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return false;
            }
            
            // Add loading state
            loginBtn.classList.add('loading');
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
        });

        // Enter key support
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').dispatchEvent(new Event('submit'));
            }
        });

        // Auto-focus on email field
        document.getElementById('email').focus();
    </script>
</body>
</html>