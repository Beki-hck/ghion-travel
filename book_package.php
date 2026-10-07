<?php
session_start();
require_once 'vendor/autoload.php';

// Get package details from URL
$packageName = $_GET['package'] ?? '';
$packagePrice = floatval($_GET['price'] ?? 0);

if (empty($packageName)) {
    header("Location: packages_mongo.php");
    exit;
}

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $packagesCollection = $database->selectCollection('packages');
    $hotelsCollection = $database->selectCollection('hotel');
    $carsCollection = $database->selectCollection('car');
    
    // Get package details from MongoDB
    $package = $packagesCollection->findOne(['name' => $packageName]);
    
    if (!$package) {
        header("Location: packages_mongo.php");
        exit;
    }
    
    // Get package area for filtering
    $packageArea = $package['area'] ?? $package['destination'] ?? '';
    
    // Get hotels and cars in the same area
    $areaHotels = $hotelsCollection->find([
        '$or' => [
            ['area' => $packageArea],
            ['destination' => $packageArea],
            ['location' => ['$regex' => $packageArea, '$options' => 'i']]
        ]
    ])->toArray();
    
    $areaCars = $carsCollection->find([
        '$or' => [
            ['area' => $packageArea],
            ['destination' => $packageArea],
            ['location' => ['$regex' => $packageArea, '$options' => 'i']]
        ]
    ])->toArray();

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
     <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
   
    
      
     
