<?php
session_start();
require_once 'vendor/autoload.php';

if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    exit('Access denied');
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Booking ID required');
}

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $bookingsCollection = $database->selectCollection('bookings');
    $packagesCollection = $database->selectCollection('packages');
    $hotelsCollection = $database->selectCollection('hotel');
    $carsCollection = $database->selectCollection('car');
    
    $booking = $bookingsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($_GET['id'])]);
    
    if (!$booking) {
        http_response_code(404);
        exit('Booking not found');
    }
    
    // Get additional details
    $package = $packagesCollection->findOne(['_id' => $booking['tour_details']['package_id']]);
    $hotel = $booking['additional_services']['selected_hotel'] ? 
        $hotelsCollection->findOne(['_id' => $booking['additional_services']['selected_hotel']]) : null;
    $car = $booking['additional_services']['selected_car'] ? 
        $carsCollection->findOne(['_id' => $booking['additional_services']['selected_car']]) : null;
    
    ?>
    <h3>Booking Details - <?php echo htmlspecialchars($booking['booking_reference']); ?></h3>
    
    <div class="booking-sections">
        <div class="booking-section">
            <h4><i class="fas fa-user"></i> Customer Information</h4>
            <div class="detail-grid">
                <div><strong>Name:</strong> <?php echo htmlspecialchars($booking['customer_info']['first_name'] . ' ' . $booking['customer_info']['last_name']); ?></div>
                <div><strong>Email:</strong> <?php echo htmlspecialchars($booking['customer_info']['email']); ?></div>
                <div><strong>Phone:</strong> <?php echo htmlspecialchars($booking['customer_info']['phone']); ?></div>
                <div><strong>Address:</strong> <?php echo htmlspecialchars($booking['customer_info']['address'] . ', ' . $booking['customer_info']['city'] . ', ' . $booking['customer_info']['country']); ?></div>
                <div><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($booking['customer_info']['emergency_contact'] . ' (' . $booking['customer_info']['emergency_phone'] . ')'); ?></div>
                <?php if (!empty($booking['customer_info']['special_requirements'])): ?>
                    <div><strong>Special Requirements:</strong> <?php echo htmlspecialchars($booking['customer_info']['special_requirements']); ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="booking-section">
            <h4><i class="fas fa-suitcase"></i> Tour Information</h4>
            <div class="detail-grid">
                <div><strong>Package:</strong> <?php echo htmlspecialchars($booking['tour_details']['package_name']); ?></div>
                <div><strong>Tour Date:</strong> <?php echo date('F j, Y', strtotime($booking['tour_details']['tour_date'])); ?></div>
                <div><strong>Passengers:</strong> <?php echo $booking['tour_details']['passengers']; ?></div>
                <div><strong>Pickup Location:</strong> <?php echo htmlspecialchars($booking['tour_details']['pickup_location']); ?></div>
                <?php if (!empty($booking['tour_details']['additional_notes'])): ?>
                    <div><strong>Additional Notes:</strong> <?php echo htmlspecialchars($booking['tour_details']['additional_notes']); ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($hotel || $car): ?>
        <div class="booking-section">
            <h4><i class="fas fa-plus-circle"></i> Additional Services</h4>
            <div class="detail-grid">
                <?php if ($hotel): ?>
                    <div><strong>Hotel:</strong> <?php echo htmlspecialchars($hotel['name']); ?> - $<?php echo number_format($hotel['price'], 2); ?>/night</div>
                <?php endif; ?>
                <?php if ($car): ?>
                    <div><strong>Car:</strong> <?php echo htmlspecialchars($car['name'] ?? $car['model']); ?> - $<?php echo number_format($car['price'], 2); ?>/day</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="booking-section">
            <h4><i class="fas fa-credit-card"></i> Payment Information</h4>
            <div class="detail-grid">
                <div><strong>Total Amount:</strong> $<?php echo number_format($booking['payment_info']['total_amount'], 2); ?></div>
                <div><strong>Payment Method:</strong> <?php echo htmlspecialchars($booking['payment_info']['payment_method']); ?></div>
                <div><strong>Payment Status:</strong> 
                    <span class="status-badge payment-<?php echo $booking['payment_info']['payment_status']; ?>">
                        <?php echo ucfirst($booking['payment_info']['payment_status']); ?>
                    </span>
                </div>
                <div><strong>Booking Status:</strong> 
                    <span class="status-badge status-<?php echo $booking['booking_status']; ?>">
                        <?php echo ucfirst($booking['booking_status']); ?>
                    </span>
                </div>
                <?php if (!empty($booking['payment_info']['admin_notes'])): ?>
                    <div><strong>Admin Notes:</strong> <?php echo htmlspecialchars($booking['payment_info']['admin_notes']); ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($booking['payment_info']['receipt_path']) && file_exists($booking['payment_info']['receipt_path'])): ?>
        <div class="booking-section">
            <h4><i class="fas fa-receipt"></i> Payment Receipt</h4>
            <img src="<?php echo $booking['payment_info']['receipt_path']; ?>" 
                 alt="Payment Receipt" 
                 style="max-width: 300px; border-radius: 8px; cursor: pointer;"
                 onclick="window.open('<?php echo $booking['payment_info']['receipt_path']; ?>', '_blank')">
        </div>
        <?php endif; ?>
    </div>
    
    <style>
        .booking-sections {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .booking-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #2a6496;
        }
        
        .booking-section h4 {
            color: #2a6496;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .detail-grid {
            display: grid;
            gap: 10px;
        }
        
        .detail-grid div {
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-grid div:last-child {
            border-bottom: none;
        }
    </style>
    <?php
    
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error loading booking details: ' . $e->getMessage();
}
?>