<?php
session_start();
require_once 'vendor/autoload.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

$userId = $_GET['user_id'] ?? '';

if (!$userId) {
    header("Location: admin_users.php");
    exit;
}

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $bookingsCollection = $database->selectCollection('bookings');
    
    // Get user's bookings
    $userBookings = $bookingsCollection->find(
        ['user_id' => $userId],
        ['sort' => ['booking_date' => -1]]
    )->toArray();
    
    // Get user info from first booking
    $userInfo = $userBookings[0] ?? null;
    
    if (!$userInfo) {
        header("Location: admin_users.php");
        exit;
    }
    
    // Calculate user statistics
    $totalBookings = count($userBookings);
    $totalSpent = array_sum(array_column($userBookings, 'total_price'));
    
    // Get first and last booking dates
    $bookingDates = array_column($userBookings, 'booking_date');
    if (!empty($bookingDates)) {
        $firstBooking = min($bookingDates);
        $lastBooking = max($bookingDates);
    } else {
        $firstBooking = $lastBooking = null;
    }

} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - Ghion Travel</title>
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
                <li><a href="admin_settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>User Details</h1>
                <a href="admin_users.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
            </div>
            
            <!-- User Header -->
            <div class="user-header">
                <div class="user-avatar-large">
                    <?php 
                    $name = $userInfo['name'] ?? 'Unknown User';
                    echo strtoupper(substr($name, 0, 1)); 
                    ?>
                </div>
                <div>
                    <h2><?php echo htmlspecialchars($name); ?></h2>
                    <p style="color: #666; margin: 5px 0;">
                        User ID: <?php echo $userId; ?>
                    </p>
                    <p style="color: #666;">
                        <?php echo htmlspecialchars($userInfo['email'] ?? 'N/A'); ?> • 
                        <?php echo htmlspecialchars($userInfo['phone'] ?? 'N/A'); ?>
                    </p>
                </div>
            </div>
            
            <!-- User Statistics -->
            <div class="user-stats">
                <div class="user-stat-card">
                    <div class="user-stat-number"><?php echo $totalBookings; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
                <div class="user-stat-card">
                    <div class="user-stat-number">$<?php echo number_format($totalSpent, 2); ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
                <div class="user-stat-card">
                    <div class="user-stat-number">
                        <?php 
                        $avgSpent = $totalBookings > 0 ? $totalSpent / $totalBookings : 0;
                        echo '$' . number_format($avgSpent, 2);
                        ?>
                    </div>
                    <div class="stat-label">Average per Booking</div>
                </div>
                <div class="user-stat-card">
                    <div class="user-stat-number">
                        <?php 
                        if ($firstBooking) {
                            if ($firstBooking instanceof MongoDB\BSON\UTCDateTime) {
                                echo $firstBooking->toDateTime()->format('M Y');
                            } else {
                                echo date('M Y', strtotime($firstBooking));
                            }
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                    <div class="stat-label">First Booking</div>
                </div>
            </div>
            
            <!-- User's Bookings -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Booking History (<?php echo $totalBookings; ?> bookings)</h3>
                </div>
                
                <?php if (empty($userBookings)): ?>
                    <div class="no-data">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No Bookings Found</h3>
                        <p>This user has no booking history.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Package</th>
                                    <th>Travel Date</th>
                                    <th>Passengers</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Booking Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userBookings as $booking): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['bookingReference'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($booking['package'] ?? $booking['package_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($booking['tourDate'] ?? $booking['travel_date'] ?? 'N/A'); ?></td>
                                    <td style="text-align: center;"><?php echo $booking['passengers'] ?? 1; ?></td>
                                    <td>$<?php echo number_format($booking['total_price'] ?? 0, 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo htmlspecialchars($booking['status'] ?? 'pending'); ?>">
                                            <?php echo ucfirst(htmlspecialchars($booking['status'] ?? 'pending')); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($booking['booking_date'])) {
                                            if ($booking['booking_date'] instanceof MongoDB\BSON\UTCDateTime) {
                                                echo $booking['booking_date']->toDateTime()->format('M j, Y');
                                            } else {
                                                echo date('M j, Y', strtotime($booking['booking_date']));
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
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