<?php

require_once 'auth_check.php';
requireLogin();

// Page content for logged-in users only
$user = getUserInfo();
require_once 'vendor/autoload.php';

// Get current user ID from session
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not logged in
    header('Location: login.php');
    exit();
}

$currentUserId = (int)$_SESSION['user_id'];
$currentUserEmail = $_SESSION['user_email'] ?? '';

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $bookingsCollection = $database->selectCollection('bookings');
    
    // Query for bookings belonging to current user only
    // Try multiple possible fields where user ID might be stored
    $filter = [
        '$or' => [
            ['user_id' => $currentUserId],
            ['customer_info.user_id' => $currentUserId],
            ['customer_info.email' => $currentUserEmail],
            ['created_by' => $currentUserId]
        ]
    ];
    
    // If user ID is stored as string in MongoDB (common issue)
    $filterString = [
        '$or' => [
            ['user_id' => (string)$currentUserId],
            ['customer_info.user_id' => (string)$currentUserId],
            ['customer_info.email' => $currentUserEmail],
            ['created_by' => (string)$currentUserId]
        ]
    ];
    
    // Try to find bookings with current user
    $bookings = $bookingsCollection->find(
        $filter,
        ['sort' => ['created_at' => -1]]
    )->toArray();
    
    // If no bookings found with integer user_id, try string user_id
    if (empty($bookings)) {
        $bookings = $bookingsCollection->find(
            $filterString,
            ['sort' => ['created_at' => -1]]
        )->toArray();
    }
    
    // As last resort, if still no bookings, get all (for debugging)
    if (empty($bookings)) {
        $allBookings = $bookingsCollection->find([], ['sort' => ['created_at' => -1]])->toArray();
        // Filter manually in PHP by email (most reliable)
        foreach ($allBookings as $booking) {
            if (isset($booking['customer_info']['email']) && 
                $booking['customer_info']['email'] === $currentUserEmail) {
                $bookings[] = $booking;
            }
        }
    }
    
    $totalBookings = count($bookings);
    
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper function to safely get array values
function getSafeValue($array, $key, $default = '') {
    return isset($array[$key]) ? $array[$key] : $default;
}

