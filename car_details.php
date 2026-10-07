<?php
require_once 'auth_check.php';
requireLogin();
require_once 'vendor/autoload.php';

// Get package ID from URL
$carId = $_GET['id'] ?? '';

if (empty($carId)) {
    header('Location: car.php');
    exit();
}

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $carsCollection = $database->selectCollection('car');
    
    // Convert string ID to MongoDB ObjectId
    $car = $carsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($carId)]);
    
    if (!$car) {
        die("car not found.");
    }
    
    // Get related packages (same destination)
    $relatedcars = $carsCollection->find([
        'destination' => $car['destination'],
        '_id' => ['$ne' => new MongoDB\BSON\ObjectId($carId)]
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
    <title><?php echo htmlspecialchars($car['name']); ?> - Tour Travel</title>
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
                <a href="car.php"class="active">Car</a>
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
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="car.php">Cars</a> &gt; 
            <span><?php echo htmlspecialchars($car['name']); ?></span>
        </div>

        <!-- Package Details -->
        <div class="car-details">
            <div class="car-hero">
                <?php
                $imagePath = 'images/' . ($car['image'] ?? '');
                $imageAlt = $car['image_alt'] ?? $car['name'];
                
                if (isset($car['image']) && file_exists($imagePath)) {
                    echo "<img src='$imagePath' alt='$imageAlt' class='car-hero-image'>";
                } else {
                    echo "<div class='car-hero-placeholder'>";
                    echo "<div>";
                    echo "<i class='fas fa-map-marked-alt' style='font-size: 64px; margin-bottom: 20px;'></i>";
                    echo "<h2>" . htmlspecialchars($car['destination']) . "</h2>";
                    echo "</div>";
                    echo "</div>";
                }
                ?>
                <div class="car-hero-content">
                    <h1 class="car-title"><?php echo htmlspecialchars($car['name']); ?></h1>
                    <div class="car-meta">
                        <div class="car-price">$<?php echo number_format($car['price'], 2); ?></div>
                        <div class="car-duration"><?php echo htmlspecialchars($car['duration']); ?></div>
                        <div class="car-destination">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($car['destination']); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="car-content">
                <!-- Description Section -->
                <div class="car-section">
                    <h2 class="section-title">car Overview</h2>
                    <p class="car-description"><?php echo htmlspecialchars($car['description']); ?></p>
                </div>
                
                <!-- Highlights Section -->
                <?php if (isset($car['highlights']) && !empty($car['highlights'])): ?>
                <div class="car-section">
                    <h2 class="section-title">car Highlights</h2>
                    <div class="highlights-grid">
                        <?php foreach ($car['highlights'] as $highlight): ?>
                            <div class="highlight-item">
                                <i class="fas fa-star"></i>
                                <?php echo htmlspecialchars($highlight); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Package Details -->
                <div class="car-section">
                    <h2 class="section-title">Car Details</h2>
                    <div class="details-grid">
                        <div class="detail-item">
                            <i class="fas fa-clock"></i>
                            <div class="detail-label">Duration</div>
                            <div class="detail-value"><?php echo htmlspecialchars($car['duration']); ?></div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="detail-label">Destination</div>
                            <div class="detail-value"><?php echo htmlspecialchars($car['destination']); ?></div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-dollar-sign"></i>
                            <div class="detail-label">Price</div>
                            <div class="detail-value">$<?php echo number_format($car['price'], 2); ?></div>
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
                <a href="book_car.php?car_id=<?php echo $carId; ?>&car=<?php echo urlencode($car['name']); ?>&price=<?php echo $car['price']; ?>" 
                   class="book-btn-large">
                    <i class="fas fa-calendar-check"></i> Book This car Now
                </a>
                <p style="margin-top: 15px; color: #666;">Secure your spot on this amazing adventure!</p>
            </div>
        </div>
        
        <!-- Related Packages -->
        <?php if (!empty($relatedcars)): ?>
        <div class="related-car">
            <h2 class="car-title">Related cars</h2>
            <div class="car-grid">
                <?php foreach ($relatedcars as $relatedcar): ?>
                    <div class="car-card">
                        <div class="car-image">
                            <?php
                            $relatedImagePath = 'images/' . ($relatedcar['image'] ?? '');
                            $relatedImageAlt = $relatedcar['image_alt'] ?? $relatedcar['name'];
                            
                            if (isset($relatedcar['image']) && file_exists($relatedImagePath)) {
                                echo "<img src='$relatedImagePath' alt='$relatedImageAlt'>";
                            } else {
                                echo "<div style='background: linear-gradient(135deg, var(--primary), var(--dark-blue)); height: 100%; display: flex; align-items: center; justify-content: center; color: white;'>";
                                echo "<i class='fas fa-map-marked-alt' style='font-size: 48px;'></i>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                        <div class="car-card-content">
                            <h3 class="car-card-title"><?php echo htmlspecialchars($relatedcar['name']); ?></h3>
                            <div class="car-card-meta">
                                <div class="car-card-price">$<?php echo number_format($relatedcar['price'], 2); ?></div>
                                <div class="car-card-duration"><?php echo htmlspecialchars($relatedcar['duration']); ?></div>
                            </div>
                            <p style="color: #666; font-size: 14px; margin-bottom: 10px;"><?php echo htmlspecialchars(substr($relatedcar['description'], 0, 100)); ?>...</p>
                            <a href="car_details.php?id=<?php echo $relatedcar['_id']; ?>" class="view-details-btn">
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
    const destination = "<?php echo htmlspecialchars($car['destination']); ?>";
    if (destination) {
        loadRecommendations(destination);
    }
});
    </script>
</body>
</html>