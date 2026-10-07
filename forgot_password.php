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
$token = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
        $database = $mongoClient->selectDatabase('ghion_travel');
        $usersCollection = $database->selectCollection('users');
        $passwordResetsCollection = $database->selectCollection('password_resets');
        
        $email = trim($_POST['email']);
        
        // Validation
        if (empty($email)) {
            $error = 'Please enter your email address';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } else {
            // Check if user exists
            $user = $usersCollection->findOne(['email' => $email]);
            
            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = new UTCDateTime((time() + 3600) * 1000); // 1 hour from now
                
                // Store reset token in database
                $passwordResetsCollection->updateOne(
                    ['email' => $email],
                    [
                        '$set' => [
                            'token' => $token,
                            'expires' => $expires,
                            'created_at' => new UTCDateTime()
                        ]
                    ],
                    ['upsert' => true]
                );
                
                $success = "Password reset token has been generated and stored in database.";
                
            } else {
                // For security, don't reveal if email exists or not
                $success = 'If an account with that email exists, a reset token has been generated.';
            }
        }
    } catch (Exception $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Ghion Travel</title>
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
            max-width: 500px;
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
        
        .instructions {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #1e40af;
            line-height: 1.5;
        }
        
        .instructions i {
            margin-right: 8px;
            color: var(--primary);
        }
        
        .token-display {
            margin-top: 15px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }
        
        .token-display h4 {
            margin: 0 0 12px 0;
            color: #1e40af;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .token-code {
            display: block;
            background: #1e293b;
            color: #f1f5f9;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            word-break: break-all;
            margin-bottom: 15px;
            line-height: 1.4;
            border: 1px solid #334155;
        }
        
        .reset-link {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary) 0%, var(--dark-blue) 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }
        
        .reset-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(42, 100, 150, 0.3);
            color: white;
        }
        
        .url-display {
            background: #f1f5f9;
            padding: 10px 12px;
            border-radius: 6px;
            font-size: 12px;
            color: #475569;
            border: 1px solid #e2e8f0;
            word-break: break-all;
            margin-top: 10px;
        }
        
        @media (max-width: 480px) {
            body {
                padding: 15px;
            }
            
            .auth-container {
                margin: 0;
                border-radius: 15px;
                max-width: 100%;
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
            
            .token-code {
                font-size: 12px;
                padding: 12px;
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
        
        .copy-btn {
            background: #64748b;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            margin-left: 8px;
            transition: background 0.3s ease;
        }
        
        .copy-btn:hover {
            background: #475569;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <i class="fas fa-key logo"></i>
            <h1>Reset Your Password</h1>
            <p>Enter your email to generate a reset token</p>
        </div>
        
        <div class="auth-body">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    
                    <?php if ($token): ?>
                    <div class="token-display">
                      <!-- <h4>
                            <i class="fas fa-key"></i> Your Password Reset Token
                        </h4>
                        <code class="token-code" id="tokenCode">
                            <?php echo htmlspecialchars($token); ?>
                            <button class="copy-btn" onclick="copyToken()">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </code>
                        <p style="margin: 15px 0 10px 0; color: #475569; font-size: 14px;">
                            <strong>Click the link below to reset your password:</strong>
                        </p>-->
                        <a href="reset_password.php?token=<?php echo urlencode($token); ?>" 
                           class="reset-link">
                           <i class="fas fa-unlock"></i> Reset Password Now
                        </a>
                       <!-- <div class="url-display">
                            <strong>Or use this URL:</strong><br>
                            reset_password.php?token=<?php echo htmlspecialchars($token); ?>
                        </div>-->
                        <!--<p style="margin: 15px 0 0 0; color: #ef4444; font-size: 12px; font-weight: 500;">
                            <i class="fas fa-clock"></i> This token expires in 1 hour
                        </p>-->
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$success || $error): ?>
            <div class="instructions">
                <i class="fas fa-info-circle"></i>
                Enter your email address to generate a password reset token. The token will be displayed on this page.
            </div>
            
            <form method="POST" id="forgotPasswordForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           required placeholder="Enter your email">
                    <i class="fas fa-envelope" style="position: absolute; left: 15px; top: 40px; color: #6b7280;"></i>
                </div>
                
                <button type="submit" class="btn" id="submitBtn">
                    <i class="fas fa-key"></i> Generate Reset Token
                </button>
            </form>
            <?php endif; ?>
        </div>
        
        <div class="auth-footer">
            Remember your password? <a href="login.php">Back to Sign In</a>
        </div>
    </div>

    <script>
        // Form validation and loading state
        document.getElementById('forgotPasswordForm')?.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const submitBtn = document.getElementById('submitBtn');
            
            if (!email) {
                e.preventDefault();
                alert('Please enter your email address');
                return false;
            }
            
            // Add loading state
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating Token...';
        });

        // Copy token to clipboard
        function copyToken() {
            const tokenText = "<?php echo $token ? htmlspecialchars($token) : ''; ?>";
            navigator.clipboard.writeText(tokenText).then(function() {
                const btn = event.target.closest('.copy-btn');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                btn.style.background = '#10b981';
                
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.style.background = '#64748b';
                }, 2000);
            }).catch(function(err) {
                alert('Failed to copy token: ' + err);
            });
        }

        // Enter key support
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('forgotPasswordForm')?.dispatchEvent(new Event('submit'));
            }
        });

        // Auto-focus on email field if no token is displayed
        <?php if (!$token): ?>
        document.getElementById('email')?.focus();
        <?php endif; ?>
    </script>
</body>
</html>