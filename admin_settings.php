<?php
session_start();
require_once 'vendor/autoload.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'site_name' => $_POST['site_name'] ?? '',
        'site_email' => $_POST['site_email'] ?? '',
        'currency' => $_POST['currency'] ?? 'USD',
        'timezone' => $_POST['timezone'] ?? 'Africa/Addis_Ababa',
        'booking_confirmation' => isset($_POST['booking_confirmation']),
        'email_notifications' => isset($_POST['email_notifications']),
        'admin_emails' => $_POST['admin_emails'] ?? ''
    ];
    
    $_SESSION['success_message'] = 'Settings updated successfully!';
    
    $_SESSION['current_settings'] = $settings;
}

// Load settings - check session first, then use defaults
if (isset($_SESSION['current_settings'])) {
    $settings = $_SESSION['current_settings'];
} else {
    // Default settings (in real app, load from database)
    $settings = [
        'site_name' => 'Ghion Tour and Travel',
        'site_email' => 'info@ghiontours.com',
        'currency' => 'USD',
        'timezone' => 'Africa/Addis_Ababa',
        'booking_confirmation' => true,
        'email_notifications' => true,
        'admin_emails' => 'admin@ghiontours.com,manager@ghiontours.com'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Ghion Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="adminstyles.css?v=<?php echo time(); ?>">
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
                <li><a href="admin_bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="admin_analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                 <li><a href="admin_notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                <li><a href="admin_settings.php" class="active"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>System Settings</h1>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> 
                    <span><?php echo $_SESSION['success_message']; ?></span>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> 
                    <span><?php echo $_SESSION['error_message']; ?></span>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <div class="card">
                <div class="settings-container">
                    <!-- Settings Tabs -->
                    <div class="settings-tabs">
                        <div class="tab active" data-tab="general">
                            <i class="fas fa-cog"></i> General
                        </div>
                        <div class="tab" data-tab="notifications">
                            <i class="fas fa-bell"></i> Notifications
                        </div>
                        <div class="tab" data-tab="system">
                            <i class="fas fa-info-circle"></i> System Info
                        </div>
                    </div>
                    
                    <!-- General Settings -->
                    <div class="tab-content active" id="general-tab">
                        <form method="POST">
                            <div class="form-group">
                                <label for="site_name">Site Name *</label>
                                <input type="text" id="site_name" name="site_name" required
                                       value="<?php echo htmlspecialchars($settings['site_name']); ?>"
                                       placeholder="Enter your site name">
                            </div>
                            
                            <div class="form-group">
                                <label for="site_email">Site Email *</label>
                                <input type="email" id="site_email" name="site_email" required
                                       value="<?php echo htmlspecialchars($settings['site_email']); ?>"
                                       placeholder="Enter your site email">
                            </div>
                            
                            <div class="form-group">
                                <label for="currency">Default Currency *</label>
                                <select id="currency" name="currency" required>
                                    <option value="USD" <?php echo $settings['currency'] === 'USD' ? 'selected' : ''; ?>>US Dollar ($)</option>
                                    <option value="ETB" <?php echo $settings['currency'] === 'ETB' ? 'selected' : ''; ?>>Ethiopian Birr (ETB)</option>
                                    <option value="EUR" <?php echo $settings['currency'] === 'EUR' ? 'selected' : ''; ?>>Euro (€)</option>
                                    <option value="GBP" <?php echo $settings['currency'] === 'GBP' ? 'selected' : ''; ?>>British Pound (£)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="timezone">Timezone *</label>
                                <select id="timezone" name="timezone" required>
                                    <option value="Africa/Addis_Ababa" <?php echo $settings['timezone'] === 'Africa/Addis_Ababa' ? 'selected' : ''; ?>>Addis Ababa (EAT)</option>
                                    <option value="UTC" <?php echo $settings['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                    <option value="America/New_York" <?php echo $settings['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>New York (EST)</option>
                                    <option value="Europe/London" <?php echo $settings['timezone'] === 'Europe/London' ? 'selected' : ''; ?>>London (GMT)</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="submit-btn">
                                <i class="fas fa-save"></i> Save General Settings
                            </button>
                        </form>
                    </div>
                    
                    <!-- Notifications Settings -->
                    <div class="tab-content" id="notifications-tab">
                        <form method="POST">
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" id="booking_confirmation" name="booking_confirmation" value="1"
                                           <?php echo $settings['booking_confirmation'] ? 'checked' : ''; ?>>
                                    <label for="booking_confirmation">Send booking confirmation emails</label>
                                </div>
                                <div class="form-help">
                                    Automatically send confirmation emails to customers when they make bookings
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" id="email_notifications" name="email_notifications" value="1"
                                           <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>>
                                    <label for="email_notifications">Enable admin email notifications</label>
                                </div>
                                <div class="form-help">
                                    Receive email notifications for new bookings and important system events
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="admin_emails">Admin Notification Emails</label>
                                <textarea id="admin_emails" name="admin_emails" rows="3"
                                          placeholder="Enter email addresses separated by commas"><?php echo htmlspecialchars($settings['admin_emails']); ?></textarea>
                                <div class="form-help">
                                    Separate multiple email addresses with commas. These emails will receive system notifications.
                                </div>
                            </div>
                            
                            <button type="submit" class="submit-btn">
                                <i class="fas fa-save"></i> Save Notification Settings
                            </button>
                        </form>
                    </div>
                    
                    <!-- System Info -->
                    <div class="tab-content" id="system-tab">
                        <h3 style="margin-bottom: 20px; color: var(--dark);">System Information</h3>
                        
                        <div class="system-info">
                            <div class="info-row">
                                <span class="info-label">PHP Version:</span>
                                <span class="info-value"><?php echo phpversion(); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">MongoDB Extension:</span>
                                <span class="info-value" style="color: <?php echo extension_loaded('mongodb') ? 'var(--success)' : 'var(--danger)'; ?>">
                                    <?php echo extension_loaded('mongodb') ? '✅ Enabled' : '❌ Disabled'; ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Server Software:</span>
                                <span class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Database:</span>
                                <span class="info-value">MongoDB</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Database Name:</span>
                                <span class="info-value">ghion_travel</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Collections:</span>
                                <span class="info-value">bookings, packages, users</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Admin Panel Version:</span>
                                <span class="info-value">1.0.0</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Last Updated:</span>
                                <span class="info-value"><?php echo date('F j, Y g:i A'); ?></span>
                            </div>
                        </div>
                        
                        <div class="maintenance-section">
                            <h4 style="color: #856404; margin-bottom: 15px;">
                                <i class="fas fa-tools"></i> System Maintenance
                            </h4>
                            <p style="margin-bottom: 15px; color: #856404;">
                                Perform system maintenance tasks to keep your application running smoothly.
                            </p>
                            <div class="maintenance-buttons">
                                <button class="btn btn-primary" onclick="performMaintenance('clear_cache')">
                                    <i class="fas fa-broom"></i> Clear Cache
                                </button>
                                <button class="btn btn-success" onclick="performMaintenance('optimize_db')">
                                    <i class="fas fa-database"></i> Optimize Database
                                </button>
                                <button class="btn btn-danger" onclick="performMaintenance('backup_data')">
                                    <i class="fas fa-file-export"></i> Backup Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                
                // Show corresponding content
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId + '-tab').classList.add('active');
            });
        });

        // Maintenance functions
        function performMaintenance(action) {
            const actions = {
                'clear_cache': 'Clear Cache',
                'optimize_db': 'Optimize Database',
                'backup_data': 'Backup Data'
            };
            
            if (confirm(`Are you sure you want to ${actions[action].toLowerCase()}?`)) {
                alert(`${actions[action]} operation would be performed in a production environment.`);
                
                const button = event.target.closest('.btn');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                button.disabled = true;
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                    alert(`${actions[action]} completed successfully!`);
                }, 1500);
            }
        }

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let valid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        valid = false;
                        field.style.borderColor = 'var(--danger)';
                    } else {
                        field.style.borderColor = '';
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                }
            });
        });
    </script>
</body>
</html>