<?php
session_start();
require_once 'vendor/autoload.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
        $database = $mongoClient->selectDatabase('ghion_travel');
        $usersCollection = $database->selectCollection('users');
        
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        if (empty($name) || empty($email) || empty($password)) {
            $error = 'All fields are required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } else {
            // Check if user already exists
            $existingUser = $usersCollection->findOne(['email' => $email]);
            if ($existingUser) {
                $error = 'Email already registered';
            } else {
                // Create new user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $userData = [
                    'name' => $name,
                    'email' => $email,
                    'password' => $hashedPassword,
                    'created_at' => new MongoDB\BSON\UTCDateTime(),
                    'role' => 'user'
                ];
                
                $result = $usersCollection->insertOne($userData);
                
                if ($result->getInsertedCount() > 0) {
                    $success = 'Registration successful! You can now login.';
                    // Clear form
                    $name = $email = '';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
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
    <title>Register - Ghion Travel</title>
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
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .auth-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        
        .auth-header {
            background: linear-gradient(135deg, var(--primary) 0%, #4b86b4 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .auth-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .auth-header p {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .auth-body {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #1a4f7a;
        }
        
        .btn-secondary {
            background: var(--secondary);
        }
        
        .btn-secondary:hover {
            background: #c0392b;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .auth-footer {
            text-align: center;
            padding: 20px 30px;
            background: #f8f9fa;
            border-top: 1px solid #e1e5e9;
        }
        
        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }
        
        .strength-weak { color: #e74c3c; }
        .strength-medium { color: #f39c12; }
        .strength-strong { color: #27ae60; }
        
        @media (max-width: 480px) {
            .auth-container {
                margin: 10px;
            }
            
            .auth-header, .auth-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1><i class="fas fa-user-plus"></i> Create Account</h1>
            <p>Join Ghion Travel and start your journey</p>
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
            <?php endif; ?>
            
            <form method="POST" id="registerForm">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                           required placeholder="Enter your full name">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           required placeholder="Enter your email">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           required placeholder="Enter your password" minlength="6">
                    <div class="password-strength" id="passwordStrength"></div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                           required placeholder="Confirm your password">
                    <div class="password-strength" id="passwordMatch"></div>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
        </div>
        
        <div class="auth-footer">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </div>

    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const strengthIndicator = document.getElementById('passwordStrength');
        const matchIndicator = document.getElementById('passwordMatch');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = '';
            let strengthClass = '';
            
            if (password.length === 0) {
                strength = '';
            } else if (password.length < 6) {
                strength = 'Weak';
                strengthClass = 'strength-weak';
            } else if (password.length < 10) {
                strength = 'Medium';
                strengthClass = 'strength-medium';
            } else {
                strength = 'Strong';
                strengthClass = 'strength-strong';
            }
            
            strengthIndicator.textContent = strength;
            strengthIndicator.className = 'password-strength ' + strengthClass;
            
            // Check password match
            checkPasswordMatch();
        });
        
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword.length === 0) {
                matchIndicator.textContent = '';
            } else if (password === confirmPassword) {
                matchIndicator.textContent = 'Passwords match';
                matchIndicator.className = 'password-strength strength-strong';
            } else {
                matchIndicator.textContent = 'Passwords do not match';
                matchIndicator.className = 'password-strength strength-weak';
            }
        }
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });
    </script>
</body>
</html>