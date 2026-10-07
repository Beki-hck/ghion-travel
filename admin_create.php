<?php
session_start();
require_once 'vendor/autoload.php';

// Check if user is logged in 
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Database connection
        $mongo = new MongoDB\Client("mongodb://localhost:27017");
        $db = $mongo->ghion_travel;
        $admins = $db->admins;

        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $role = $_POST['role'] ?? 'admin';
        $status = $_POST['status'] ?? 'active';

        // Validation
        if (empty($username)) {
            $errors[] = "Username is required";
        }

        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }

        if (empty($errors)) {
            // Check if username or email already exists
            $existing_admin = $admins->findOne([
                '$or' => [
                    ['username' => $username],
                    ['email' => $email]
                ]
            ]);

            if ($existing_admin) {
                $errors[] = "Username or email already exists";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Create admin document
                $admin_data = [
                    'username' => $username,
                    'email' => $email,
                    'password' => $hashed_password,
                    'role' => $role,
                    'status' => $status,
                    'created_at' => new MongoDB\BSON\UTCDateTime(),
                    'created_by' => $_SESSION['admin_username'] ?? 'system',
                    'last_login' => null
                ];

                $result = $admins->insertOne($admin_data);
                
                if ($result->getInsertedCount() > 0) {
                    $_SESSION['success_message'] = "Admin user created successfully!";
                    header("Location: admin_create.php");
                    exit;
                } else {
                    $errors[] = 'Registration failed. Please try again.';
                }
            }
        }
    } catch (Exception $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin - Ghion Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c5530;
            --secondary: #d4af37;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: var(--primary);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            background: rgba(0,0,0,0.2);
            text-align: center;
        }
        .sidebar-header p {
            color: #b0b7c3;
            font-size: 14px;
        }
        .sidebar-header h2 {
            margin-bottom: 5px;
            font-size: 1.5rem;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .sidebar-menu a.active {
            background: var(--secondary);
            color: var(--dark);
        }
        
        .sidebar-menu i {
            width: 20px;
            margin-right: 10px;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .header h1 {
            color: var(--primary);
            font-size: 2rem;
        }
        
        /* Card Styles */
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 25px;
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
        }
        
        .card-header h2 {
            color: var(--dark);
            font-size: 20px;
            font-weight: 600;
        }
        
        /* Form Styles */
        .form-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 15px;
            transition: all 0.3s;
            background: white;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(44, 85, 48, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-help {
            color: #6b7280;
            font-size: 13px;
            margin-top: 6px;
            font-style: italic;
        }
        
        .submit-btn {
            background: var(--success);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            width: 100%;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .submit-btn:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        
        .password-strength {
            margin-top: 8px;
            font-size: 12px;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
            
            .sidebar {
                display: none;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-crown"></i> Admin Panel</h2>
                <p>Ghion Travel</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="admin_packages.php"><i class="fas fa-suitcase"></i> Tour Packages</a></li>
                <li><a href="admin_cars.php"><i class="fas fa-car"></i> Cars</a></li>
                <li><a href="admin_hotels.php"><i class="fas fa-hotel"></i> Hotels</a></li>
                <li><a href="admin_bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="admin_contacts.php"><i class="fas fa-envelope"></i> Contacts & Feedback</a></li>
                <li><a href="admin_analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                <li><a href="admin_notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                <li><a href="admin_settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="admin_create.php" class="active"><i class="fas fa-user-plus"></i> Create Admin</a></li>
                <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Create Admin User</h1>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> 
                    <span><?php echo $_SESSION['success_message']; ?></span>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Please fix the following errors:</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h2>Admin Information</h2>
                </div>
                <div class="form-container">
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username *</label>
                                <input type="text" id="username" name="username" required 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                       placeholder="Enter username">
                                <div class="form-help">Unique username for the admin</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" required 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       placeholder="Enter email address">
                                <div class="form-help">Valid email address for notifications</div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" id="password" name="password" required 
                                       placeholder="Enter password" onkeyup="checkPasswordStrength()">
                                <div class="form-help">Minimum 6 characters</div>
                                <div id="password-strength" class="password-strength"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" required 
                                       placeholder="Confirm password" onkeyup="checkPasswordMatch()">
                                <div id="password-match" class="password-strength"></div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="role">Role</label>
                                <select id="role" name="role">
                                    <option value="admin" <?php echo ($_POST['role'] ?? 'admin') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="super_admin" <?php echo ($_POST['role'] ?? '') === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                                    <option value="manager" <?php echo ($_POST['role'] ?? '') === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                    <option value="support" <?php echo ($_POST['role'] ?? '') === 'support' ? 'selected' : ''; ?>>Support</option>
                                </select>
                                <div class="form-help">User role and permissions level</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="active" <?php echo ($_POST['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($_POST['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="suspended" <?php echo ($_POST['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                                <div class="form-help">Account status</div>
                            </div>
                        </div>
                        
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-user-plus"></i> Create Admin User
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Admin Roles Information</h2>
                </div>
                <div style="padding: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid var(--primary);">
                            <h4 style="color: var(--primary); margin-bottom: 10px;">Super Admin</h4>
                            <p style="color: #666; font-size: 14px;">Full access to all features and settings</p>
                        </div>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid var(--success);">
                            <h4 style="color: var(--success); margin-bottom: 10px;">Admin</h4>
                            <p style="color: #666; font-size: 14px;">Manage bookings, packages, users, and content</p>
                        </div>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid var(--warning);">
                            <h4 style="color: var(--warning); margin-bottom: 10px;">Manager</h4>
                            <p style="color: #666; font-size: 14px;">Manage bookings and customer service</p>
                        </div>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid var(--info);">
                            <h4 style="color: var(--info); margin-bottom: 10px;">Support</h4>
                            <p style="color: #666; font-size: 14px;">Customer support and basic operations</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthText = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthText.textContent = '';
                strengthText.className = 'password-strength';
                return;
            }
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            if (strength <= 2) {
                strengthText.textContent = 'Weak password';
                strengthText.className = 'password-strength strength-weak';
            } else if (strength <= 4) {
                strengthText.textContent = 'Medium password';
                strengthText.className = 'password-strength strength-medium';
            } else {
                strengthText.textContent = 'Strong password';
                strengthText.className = 'password-strength strength-strong';
            }
        }
        
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchText = document.getElementById('password-match');
            
            if (confirmPassword.length === 0) {
                matchText.textContent = '';
                return;
            }
            
            if (password === confirmPassword) {
                matchText.textContent = 'Passwords match';
                matchText.className = 'password-strength strength-strong';
            } else {
                matchText.textContent = 'Passwords do not match';
                matchText.className = 'password-strength strength-weak';
            }
        }
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Please make sure passwords match!');
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