// Helper function to safely get nested array values
function getNestedValue($array, $keys, $default = '') {
    $current = $array;
    foreach ($keys as $key) {
        if (!isset($current[$key])) {
            return $default;
        }
        $current = $current[$key];
    }
    return $current;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Ghion Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
    
</head>
<body>
   <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="logo">Tour <span>Travel</div>
            <nav class="nav">
                <a href="index.php">Home</a>
                <a href="packages_mongo.php">Tour Packages</a>
                <a href="car.php">Car</a>
                 <a href="hotel.php">Hotel</a>
                <a href="about.php">About Us</a>
                <a href="contact.php">Contact & feedback</a>
                <a href="my_bookings_mongo.php"class="active">My Bookings</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span style="color: white;">Hello, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                  
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
    <main class="bookings-main-content">
        <div class="container">
            <div class="stats-card">
                <div class="stats-number"><?php echo $totalBookings; ?></div>
                <p>Total Bookings Found</p>
                <small style="color: var(--text-light);">Displaying bookings for user: <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></small>
            </div>
            
            <?php if ($totalBookings === 0): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Bookings Found</h3>
                    <p>You don't have any bookings yet. Start planning your adventure today!</p>
                    <ul style="text-align: left; max-width: 400px; margin: 20px auto; color: var(--text-light);">
                        <li><i class="fas fa-check-circle"></i> Your user ID: <?php echo $currentUserId; ?></li>
                        <li><i class="fas fa-check-circle"></i> Your email: <?php echo htmlspecialchars($currentUserEmail); ?></li>
                        <li><i class="fas fa-check-circle"></i> No matching bookings found in database</li>
                    </ul>
                    <a href="packages_mongo.php" class="explore-btn">
                        <i class="fas fa-search"></i> Explore Tour Packages
                    </a>
                </div>
            <?php else: ?>
                <div class="bookings-grid">
                    <?php foreach ($bookings as $booking): 
                        // Safely extract data from MongoDB document structure
                        $bookingReference = getSafeValue($booking, 'booking_reference', 'N/A');
                        $customerInfo = getSafeValue($booking, 'customer_info', []);
                        $tourDetails = getSafeValue($booking, 'tour_details', []);
                        $paymentInfo = getSafeValue($booking, 'payment_info', []);
                        $additionalServices = getSafeValue($booking, 'additional_services', []);
                        
                        // Customer details
                        $customerName = getSafeValue($customerInfo, 'first_name', '') . ' ' . getSafeValue($customerInfo, 'last_name', '');
                        $customerEmail = getSafeValue($customerInfo, 'email', 'N/A');
                        $customerPhone = getSafeValue($customerInfo, 'phone', 'N/A');
                        
                        // Tour details
                        $packageName = getSafeValue($tourDetails, 'package_name', 'Unknown Package');
                        $tourDate = getSafeValue($tourDetails, 'tour_date', 'N/A');
                        $passengers = getSafeValue($tourDetails, 'passengers', 1);
                        $packageArea = getSafeValue($tourDetails, 'package_area', 'N/A');
                        $pickupLocation = getSafeValue($tourDetails, 'pickup_location', 'N/A');
                        
                        // Payment details
                        $totalAmount = getNestedValue($paymentInfo, ['total_amount'], 0);
                        $paymentMethod = getNestedValue($paymentInfo, ['payment_method'], 'N/A');
                        $paymentStatus = getNestedValue($paymentInfo, ['payment_status'], 'pending');
                        $bookingStatus = getSafeValue($booking, 'booking_status', 'pending');
                        
                        // Additional services
                        $selectedHotel = getSafeValue($additionalServices, 'selected_hotel', null);
                        $selectedCar = getSafeValue($additionalServices, 'selected_car', null);
                        
                        // Format dates
                        $formattedTourDate = $tourDate !== 'N/A' ? date('M j, Y', strtotime($tourDate)) : 'N/A';
                        
                        // Get user ID from booking for verification
                        $bookingUserId = getSafeValue($booking, 'user_id', getNestedValue($booking, ['customer_info', 'user_id'], null));
                    ?>
                        <div class="booking-card">
                            <div class="booking-header">
                                <h3 class="booking-title"><?php echo htmlspecialchars($packageName); ?></h3>
                                <span class="booking-ref"><?php echo htmlspecialchars($bookingReference); ?></span>
                            </div>
                            
                            <div class="booking-body">
                                <div class="booking-details">
                                    <div class="detail-item">
                                        <span class="detail-label">Traveler Name</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($customerName); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Email</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($customerEmail); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Phone</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($customerPhone); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Travel Date</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($formattedTourDate); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Number of Travelers</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($passengers); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Destination Area</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($packageArea); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Pickup Location</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($pickupLocation); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Payment Method</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($paymentMethod); ?></span>
                                    </div>
                                </div>
                                
                                <?php if ($selectedHotel || $selectedCar): ?>
                                <div class="services-section">
                                    <h4 class="services-title">Additional Services</h4>
                                    <?php if ($selectedHotel): ?>
                                        <div class="service-item">
                                            <span><i class="fas fa-hotel"></i> <strong>Hotel:</strong> Selected</span>
                                            <span>Included</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($selectedCar): ?>
                                        <div class="service-item">
                                            <span><i class="fas fa-car"></i> <strong>Car:</strong> Selected</span>
                                            <span>Included</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="booking-footer">
                                <div class="booking-price">$<?php echo number_format($totalAmount, 2); ?></div>
                                <div class="booking-status status-<?php echo htmlspecialchars($bookingStatus); ?>">
                                    <?php echo ucfirst(htmlspecialchars($bookingStatus)); ?>
                                    <?php if ($paymentStatus !== $bookingStatus): ?>
                                        <br><small style="font-size: 12px; opacity: 0.8;">Payment: <?php echo ucfirst(htmlspecialchars($paymentStatus)); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="debug-info">
                <h3><i class="fas fa-user-shield"></i> User Bookings Filter</h3>
                <p><strong>Current User:</strong> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?> (ID: <?php echo $currentUserId; ?>)</p>
                <p><strong>Bookings Found:</strong> <?php echo $totalBookings; ?> booking(s)</p>
                <p><strong>Filter Method:</strong> 
                    <?php 
                    if ($totalBookings > 0) {
                        echo 'User ID/Email filter applied successfully';
                    } else {
                        echo 'No bookings matched your user credentials';
                    }
                    ?>
                </p>
                <p><i class="fas fa-database"></i> MongoDB: ghion_travel.bookings | 
                    <?php if ($totalBookings > 0): ?>
                        <span style="color: var(--success);">✓ Personal bookings loaded</span>
                    <?php else: ?>
                        <span style="color: var(--warning);">⚠ No personal bookings found</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </main>
    
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
                        <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook"></i></a>
                        <a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="https://twitter.com" target="_blank"><i class="fab fa-twitter"></i></a>
                        <a href="https://t.me" target="_blank"><i class="fab fa-telegram"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="packages_mongo.php">Tour Packages</a></li>
                        <li><a href="destination.php">Destinations</a></li>
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
        // Enhanced Dark Mode Functionality
        function initializeDarkMode() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const currentTheme = localStorage.getItem('theme') || 'light';
            
            // Apply saved theme
            applyTheme(currentTheme);
            
            // Toggle dark mode
            darkModeToggle.addEventListener('click', function() {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                applyTheme(newTheme);
            });
        }

        function applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            updateDarkModeButton(theme);
            
            // Force CSS repaint
            setTimeout(() => {
                document.querySelectorAll('*').forEach(el => {
                    el.style.display = 'none';
                    el.offsetHeight;
                    el.style.display = '';
                });
            }, 50);
        }

        function updateDarkModeButton(theme) {
            const darkModeToggle = document.getElementById('darkModeToggle');
            if (theme === 'dark') {
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
                darkModeToggle.style.background = '#e74c3c';
            } else {
                darkModeToggle.innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
                darkModeToggle.style.background = '#3498db';
            }
        }

        // Add loading animation to booking cards
        document.addEventListener('DOMContentLoaded', function() {
            const bookingCards = document.querySelectorAll('.booking-card');
            bookingCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Initialize dark mode
            initializeDarkMode();
            
            // Apply text color fix
            setTimeout(function() {
                if (document.documentElement.getAttribute('data-theme') === 'dark') {
                    const detailValues = document.querySelectorAll('.detail-value');
                    detailValues.forEach(value => {
                        value.style.color = '#ecf0f1';
                    });
                }
            }, 100);
            
            // Header scroll effect
            window.addEventListener('scroll', function() {
                const header = document.querySelector('.header');
                if (window.scrollY > 100) {
                    header.style.background = 'var(--primary)';
                    header.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
                } else {
                    header.style.background = 'var(--header-bg)';
                    header.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
                }
            });
        });

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>