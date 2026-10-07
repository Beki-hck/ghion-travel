<?php
session_start();
require_once 'vendor/autoload.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $notificationsCollection = $database->selectCollection('notifications');
    $bookingsCollection = $database->selectCollection('bookings');
    
    // Handle notification actions
    if (isset($_POST['mark_all_read'])) {
        $notificationsCollection->updateMany(
            ['read' => false],
            ['$set' => ['read' => true, 'read_at' => new MongoDB\BSON\UTCDateTime()]]
        );
        $_SESSION['success_message'] = 'All notifications marked as read!';
    }
    
    if (isset($_POST['mark_read'])) {
        $notificationsCollection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($_POST['notification_id'])],
            ['$set' => ['read' => true, 'read_at' => new MongoDB\BSON\UTCDateTime()]]
        );
        $_SESSION['success_message'] = 'Notification marked as read!';
    }
    
    if (isset($_POST['clear_all'])) {
        $notificationsCollection->deleteMany(['read' => true]);
        $_SESSION['success_message'] = 'All read notifications cleared!';
    }
    
    if (isset($_POST['delete_notification'])) {
        $notificationsCollection->deleteOne(
            ['_id' => new MongoDB\BSON\ObjectId($_POST['notification_id'])]
        );
        $_SESSION['success_message'] = 'Notification deleted!';
    }
    
    // Get notifications 
    $notifications = $notificationsCollection->find(
        [],
        ['sort' => ['created_at' => -1]]
    )->toArray();
    
    // Count unread notifications
    $unreadCount = $notificationsCollection->countDocuments(['read' => false]);
    
    // Get notification statistics
    $notificationStats = $notificationsCollection->aggregate([
        ['$group' => [
            '_id' => '$type',
            'count' => ['$sum' => 1],
            'unread' => ['$sum' => ['$cond' => ['if' => '$read', 'then' => 0, 'else' => 1]]]
        ]]
    ])->toArray();

} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

function generateSampleNotifications($notificationsCollection) {
    if ($notificationsCollection->countDocuments() === 0) {
        $sampleNotifications = [
            [
                'type' => 'booking',
                'title' => 'New Booking Received',
                'message' => 'Customer John Doe booked "Northern Historical Tour" for 2 passengers',
                'related_id' => '1',
                'read' => false,
                'created_at' => new MongoDB\BSON\UTCDateTime()
            ],
            [
                'type' => 'payment',
                'title' => 'Payment Received',
                'message' => 'Payment of $450.00 received for booking #BK001',
                'related_id' => '1',
                'read' => true,
                'read_at' => new MongoDB\BSON\UTCDateTime(strtotime('-1 hour') * 1000),
                'created_at' => new MongoDB\BSON\UTCDateTime(strtotime('-2 hours') * 1000)
            ],
            [
                'type' => 'system',
                'title' => 'System Update',
                'message' => 'New admin panel features available. Check the settings page.',
                'read' => false,
                'created_at' => new MongoDB\BSON\UTCDateTime(strtotime('-1 day') * 1000)
            ],
            [
                'type' => 'user',
                'title' => 'New User Registration',
                'message' => 'New user "Sarah Johnson" registered with email sarah@example.com',
                'related_id' => '2',
                'read' => false,
                'created_at' => new MongoDB\BSON\UTCDateTime(strtotime('-2 days') * 1000)
            ]
        ];
        
        foreach ($sampleNotifications as $notification) {
            $notificationsCollection->insertOne($notification);
        }
    }
}

