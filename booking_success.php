<?php
// booking_success.php
session_start();
require_once 'vendor/autoload.php';

$bookingId = $_GET['booking_id'] ?? '';

if (empty($bookingId)) {
    header('Location: packages_mongo.php');
    exit();
}

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $bookingsCollection = $database->selectCollection('bookings');
    
    $booking = $bookingsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($bookingId)]);
    
    if (!$booking) {
        die("Booking not found.");
    }

} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - Ghion Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .success-container {
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .success-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .success-title {
            font-size: 36px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .success-subtitle {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .success-content {
            padding: 40px;
        }
        
        .booking-details {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 4px solid #28a745;
        }
        
        .detail-section {
            margin-bottom: 25px;
        }
        
        .detail-section:last-child {
            margin-bottom: 0;
        }
        
        .detail-section h3 {
            color: #2a6496;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e8f4fc;
            font-size: 20px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .detail-value {
            color: #666;
            text-align: right;
        }
        
        .total-amount {
            background: #e8f4fc;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            text-align: center;
            border: 2px solid #2a6496;
        }
        
        .total-label {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .total-value {
            font-size: 32px;
            font-weight: bold;
            color: #e74c3c;
        }
        
        .next-steps {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .next-steps h3 {
            color: #856404;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .next-steps ul {
            padding-left: 20px;
            color: #856404;
        }
        
        .next-steps li {
            margin-bottom: 10px;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 15px 25px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .btn-primary {
            background: #2a6496;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1d4f7a;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .success-header {
                padding: 30px 20px;
            }
            
            .success-title {
                font-size: 28px;
            }
            
            .success-content {
                padding: 20px;
            }
            
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .detail-value {
                text-align: left;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="success-title">Booking Confirmed!</h1>
            <p class="success-subtitle">Thank you for choosing Ghion Travel. Your adventure awaits!</p>
        </div>
        
        <div class="success-content">
            <div class="booking-details">
                <div class="detail-section">
                    <h3><i class="fas fa-user"></i> Traveler Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['email']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['phone']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Emergency Contact:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['emergency_contact'] . ' (' . $booking['emergency_phone'] . ')'); ?></span>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3><i class="fas fa-map-marked-alt"></i> Tour Details</h3>
                    <div class="detail-row">
                        <span class="detail-label">Package:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['package_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Destination:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['package_area']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Tour Date:</span>
                        <span class="detail-value"><?php echo date('F j, Y', strtotime($booking['tour_date'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Passengers:</span>
                        <span class="detail-value"><?php echo $booking['passengers']; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Pickup Location:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['pickup_location']); ?></span>
                    </div>
                </div>
                
                <?php if (isset($booking['hotel_name'])): ?>
                <div class="detail-section">
                    <h3><i class="fas fa-hotel"></i> Hotel Accommodation</h3>
                    <div class="detail-row">
                        <span class="detail-label">Hotel:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['hotel_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Hotel Cost:</span>
                        <span class="detail-value">$<?php echo number_format($booking['hotel_price'], 2); ?> per night</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($booking['car_name'])): ?>
                <div class="detail-section">
                    <h3><i class="fas fa-car"></i> Transportation</h3>
                    <div class="detail-row">
                        <span class="detail-label">Vehicle:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['car_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Car Rental Cost:</span>
                        <span class="detail-value">$<?php echo number_format($booking['car_price'], 2); ?> per day</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="detail-section">
                    <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
                    <div class="detail-row">
                        <span class="detail-label">Payment Method:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['payment_method']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Booking Reference:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['booking_reference']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Booking Date:</span>
                        <span class="detail-value"><?php echo date('F j, Y g:i A', $booking['booking_date']->toDateTime()->getTimestamp()); ?></span>
                    </div>
                </div>
                
                <div class="total-amount">
                    <div class="total-label">Total Amount Paid</div>
                    <div class="total-value">$<?php echo number_format($booking['total_price'], 2); ?></div>
                </div>
            </div>
            
            <div class="next-steps">
                <h3><i class="fas fa-info-circle"></i> What Happens Next?</h3>
                <ul>
                    <li>You will receive a confirmation email within 24 hours</li>
                    <li>Our travel coordinator will contact you to finalize details</li>
                    <li>Prepare your travel documents and necessary items</li>
                    <li>Contact us if you have any questions or need changes</li>
                </ul>
            </div>
            
            <div class="action-buttons">
                <a href="my_bookings_mongo.php" class="btn btn-primary">
                    <i class="fas fa-list"></i> View My Bookings
                </a>
                <a href="packages_mongo.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Browse More Tours
                </a>
                <a href="index.php" class="btn btn-success">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>