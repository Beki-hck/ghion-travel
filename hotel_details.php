<?php
require_once 'auth_check.php';
requireLogin();
require_once 'vendor/autoload.php';

// Get package ID from URL
$hotelId = $_GET['id'] ?? '';

if (empty($hotelId)) {
    header('Location: hotel.php');
    exit();
}

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $hotelsCollection = $database->selectCollection('hotel');
    
    // Convert string ID to MongoDB ObjectId
    $hotel = $hotelsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($hotelId)]);
    
    if (!$hotel) {
        die("hotel not found.");
    }
    
    // Get related packages (same destination)
    $relatedhotels = $hotelsCollection->find([
        'destination' => $hotel['destination'],
        '_id' => ['$ne' => new MongoDB\BSON\ObjectId($hotelId)]
    ], [
        'limit' => 3,
        'sort' => ['price' => 1]
    ])->toArray();

} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hotel['name']); ?> - Tour Travel</title>
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
                 <a href="hotel.php"class="active">Hotel</a>
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
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="hotel.php">Hotels</a> &gt; 
            <span><?php echo htmlspecialchars($hotel['name']); ?></span>
        </div>

        <!-- Package Details -->
        <div class="hotel-details">
            <div class="hotel-hero">
                <?php
                $imagePath = 'images/' . ($hotel['image'] ?? '');
                $imageAlt = $hotel['image_alt'] ?? $hotel['name'];
                
                if (isset($hotel['image']) && file_exists($imagePath)) {
                    echo "<img src='$imagePath' alt='$imageAlt' class='hotel-hero-image'>";
                } else {
                    echo "<div class='hotel-hero-placeholder'>";
                    echo "<div>";
                    echo "<i class='fas fa-map-marked-alt' style='font-size: 64px; margin-bottom: 20px;'></i>";
                    echo "<h2>" . htmlspecialchars($hotel['destination']) . "</h2>";
                    echo "</div>";
                    echo "</div>";
                }
                ?>
                <div class="hotel-hero-content">
                    <h1 class="hotel-title"><?php echo htmlspecialchars($hotel['name']); ?></h1>
                    <div class="hotel-meta">
                        <div class="hotel-price">$<?php echo number_format($hotel['price'], 2); ?></div>
                        <div class="hotel-duration"><?php echo htmlspecialchars($hotel['duration']); ?></div>
                        <div class="hotel-destination">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($hotel['destination']); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="hotel-content">
                <!-- Description Section -->
                <div class="hotel-section">
                    <h2 class="section-title">hotel Overview</h2>
                    <p class="hotel-description"><?php echo htmlspecialchars($hotel['description']); ?></p>
                </div>
                
                <!-- Highlights Section -->
                <?php if (isset($hotel['highlights']) && !empty($hotel['highlights'])): ?>
                <div class="hotel-section">
                    <h2 class="section-title">hotel Highlights</h2>
                    <div class="highlights-grid">
                        <?php foreach ($hotel['highlights'] as $highlight): ?>
                            <div class="highlight-item">
                                <i class="fas fa-star"></i>
                                <?php echo htmlspecialchars($highlight); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Package Details -->
                <div class="hotel-section">
                    <h2 class="section-title">hotel Details</h2>
                    <div class="details-grid">
                        <div class="detail-item">
                            <i class="fas fa-clock"></i>
                            <div class="detail-label">Duration</div>
                            <div class="detail-value"><?php echo htmlspecialchars($hotel['duration']); ?></div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="detail-label">Destination</div>
                            <div class="detail-value"><?php echo htmlspecialchars($hotel['destination']); ?></div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-dollar-sign"></i>
                            <div class="detail-label">Price</div>
                            <div class="detail-value">$<?php echo number_format($hotel['price'], 2); ?></div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-calendar-alt"></i>
                            <div class="detail-label">Availability</div>
                            <div class="detail-value">Year Round</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Section -->
            <div class="action-section">
                <a href="book_hotel.php?hotel_id=<?php echo $hotelId; ?>&hotel=<?php echo urlencode($hotel['name']); ?>&price=<?php echo $hotel['price']; ?>" 
                   class="book-btn-large">
                    <i class="fas fa-calendar-check"></i> Book This hotel Now
                </a>
                <p style="margin-top: 15px; color: #666;">Secure your spot on this amazing adventure!</p>
            </div>
        </div>
        
        <!-- Related Packages -->
        <?php if (!empty($relatedhotels)): ?>
        <div class="related-hotel">
            <h2 class="hotel-title">Related hotels</h2>
            <div class="hotel-grid">
                <?php foreach ($relatedhotels as $relatedhotel): ?>
                    <div class="hotel-card">
                        <div class="hotel-image">
                            <?php
                            $relatedImagePath = 'images/' . ($relatedhotel['image'] ?? '');
                            $relatedImageAlt = $relatedhotel['image_alt'] ?? $relatedhotel['name'];
                            
                            if (isset($relatedhotel['image']) && file_exists($relatedImagePath)) {
                                echo "<img src='$relatedImagePath' alt='$relatedImageAlt'>";
                            } else {
                                echo "<div style='background: linear-gradient(135deg, var(--primary), var(--dark-blue)); height: 100%; display: flex; align-items: center; justify-content: center; color: white;'>";
                                echo "<i class='fas fa-map-marked-alt' style='font-size: 48px;'></i>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                        <div class="hotel-card-content">
                            <h3 class="hotel-card-title"><?php echo htmlspecialchars($relatedhotel['name']); ?></h3>
                            <div class="hotel-card-meta">
                                <div class="hotel-card-price">$<?php echo number_format($relatedhotel['price'], 2); ?></div>
                                <div class="hotel-card-duration"><?php echo htmlspecialchars($relatedhotel['duration']); ?></div>
                            </div>
                            <p style="color: #666; font-size: 14px; margin-bottom: 10px;"><?php echo htmlspecialchars(substr($relatedhotel['description'], 0, 100)); ?>...</p>
                            <a href="hotel_details.php?id=<?php echo $relatedhotel['_id']; ?>" class="view-details-btn">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
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
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
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
    const destination = "<?php echo htmlspecialchars($hotel['destination']); ?>";
    if (destination) {
        loadRecommendations(destination);
    }
});
    </script>
</body>
</html>