// Generate sample data if needed
generateSampleNotifications($notificationsCollection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Ghion Travel</title>
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
                <li><a href="admin_notifications.php" class="active"><i class="fas fa-bell"></i> Notifications</a></li>
                <li><a href="admin_settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Notifications</h1>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <?php if ($unreadCount > 0): ?>
                        <span class="badge-unread notification-badge">
                            <i class="fas fa-bell"></i> <?php echo $unreadCount; ?> unread
                        </span>
                    <?php endif; ?>
                    <span style="color: #666; font-size: 14px;">
                        Total: <?php echo count($notifications); ?> notifications
                    </span>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> 
                    <span><?php echo $_SESSION['success_message']; ?></span>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <div class="notifications-grid">
                <!-- Left Column - Notifications List -->
                <div>
                    <!-- Bulk Actions -->
                    <div class="bulk-actions">
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="mark_all_read" class="btn btn-success">
                                <i class="fas fa-check-double"></i> Mark All as Read
                            </button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="clear_all" class="btn btn-warning" 
                                    onclick="return confirm('Are you sure you want to clear all read notifications?')">
                                <i class="fas fa-trash"></i> Clear Read Notifications
                            </button>
                        </form>
                    </div>
                    
                    <!-- Notifications List -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Notifications</h3>
                        </div>
                        
                        <?php if (empty($notifications)): ?>
                            <div class="empty-state">
                                <i class="fas fa-bell-slash"></i>
                                <h3>No Notifications</h3>
                                <p>You're all caught up! No new notifications at the moment.</p>
                            </div>
                        <?php else: ?>
                            <div style="max-height: 600px; overflow-y: auto;">
                                <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item <?php echo $notification['read'] ? '' : 'unread'; ?> <?php echo $notification['type'] ?? 'system'; ?>">
                                    <div class="notification-header">
                                        <div style="flex: 1;">
                                            <div class="notification-title">
                                                <?php echo htmlspecialchars($notification['title']); ?>
                                                <span class="notification-badge badge-<?php echo $notification['type'] ?? 'system'; ?>">
                                                    <?php echo ucfirst($notification['type'] ?? 'system'); ?>
                                                </span>
                                                <?php if (!$notification['read']): ?>
                                                    <span class="badge-unread notification-badge">New</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="notification-message">
                                                <?php echo htmlspecialchars($notification['message']); ?>
                                            </div>
                                        </div>
                                        <div class="notification-time">
                                            <?php 
                                            if ($notification['created_at'] instanceof MongoDB\BSON\UTCDateTime) {
                                                $createdAt = $notification['created_at']->toDateTime();
                                                $now = new DateTime();
                                                $diff = $now->diff($createdAt);
                                                
                                                if ($diff->days > 0) {
                                                    echo $createdAt->format('M j, Y g:i A');
                                                } elseif ($diff->h > 0) {
                                                    echo $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
                                                } elseif ($diff->i > 0) {
                                                    echo $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
                                                } else {
                                                    echo 'Just now';
                                                }
                                            } else {
                                                echo 'Unknown time';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <div class="notification-actions">
                                        <?php if (!$notification['read']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['_id']; ?>">
                                            <button type="submit" name="mark_read" class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i> Mark Read
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($notification['related_id'])): ?>
                                        <a href="#" class="btn btn-primary btn-sm">
                                            <i class="fas fa-external-link-alt"></i> View Related
                                        </a>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['_id']; ?>">
                                            <button type="submit" name="delete_notification" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Are you sure you want to delete this notification?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Column - Statistics & Settings -->
                <div>
                    <!-- Notification Statistics -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Notification Stats</h3>
                        </div>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo count($notifications); ?></div>
                                <div class="stat-label">Total</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number" style="color: var(--success);"><?php echo $unreadCount; ?></div>
                                <div class="stat-label">Unread</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number" style="color: var(--primary);">
                                    <?php 
                                    $bookingCount = array_reduce($notificationStats, function($carry, $item) {
                                        return $carry + ($item['_id'] === 'booking' ? $item['count'] : 0);
                                    }, 0);
                                    echo $bookingCount;
                                    ?>
                                </div>
                                <div class="stat-label">Bookings</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number" style="color: var(--success);">
                                    <?php 
                                    $paymentCount = array_reduce($notificationStats, function($carry, $item) {
                                        return $carry + ($item['_id'] === 'payment' ? $item['count'] : 0);
                                    }, 0);
                                    echo $paymentCount;
                                    ?>
                                </div>
                                <div class="stat-label">Payments</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notification Settings -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Notification Settings</h3>
                        </div>
                        <div style="padding: 15px;">
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <span style="font-weight: 500;">Email Notifications</span>
                                    <label class="switch">
                                        <input type="checkbox" checked>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <div style="font-size: 12px; color: #666;">
                                    Receive email notifications for important events
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <span style="font-weight: 500;">New Bookings</span>
                                    <label class="switch">
                                        <input type="checkbox" checked>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <div style="font-size: 12px; color: #666;">
                                    Get notified when new bookings are made
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <span style="font-weight: 500;">Payment Updates</span>
                                    <label class="switch">
                                        <input type="checkbox" checked>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <div style="font-size: 12px; color: #666;">
                                    Notify when payments are received
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <span style="font-weight: 500;">System Alerts</span>
                                    <label class="switch">
                                        <input type="checkbox" checked>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <div style="font-size: 12px; color: #666;">
                                    Receive system maintenance alerts
                                </div>
                            </div>
                            
                            <a href="admin_settings.php" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                                <i class="fas fa-cog"></i> Manage All Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Switch toggle styles */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
        }
        
        input:checked + .slider {
            background-color: var(--success);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .slider.round {
            border-radius: 24px;
        }
        
        .slider.round:before {
            border-radius: 50%;
        }
    </style>

    <script>
        // Auto-refresh notifications every 30 seconds
        setInterval(() => {
            console.log('Checking for new notifications...');
        }, 30000);
        
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (!e.target.closest('.notification-actions')) {
                    const form = this.querySelector('form');
                    if (form && !this.classList.contains('read')) {
                        form.submit();
                    }
                }
            });
        });
    </script>
</body>
</html>