</head>
<body>

 <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="logo">Tour <span>Travel</div>
            <nav class="nav">
                <a href="index.php">Home</a>
                <a href="packages_mongo.php"class="active">Tour Packages</a>
                <a href="car.php">Car</a>
                 <a href="hotel.php">Hotel</a>
                <a href="about.php">About Us</a>
                <a href="contact.php">Contact & feedback</a>
                <a href="my_bookings_mongo.php">My Bookings</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                 <span style="color: orange;">Hello, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                  
                  <button id="darkModeToggle" class="dark-toggle">
                    <i class="fas fa-moon"></i> Dark Mode
                  </button>
                  <a href="logout.php" class="btn">Logout</a>
                <?php else: ?>
                    <button id="darkModeToggle" class="dark-toggle">
                        <i class="fas fa-moon"></i> Dark Mode
                    </button>
                    <a href="login.php" class="btn-outline">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
            <div class="container">
        <div class="book">
            <h1>Book Your Tour</h1>
            <p>Complete your booking in 4 simple steps</p>
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
                <div class="step-label">Add Services</div>
            </div>
            <div class="step" data-step="4">
                <div class="step-number">4</div>
                <div class="step-label">Payment</div>
            </div>
        </div>
        
        <form id="bookingForm" action="process_booking.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="package_id" value="<?php echo $package['_id']; ?>">
            <input type="hidden" name="package_name" value="<?php echo htmlspecialchars($package['name']); ?>">
            <input type="hidden" name="package_price" value="<?php echo $package['price']; ?>">
            <input type="hidden" name="package_area" value="<?php echo htmlspecialchars($packageArea); ?>">
            <input type="hidden" name="selected_hotel" id="selectedHotel" value="">
            <input type="hidden" name="selected_car" id="selectedCar" value="">
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
                    
                    <div class="package-card">
                        <h3><?php echo htmlspecialchars($package['name']); ?></h3>
                        <p><strong>Destination:</strong> <?php echo htmlspecialchars($package['destination']); ?></p>
                        <p><strong>Duration:</strong> <?php echo htmlspecialchars($package['duration']); ?></p>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($package['description']); ?></p>
                        <p><strong>Price per person:</strong> $<?php echo number_format($package['price'], 2); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="tour_date">Select Tour Date *</label>
                        <input type="date" id="tour_date" name="tour_date" min="<?php echo $today; ?>" max="<?php echo $maxDate; ?>" required>
                        <div class="error-message" id="dateError">Please select a tour date</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Number of Passengers *</label>
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
            
            <!-- Step 3: Hotel and Car Selection -->
            <div class="form-step" id="step3">
                <div class="selection-section">
                    <h2 class="section-title">
                        <i class="fas fa-hotel"></i> Available Hotels in <?php echo htmlspecialchars($packageArea); ?>
                    </h2>
                    <p class="section-subtitle">Choose your accommodation for the tour</p>
                    
                    <div class="selection-grid" id="hotelsGrid">
                        <?php if (!empty($areaHotels)): ?>
                            <?php foreach ($areaHotels as $hotel): ?>
                             <div class="selection-card" data-hotel-id="<?php echo $hotel['_id']; ?>" 
     data-hotel-price="<?php echo $hotel['price'] ?? 0; ?>"
     data-hotel-image="<?php echo htmlspecialchars($hotel['image'] ?? $hotel['images'][0] ?? ''); ?>"
     onclick="selectHotel(this)">
                                    <div class="area-badge"><?php echo htmlspecialchars($packageArea); ?></div>
                                    <h4><?php echo htmlspecialchars($hotel['name']); ?></h4>
                                    <div class="selection-price">$<?php echo number_format($hotel['price'] ?? 0, 2); ?>/night</div>
                                    <p class="selection-description"><?php echo htmlspecialchars($hotel['description'] ?? 'No description available'); ?></p>
                                    
                                    <?php if (isset($hotel['features'])): ?>
                                        <ul class="selection-features">
                                            <?php foreach (array_slice($hotel['features'], 0, 3) as $feature): ?>
                                                <li><?php echo htmlspecialchars($feature); ?></li>
                                            <?php endforeach; ?>
                                            <?php if (count($hotel['features']) > 3): ?>
                                                <li>+<?php echo count($hotel['features']) - 3; ?> more features</li>
                                            <?php endif; ?>
                                        </ul>
                                    <?php endif; ?>
                                    
                                    <div class="selection-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($hotel['location'] ?? $hotel['destination'] ?? 'Location not specified'); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-hotel" style="font-size: 48px; color: #6c757d; margin-bottom: 15px;"></i>
                                <h4>No Hotels Available</h4>
                                <p>No hotels found in the <?php echo htmlspecialchars($packageArea); ?> area.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="selection-section">
                    <h2 class="section-title">
                        <i class="fas fa-car"></i> Available Cars in <?php echo htmlspecialchars($packageArea); ?>
                    </h2>
                    <p class="section-subtitle">Choose your vehicle for the tour</p>
                    
                    <div class="selection-grid" id="carsGrid">
                        <?php if (!empty($areaCars)): ?>
                            <?php foreach ($areaCars as $car): ?>
                               <div class="selection-card" data-car-id="<?php echo $car['_id']; ?>" 
     data-car-price="<?php echo $car['price'] ?? 0; ?>"
     data-car-image="<?php echo htmlspecialchars($car['image'] ?? $car['images'][0] ?? ''); ?>"
     onclick="selectCar(this)">
                                    <div class="area-badge"><?php echo htmlspecialchars($packageArea); ?></div>
                                    <h4><?php echo htmlspecialchars($car['name'] ?? $car['model'] ?? 'Unknown Car'); ?></h4>
                                    <div class="selection-price">$<?php echo number_format($car['price'] ?? 0, 2); ?>/day</div>
                                    <p class="selection-description"><?php echo htmlspecialchars($car['description'] ?? 'No description available'); ?></p>
                                    
                                    <?php if (isset($car['type'])): ?>
                                        <div class="selection-feature">
                                            <strong>Type:</strong> <?php echo htmlspecialchars($car['type']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($car['capacity'])): ?>
                                        <div class="selection-feature">
                                            <strong>Capacity:</strong> <?php echo htmlspecialchars($car['capacity']); ?> passengers
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($car['features'])): ?>
                                        <ul class="selection-features">
                                            <?php foreach (array_slice($car['features'], 0, 3) as $feature): ?>
                                                <li><?php echo htmlspecialchars($feature); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-car" style="font-size: 48px; color: #6c757d; margin-bottom: 15px;"></i>
                                <h4>No Cars Available</h4>
                                <p>No cars found in the <?php echo htmlspecialchars($packageArea); ?> area.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="navigation-buttons">
                    <button type="button" class="btn btn-prev" onclick="prevStep(3)">
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <button type="button" class="btn btn-next" onclick="nextStep(3)">
                        Next <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
            
               <!-- Step 4: Payment & Summary -->
