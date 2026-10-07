<?php
session_start();
require_once 'vendor/autoload.php';

use MongoDB\BSON\UTCDateTime;

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';
$token_valid = false;
$email = '';
$token = '';

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $passwordResetsCollection = $database->selectCollection('password_resets');
    $usersCollection = $database->selectCollection('users');

    // Check if token is provided
    if (!isset($_GET['token']) || empty($_GET['token'])) {
        $error = 'Invalid or missing reset token.';
    } else {
        $token = $_GET['token'];
        
        // Validate token
        $reset_request = $passwordResetsCollection->findOne([
            'token' => $token,
            'expires' => ['$gt' => new UTCDateTime(time() * 1000)]
        ]);
        
        if ($reset_request) {
            $token_valid = true;
            $email = $reset_request['email'];
        } else {
            $error = 'Invalid or expired reset token. Please request a new password reset.';
        }
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
        $new_password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($new_password) || empty($confirm_password)) {
            $error = 'Please fill in all fields.';
        } elseif (strlen($new_password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            // Update user's password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $usersCollection->updateOne(
                ['email' => $email],
                ['$set' => [
                    'password' => $hashed_password,
                    'updated_at' => new UTCDateTime()
                ]]
            );
            
            // Delete used reset token
            $passwordResetsCollection->deleteOne(['token' => $token]);
            
            $success = 'Password has been reset successfully! You can now login with your new password.';
            $token_valid = false; // Prevent further use
        }
    }
} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Ghion Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
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
    scroll-behavior: smooth; 
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
            width: 80%;
            max-width: 450px;
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
            padding: 14px 16px 14px 45px;
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
        
        .alert-success {
            background: #f0f9f4;
            color: #0d6832;
            border-color: #b8ebd0;
        }
        
        .alert-success::before {
            content: '\f058';
        }
        
        .alert-info {
            background: #f0f9ff;
            color: #1e40af;
            border-color: #bae6fd;
        }
        
        .alert-info::before {
            content: '\f05a';
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
        
        .requirements {
            background: #f8f9fa;
            border: 1px solid #e1e5e9;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            font-size: 13px;
            color: #666;
            line-height: 1.5;
        }
        
        .requirements ul {
            margin-left: 20px;
            margin-top: 8px;
        }
        
        .requirements li {
            margin-bottom: 4px;
        }
        
        .password-strength {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .password-match {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 500;
        }
        
        @media (max-width: 480px) {
            body {
                padding: 15px;
            }
            
            .auth-container {
                margin: 0;
                border-radius: 15px;
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
        }
        
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
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <i class="fas fa-lock logo"></i>
            <h1>Set New Password</h1>
            <p>Create a new password for your account</p>
        </div>
        
        <div class="auth-body">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
                
                <div class="auth-footer" style="border-top: none; padding-top: 0;">
                    <a href="login.php">← Back to Sign In</a>
                </div>
            <?php elseif ($token_valid): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    You are resetting password for: <strong><?php echo htmlspecialchars($email); ?></strong>
                </div>

                <div class="requirements">
                    <strong>Password Requirements:</strong>
                    <ul>
                        <li>At least 8 characters long</li>
                        <li>Include both letters and numbers</li>
                        <li>Use special characters for better security</li>
                    </ul>
                </div>

                <form method="POST" id="resetPasswordForm">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               required placeholder="Enter new password" minlength="8">
                        <i class="fas fa-lock" style="position: absolute; left: 15px; top: 40px; color: #6b7280;"></i>
                        <button type="button" class="password-toggle" id="passwordToggle">
                            <i class="fas fa-eye"></i>
                        </button>
                        <div class="password-strength" id="password-strength"></div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                               required placeholder="Confirm new password" minlength="8">
                        <i class="fas fa-lock" style="position: absolute; left: 15px; top: 40px; color: #6b7280;"></i>
                        <button type="button" class="password-toggle" id="confirmPasswordToggle">
                            <i class="fas fa-eye"></i>
                        </button>
                        <div class="password-match" id="password-match"></div>
                    </div>

                    <button type="submit" class="btn" id="submitBtn">
                        <i class="fas fa-save"></i> Reset Password
                    </button>
                </form>

                <div class="auth-footer" style="border-top: none; padding-top: 20px;">
                    <a href="login.php">← Back to Sign In</a>
                </div>

            <?php else: ?>
                <div class="auth-footer" style="border-top: none;">
                    <a href="forgot_password.php">← Request New Reset Token</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Password toggle functionality
        const passwordToggle = document.getElementById('passwordToggle');
        const confirmPasswordToggle = document.getElementById('confirmPasswordToggle');
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        
        if (passwordToggle) {
            passwordToggle.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
            
            confirmPasswordToggle.addEventListener('click', function() {
                const type = confirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
        }

        // Password strength indicator
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 'Weak';
                let color = '#c33';

                if (password.length >= 12 && /[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                    strength = 'Strong';
                    color = '#363';
                } else if (password.length >= 8) {
                    strength = 'Medium';
                    color = '#c60';
                }

                document.getElementById('password-strength').textContent = `Strength: ${strength}`;
                document.getElementById('password-strength').style.color = color;
            });

            // Password match checker
            confirmInput.addEventListener('input', function() {
                if (passwordInput.value !== this.value) {
                    document.getElementById('password-match').textContent = 'Passwords do not match';
                    document.getElementById('password-match').style.color = '#c33';
                } else {
                    document.getElementById('password-match').textContent = 'Passwords match';
                    document.getElementById('password-match').style.color = '#363';
                }
            });
        }

        // Form validation and loading state
        document.getElementById('resetPasswordForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password')?.value;
            const confirm = document.getElementById('confirm_password')?.value;
            const submitBtn = document.getElementById('submitBtn');
            
            if (!password || !confirm) {
                e.preventDefault();
                alert('Please fill in all fields');
                return false;
            }

            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
                return;
            }

            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match');
                return;
            }
            
            // Add loading state
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';
        });

        // Enter key support
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('resetPasswordForm')?.dispatchEvent(new Event('submit'));
            }
        });
    </script>
</body>
</html>