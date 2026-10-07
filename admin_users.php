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
    $bookingsCollection = $database->selectCollection('bookings');
    
    // Get unique users from bookings
    $users = $bookingsCollection->aggregate([
        ['$group' => [
            '_id' => '$user_id',
            'name' => ['$first' => '$name'],
            'email' => ['$first' => '$email'],
            'phone' => ['$first' => '$phone'],
            'totalBookings' => ['$sum' => 1],
            'totalSpent' => ['$sum' => '$total_price'],
            'firstBooking' => ['$min' => '$booking_date'],
            'lastBooking' => ['$max' => '$booking_date']
        ]],
        ['$sort' => ['totalBookings' => -1]]
    ])->toArray();

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
                <li><a href="admin_users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="admin_analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                                <li><a href="admin_contacts.php"><i class="fas fa-comments"></i> Contacts & Feedback</a></li>
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
                    <span>Total: <?php echo count($users); ?> users</span>
                </div>
            </div>
            
            <!-- User Statistics -->
            <div class="user-stats">
                <div class="user-stat-card">
                    <div class="user-stat-number"><?php echo count($users); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                
                <div class="user-stat-card">
                    <div class="user-stat-number">
                        <?php 
                        $totalBookings = array_sum(array_column($users, 'totalBookings'));
                        echo $totalBookings;
                        ?>
                    </div>
                    <div class="stat-label">Total Bookings</div>
                </div>
                
                <div class="user-stat-card">
                    <div class="user-stat-number">
                        $<?php 
                        $totalRevenue = array_sum(array_column($users, 'totalSpent'));
                        echo number_format($totalRevenue, 2);
                        ?>
                    </div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                
                <div class="user-stat-card">
                    <div class="user-stat-number">
                        <?php 
                        $avgBookings = count($users) > 0 ? $totalBookings / count($users) : 0;
                        echo number_format($avgBookings, 1);
                        ?>
                    </div>
                    <div class="stat-label">Avg Bookings/User</div>
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
                        <p>There are no users with bookings in the system yet.</p>
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
                                    $name = $user['name'] ?? 'Unknown User';
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
                                                <div style="color: #666; font-size: 12px;">
                                                    User ID: <?php echo $user['_id']; ?>
                                                </div>
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
                                        <strong><?php echo $user['totalBookings']; ?></strong>
                                    </td>
                                    <td>
                                        <strong>$<?php echo number_format($user['totalSpent'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($user['firstBooking'])) {
                                            if ($user['firstBooking'] instanceof MongoDB\BSON\UTCDateTime) {
                                                echo $user['firstBooking']->toDateTime()->format('M j, Y');
                                            } else {
                                                echo date('M j, Y', strtotime($user['firstBooking']));
                                            }
                                        } else {
                                            echo 'No bookings';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($user['lastBooking'])) {
                                            if ($user['lastBooking'] instanceof MongoDB\BSON\UTCDateTime) {
                                                echo $user['lastBooking']->toDateTime()->format('M j, Y');
                                            } else {
                                                echo date('M j, Y', strtotime($user['lastBooking']));
                                            }
                                        } else {
                                            echo 'No bookings';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="actions-column">
                                            <a href="admin_user_details.php?user_id=<?php echo $user['_id']; ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </div>
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