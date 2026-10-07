<?php
session_start();
require_once 'vendor/autoload.php';

if (isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Database connection
        $mongo = new MongoDB\Client("mongodb://localhost:27017");
        $db = $mongo->ghion_travel;
        $admins = $db->admins;
        
        $admin = $admins->findOne(['username' => $username]);
        
        if ($admin && password_verify($password, $admin['password'])) {
            if (($admin['status'] ?? 'active') === 'active') {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
                $_SESSION['admin_id'] = (string)$admin['_id'];
                
                // Update last login
                $admins->updateOne(
                    ['_id' => $admin['_id']],
                    ['$set' => ['last_login' => new MongoDB\BSON\UTCDateTime()]]
                );
                
                header("Location: admin_dashboard.php");
                exit;
            } else {
                $error = "Your account is not active. Please contact administrator.";
            }
        } else {
            $error = "Invalid username or password!";
        }
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Ghion Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        
        .login-header {
            background: linear-gradient(135deg, #2a6496, #4b86b4);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .login-header i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: #2a6496;
            outline: none;
        }
        
        .login-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .login-btn:hover {
            background: #c0392b;
        }
        
        .error-message {
            background: #ffebee;
            color: #d32f2f;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .signup-link {
            text-align: center;
            margin-top: 15px;
        }
        
        .signup-link a {
            color: #2a6496;
            text-decoration: none;
        }
        
        .signup-link a:hover {
            text-decoration: underline;
        }
        
        .demo-credentials {
            background: #e8f4fc;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-crown"></i>
            <h1>Admin Login</h1>
            <p>Ghion Travel Management System</p>
        </div>
        
        <div class="login-body">
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Enter your username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password">
                </div>
                
                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                
                
            </form>
        </div>
    </div>
</body>
</html>