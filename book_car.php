<?php
session_start();
require_once 'vendor/autoload.php';

// Get package details from URL
$carName = $_GET['car'] ?? '';
$carPrice = floatval($_GET['price'] ?? 0);

if (empty($carName)) {
    header("Location: car.php");
    exit;
}

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $carsCollection = $database->selectCollection('car');
    
    // Get package details from MongoDB
    $car = $carsCollection->findOne(['name' => $carName]);
    
    if (!$car) {
        header("Location: car.php");
        exit;
    }
    
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Set default user session if not exists
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = rand(1000, 9999);
    $_SESSION['user_name'] = 'Guest User';
}

// Calculate dates
$today = date('Y-m-d');
$maxDate = date('Y-m-d', strtotime('+1 year'));

// Generate unique booking reference for QR codes
$bookingReference = 'BOOK-' . strtoupper(uniqid());
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Tour - Ghion Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Include QR Code Generator Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
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
            --dark: #2c3e50;
            --light: #f8f9fa;
            --gray: #6c757d;
            --border: #e1e5e9;
        }
        
        body {
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 40px 20px;
            background: linear-gradient(135deg, var(--primary) 0%, #4b86b4 100%);
            color: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 36px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            font-size: 18px;
            opacity: 0.9;
            font-weight: 400;
        }
        
        /* Step Indicator */
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
            padding: 0 20px;
        }
        
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50px;
            right: 50px;
            height: 3px;
            background: var(--border);
            z-index: 1;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        
        .step-number {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--gray);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 8px;
            border: 3px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .step.active .step-number {
            background: var(--primary);
            transform: scale(1.1);
        }
        
        .step.completed .step-number {
            background: var(--success);
        }
        
        .step-label {
            font-size: 14px;
            color: var(--gray);
            font-weight: 500;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .step.active .step-label {
            color: var(--primary);
            font-weight: 600;
        }
        
        .step.completed .step-label {
            color: var(--success);
        }
        
        /* Form Steps */
        .form-step {
            display: none;
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-step.active {
            display: block;
        }
        
        /* Form Sections */
        .form-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            border: 1px solid var(--border);
        }
        
        .section-title {
            color: var(--primary);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary);
            font-size: 24px;
            font-weight: 600;
        }
        
        /* Form Elements */
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 15px;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 100, 150, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        /* Passenger Controls */
        .passenger-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .passenger-btn {
            width: 40px;
            height: 40px;
            border: 2px solid var(--primary);
            background: white;
            color: var(--primary);
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .passenger-btn:hover {
            background: var(--primary);
            color: white;
        }
        
        .passenger-count {
            font-size: 20px;
            font-weight: bold;
            color: var(--dark);
            min-width: 30px;
            text-align: center;
        }
        
        /* Checkbox */
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-top: 3px;
        }
        
        .checkbox-group label {
            margin-bottom: 0;
            font-weight: normal;
        }
        
        .checkbox-group a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .checkbox-group a:hover {
            text-decoration: underline;
        }
        
        /* Package Card */
        .car-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 12px;
            border-left: 4px solid var(--primary);
            margin-bottom: 25px;
        }
        
        .car-card h3 {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 20px;
            font-weight: 600;
        }
        
        .car-card p {
            margin-bottom: 8px;
            color: var(--dark);
        }
        
        .car-card strong {
            color: var(--primary);
        }
        
        /* Price Summary */
        .price-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid var(--border);
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
            font-size: 16px;
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .summary-total {
            font-size: 18px;
            font-weight: bold;
            color: var(--primary);
            border-top: 2px solid var(--primary);
            margin-top: 10px;
            padding-top: 15px;
        }
        
        /* Payment Methods */
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .payment-method {
            border: 2px solid var(--border);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .payment-method:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }
        
        .payment-method.selected {
            border-color: var(--primary);
            background: #e8f4fc;
            box-shadow: 0 4px 12px rgba(42, 100, 150, 0.1);
        }
        
        .payment-icon {
            font-size: 32px;
            margin-bottom: 10px;
            color: var(--primary);
        }
        
        .payment-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .payment-desc {
            font-size: 12px;
            color: var(--gray);
        }
        
        /* Payment Details */
        .payment-details {
            display: none;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 15px;
            border-left: 4px solid var(--primary);
        }
        
        .payment-details.active {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* QR Code and Barcode Sections */
        .qr-section {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 2px dashed var(--border);
        }
        
        .qr-title {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 600;
        }
        
        .qr-code-container {
            display: inline-block;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }
        
        #cbeQrCode, #telebirrQrCode {
            width: 200px;
            height: 200px;
        }
        
        .barcode-container {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: center;
        }
        
        .barcode {
            font-family: 'Libre Barcode 128', cursive;
            font-size: 48px;
            letter-spacing: 2px;
            background: white;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 0;
        }
        
        .payment-instructions {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .payment-instructions h4 {
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .payment-instructions ul {
            padding-left: 20px;
            margin-bottom: 15px;
        }
        
        .payment-instructions li {
            margin-bottom: 8px;
            color: var(--dark);
        }
        
        /* Bank Transfer Details */
        .bank-details {
            background: #e8f4fc;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--primary);
            margin-bottom: 15px;
        }
        
        .bank-details h5 {
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .bank-detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(42, 100, 150, 0.1);
        }
        
        .bank-detail-row:last-child {
            border-bottom: none;
        }
        
        .copy-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 10px;
            transition: all 0.3s ease;
        }
        
        .copy-btn:hover {
            background: #1d4f7a;
        }
        
        /* Navigation Buttons */
        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            gap: 15px;
        }
        
        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            min-width: 120px;
            justify-content: center;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-prev {
            background: var(--gray);
            color: white;
        }
        
        .btn-prev:hover:not(:disabled) {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-next {
            background: var(--primary);
            color: white;
        }
        
        .btn-next:hover:not(:disabled) {
            background: #1d4f7a;
            transform: translateY(-2px);
        }
        
        .btn-pay {
            background: var(--success);
            color: white;
        }
        
        .btn-pay:hover:not(:disabled) {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-telebirr {
            background: #ff6b00;
            color: white;
        }
        
        .btn-telebirr:hover:not(:disabled) {
            background: #e55a00;
            transform: translateY(-2px);
        }
        
        .btn-cbe {
            background: #2a6496;
            color: white;
        }
        
        .btn-cbe:hover:not(:disabled) {
            background: #1d4f7a;
            transform: translateY(-2px);
        }
        
        /* Error Handling */
        .error-message {
            color: #dc3545;
            font-size: 14px;
            margin-top: 6px;
            display: none;
            font-weight: 500;
        }
        
        .input-error {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1) !important;
        }
        
        /* Loading Spinner */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-spinner p {
            color: var(--dark);
            font-size: 16px;
            font-weight: 500;
        }
        
        /* Payment Processing */
        .payment-processing {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .payment-processing i {
            font-size: 64px;
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        .payment-processing h3 {
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        /* Scan Instructions */
        .scan-instructions {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: center;
        }
        
        .scan-instructions i {
            color: #856404;
            margin-right: 8px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header {
                padding: 30px 15px;
            }
            
            .header h1 {
                font-size: 28px;
            }
            
            .step-indicator {
                padding: 0 10px;
            }
            
            .step-indicator::before {
                left: 30px;
                right: 30px;
            }
            
            .step-label {
                font-size: 12px;
            }
            
            .form-section {
                padding: 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .payment-methods {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .navigation-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            #cbeQrCode, #telebirrQrCode {
                width: 150px;
                height: 150px;
            }
        }
        
        @media (max-width: 480px) {
            .header h1 {
                font-size: 24px;
            }
            
            .header p {
                font-size: 16px;
            }
            
            .section-title {
                font-size: 20px;
            }
            
            .step-number {
                width: 30px;
                height: 30px;
                font-size: 14px;
            }
            
            input, select, textarea {
                padding: 12px 14px;
                font-size: 15px;
            }
            
            .payment-methods {
                grid-template-columns: 1fr;
            }
            
            .barcode {
                font-size: 36px;
            }
        }
    </style>
    <!-- Include barcode font -->
    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+128&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Book Your Tour</h1>
            <p>Complete your booking in 3 simple steps</p>
        </div>
        
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-label">Personal Info</div>
            </div>
            <div class="step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-label">Tour Details</div>
            </div>
            <div class="step" data-step="3">
                <div class="step-number">3</div>
                <div class="step-label">Payment</div>
            </div>
        </div>
        
        <form id="bookingForm" action="process_booking.php" method="POST">
            <input type="hidden" name="car_name" value="<?php echo htmlspecialchars($car['name']); ?>">
            <input type="hidden" name="car_price" value="<?php echo $car['price']; ?>">
            <input type="hidden" name="payment_method" id="selectedPaymentMethod" value="">
            <input type="hidden" name="booking_reference" id="bookingReference" value="<?php echo $bookingReference; ?>">
            
            <!-- Step 1: Personal Information -->
           <div class="form-step active" id="step1">
    <div class="form-section">
        <h2 class="section-title">Personal Information</h2>
        
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" required>
                <div class="error-message" id="firstNameError">Please enter your first name</div>
            </div>
            
            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" required>
                <div class="error-message" id="lastNameError">Please enter your last name</div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required>
                <div class="error-message" id="emailError">Please enter a valid email address</div>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number *</label>
                <input type="tel" id="phone" name="phone" required>
                <div class="error-message" id="phoneError">Please enter a valid phone number</div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="address">Address *</label>
            <input type="text" id="address" name="address" required>
            <div class="error-message" id="addressError">Please enter your address</div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="city">City *</label>
                <input type="text" id="city" name="city" required>
                <div class="error-message" id="cityError">Please enter your city</div>
            </div>
            
            <div class="form-group">
                <label for="country">Country *</label>
                <select id="country" name="country" required>
                    <option value="">Select Country</option>
                    <option value="Ethiopia" selected>Ethiopia</option>
                    <option value="United States">United States</option>
                    <option value="United Kingdom">United Kingdom</option>
                    <option value="Canada">Canada</option>
                    <option value="Australia">Australia</option>
                    <option value="Germany">Germany</option>
                    <option value="France">France</option>
                    <option value="Other">Other</option>
                </select>
                <div class="error-message" id="countryError">Please select your country</div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="emergency_contact">Emergency Contact Name *</label>
            <input type="text" id="emergency_contact" name="emergency_contact" required>
            <div class="error-message" id="emergencyContactError">Please enter emergency contact name</div>
        </div>
        
        <div class="form-group">
            <label for="emergency_phone">Emergency Contact Phone *</label>
            <input type="tel" id="emergency_phone" name="emergency_phone" required>
            <div class="error-message" id="emergencyPhoneError">Please enter emergency contact phone</div>
        </div>
        
        <div class="form-group">
            <label>Special Requirements or Dietary Restrictions</label>
            <textarea id="special_requirements" name="special_requirements" placeholder="Any allergies, dietary restrictions, or special needs we should know about..."></textarea>
        </div>
    </div>
    
    <div class="navigation-buttons">
        <button type="button" class="btn btn-next" onclick="nextStep(1)">
            Next <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>
            
            <!-- Step 2: Tour Details -->
            <div class="form-step" id="step2">
    <div class="form-section">
        <h2 class="section-title">Tour Details</h2>
        
        <div class="car-card">
            <h3><?php echo htmlspecialchars($car['name']); ?></h3>
            <p><strong>Destination:</strong> <?php echo htmlspecialchars($car['destination']); ?></p>
            <p><strong>Duration:</strong> <?php echo htmlspecialchars($car['duration']); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($car['description']); ?></p>
            <p><strong>Price per person:</strong> $<?php echo number_format($car['price'], 2); ?></p>
        </div>
        
        <div class="form-group">
            <label for="tour_date">Select Tour Date *</label>
            <input type="date" id="tour_date" name="tour_date" min="<?php echo $today; ?>" max="<?php echo $maxDate; ?>" required>
            <div class="error-message" id="dateError">Please select a tour date</div>
        </div>
        
        <div class="form-group">
            <label>Number of Cars *</label>
            <div class="passenger-controls">
                <button type="button" class="passenger-btn" onclick="changePassengers(-1)">-</button>
                <span class="passenger-count" id="passengerCount">1</span>
                <button type="button" class="passenger-btn" onclick="changePassengers(1)">+</button>
                <input type="hidden" id="passengers" name="passengers" value="1">
            </div>
            <small>Maximum 10 passengers per booking</small>
        </div>
        
        <div class="form-group">
            <label for="pickup_location">Preferred Pickup Location *</label>
            <select id="pickup_location" name="pickup_location" required>
                <option value="">Select Pickup Location</option>
                <option value="Bole International Airport">Bole International Airport</option>
                <option value="Ghion Hotel">Ghion Hotel</option>
                <option value="Sheraton Addis">Sheraton Addis</option>
                <option value="City Center">City Center</option>
                <option value="Other">Other (specify in notes)</option>
            </select>
            <div class="error-message" id="pickupError">Please select a pickup location</div>
        </div>
        
        <div class="form-group">
            <label for="additional_notes">Additional Notes or Special Requests</label>
            <textarea id="additional_notes" name="additional_notes" placeholder="Any additional requests or notes for your tour..."></textarea>
        </div>
    </div>
    
    <div class="navigation-buttons">
        <button type="button" class="btn btn-prev" onclick="prevStep(2)">
            <i class="fas fa-arrow-left"></i> Previous
        </button>
        <button type="button" class="btn btn-next" onclick="nextStep(2)">
            Next <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>
            
            <!-- Step 3: Payment & Summary -->
            <div class="form-step" id="step3">
                <div class="form-section">
                    <h2 class="section-title">Booking Summary & Payment</h2>
                    
                    <div class="car-card">
                        <h3><?php echo htmlspecialchars($car['name']); ?></h3>
                        <p><strong>Destination:</strong> <?php echo htmlspecialchars($car['destination']); ?></p>
                        <p><strong>Duration:</strong> <?php echo htmlspecialchars($car['duration']); ?></p>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($car['description']); ?></p>
                    </div>
                    
                    <div class="price-summary">
                        <div class="summary-item">
                            <span>Price per person:</span>
                            <span>$<?php echo number_format($car['price'], 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Number of passengers:</span>
                            <span id="summaryPassengers">1</span>
                        </div>
                        <div class="summary-item summary-total">
                            <span>Total Amount:</span>
                            <span id="totalAmount">$<?php echo number_format($car['price'], 2); ?></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Select Payment Method *</label>
                        <div class="payment-methods">
                            <div class="payment-method" data-method="CBE Bank" onclick="selectPaymentMethod('CBE Bank')">
                                <div class="payment-icon">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div class="payment-name">CBE Bank</div>
                                <div class="payment-desc">QR Code & Transfer</div>
                            </div>
                            
                            <div class="payment-method" data-method="TeleBirr" onclick="selectPaymentMethod('TeleBirr')">
                                <div class="payment-icon">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="payment-name">TeleBirr</div>
                                <div class="payment-desc">QR Code Payment</div>
                            </div>
                            
                            <div class="payment-method" data-method="Credit Card" onclick="selectPaymentMethod('Credit Card')">
                                <div class="payment-icon">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <div class="payment-name">Credit Card</div>
                                <div class="payment-desc">Visa/Mastercard</div>
                            </div>
                            
                            <div class="payment-method" data-method="Cash" onclick="selectPaymentMethod('Cash')">
                                <div class="payment-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="payment-name">Cash</div>
                                <div class="payment-desc">On Arrival</div>
                            </div>
                        </div>
                        <div class="error-message" id="paymentError">Please select a payment method</div>
                    </div>
                    
                    <!-- Payment Method Details -->
                    <div id="paymentDetails" class="payment-details">
                        <!-- CBE Bank QR Code Payment -->
                        <div id="cbeDetails" class="payment-instructions">
                            <h4><i class="fas fa-university"></i> CBE Bank QR Payment</h4>
                            
                            <div class="scan-instructions">
                                <i class="fas fa-camera"></i>
                                Scan the QR code with your CBE Birr app to pay instantly
                            </div>
                            
                            <div class="qr-section">
                                <div class="qr-title">Scan to Pay with CBE Birr</div>
                                <div class="qr-code-container">
                                    <div id="cbeQrCode"></div>
                                </div>
                                <div class="barcode-container">
                                    <div class="barcode" id="cbeBarcode">*<?php echo $bookingReference; ?>*</div>
                                </div>
                                <p><strong>Amount:</strong> $<span id="cbeAmount"><?php echo number_format($car['price'], 2); ?></span></p>
                                <p><strong>Reference:</strong> <?php echo $bookingReference; ?></p>
                            </div>
                            
                            <div class="bank-details">
                                <h5>Alternative: Bank Transfer</h5>
                                <div class="bank-detail-row">
                                    <span><strong>Bank:</strong></span>
                                    <span>Commercial Bank of Ethiopia</span>
                                </div>
                                <div class="bank-detail-row">
                                    <span><strong>Account Name:</strong></span>
                                    <span>Ghion Tour and Travel</span>
                                </div>
                                <div class="bank-detail-row">
                                    <span><strong>Account Number:</strong></span>
                                    <span>1000203040506 <button class="copy-btn" onclick="copyToClipboard('1000203040506')">Copy</button></span>
                                </div>
                                <div class="bank-detail-row">
                                    <span><strong>Reference:</strong></span>
                                    <span><?php echo $bookingReference; ?> <button class="copy-btn" onclick="copyToClipboard('<?php echo $bookingReference; ?>')">Copy</button></span>
                                </div>
                            </div>
                            
                            <button type="button" class="btn btn-cbe" onclick="processCBEPayment()">
                                <i class="fas fa-check"></i> I have completed the payment
                            </button>
                        </div>
                        
                        <!-- TeleBirr QR Code Payment -->
                        <div id="telebirrDetails" class="payment-instructions">
                            <h4><i class="fas fa-mobile-alt"></i> TeleBirr QR Payment</h4>
                            
                            <div class="scan-instructions">
                                <i class="fas fa-camera"></i>
                                Scan the QR code with your TeleBirr app to pay instantly
                            </div>
                            
                            <div class="qr-section">
                                <div class="qr-title">Scan to Pay with TeleBirr</div>
                                <div class="qr-code-container">
                                    <div id="telebirrQrCode"></div>
                                </div>
                                <div class="barcode-container">
                                    <div class="barcode" id="telebirrBarcode">*<?php echo $bookingReference; ?>*</div>
                                </div>
                                <p><strong>Amount:</strong> $<span id="telebirrAmount"><?php echo number_format($car['price'], 2); ?></span></p>
                                <p><strong>Reference:</strong> <?php echo $bookingReference; ?></p>
                            </div>
                            
                            <div class="payment-instructions">
                                <h5>Manual Payment Option</h5>
                                <ul>
                                    <li>Open your TeleBirr app</li>
                                    <li>Select "Send Money"</li>
                                    <li>Enter phone number: <strong>0912345678</strong></li>
                                    <li>Amount: <strong>$<span id="telebirrManualAmount"><?php echo number_format($car['price'], 2); ?></span></strong></li>
                                    <li>Add reference: <strong><?php echo $bookingReference; ?></strong></li>
                                </ul>
                            </div>
                            
                            <button type="button" class="btn btn-telebirr" onclick="processTeleBirrPayment()">
                                <i class="fas fa-mobile-alt"></i> I have completed the payment
                            </button>
                        </div>
                        
                        <!-- Credit Card Details -->
                        <div id="creditCardDetails" class="payment-instructions">
                            <!-- ... Credit card form remains the same ... -->
                        </div>
                        
                        <!-- Cash Payment -->
                        <div id="cashDetails" class="payment-instructions">
                            <!-- ... Cash payment details remain the same ... -->
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms">I agree to the <a href="terms.php" target="_blank">Terms & Conditions</a> and <a href="privacy.php" target="_blank">Privacy Policy</a></label>
                        </div>
                        <div class="error-message" id="termsError">You must agree to the terms and conditions</div>
                    </div>
                </div>
                
                <div class="navigation-buttons">
                    <button type="button" class="btn btn-prev" onclick="prevStep(2)">
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <button type="button" class="btn btn-submit" id="submitBtn" onclick="processFinalBooking()" style="display: none;">
                        <i class="fas fa-credit-card"></i> Complete Booking
                    </button>
                </div>
            </div>
        </form>
        
        <!-- Loading Spinner -->
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner"></div>
            <p>Processing your booking...</p>
        </div>
        
        <!-- Payment Processing -->
        <div class="payment-processing" id="paymentProcessing" style="display: none;">
            <i class="fas fa-sync-alt fa-spin"></i>
            <h3>Processing Payment...</h3>
            <p>Please wait while we process your payment. Do not refresh the page.</p>
        </div>
    </div>

    <script>
        function nextStep(current) {
    if (validateStep(current)) {
        // Hide current step
        document.getElementById(`step${current}`).classList.remove('active');
        
        // Update step indicator
        document.querySelector(`.step[data-step="${current}"]`).classList.remove('active');
        document.querySelector(`.step[data-step="${current}"]`).classList.add('completed');
        
        // Show next step
        const nextStep = current + 1;
        document.getElementById(`step${nextStep}`).classList.add('active');
        document.querySelector(`.step[data-step="${nextStep}"]`).classList.add('active');
        
        currentStep = nextStep;
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Show submit button on final step
        if (nextStep === 3) {
            document.getElementById('submitBtn').style.display = 'inline-flex';
        }
    }
}

function prevStep(current) {
    // Hide current step
    document.getElementById(`step${current}`).classList.remove('active');
    
    // Update step indicator
    document.querySelector(`.step[data-step="${current}"]`).classList.remove('active');
    
    // Show previous step
    const prevStep = current - 1;
    document.getElementById(`step${prevStep}`).classList.add('active');
    document.querySelector(`.step[data-step="${prevStep}"]`).classList.add('active');
    
    currentStep = prevStep;
    
    // Hide submit button if not on final step
    document.getElementById('submitBtn').style.display = 'none';
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateStep(step) {
    let isValid = true;
    
    switch(step) {
        case 1:
            // Validate personal information
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const address = document.getElementById('address').value.trim();
            const city = document.getElementById('city').value.trim();
            const country = document.getElementById('country').value;
            const emergencyContact = document.getElementById('emergency_contact').value.trim();
            const emergencyPhone = document.getElementById('emergency_phone').value.trim();
            
            if (!firstName) {
                showError('firstNameError', 'first_name');
                isValid = false;
            } else {
                hideError('firstNameError', 'first_name');
            }
            
            if (!lastName) {
                showError('lastNameError', 'last_name');
                isValid = false;
            } else {
                hideError('lastNameError', 'last_name');
            }
            
            if (!email || !isValidEmail(email)) {
                showError('emailError', 'email');
                isValid = false;
            } else {
                hideError('emailError', 'email');
            }
            
            if (!phone || !isValidPhone(phone)) {
                showError('phoneError', 'phone');
                isValid = false;
            } else {
                hideError('phoneError', 'phone');
            }
            
            if (!address) {
                showError('addressError', 'address');
                isValid = false;
            } else {
                hideError('addressError', 'address');
            }
            
            if (!city) {
                showError('cityError', 'city');
                isValid = false;
            } else {
                hideError('cityError', 'city');
            }
            
            if (!country) {
                showError('countryError', 'country');
                isValid = false;
            } else {
                hideError('countryError', 'country');
            }
            
            if (!emergencyContact) {
                showError('emergencyContactError', 'emergency_contact');
                isValid = false;
            } else {
                hideError('emergencyContactError', 'emergency_contact');
            }
            
            if (!emergencyPhone || !isValidPhone(emergencyPhone)) {
                showError('emergencyPhoneError', 'emergency_phone');
                isValid = false;
            } else {
                hideError('emergencyPhoneError', 'emergency_phone');
            }
            break;
            
        case 2:
            // Validate tour details
            const tourDate = document.getElementById('tour_date').value;
            const pickupLocation = document.getElementById('pickup_location').value;
            
            if (!tourDate) {
                showError('dateError', 'tour_date');
                isValid = false;
            } else {
                hideError('dateError', 'tour_date');
            }
            
            if (!pickupLocation) {
                showError('pickupError', 'pickup_location');
                isValid = false;
            } else {
                hideError('pickupError', 'pickup_location');
            }
            break;
    }
    
    return isValid;
}

function showError(errorElementId, inputElementId) {
    document.getElementById(errorElementId).style.display = 'block';
    document.getElementById(inputElementId).classList.add('input-error');
}

function hideError(errorElementId, inputElementId) {
    document.getElementById(errorElementId).style.display = 'none';
    document.getElementById(inputElementId).classList.remove('input-error');
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    // Basic phone validation - accepts numbers, spaces, dashes, and parentheses
    const phoneRegex = /^[\d\s\-\(\)\+]{10,}$/;
    return phoneRegex.test(phone.replace(/\s/g, ''));
}
        let passengerCount = 1;
        const basePrice = <?php echo $car['price']; ?>;
        let currentStep = 1;
        let selectedPaymentMethod = '';
        const bookingReference = '<?php echo $bookingReference; ?>';
        
        // Initialize QR Codes when page loads
        document.addEventListener('DOMContentLoaded', function() {
            generateCBEQRCode();
            generateTeleBirrQRCode();
        });
        
        // Real QR Code Implementation
async function generateRealCBEQRCode() {
    const totalAmount = basePrice * passengerCount;
    
    try {
        const response = await fetch('/api/generate-cbe-qr.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                amount: totalAmount,
                reference: bookingReference,
                account: '1000203040506'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Display the generated QR code
            document.getElementById('cbeQrCode').innerHTML = 
                `<img src="${data.qrCodeUrl}" alt="CBE QR Code" style="width: 200px; height: 200px;">`;
            
            // Update barcode with reference
            document.getElementById('cbeBarcode').textContent = `*${bookingReference}*`;
        } else {
            console.error('CBE QR generation failed:', data.error);
            // Fallback to client-side QR generation
            generateFallbackCBEQRCode(totalAmount);
        }
    } catch (error) {
        console.error('CBE QR API error:', error);
        generateFallbackCBEQRCode(totalAmount);
    }
}

async function generateRealTeleBirrQRCode() {
    const totalAmount = basePrice * passengerCount;
    
    try {
        const response = await fetch('/api/generate-telebirr-qr.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                amount: totalAmount,
                reference: bookingReference,
                phone: '0912345678'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Display the generated QR code
            document.getElementById('telebirrQrCode').innerHTML = 
                `<img src="${data.qrCodeUrl}" alt="TeleBirr QR Code" style="width: 200px; height: 200px;">`;
            
            // Store payment URL for later use
            window.telebirrPaymentUrl = data.paymentUrl;
            
            // Update barcode with reference
            document.getElementById('telebirrBarcode').textContent = `*${bookingReference}*`;
        } else {
            console.error('TeleBirr QR generation failed:', data.error);
            // Fallback to client-side QR generation
            generateFallbackTeleBirrQRCode(totalAmount);
        }
    } catch (error) {
        console.error('TeleBirr QR API error:', error);
        generateFallbackTeleBirrQRCode(totalAmount);
    }
}

// Fallback functions (client-side generation)
function generateFallbackCBEQRCode(totalAmount) {
    const qrData = `CBE|Ghion Travel|${totalAmount}|${bookingReference}|1000203040506`;
    
    QRCode.toCanvas(document.getElementById('cbeQrCode'), qrData, {
        width: 200,
        height: 200,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    }, function(error) {
        if (error) console.error('Fallback CBE QR Code error:', error);
    });
}

function generateFallbackTeleBirrQRCode(totalAmount) {
    const qrData = `telebirr://payment?phone=0912345678&amount=${totalAmount}&reference=${bookingReference}`;
    
    QRCode.toCanvas(document.getElementById('telebirrQrCode'), qrData, {
        width: 200,
        height: 200,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    }, function(error) {
        if (error) console.error('Fallback TeleBirr QR Code error:', error);
    });
}
        
        function generateTeleBirrQRCode() {
            const totalAmount = basePrice * passengerCount;
            const qrData = `telebirr://payment?phone=0912345678&amount=${totalAmount}&reference=${bookingReference}`;
            
            QRCode.toCanvas(document.getElementById('telebirrQrCode'), qrData, {
                width: 200,
                height: 200,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            }, function(error) {
                if (error) console.error('TeleBirr QR Code error:', error);
            });
        }
        
        function changePassengers(change) {
            passengerCount += change;
            if (passengerCount < 1) passengerCount = 1;
            if (passengerCount > 10) passengerCount = 10;
            
            document.getElementById('passengerCount').textContent = passengerCount;
            document.getElementById('passengers').value = passengerCount;
            document.getElementById('summaryPassengers').textContent = passengerCount;
            
            const totalAmount = basePrice * passengerCount;
            document.getElementById('totalAmount').textContent = '$' + totalAmount.toFixed(2);
            document.getElementById('telebirrAmount').textContent = totalAmount.toFixed(2);
            document.getElementById('telebirrManualAmount').textContent = totalAmount.toFixed(2);
            document.getElementById('cbeAmount').textContent = totalAmount.toFixed(2);
            document.getElementById('cashAmount').textContent = totalAmount.toFixed(2);
            
            // Regenerate QR codes with updated amount
            generateCBEQRCode();
            generateTeleBirrQRCode();
        }
        
        function selectPaymentMethod(method) {
            selectedPaymentMethod = method;
            document.getElementById('selectedPaymentMethod').value = method;
            
            // Remove selected class from all methods
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selected class to clicked method
            event.currentTarget.classList.add('selected');
            
            // Show payment details
            const paymentDetails = document.getElementById('paymentDetails');
            paymentDetails.classList.add('active');
            
            // Hide all payment detail sections
            document.querySelectorAll('#paymentDetails > div').forEach(el => {
                el.style.display = 'none';
            });
            
            // Show selected payment method details
            switch(method) {
                case 'CBE Bank':
                    document.getElementById('cbeDetails').style.display = 'block';
                    break;
                case 'TeleBirr':
                    document.getElementById('telebirrDetails').style.display = 'block';
                    break;
                case 'Credit Card':
                    document.getElementById('creditCardDetails').style.display = 'block';
                    break;
                case 'Cash':
                    document.getElementById('cashDetails').style.display = 'block';
                    break;
            }
            
            // Hide error if any
            hideError('paymentError', 'payment_method');
        }
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                btn.style.background = '#28a745';
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.style.background = '';
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy: ', err);
                alert('Failed to copy text. Please copy manually.');
            });
        }
        
        function processCBEPayment() {
            if (validatePaymentDetails()) {
                showPaymentProcessing();
                // Simulate CBE payment verification
                setTimeout(() => {
                    completeBooking();
                }, 3000);
            }
        }
        
        function processTeleBirrPayment() {
            if (validatePaymentDetails()) {
                showPaymentProcessing();
                // Simulate TeleBirr payment verification
                setTimeout(() => {
                    completeBooking();
                }, 3000);
            }
        }
        
        function processCreditCardPayment() {
            if (validateCreditCard() && validatePaymentDetails()) {
                showPaymentProcessing();
                // Simulate credit card payment
                setTimeout(() => {
                    completeBooking();
                }, 5000);
            }
        }
        
        function processCashPayment() {
            if (validatePaymentDetails()) {
                showPaymentProcessing();
                setTimeout(() => {
                    completeBooking();
                }, 2000);
            }
        }
        
        function processFinalBooking() {
            if (validateStep(3) && validatePaymentDetails()) {
                showPaymentProcessing();
                setTimeout(() => {
                    document.getElementById('bookingForm').submit();
                }, 2000);
            }
        }
        
        function completeBooking() {
            document.getElementById('paymentProcessing').style.display = 'none';
            document.getElementById('loadingSpinner').style.display = 'block';
            document.getElementById('bookingForm').submit();
        }
        
        function showPaymentProcessing() {
            document.getElementById('paymentProcessing').style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function validatePaymentDetails() {
            if (!selectedPaymentMethod) {
                showError('paymentError', 'payment_method');
                return false;
            }
            
            const terms = document.getElementById('terms').checked;
            if (!terms) {
                showError('termsError', 'terms');
                return false;
            }
            
            return true;
        }
        
        function validateCreditCard() {
            // ... credit card validation remains the same ...
        }
        
        // ... rest of the JavaScript functions remain the same ...
        
        // Initialize
        changePassengers(0);
        document.getElementById('tour_date').min = '<?php echo $today; ?>';
    </script>
</body>
</html>