<div class="form-step" id="step4">
    <div class="form-section">
        <h2 class="section-title">Booking Summary & Payment</h2>
        
        <div class="booking-summary-grid">
            <!-- Package Summary -->
            <div class="package-card summary-card">
                <div class="summary-card-header">
                    <h3><?php echo htmlspecialchars($package['name']); ?></h3>
                </div>
                <div class="summary-card-content">
                    <?php if (isset($package['image']) && !empty($package['image'])): ?>
                        <div class="summary-image">
                            <img src="<?php echo htmlspecialchars($package['image']); ?>" alt="<?php echo htmlspecialchars($package['name']); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        </div>
                        <div class="summary-image placeholder" style="display: none;">
                            <i class="fas fa-image"></i>
                            <span>No Image</span>
                        </div>
                    <?php else: ?>
                        <div class="summary-image placeholder">
                            <i class="fas fa-image"></i>
                            <span>No Image</span>
                        </div>
                    <?php endif; ?>
                    <div class="summary-details">
                        <p><strong>Destination:</strong> <?php echo htmlspecialchars($package['destination']); ?></p>
                        <p><strong>Area:</strong> <?php echo htmlspecialchars($packageArea); ?></p>
                        <p><strong>Duration:</strong> <?php echo htmlspecialchars($package['duration']); ?></p>
                        <p><strong>Price per person:</strong> $<?php echo number_format($package['price'], 2); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Selected Hotel Summary -->
            <div id="selectedHotelSummary" class="summary-card" style="display: none;">
                <div class="summary-card-header">
                    <h4><i class="fas fa-hotel"></i> Selected Hotel</h4>
                </div>
                <div class="summary-card-content">
                    <div id="hotelImageContainer">
                        <div class="summary-image placeholder">
                            <i class="fas fa-hotel"></i>
                            <span>No Image</span>
                        </div>
                    </div>
                    <div class="summary-details">
                        <div id="hotelSummaryContent"></div>
                    </div>
                </div>
            </div>
            
            <!-- Selected Car Summary -->
            <div id="selectedCarSummary" class="summary-card" style="display: none;">
                <div class="summary-card-header">
                    <h4><i class="fas fa-car"></i> Selected Car</h4>
                </div>
                <div class="summary-card-content">
                    <div id="carImageContainer">
                        <div class="summary-image placeholder">
                            <i class="fas fa-car"></i>
                            <span>No Image</span>
                        </div>
                    </div>
                    <div class="summary-details">
                        <div id="carSummaryContent"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="price-summary">
            <div class="summary-item">
                <span>Tour Package:</span>
                <span>$<?php echo number_format($package['price'], 2); ?></span><br>
            </div>
            <div class="summary-item" id="hotelCostItem" style="display: none;">
                <span>Hotel:</span>
                <span id="hotelCost">$0.00</span><br>
            </div>
            <div class="summary-item" id="carCostItem" style="display: none;">
                <span>Car Rental:</span>
                <span id="carCost">$0.00</span><br>
            </div>
            <div class="summary-item">
                <span>Number of passengers:</span>
                <span id="summaryPassengers">1</span><br>
            </div>
            <div class="summary-item summary-total">
                <span>Total Amount:</span>
                <span id="totalAmount">$<?php echo number_format($package['price'], 2); ?></span>
            </div>
        </div>
        
        <!-- Payment Methods with Real Logos -->
        <div class="form-group">
            <label>Select Payment Method *</label>
            <div class="payment-methods-grid">
                <!-- CBE Birr with Official Logo -->
                <div class="payment-method-card" data-method="CBE Birr" onclick="selectPaymentMethod('CBE Birr')">
                    <div class="payment-method-logo">
                        <!-- CBE Birr Official Logo (using text with CBE colors) -->
                        <div class="cbe-logo">
                            <span style="color: #003366; font-weight: bold; font-size: 18px;">CBE</span>
                            <span style="color: #00A859; font-weight: bold; font-size: 18px;">Birr</span>
                        </div>
                        <div class="payment-method-badge">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                    </div>
                    <div class="payment-method-info">
                        <h4 class="payment-method-name">CBE Birr</h4>
                        <p class="payment-method-desc">Mobile banking via CBE</p>
                        <div class="payment-features">
                            <span><i class="fas fa-shield-alt"></i> Secure</span>
                            <span><i class="fas fa-bolt"></i> Instant</span>
                        </div>
                    </div>
                    <div class="payment-method-action">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                    <div class="payment-method-selected">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                
                <!-- TeleBirr with Official Logo -->
                <div class="payment-method-card" data-method="TeleBirr" onclick="selectPaymentMethod('TeleBirr')">
                    <div class="payment-method-logo">
                        <!-- TeleBirr Official Logo (using Ethiopian colors) -->
                        <div class="telebirr-logo">
                            <span style="color: #006400; font-weight: bold; font-size: 18px;">Tele</span>
                            <span style="color: #D4AF37; font-weight: bold; font-size: 18px;">Birr</span>
                        </div>
                        <div class="payment-method-badge">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                    </div>
                    <div class="payment-method-info">
                        <h4 class="payment-method-name">TeleBirr</h4>
                        <p class="payment-method-desc">Ethio Telecom mobile money</p>
                        <div class="payment-features">
                            <span><i class="fas fa-shield-alt"></i> Secure</span>
                            <span><i class="fas fa-bolt"></i> Fast</span>
                        </div>
                    </div>
                    <div class="payment-method-action">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                    <div class="payment-method-selected">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                
                <!-- Bank Transfer -->
                <div class="payment-method-card" data-method="Bank Transfer" onclick="selectPaymentMethod('Bank Transfer')">
                    <div class="payment-method-logo">
                        <div class="bank-transfer-logo">
                            <i class="fas fa-university" style="color: #003366; font-size: 32px;"></i>
                        </div>
                        <div class="payment-method-badge">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                    <div class="payment-method-info">
                        <h4 class="payment-method-name">Bank Transfer</h4>
                        <p class="payment-method-desc">Any commercial bank</p>
                        <div class="payment-features">
                            <span><i class="fas fa-receipt"></i> Receipt</span>
                            <span><i class="fas fa-clock"></i> 24h</span>
                        </div>
                    </div>
                    <div class="payment-method-action">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                    <div class="payment-method-selected">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                
                <!-- Cash Payment -->
                <div class="payment-method-card" data-method="Cash" onclick="selectPaymentMethod('Cash')">
                    <div class="payment-method-logo">
                        <div class="cash-logo">
                            <i class="fas fa-money-bill-wave" style="color: #00A859; font-size: 32px;"></i>
                        </div>
                        <div class="payment-method-badge">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                    <div class="payment-method-info">
                        <h4 class="payment-method-name">Cash</h4>
                        <p class="payment-method-desc">Pay at our office</p>
                        <div class="payment-features">
                            <span><i class="fas fa-map-marker-alt"></i> Office</span>
                            <span><i class="fas fa-handshake"></i> In-person</span>
                        </div>
                    </div>
                    <div class="payment-method-action">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                    <div class="payment-method-selected">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <div class="error-message" id="paymentError">Please select a payment method</div>
        </div>
        
        <!-- Receipt Upload Section -->
        <div id="receiptUploadSection" class="form-section" style="display: none;">
            <h3 class="section-title">Upload Payment Receipt</h3>
            
            <div class="payment-instructions-card">
                <div class="payment-instructions-header">
                    <i class="fas fa-info-circle"></i>
                    <h4>Payment Instructions</h4>
                </div>
                <div class="payment-instructions-content" id="paymentInstructionsContent">
                    <!-- Instructions will be populated based on payment method -->
                </div>
            </div>
            
            <div class="file-upload-card" id="receiptUploadContainer">
                <div class="file-upload-header">
                    <div class="file-upload-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="file-upload-text">
                        <h4>Upload Payment Receipt</h4>
                        <p>Upload a clear image or screenshot of your payment receipt</p>
                    </div>
                </div>
                
                <div class="file-upload-body">
                    <input type="file" id="payment_receipt" name="payment_receipt" accept=".jpg,.jpeg,.png,.pdf" class="file-input" required>
                    <button type="button" class="file-upload-btn" onclick="document.getElementById('payment_receipt').click()">
                        <i class="fas fa-upload"></i> Choose File
                    </button>
                    <p class="file-upload-note">
                        <small>Supported formats: JPG, PNG, PDF (Max: 5MB)</small>
                    </p>
                    
                    <div class="file-preview" id="receiptPreview">
                        <div class="file-preview-header">
                            <i class="fas fa-file-alt"></i>
                            <span>File Preview</span>
                        </div>
                        <div class="file-preview-content">
                            <img id="receiptPreviewImage" src="" alt="Receipt Preview">
                            <div class="file-info" id="receiptFileInfo"></div>
                        </div>
                        <div class="file-preview-actions">
                            <button type="button" class="btn-remove-file" onclick="removeReceipt()">
                                <i class="fas fa-times"></i> Remove File
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="error-message" id="receiptError">Please upload your payment receipt</div>
            
            <div class="payment-status-card status-pending">
                <div class="payment-status-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="payment-status-content">
                    <h5>Payment Status: Pending Approval</h5>
                    <p>Your booking will be confirmed after we verify your payment receipt. You'll receive a confirmation email within 24 hours.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="navigation-buttons">
        <button type="button" class="btn btn-prev" onclick="prevStep(4)">
            <i class="fas fa-arrow-left"></i> Previous
        </button>
        <button type="submit" class="btn btn-submit" id="submitBtn">
            <i class="fas fa-paper-plane"></i> Complete Booking
        </button>
    </div>
