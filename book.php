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
    $packageArea = $package['area'] ?? '';
    
    // Get hotels and cars in the same area
    $areaHotels = $hotelsCollection->find(['area' => $packageArea])->toArray();
    $areaCars = $carsCollection->find(['area' => $packageArea])->toArray();
    
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
        /* Add these new styles for hotel and car selection */
        .selection-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .selection-card {
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .selection-card:hover {
            border-color: #2a6496;
            transform: translateY(-2px);
        }
        
        .selection-card.selected {
            border-color: #2a6496;
            background: #e8f4fc;
        }
        
        .selection-card h4 {
            color: #2a6496;
            margin-bottom: 10px;
        }
        
        .selection-price {
            font-size: 18px;
            font-weight: bold;
            color: #e74c3c;
            margin: 10px 0;
        }
        
        .selection-features {
            list-style: none;
            margin: 10px 0;
        }
        
        .selection-features li {
            padding: 5px 0;
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        
        .selection-features li:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 8px;
        }
        
        .area-badge {
            background: #2a6496;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        /* Rest of your existing styles remain the same */
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
            --dark: #2c3e50;
            --light: #f8f9fa;
            --border: #e1e5e9;
        }
        
        /* ... rest of your existing CSS styles ... */
        
    </style>
    <!-- Include barcode font -->
    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+128&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="header">
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
        
        <form id="bookingForm" action="process_booking.php" method="POST">
            <input type="hidden" name="package_name" value="<?php echo htmlspecialchars($package['name']); ?>">
            <input type="hidden" name="package_price" value="<?php echo $package['price']; ?>">
            <input type="hidden" name="package_area" value="<?php echo htmlspecialchars($packageArea); ?>">
            <input type="hidden" name="selected_hotel" id="selectedHotel" value="">
            <input type="hidden" name="selected_car" id="selectedCar" value="">
            <input type="hidden" name="payment_method" id="selectedPaymentMethod" value="">
            <input type="hidden" name="booking_reference" id="bookingReference" value="<?php echo $bookingReference; ?>">
            
            <!-- Step 1: Personal Information -->
            <div class="form-step active" id="step1">
                <!-- Your existing personal information form -->
            </div>
            
            <!-- Step 2: Tour Details -->
            <div class="form-step" id="step2">
                <!-- Your existing tour details form -->
            </div>
            
            <!-- NEW Step 3: Hotel and Car Selection -->
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
                                     data-hotel-price="<?php echo $hotel['price']; ?>"
                                     onclick="selectHotel(this)">
                                    <div class="area-badge"><?php echo htmlspecialchars($packageArea); ?></div>
                                    <h4><?php echo htmlspecialchars($hotel['name']); ?></h4>
                                    <div class="selection-price">$<?php echo number_format($hotel['price'], 2); ?>/night</div>
                                    <p class="selection-description"><?php echo htmlspecialchars($hotel['description']); ?></p>
                                    
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
                                        <?php echo htmlspecialchars($hotel['location'] ?? $hotel['destination']); ?>
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
                                     data-car-price="<?php echo $car['price']; ?>"
                                     onclick="selectCar(this)">
                                    <div class="area-badge"><?php echo htmlspecialchars($packageArea); ?></div>
                                    <h4><?php echo htmlspecialchars($car['name']); ?></h4>
                                    <div class="selection-price">$<?php echo number_format($car['price'], 2); ?>/day</div>
                                    <p class="selection-description"><?php echo htmlspecialchars($car['description']); ?></p>
                                    
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
                    
                    <div class="package-card">
                        <h3><?php echo htmlspecialchars($package['name']); ?></h3>
                        <p><strong>Destination:</strong> <?php echo htmlspecialchars($package['destination']); ?></p>
                        <p><strong>Area:</strong> <?php echo htmlspecialchars($packageArea); ?></p>
                        <p><strong>Duration:</strong> <?php echo htmlspecialchars($package['duration']); ?></p>
                    </div>
                    
                    <!-- Selected Hotel Summary -->
                    <div id="selectedHotelSummary" class="package-card" style="display: none;">
                        <h4><i class="fas fa-hotel"></i> Selected Hotel</h4>
                        <div id="hotelSummaryContent"></div>
                    </div>
                    
                    <!-- Selected Car Summary -->
                    <div id="selectedCarSummary" class="package-card" style="display: none;">
                        <h4><i class="fas fa-car"></i> Selected Car</h4>
                        <div id="carSummaryContent"></div>
                    </div>
                    
                    <div class="price-summary">
                        <div class="summary-item">
                            <span>Tour Package:</span>
                            <span>$<?php echo number_format($package['price'], 2); ?></span>
                        </div>
                        <div class="summary-item" id="hotelCostItem" style="display: none;">
                            <span>Hotel:</span>
                            <span id="hotelCost">$0.00</span>
                        </div>
                        <div class="summary-item" id="carCostItem" style="display: none;">
                            <span>Car Rental:</span>
                            <span id="carCost">$0.00</span>
                        </div>
                        <div class="summary-item">
                            <span>Number of passengers:</span>
                            <span id="summaryPassengers">1</span>
                        </div>
                        <div class="summary-item summary-total">
                            <span>Total Amount:</span>
                            <span id="totalAmount">$<?php echo number_format($package['price'], 2); ?></span>
                        </div>
                    </div>
                    
                    <!-- Your existing payment method selection -->
                    
                </div>
                
                <div class="navigation-buttons">
                    <button type="button" class="btn btn-prev" onclick="prevStep(4)">
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <button type="button" class="btn btn-submit" id="submitBtn" onclick="processFinalBooking()" style="display: none;">
                        <i class="fas fa-credit-card"></i> Complete Booking
                    </button>
                </div>
            </div>
        </form>
        
        <!-- Loading and payment processing sections -->
    </div>

    <script>
        // Add these new JavaScript functions for hotel and car selection
        let selectedHotel = null;
        let selectedCar = null;
        let passengerCount = 1;
        const basePrice = <?php echo $package['price']; ?>;
        let currentStep = 1;
        let selectedPaymentMethod = '';
        const bookingReference = '<?php echo $bookingReference; ?>';
        
        function selectHotel(element) {
            // Remove selected class from all hotels
            document.querySelectorAll('#hotelsGrid .selection-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked hotel
            element.classList.add('selected');
            
            // Store selected hotel data
            selectedHotel = {
                id: element.getAttribute('data-hotel-id'),
                name: element.querySelector('h4').textContent,
                price: parseFloat(element.getAttribute('data-hotel-price'))
            };
            
            // Update hidden input
            document.getElementById('selectedHotel').value = selectedHotel.id;
            
            // Update summary in step 4
            updateHotelSummary();
            updateTotalPrice();
        }
        
        function selectCar(element) {
            // Remove selected class from all cars
            document.querySelectorAll('#carsGrid .selection-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked car
            element.classList.add('selected');
            
            // Store selected car data
            selectedCar = {
                id: element.getAttribute('data-car-id'),
                name: element.querySelector('h4').textContent,
                price: parseFloat(element.getAttribute('data-car-price'))
            };
            
            // Update hidden input
            document.getElementById('selectedCar').value = selectedCar.id;
            
            // Update summary in step 4
            updateCarSummary();
            updateTotalPrice();
        }
        
        function updateHotelSummary() {
            if (selectedHotel) {
                document.getElementById('selectedHotelSummary').style.display = 'block';
                document.getElementById('hotelCostItem').style.display = 'flex';
                document.getElementById('hotelCost').textContent = '$' + selectedHotel.price.toFixed(2);
                document.getElementById('hotelSummaryContent').innerHTML = `
                    <p><strong>${selectedHotel.name}</strong></p>
                    <p>Price: $${selectedHotel.price.toFixed(2)} per night</p>
                `;
            } else {
                document.getElementById('selectedHotelSummary').style.display = 'none';
                document.getElementById('hotelCostItem').style.display = 'none';
            }
        }
        
        function updateCarSummary() {
            if (selectedCar) {
                document.getElementById('selectedCarSummary').style.display = 'block';
                document.getElementById('carCostItem').style.display = 'flex';
                document.getElementById('carCost').textContent = '$' + selectedCar.price.toFixed(2);
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
            let total = basePrice * passengerCount;
            
            if (selectedHotel) {
                total += selectedHotel.price * passengerCount;
            }
            
            if (selectedCar) {
                total += selectedCar.price * passengerCount;
            }
            
            document.getElementById('totalAmount').textContent = '$' + total.toFixed(2);
        }
        
        // Modify the existing changePassengers function
        function changePassengers(change) {
            passengerCount += change;
            if (passengerCount < 1) passengerCount = 1;
            if (passengerCount > 10) passengerCount = 10;
            
            document.getElementById('passengerCount').textContent = passengerCount;
            document.getElementById('passengers').value = passengerCount;
            document.getElementById('summaryPassengers').textContent = passengerCount;
            
            updateTotalPrice();
        }
        
        // Your existing JavaScript functions (nextStep, prevStep, validateStep, etc.) remain the same
        // ... but make sure to update the step logic to include the new step 3
        
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
                if (nextStep === 4) {
                    document.getElementById('submitBtn').style.display = 'inline-flex';
                    updateTotalPrice(); // Ensure price is updated
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
        
        // Initialize
        document.getElementById('tour_date').min = '<?php echo $today; ?>';
    </script>
</body>
</html>