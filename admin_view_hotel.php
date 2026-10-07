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
    $packagesCollection = $database->selectCollection('packages');
    $bookingsCollection = $database->selectCollection('bookings');
    $carsCollection = $database->selectCollection('car');
    $hotelsCollection = $database->selectCollection('hotel');
    

    
    // Get package ID from URL
    $hotelId = $_GET['id'] ?? '';
    if (!$hotelId) {
        header("Location: admin_hotel.php");
        exit;
    }
    
    // Get package data
    $hotel = $hotelsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($hotelId)]);
    if (!$hotel) {
        $_SESSION['error_message'] = 'hotel not found!';
        header("Location: admin_hotel.php");
        exit;
    }
    
    // Helper function to convert BSONArray to regular array
    function bsonToArray($bsonArray) {
        if ($bsonArray instanceof MongoDB\Model\BSONArray) {
            return $bsonArray->getArrayCopy();
        }
        return is_array($bsonArray) ? $bsonArray : [];
    }
    
    // Convert arrays for display
    $highlightsArray = bsonToArray($hotel['highlights'] ?? []);
    $includesArray = bsonToArray($hotel['includes'] ?? []);
    
    // Get booking statistics for this package
    $packageBookings = $bookingsCollection->find(['package' => $hotel['name']])->toArray();
    $totalBookings = count($packageBookings);
    $totalRevenue = array_sum(array_column($packageBookings, 'total_price'));
    
    // Get recent bookings for this package
    $recentBookings = $bookingsCollection->find(
        ['hotel' => $hotel['name']],
        ['sort' => ['booking_date' => -1], 'limit' => 5]
    )->toArray();

} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View hotel Package - Ghion Travel</title>
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
                <li><a href="admin_car.php"><i class="fas fa-calendar-check"></i> Car </a></li>
                <li><a href="admin_hotel.php" class="active"><i class="fas fa-calendar-check"></i> Hotel</a></li>
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
                <h1>Hotel Details</h1>
                <div style="display: flex; gap: 10px;">
                    <a href="admin_hotel.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to hotel
                    </a>
                    <a href="admin_edit_hotel.php?id=<?php echo $hotel['_id']; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit hotel
                    </a>
                </div>
            </div>
            
            <!-- Package Header -->
            <div class="car-header">
                <div>
                    <?php if (isset($hotel['image']) && !empty($hotel['image'])): ?>
                        <img src="images/<?php echo htmlspecialchars($hotel['image']); ?>" 
                             alt="<?php echo htmlspecialchars($hotel['image_alt'] ?? $hotel['name']); ?>" 
                             class="hotel-image">
                    <?php else: ?>
                        <div class="image-placeholder">
                            <i class="fas fa-image" style="font-size: 64px; margin-bottom: 15px;"></i>
                            <span>No Image Available</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="hotel-info">
                    <div>
                        <h1 class="hotel-title"><?php echo htmlspecialchars($hotel['name']); ?></h1>
                        <div class="hotel-meta">
                            <div class="meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($hotel['destination'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-clock"></i>
                                <span><?php echo htmlspecialchars($hotel['duration'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-tag"></i>
                                <span><?php echo htmlspecialchars($hotel['category'] ?? 'General'); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="status-badge status-<?php echo htmlspecialchars($hotel['status'] ?? 'active'); ?>">
                                    <?php echo ucfirst(htmlspecialchars($hotel['status'] ?? 'active')); ?>
                                </span>
                                <?php if ($hotel['featured'] ?? false): ?>
                                    <span class="featured-badge">
                                        <i class="fas fa-star"></i> Featured
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="price-section">
                        <?php if (isset($hotel['discount_price']) && $hotel['discount_price'] > 0): ?>
                            <div class="price-original">$<?php echo number_format($hotel['price'], 2); ?></div>
                            <div class="price-main">
                                $<?php echo number_format($hotel['discount_price'], 2); ?>
                                <span class="price-discount">
                                    Save $<?php echo number_format($hotel['price'] - $hotel['discount_price'], 2); ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="price-main">$<?php echo number_format($hotel['price'], 2); ?></div>
                        <?php endif; ?>
                        <div style="opacity: 0.9; font-size: 14px;">per person</div>
                    </div>
                </div>
            </div>
            
            <!-- car Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalBookings; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($totalRevenue, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $hotel['price']; ?></div>
                    <div class="stat-label">Base Price</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php if (isset($hotel['discount_price']) && $hotel['discount_price'] > 0): ?>
                            $<?php echo number_format($hotel['discount_price'], 2); ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </div>
                    <div class="stat-label">Discount Price</div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <!-- Left Column -->
                <div>
                    <!-- Description -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">hotel Description</h3>
                        </div>
                        <div class="description">
                            <?php echo nl2br(htmlspecialchars($hotel['description'] ?? 'No description available.')); ?>
                        </div>
                    </div>
                    
                    <!-- Highlights -->
                    <?php if (!empty($highlightsArray)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Tour Highlights</h3>
                        </div>
                        <div class="features-list">
                            <?php foreach ($highlightsArray as $highlight): ?>
                            <div class="feature-item">
                                <i class="fas fa-check-circle"></i>
                                <span><?php echo htmlspecialchars(trim($highlight)); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- What's Included -->
                    <?php if (!empty($includesArray)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">What's Included</h3>
                        </div>
                        <div class="features-list">
                            <?php foreach ($includesArray as $include): ?>
                            <div class="feature-item">
                                <i class="fas fa-check"></i>
                                <span><?php echo htmlspecialchars(trim($include)); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Right Column -->
                <div>
                    <!-- car Details -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">hotelDetails</h3>
                        </div>
                        <div style="display: grid; gap: 15px;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-weight: 500;">hotel ID:</span>
                                <span style="color: #666;"><?php echo $hotel['_id']; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-weight: 500;">Category:</span>
                                <span style="color: #666;"><?php echo htmlspecialchars($hotel['category'] ?? 'General'); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-weight: 500;">Destination:</span>
                                <span style="color: #666;"><?php echo htmlspecialchars($hotel['destination'] ?? 'N/A'); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-weight: 500;">Duration:</span>
                                <span style="color: #666;"><?php echo htmlspecialchars($hotel['duration'] ?? 'N/A'); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-weight: 500;">Status:</span>
                                <span class="status-badge status-<?php echo htmlspecialchars($hotel['status'] ?? 'active'); ?>">
                                    <?php echo ucfirst(htmlspecialchars($hotel['status'] ?? 'active')); ?>
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-weight: 500;">Featured:</span>
                                <span style="color: #666;">
                                    <?php echo ($hotel['featured'] ?? false) ? 'Yes' : 'No'; ?>
                                </span>
                            </div>
                            <?php if (isset($hotel['created_at'])): ?>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-weight: 500;">Created:</span>
                                <span style="color: #666;">
                                    <?php 
                                    if ($hotel['created_at'] instanceof MongoDB\BSON\UTCDateTime) {
                                        echo $hotel['created_at']->toDateTime()->format('M j, Y g:i A');
                                    } else {
                                        echo date('M j, Y g:i A', strtotime($hotel['created_at']));
                                    }
                                    ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            <?php if (isset($hotel['updated_at'])): ?>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-weight: 500;">Last Updated:</span>
                                <span style="color: #666;">
                                    <?php 
                                    if ($hotel['updated_at'] instanceof MongoDB\BSON\UTCDateTime) {
                                        echo $hotel['updated_at']->toDateTime()->format('M j, Y g:i A');
                                    } else {
                                        echo date('M j, Y g:i A', strtotime($hotel['updated_at']));
                                    }
                                    ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Bookings -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Bookings</h3>
                        </div>
                        <?php if (empty($recentBookings)): ?>
                            <div class="no-data" style="padding: 20px;">
                                <i class="fas fa-calendar-times"></i>
                                <p>No bookings yet</p>
                            </div>
                        <?php else: ?>
                            <div style="max-height: 300px; overflow-y: auto;">
                                <?php foreach ($recentBookings as $booking): ?>
                                <div style="padding: 10px; border-bottom: 1px solid #eee;">
                                    <div style="font-weight: 500; margin-bottom: 5px;">
                                        <?php echo htmlspecialchars($booking['name'] ?? 'Customer'); ?>
                                    </div>
                                    <div style="font-size: 12px; color: #666;">
                                        $<?php echo number_format($booking['total_price'] ?? 0, 2); ?> • 
                                        <?php 
                                        if (isset($booking['booking_date'])) {
                                            if ($booking['booking_date'] instanceof MongoDB\BSON\UTCDateTime) {
                                                echo $booking['booking_date']->toDateTime()->format('M j, Y');
                                            } else {
                                                echo date('M j, Y', strtotime($booking['booking_date']));
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div style="text-align: center; margin-top: 15px;">
                                <a href="admin_bookings.php?hotel=<?php echo urlencode($hotel['name']); ?>" 
                                   class="btn btn-primary btn-sm">
                                    View All Bookings
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>