</div>
        </form>
    </div>
 <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h3>Tour Travel</h3>
                    <p style="color: #bbb; line-height: 1.6; margin-bottom: 20px;">
                        Your trusted partner for unforgettable Ethiopian adventures. Explore ancient history, vibrant cultures, and breathtaking landscapes with us.
                    </p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="packages_mongo.php">Tour Packages</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Support</h3>
                    <ul class="footer-links">
                        <li><a href="faq.php">FAQ</a></li>
                        <li><a href="terms.php">Terms & Conditions</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                        <li><a href="my_bookings_mongo.php">My Bookings</a></li>
                        <li><a href="contact.php">Customer Support</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contact Info</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt"></i> Addis Ababa, Ethiopia</li>
                        <li><i class="fas fa-phone"></i> +251 11 123 4567</li>
                        <li><i class="fas fa-envelope"></i> info@ghiontours.com</li>
                        <li><i class="fas fa-clock"></i> Mon - Sun: 24/7</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Ghion Tour and Travel. All rights reserved. | 
                   <a href="admin_login.php" style="color: #bbb;">Admin Panel</a> | 
                   Powered by MongoDB</p>
            </div>
        </div>
    </footer>
    <script>
        // Global variables
        let selectedHotel = null;
        let selectedCar = null;
        let passengerCount = 1;
        const basePrice = <?php echo $package['price']; ?>;
        let currentStep = 1;
        let selectedPaymentMethod = '';
        const bookingReference = '<?php echo $bookingReference; ?>';
        
        // Hotel and Car Selection Functions
      // Update the selectHotel function to handle images
