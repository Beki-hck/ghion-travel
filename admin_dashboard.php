<?php
session_start();
require_once 'vendor/autoload.php';

// Simple admin authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $bookingsCollection = $database->selectCollection('bookings');
    $packagesCollection = $database->selectCollection('packages');
    
    // Get statistics
    $totalBookings = $bookingsCollection->countDocuments();
    $totalPackages = $packagesCollection->countDocuments();
    $totalRevenue = $bookingsCollection->aggregate([
        ['$group' => ['_id' => null, 'total' => ['$sum' => '$total_price']]]
    ])->toArray();
    $totalRevenue = $totalRevenue ? $totalRevenue[0]['total'] : 0;
    
    // Get recent bookings with proper field mapping
    $recentBookings = $bookingsCollection->find(
        [],
        [
            'sort' => ['booking_date' => -1], 
            'limit' => 5,
            'projection' => [
                'bookingReference' => 1,
                'package_name' => 1,
                'customer_name' => 1,
                'total_price' => 1,
                'status' => 1,
                'booking_date' => 1
            ]
        ]
    )->toArray();
    
    // Get booking status statistics
    $statusStats = $bookingsCollection->aggregate([
        ['$group' => [
            '_id' => '$status',
            'count' => ['$sum' => 1],
            'revenue' => ['$sum' => '$total_price']
        ]]
    ])->toArray();
    
    // Get popular packages
    $popularPackages = $bookingsCollection->aggregate([
        ['$group' => [
            '_id' => '$package_name',
            'bookingCount' => ['$sum' => 1],
            'totalRevenue' => ['$sum' => '$total_price']
        ]],
        ['$sort' => ['bookingCount' => -1]],
        ['$limit' => 5]
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
    <title>Admin Dashboard - Ghion Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="adminstyles.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-crown"></i> Admin Panel</h2>
                <p>Tour Travel</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="admin_packages.php"><i class="fas fa-suitcase"></i> Tour Packages</a></li>
                <li><a href="admin_bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="admin_car.php"><i class="fas fa-car"></i>Car</a></li>
                <li><a href="admin_hotel.php"><i class="fas fa-hotel"></i> Hotels</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="admin_contacts.php"><i class="fas fa-comments"></i> Contacts & Feedback</a></li>
                <li><a href="admin_analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                <li><a href="admin_notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                <li><a href="admin_create.php"><i class="fas fa-user-plus"></i> Create Admin</a></li>
                <li><a href="admin_settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Admin Dashboard</h1>
                <a href="admin_logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card revenue">
                    <div class="stat-number">$<?php echo number_format($totalRevenue, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                    <i class="fas fa-dollar-sign" style="font-size: 24px; margin-top: 10px; opacity: 0.7;"></i>
                </div>
                
                <div class="stat-card bookings">
                    <div class="stat-number"><?php echo $totalBookings; ?></div>
                    <div class="stat-label">Total Bookings</div>
                    <i class="fas fa-calendar-check" style="font-size: 24px; margin-top: 10px; opacity: 0.7;"></i>
                </div>
                
                <div class="stat-card packages">
                    <div class="stat-number"><?php echo $totalPackages; ?></div>
                    <div class="stat-label">Tour Packages</div>
                    <i class="fas fa-suitcase" style="font-size: 24px; margin-top: 10px; opacity: 0.7;"></i>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <!-- Left Column -->
                <div>
                    <!-- Recent Bookings -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Bookings</h3>
                            <a href="admin_bookings.php" class="btn btn-primary">View All</a>
                        </div>
                        <?php if (empty($recentBookings)): ?>
                            <div class="no-data">
                                <i class="fas fa-calendar-times"></i>
                                <h3>No Bookings Found</h3>
                                <p>There are no recent bookings to display.</p>
                            </div>
                        <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Package</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentBookings as $booking): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['bookingReference'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($booking['package_name'] ?? $booking['package'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($booking['customer_name'] ?? $booking['name'] ?? 'N/A'); ?></td>
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
                        <?php endif; ?>
                    </div>
                    
                    <!-- Popular Packages -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Popular Packages</h3>
                        </div>
                        <?php if (empty($popularPackages)): ?>
                            <div class="no-data">
                                <i class="fas fa-chart-bar"></i>
                                <h3>No Data Available</h3>
                                <p>Popular packages will appear here once bookings are made.</p>
                            </div>
                        <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Package Name</th>
                                        <th>Bookings</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($popularPackages as $package): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($package['_id'] ?? 'Unknown Package'); ?></td>
                                        <td><?php echo $package['bookingCount'] ?? 0; ?></td>
                                        <td>$<?php echo number_format($package['totalRevenue'] ?? 0, 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div>
                    <!-- Booking Status -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Booking Status</h3>
                        </div>
                        <div style="padding: 20px 0;">
                            <?php if (empty($statusStats)): ?>
                                <div class="no-data" style="padding: 20px;">
                                    <i class="fas fa-chart-pie"></i>
                                    <p>No status data available</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($statusStats as $stat): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">
                                    <div>
                                        <span class="status-badge status-<?php echo htmlspecialchars($stat['_id'] ?? 'pending'); ?>">
                                            <?php echo ucfirst(htmlspecialchars($stat['_id'] ?? 'pending')); ?>
                                        </span>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-weight: bold;"><?php echo $stat['count'] ?? 0; ?> bookings</div>
                                        <div style="color: #666; font-size: 14px;">$<?php echo number_format($stat['revenue'] ?? 0, 2); ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Quick Actions</h3>
                        </div>
                        <div style="display: grid; gap: 10px; padding: 10px 0;">
                            <a href="admin_add_package.php" class="btn btn-success">
                                <i class="fas fa-plus"></i> Add New Package
                            </a>
                             <a href="admin_add_car.php" class="btn btn-success">
                                <i class="fas fa-plus"></i> Add New Car
                            </a>
                             <a href="admin_add_hotel.php" class="btn btn-success">
                                <i class="fas fa-plus"></i> Add New Hotel
                            </a>
                            <a href="admin_bookings.php" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View All Bookings
                            </a>
                            <a href="admin_analytics.php" class="btn btn-primary">
                                <i class="fas fa-chart-bar"></i> View Analytics
                            </a>
                        </div>
                    </div>
                    
                    <!-- System Info -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">System Information</h3>
                        </div>
                        <div style="padding: 15px 0;">
                            <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                                <span>Database:</span>
                                <span style="font-weight: bold;">MongoDB</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                                <span>Total Collections:</span>
                                <span style="font-weight: bold;">2</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                                <span>Last Updated:</span>
                                <span style="font-weight: bold;"><?php echo date('M j, Y g:i A'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>