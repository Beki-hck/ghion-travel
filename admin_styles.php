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
    $usersCollection = $database->selectCollection('users');
    $bookingsCollection = $database->selectCollection('bookings');
    
    // Get user statistics
    $totalUsers = $usersCollection->countDocuments();
    
    // Get booking statistics per user
    $userStats = $bookingsCollection->aggregate([
        ['$group' => [
            '_id' => '$user_id',
            'bookingCount' => ['$sum' => 1],
            'totalSpent' => ['$sum' => '$total_price'],
            'firstBooking' => ['$min' => '$booking_date'],
            'lastBooking' => ['$max' => '$booking_date']
        ]],
        ['$sort' => ['totalSpent' => -1]]
    ])->toArray();
    
    // Get all users
    $users = $usersCollection->find([], ['sort' => ['created_at' => -1]])->toArray();
    
    // Calculate average bookings per user
    $avgBookingsPerUser = $totalUsers > 0 ? array_sum(array_column($userStats, 'bookingCount')) / $totalUsers : 0;

} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Ghion Travel</title>
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
            --warning: #ffc107;
            --danger: #dc3545;
            --dark: #343a40;
            --light: #f8f9fa;
        }
        
        body {
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: var(--dark);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            background: var(--primary);
            text-align: center;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
        }
        
        .sidebar-menu li {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: var(--primary);
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* User Stats Grid */
        .user-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .user-stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid var(--primary);
        }
        
        .user-stat-number {
            font-size: 32px;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .user-stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            margin: 0 auto 15px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light);
        }
        
        .card-title {
            font-size: 20px;
            color: var(--dark);
            font-weight: 600;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: var(--light);
            font-weight: 600;
            color: var(--dark);
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #1b4a75;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }
        
        .logout-btn {
            background: var(--danger);
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .user-email {
            color: #666;
            font-size: 14px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
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
                <li><a href="admin_bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="admin_contacts.php" class="active"><i class="fas fa-comments"></i> Contacts & Feedback</a></li>
                <li><a href="admin_analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                <li><a href="admin_notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                <li><a href="admin_settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Users Management</h1>
                <div>
                    <span>Total: <?php echo $totalUsers; ?> users</span>
                </div>
            </div>
            
            <!-- User Statistics -->
            <div class="user-stats">
                <div class="user-stat-card">
                    <div class="user-stat-number"><?php echo $totalUsers; ?></div>
                    <div class="user-stat-label">Total Users</div>
                </div>
                
                <div class="user-stat-card">
                    <div class="user-stat-number"><?php echo count($userStats); ?></div>
                    <div class="user-stat-label">Active Users</div>
                </div>
                
                <div class="user-stat-card">
                    <div class="user-stat-number">$<?php echo number_format(array_sum(array_column($userStats, 'totalSpent')), 2); ?></div>
                    <div class="user-stat-label">Total Revenue</div>
                </div>
                
                <div class="user-stat-card">
                    <div class="user-stat-number"><?php echo number_format($avgBookingsPerUser, 1); ?></div>
                    <div class="user-stat-label">Avg Bookings/User</div>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Users</h3>
                </div>
                
                <?php if (empty($users)): ?>
                    <div class="no-data">
                        <i class="fas fa-users"></i>
                        <h3>No Users Found</h3>
                        <p>There are no users in the system yet.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Contact</th>
                                    <th>Bookings</th>
                                    <th>Total Spent</th>
                                    <th>First Booking</th>
                                    <th>Last Booking</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): 
                                    $userBookingStats = array_filter($userStats, function($stat) use ($user) {
                                        return (string)$stat['_id'] === (string)$user['_id'];
                                    });
                                    $userBookingStats = !empty($userBookingStats) ? array_values($userBookingStats)[0] : null;
                                    
                                    // Get initials for avatar
                                    $name = $user['name'] ?? $user['username'] ?? 'User';
                                    $initials = getInitials($name);
                                ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php echo $initials; ?>
                                            </div>
                                            <div class="user-details">
                                                <div class="user-name"><?php echo htmlspecialchars($name); ?></div>
                                                <div class="user-email">User ID: <?php echo $user['_id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size: 14px;">
                                            <?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?><br>
                                            <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php echo $userBookingStats['bookingCount'] ?? 0; ?>
                                    </td>
                                    <td>
                                        <strong>$<?php echo number_format($userBookingStats['totalSpent'] ?? 0, 2); ?></strong>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($userBookingStats['firstBooking'])) {
                                            if ($userBookingStats['firstBooking'] instanceof MongoDB\BSON\UTCDateTime) {
                                                echo $userBookingStats['firstBooking']->toDateTime()->format('M j, Y');
                                            } else {
                                                echo date('M j, Y', strtotime($userBookingStats['firstBooking']));
                                            }
                                        } else {
                                            echo 'No bookings';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($userBookingStats['lastBooking'])) {
                                            if ($userBookingStats['lastBooking'] instanceof MongoDB\BSON\UTCDateTime) {
                                                echo $userBookingStats['lastBooking']->toDateTime()->format('M j, Y');
                                            } else {
                                                echo date('M j, Y', strtotime($userBookingStats['lastBooking']));
                                            }
                                        } else {
                                            echo 'No bookings';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="admin_view_user.php?id=<?php echo $user['_id']; ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <button class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    
    return substr($initials, 0, 2);
}
?>