// Update the selectHotel function to handle images
function selectHotel(element) {
    document.querySelectorAll('#hotelsGrid .selection-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    element.classList.add('selected');
    
    // Get hotel image from data attribute or from image element in the card
    const hotelImage = element.getAttribute('data-hotel-image') || 
                     (element.querySelector('.hotel-image img') ? element.querySelector('.hotel-image img').src : null);
    
    selectedHotel = {
        id: element.getAttribute('data-hotel-id'),
        name: element.querySelector('h4').textContent,
        price: parseFloat(element.getAttribute('data-hotel-price')),
        image: hotelImage
    };
    
    document.getElementById('selectedHotel').value = selectedHotel.id;
    updateHotelSummary();
    updateTotalPrice();
}

// Update the selectCar function to handle images
function selectCar(element) {
    document.querySelectorAll('#carsGrid .selection-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    element.classList.add('selected');
    
    // Get car image from data attribute or from image element in the card
    const carImage = element.getAttribute('data-car-image') || 
                   (element.querySelector('.car-image img') ? element.querySelector('.car-image img').src : null);
    
    selectedCar = {
        id: element.getAttribute('data-car-id'),
        name: element.querySelector('h4').textContent,
        price: parseFloat(element.getAttribute('data-car-price')),
        image: carImage
    };
    
    document.getElementById('selectedCar').value = selectedCar.id;
    updateCarSummary();
    updateTotalPrice();
}

// Update the updateHotelSummary function to show images properly
function updateHotelSummary() {
    if (selectedHotel) {
        document.getElementById('selectedHotelSummary').style.display = 'block';
        document.getElementById('hotelCostItem').style.display = 'flex';
        document.getElementById('hotelCost').textContent = '$' + selectedHotel.price.toFixed(2);
        
        // Update hotel image
        const hotelImageContainer = document.getElementById('hotelImageContainer');
        if (selectedHotel.image && selectedHotel.image !== 'null' && selectedHotel.image !== '') {
            hotelImageContainer.innerHTML = `
                <div class="summary-image">
                    <img src="${selectedHotel.image}" alt="${selectedHotel.name}" 
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                </div>
                <div class="summary-image placeholder" style="display: none;">
                    <i class="fas fa-hotel"></i>
                    <span>No Image</span>
                </div>
            `;
        } else {
            hotelImageContainer.innerHTML = `
                <div class="summary-image placeholder">
                    <i class="fas fa-hotel"></i>
                    <span>No Image</span>
                </div>
            `;
        }
        
        document.getElementById('hotelSummaryContent').innerHTML = `
            <p><strong>${selectedHotel.name}</strong></p>
            <p>Price: $${selectedHotel.price.toFixed(2)} per night</p>
        `;
    } else {
        document.getElementById('selectedHotelSummary').style.display = 'none';
        document.getElementById('hotelCostItem').style.display = 'none';
    }
}

// Update the updateCarSummary function to show images properly
function updateCarSummary() {
    if (selectedCar) {
        document.getElementById('selectedCarSummary').style.display = 'block';
        document.getElementById('carCostItem').style.display = 'flex';
        document.getElementById('carCost').textContent = '$' + selectedCar.price.toFixed(2);
        
        // Update car image
        const carImageContainer = document.getElementById('carImageContainer');
        if (selectedCar.image && selectedCar.image !== 'null' && selectedCar.image !== '') {
            carImageContainer.innerHTML = `
                <div class="summary-image">
                    <img src="${selectedCar.image}" alt="${selectedCar.name}" 
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                </div>
                <div class="summary-image placeholder" style="display: none;">
                    <i class="fas fa-car"></i>
                    <span>No Image</span>
                </div>
            `;
        } else {
            carImageContainer.innerHTML = `
                <div class="summary-image placeholder">
                    <i class="fas fa-car"></i>
                    <span>No Image</span>
                </div>
            `;
        }
        
        document.getElementById('carSummaryContent').innerHTML = `
            <p><strong>${selectedCar.name}</strong></p>
            <p>Price: $${selectedCar.price.toFixed(2)} per day</p>
        `;
    } else {
        document.getElementById('selectedCarSummary').style.display = 'none';
        document.getElementById('carCostItem').style.display = 'none';
    }
}
        
       function updateTotalPrice() {
    // Only multiply package price by passengers
    let total = basePrice * passengerCount;
    
    // Add hotel price (NOT multiplied by passengers)
    if (selectedHotel) {
        total += selectedHotel.price; // Remove * passengerCount
    }
    
    // Add car price (NOT multiplied by passengers)
    if (selectedCar) {
        total += selectedCar.price; // Remove * passengerCount
    }
    
    document.getElementById('totalAmount').textContent = '$' + total.toFixed(2);
}
        
        // Step Navigation Functions
        function nextStep(current) {
            if (validateStep(current)) {
                document.getElementById(`step${current}`).classList.remove('active');
                
                document.querySelectorAll('.step').forEach(step => {
                    step.classList.remove('active');
                });
                
                const nextStepNum = current + 1;
                document.getElementById(`step${nextStepNum}`).classList.add('active');
                document.querySelector(`.step[data-step="${nextStepNum}"]`).classList.add('active');
                
                currentStep = nextStepNum;
                document.querySelector(`.step[data-step="${current}"]`).classList.add('completed');
                
                window.scrollTo({ top: 0, behavior: 'smooth' });
                
                if (nextStepNum === 4) {
                    updateHotelSummary();
                    updateCarSummary();
                    updateTotalPrice();
                }
            }
        }
        
        function prevStep(current) {
            document.getElementById(`step${current}`).classList.remove('active');
            
            document.querySelectorAll('.step').forEach(step => {
                step.classList.remove('active');
                step.classList.remove('completed');
            });
            
            const prevStepNum = current - 1;
            document.getElementById(`step${prevStepNum}`).classList.add('active');
            document.querySelector(`.step[data-step="${prevStepNum}"]`).classList.add('active');
            
            for (let i = 1; i < current; i++) {
                document.querySelector(`.step[data-step="${i}"]`).classList.add('completed');
            }
            
            currentStep = prevStepNum;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Validation Functions
        function validateStep(step) {
            let isValid = true;
            
            switch(step) {
                case 1:
                    const requiredFields = [
                        'first_name', 'last_name', 'email', 'phone', 
                        'address', 'city', 'country', 'emergency_contact', 'emergency_phone'
                    ];
                    
                    requiredFields.forEach(fieldId => {
                        const field = document.getElementById(fieldId);
                        if (!field.value.trim()) {
                            showError(fieldId + 'Error', fieldId);
                            isValid = false;
                        } else {
                            hideError(fieldId + 'Error', fieldId);
                        }
                    });
                    
                    const emailField = document.getElementById('email');
                    if (emailField.value.trim() && !isValidEmail(emailField.value.trim())) {
                        showError('emailError', 'email');
                        isValid = false;
                    }
                    break;
                    
                case 2:
                    const tourDate = document.getElementById('tour_date');
                    const pickupLocation = document.getElementById('pickup_location');
                    
                    if (!tourDate.value) {
                        showError('dateError', 'tour_date');
                        isValid = false;
                    } else {
                        hideError('dateError', 'tour_date');
                    }
                    
                    if (!pickupLocation.value) {
                        showError('pickupError', 'pickup_location');
                        isValid = false;
                    } else {
                        hideError('pickupError', 'pickup_location');
                    }
                    break;
                    
                case 3:
                    isValid = true;
                    break;
                    
                default:
                    isValid = true;
            }
            
            return isValid;
        }
        
        function showError(errorElementId, inputElementId) {
            const errorElement = document.getElementById(errorElementId);
            const inputElement = document.getElementById(inputElementId);
            
            if (errorElement) {
                errorElement.style.display = 'block';
            }
            if (inputElement) {
                inputElement.classList.add('input-error');
            }
        }
        
        function hideError(errorElementId, inputElementId) {
            const errorElement = document.getElementById(errorElementId);
            const inputElement = document.getElementById(inputElementId);
            
            if (errorElement) {
                errorElement.style.display = 'none';
            }
            if (inputElement) {
                inputElement.classList.remove('input-error');
            }
        }
        
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        // Passenger Controls
        function changePassengers(change) {
            passengerCount += change;
            if (passengerCount < 1) passengerCount = 1;
            if (passengerCount > 10) passengerCount = 10;
            
            document.getElementById('passengerCount').textContent = passengerCount;
            document.getElementById('passengers').value = passengerCount;
            document.getElementById('summaryPassengers').textContent = passengerCount;
            
            updateTotalPrice();
        }
        
        // Payment Functions
        function selectPaymentMethod(method) {
    // 1. Identify the card clicked using the data attribute
    const clickedCard = document.querySelector(`.payment-method-card[data-method="${method}"]`);
    
    // 2. Check if this card is ALREADY selected
    const isAlreadySelected = clickedCard.classList.contains('selected');

    // 3. RESET: Remove 'selected' class from ALL cards (fixing the class name mismatch)
    document.querySelectorAll('.payment-method-card').forEach(el => {
        el.classList.remove('selected');
    });

    // 4. TOGGLE LOGIC
    if (isAlreadySelected) {
        // CASE A: We are deselecting (clicking the same card again)
        selectedPaymentMethod = '';
        document.getElementById('selectedPaymentMethod').value = '';
        
        // Hide the receipt upload section since nothing is selected
        document.getElementById('receiptUploadSection').style.display = 'none';
        
    } else {
        // CASE B: We are selecting a new card
        clickedCard.classList.add('selected');
        selectedPaymentMethod = method;
        document.getElementById('selectedPaymentMethod').value = method;
        
        // Show receipt section and update instructions
        document.getElementById('receiptUploadSection').style.display = 'block';
        updatePaymentInstructions(method);
        
        // Hide error message
        hideError('paymentError', 'payment_method');
    }
}
function updatePaymentInstructions(method) {
    const instructionsContent = document.getElementById('paymentInstructionsContent');
    let instructions = '';
    
    switch(method) {
        case 'CBE Birr':
            instructions = `
                <p><strong>CBE Birr Payment Instructions:</strong></p>
                <ul>
                    <li>Open your CBE Birr mobile app</li>
                    <li>Select "Send Money" option</li>
                    <li>Enter recipient number: <strong>0911 234 567</strong></li>
                    <li>Amount: <strong>$${calculateTotalAmount().toFixed(2)}</strong></li>
                    <li>Reference/Note: <strong>${bookingReference}</strong></li>
                    <li>Complete the transaction</li>
                    <li>Take a screenshot of the payment confirmation</li>
                    <li>Upload the screenshot below</li>
                </ul>
                <p><em>Note: CBE Birr transactions are usually instant. Keep your transaction ID for reference.</em></p>
            `;
            break;
            
        case 'TeleBirr':
            instructions = `
                <p><strong>TeleBirr Payment Instructions:</strong></p>
                <ul>
                    <li>Open your TeleBirr app on your phone</li>
                    <li>Tap on "Send Money"</li>
                    <li>Enter recipient: <strong>0978 123 456</strong></li>
                    <li>Amount: <strong>$${calculateTotalAmount().toFixed(2)}</strong></li>
                    <li>Add Reference: <strong>${bookingReference}</strong></li>
                    <li>Enter your PIN to complete payment</li>
                    <li>Take a screenshot of the success message</li>
                    <li>Upload the screenshot below</li>
                </ul>
                <p><em>TeleBirr is available 24/7. Transaction fees may apply.</em></p>
            `;
            break;
            
        case 'Bank Transfer':
            instructions = `
                <p><strong>Bank Transfer Instructions:</strong></p>
                <ul>
                    <li>Transfer the total amount to our bank account</li>
                    <li><strong>Bank:</strong> Commercial Bank of Ethiopia</li>
                    <li><strong>Account Name:</strong> Ghion Tour and Travel PLC</li>
                    <li><strong>Account Number:</strong> 1000 2030 4050 6</li>
                    <li><strong>SWIFT Code:</strong> CBETETAA</li>
                    <li><strong>Reference:</strong> ${bookingReference}</li>
                    <li>Upload the bank transfer receipt/slip below</li>
                </ul>
                <p><em>Processing time: 1-2 business days. International transfers may take longer.</em></p>
            `;
            break;
            
        case 'Cash':
            instructions = `
                <p><strong>Cash Payment Instructions:</strong></p>
                <ul>
                    <li>Visit our main office at:</li>
                    <li><strong>Address:</strong> Ghion Travel Building, Bole Road, Addis Ababa</li>
                    <li><strong>Office Hours:</strong> Mon-Fri 8:30AM-5:30PM, Sat 9:00AM-1:00PM</li>
                    <li>Bring the exact amount: <strong>$${calculateTotalAmount().toFixed(2)}</strong></li>
                    <li>Mention your booking reference: <strong>${bookingReference}</strong></li>
                    <li>We will provide an official receipt upon payment</li>
                    <li>Your booking will be confirmed immediately</li>
                </ul>
                <p><em>For cash payments, please bring valid ID. Ethiopian Birr equivalent is also accepted.</em></p>
            `;
            break;
    }
    
    instructionsContent.innerHTML = instructions;
}
        
        function calculateTotalAmount() {
            let total = basePrice * passengerCount;
            
            if (selectedHotel) {
                total += selectedHotel.price ;
            }
            
            if (selectedCar) {
                total += selectedCar.price ;
            }
            
            return total;
        }
        
        // File Upload Handling
        document.getElementById('payment_receipt').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const preview = document.getElementById('receiptPreview');
                const previewImage = document.getElementById('receiptPreviewImage');
                const fileInfo = document.getElementById('receiptFileInfo');
                
                // Validate file type and size
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!validTypes.includes(file.type)) {
                    alert('Please upload a JPG, PNG, or PDF file.');
                    this.value = '';
                    return;
                }
                
                if (file.size > maxSize) {
                    alert('File size must be less than 5MB.');
                    this.value = '';
                    return;
                }
                
                // Show file info
                fileInfo.innerHTML = `
                    <strong>File Name:</strong> ${file.name}<br>
                    <strong>File Size:</strong> ${(file.size / 1024 / 1024).toFixed(2)} MB<br>
                    <strong>File Type:</strong> ${file.type}
                `;
                
                // Show preview for images
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                    preview.style.display = 'block';
                } else {
                    previewImage.style.display = 'none';
                    preview.style.display = 'block';
                }
                
                hideError('receiptError', 'payment_receipt');
            }
        });
        
        function removeReceipt() {
            document.getElementById('payment_receipt').value = '';
            document.getElementById('receiptPreview').style.display = 'none';
        }
        
        // Form submission validation
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            if (!selectedPaymentMethod) {
                e.preventDefault();
                showError('paymentError', 'payment_method');
                alert('Please select a payment method.');
                return false;
            }
            
            if (selectedPaymentMethod !== 'Cash') {
                const receiptFile = document.getElementById('payment_receipt').files[0];
                if (!receiptFile) {
                    e.preventDefault();
                    showError('receiptError', 'payment_receipt');
                    alert('Please upload your payment receipt.');
                    return false;
                }
            }
            
            const terms = document.getElementById('terms');
            if (terms && !terms.checked) {
                e.preventDefault();
                showError('termsError', 'terms');
                alert('Please agree to the terms and conditions.');
                return false;
            }
        });
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            const tourDateInput = document.getElementById('tour_date');
            if (tourDateInput) {
                tourDateInput.min = '<?php echo $today; ?>';
            }
            
            updateTotalPrice();
            
            // Add event listeners for real-time validation
            const inputs = document.querySelectorAll('input[required], select[required]');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    const step = parseInt(this.closest('.form-step').id.replace('step', ''));
                    validateStep(step);
                });
            });
        });
        // Dark Mode Functionality
function initializeDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    
    // Check for saved theme or prefer-color-scheme
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const savedTheme = localStorage.getItem('theme');
    
    // Set initial theme
    if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
    } else if (prefersDark) {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
    }
    
    updateDarkModeButton();
    
    // Toggle dark mode
    darkModeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateDarkModeButton();
    });
}

function updateDarkModeButton() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const currentTheme = document.documentElement.getAttribute('data-theme');
    
    if (currentTheme === 'dark') {
        darkModeToggle.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
    } else {
        darkModeToggle.innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
    }
}

// Initialize dark mode when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeDarkMode();
    
    // Your existing initialization code...
    const tourDateInput = document.getElementById('tour_date');
    if (tourDateInput) {
        tourDateInput.min = '<?php echo $today; ?>';
    }
    
    updateTotalPrice();
    
    // Add event listeners for real-time validation
    const inputs = document.querySelectorAll('input[required], select[required]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            const step = parseInt(this.closest('.form-step').id.replace('step', ''));
            validateStep(step);
        });
    });
});
    </script>
</body>
</html>