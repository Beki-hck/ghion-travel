<?php
session_start();
require_once 'vendor/autoload.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: admin_bookings.php");
    exit;
}

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $bookingsCollection = $database->selectCollection('bookings');
    
    $booking = $bookingsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($_GET['id'])]);
    
    if (!$booking) {
        $_SESSION['error_message'] = 'Booking not found!';
        header("Location: admin_bookings.php");
        exit;
    }
    
    function bsonToArray($bsonArray) {
        if ($bsonArray instanceof MongoDB\Model\BSONArray) {
            return $bsonArray->getArrayCopy();
        }
        return is_array($bsonArray) ? $bsonArray : [];
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
    <title>Booking Details - Ghion Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="adminstyles.css">
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
                <li><a href="admin_bookings.php" class="active"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="admin_analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                <li><a href="admin_settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Booking Details</h1>
                <div style="display: flex; gap: 10px;">
                    <a href="admin_bookings.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Bookings
                    </a>
                    <a href="admin_edit_booking.php?id=<?php echo $booking['_id']; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit Booking
                    </a>
                </div>
            </div>
            
            <!-- Booking Header -->
            <div class="booking-header">
                <div class="booking-info-card">
                    <div class="booking-ref">
                        <?php echo htmlspecialchars($booking['bookingReference'] ?? 'N/A'); ?>
                    </div>
                    <div class="booking-status status-<?php echo htmlspecialchars($booking['status'] ?? 'pending'); ?>">
                        <?php echo ucfirst(htmlspecialchars($booking['status'] ?? 'pending')); ?>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <div style="font-size: 18px; font-weight: 600; margin-bottom: 10px;">
                            <?php echo htmlspecialchars($booking['package'] ?? $booking['package_name'] ?? 'N/A'); ?>
                        </div>
                        <div style="color: #666;">
                            <i class="fas fa-map-marker-alt" style="margin-right: 8px;"></i>
                            <?php echo htmlspecialchars($booking['destination'] ?? 'N/A'); ?>
                        </div>
                    </div>
                </div>
                
                <div class="price-section">
                    <div class="price-main">
                        $<?php echo number_format($booking['total_price'] ?? 0, 2); ?>
                    </div>
                    <div class="price-label">Total Amount</div>
                    <div style="margin-top: 15px; font-size: 14px; opacity: 0.8;">
                        <?php echo $booking['passengers'] ?? 1; ?> passenger(s)
                    </div>
                </div>
            </div>
            
            <!-- Information Grid -->
            <div class="info-grid">
                <!-- Travel Information -->
                <div class="info-card">
                    <div class="info-card-title">
                        <i class="fas fa-plane" style="margin-right: 8px;"></i>
                        Travel Information
                    </div>
                    <div class="info-item">
                        <span class="info-label">Travel Date:</span>
                        <span class="info-value"><?php echo htmlspecialchars($booking['tourDate'] ?? $booking['travel_date'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Duration:</span>
                        <span class="info-value"><?php echo htmlspecialchars($booking['duration'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Passengers:</span>
                        <span class="info-value"><?php echo $booking['passengers'] ?? 1; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Destination:</span>
                        <span class="info-value"><?php echo htmlspecialchars($booking['destination'] ?? 'N/A'); ?></span>
                    </div>
                </div>
                
                <!-- Payment Information -->
                <div class="info-card">
                    <div class="info-card-title">
                        <i class="fas fa-credit-card" style="margin-right: 8px;"></i>
                        Payment Information
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total Amount:</span>
                        <span class="info-value" style="color: var(--success);">
                            $<?php echo number_format($booking['total_price'] ?? 0, 2); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Payment Method:</span>
                        <span class="info-value"><?php echo htmlspecialchars($booking['paymentMethod'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Payment Status:</span>
                        <span class="info-value">
                            <?php 
                            $status = $booking['status'] ?? 'pending';
                            if (in_array($status, ['paid', 'completed'])) {
                                echo '<span style="color: var(--success);">Paid</span>';
                            } else {
                                echo '<span style="color: var(--warning);">Pending</span>';
                            }
                            ?>
                        </span>
                    </div>
                </div>
                
                <!-- Booking Timeline -->
                <div class="info-card">
                    <div class="info-card-title">
                        <i class="fas fa-history" style="margin-right: 8px;"></i>
                        Booking Timeline
                    </div>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-date">
                                <?php 
                                if (isset($booking['booking_date'])) {
                                    if ($booking['booking_date'] instanceof MongoDB\BSON\UTCDateTime) {
                                        echo $booking['booking_date']->toDateTime()->format('M j, Y g:i A');
                                    } else {
                                        echo date('M j, Y g:i A', strtotime($booking['booking_date']));
                                    }
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </div>
                            <div class="timeline-content">Booking Created</div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-date">
                                <?php echo htmlspecialchars($booking['tourDate'] ?? $booking['travel_date'] ?? 'N/A'); ?>
                            </div>
                            <div class="timeline-content">Scheduled Travel Date</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Customer Information -->
            <div class="customer-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user" style="margin-right: 10px;"></i>
                        Customer Information
                    </h3>
                </div>
                
                <div class="customer-header">
                    <div class="customer-avatar">
                        <?php 
                        $name = $booking['name'] ?? $booking['customer_name'] ?? 'Customer';
                        echo strtoupper(substr($name, 0, 1)); 
                        ?>
                    </div>
                    <div>
                        <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($name); ?></h3>
                        <p style="color: #666; margin: 0;">
                            User ID: <?php echo $booking['user_id'] ?? 'N/A'; ?>
                        </p>
                    </div>
                </div>
                
                <div class="customer-details">
                    <div class="detail-item">
                        <span class="detail-label">Email Address</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['email'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Phone Number</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['phone'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Country</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['country'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Special Requests</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['special_requests'] ?? 'None'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Additional Information -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Additional Information</h3>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div>
                        <h4 style="margin-bottom: 15px; color: var(--dark);">Booking Notes</h4>
                        <div style="background: var(--light); padding: 15px; border-radius: 5px; min-height: 100px;">
                            <?php if (isset($booking['notes']) && !empty($booking['notes'])): ?>
                                <?php echo nl2br(htmlspecialchars($booking['notes'])); ?>
                            <?php else: ?>
                                <p style="color: #666; font-style: italic;">No additional notes for this booking.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 15px; color: var(--dark);">System Information</h4>
                        <div style="display: grid; gap: 10px;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #666;">Booking ID:</span>
                                <span style="font-weight: 500;"><?php echo $booking['_id']; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #666;">Created:</span>
                                <span style="font-weight: 500;">
                                    <?php 
                                    if (isset($booking['booking_date'])) {
                                        if ($booking['booking_date'] instanceof MongoDB\BSON\UTCDateTime) {
                                            echo $booking['booking_date']->toDateTime()->format('M j, Y g:i A');
                                        } else {
                                            echo date('M j, Y g:i A', strtotime($booking['booking_date']));
                                        }
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </span>
                            </div>
                            <?php if (isset($booking['updated_at'])): ?>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #666;">Last Updated:</span>
                                <span style="font-weight: 500;">
                                    <?php 
                                    if ($booking['updated_at'] instanceof MongoDB\BSON\UTCDateTime) {
                                        echo $booking['updated_at']->toDateTime()->format('M j, Y g:i A');
                                    } else {
                                        echo date('M j, Y g:i A', strtotime($booking['updated_at']));
                                    }
                                    ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>