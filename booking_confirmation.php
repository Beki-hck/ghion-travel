<?php
session_start();
if (!isset($_SESSION['booking_success'])) {
    header("Location: packages_mongo.php");
    exit;
}

$bookingReference = $_SESSION['booking_reference'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - Ghion Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .confirmation-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }
        
        .confirmation-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .confirmation-header i {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .confirmation-body {
            padding: 40px;
        }
        
        .success-message {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .booking-details {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .next-steps {
            background: #e8f4fc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .next-steps h3 {
            color: #2a6496;
            margin-bottom: 15px;
        }
        
        .next-steps ul {
            list-style: none;
            padding: 0;
        }
        
        .next-steps li {
            margin-bottom: 10px;
            padding-left: 25px;
            position: relative;
        }
        
        .next-steps li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #2a6496;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="confirmation-header">
            <i class="fas fa-check-circle"></i>
            <h1>Booking Submitted Successfully!</h1>
            <p>Your booking is pending payment verification</p>
        </div>
        
        <div class="confirmation-body">
            <div class="success-message">
                <h2>Thank You for Your Booking!</h2>
                <p>We have received your booking request and it's currently pending payment verification.</p>
            </div>
            
            <div class="booking-details">
                <h3>Booking Details</h3>
                <div class="detail-item">
                    <span><strong>Booking Reference:</strong></span>
                    <span><?php echo htmlspecialchars($bookingReference); ?></span>
                </div>
                <div class="detail-item">
                    <span><strong>Status:</strong></span>
                    <span style="color: #ffc107; font-weight: bold;">Pending Approval</span>
                </div>
                <div class="detail-item">
                    <span><strong>Next Step:</strong></span>
                    <span>Payment Verification</span>
                </div>
            </div>
            
            <div class="next-steps">
                <h3>What Happens Next?</h3>
                <ul>
                    <li>Our team will verify your payment receipt</li>
                    <li>You will receive a confirmation email within 24 hours</li>
                    <li>Once approved, your booking will be confirmed</li>
                    <li>We'll send you detailed tour information</li>
                </ul>
            </div>
            
            <div class="action-buttons">
                <a href="packages_mongo.php" class="btn btn-primary">
                    <i class="fas fa-suitcase"></i> Browse More Packages
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
    
    <?php
    // Clear the session after showing confirmation
    unset($_SESSION['booking_success']);
    unset($_SESSION['booking_reference']);
    ?>
</body>
</html>