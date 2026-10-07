<?php
require_once 'auth_check.php';
requireLogin();
require_once 'vendor/autoload.php';

// Get package ID from URL
$packageId = $_GET['id'] ?? '';

if (empty($packageId)) {
    header('Location: packages_mongo.php');
    exit();
}

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $packagesCollection = $database->selectCollection('packages');
    
    // Convert string ID to MongoDB ObjectId
    $package = $packagesCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($packageId)]);
    
    if (!$package) {
        die("Package not found.");
    }
    
    // Get related packages (same destination)
    $relatedPackages = $packagesCollection->find([
        'destination' => $package['destination'],
        '_id' => ['$ne' => new MongoDB\BSON\ObjectId($packageId)]
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
    <title><?php echo htmlspecialchars($package['name']); ?> - Tour Travel</title>
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
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="packages_mongo.php">Tour Packages</a> &gt; 
              <h1 class="package-titl"><span><?php echo htmlspecialchars($package['name']); ?></span></h1>
        </div>

        <!-- Package Details -->
        <div class="package-details">
            <div class="package-hero">
                <?php
                $imagePath = 'images/' . ($package['image'] ?? '');
                $imageAlt = $package['image_alt'] ?? $package['name'];
                
                if (isset($package['image']) && file_exists($imagePath)) {
                    echo "<img src='$imagePath' alt='$imageAlt' class='package-hero-image'>";
                } else {
                    echo "<div class='package-hero-placeholder'>";
                    echo "<div>";
                    echo "<i class='fas fa-map-marked-alt' style='font-size: 64px; margin-bottom: 20px;'></i>";
                    echo "<h2>" . htmlspecialchars($package['destination']) . "</h2>";
                    echo "</div>";
                    echo "</div>";
                }
                ?>
                <div class="package-hero-content">
                    <h1 class="package-title"><?php echo htmlspecialchars($package['name']); ?></h1>
                    <div class="package-meta">
                        <div class="package-price">$<?php echo number_format($package['price'], 2); ?></div>
                        <div class="package-duration"><?php echo htmlspecialchars($package['duration']); ?></div>
                        <div class="package-destination">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($package['destination']); ?>
                        </div>
                    </div>

                    <!-- ====== ADDED: hidden element with package destination for JS to read ====== -->
                    <span id="packageArea" style="display:none;"><?php echo htmlspecialchars($package['destination']); ?></span>
                    <!-- ======================================================================= -->
                </div>
            </div>
            
            <div class="package-content">
                <!-- Description Section -->
                <div class="package-section">
                    <h2 class="section-title">Package Overview</h2>
                    <p class="package-description"><?php echo htmlspecialchars($package['description']); ?></p>
                </div>
                
                <!-- Highlights Section -->
                <?php if (isset($package['highlights']) && !empty($package['highlights'])): ?>
                <div class="package-section">
                    <h2 class="section-title">Package Highlights</h2>
                    <div class="highlights-grid">
                        <?php foreach ($package['highlights'] as $highlight): ?>
                            <div class="highlight-item">
                                <i class="fas fa-star"></i>
                                <?php echo htmlspecialchars($highlight); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Package Details -->
                <div class="package-section">
                    <h2 class="section-title">Package Details</h2>
                    <div class="details-grid">
                        <div class="detail-item">
                            <i class="fas fa-clock"></i>
                            <div class="detail-label">Duration</div>
                            <div class="detail-value"><?php echo htmlspecialchars($package['duration']); ?></div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="detail-label">Destination</div>
                            <div class="detail-value"><?php echo htmlspecialchars($package['destination']); ?></div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-dollar-sign"></i>
                            <div class="detail-label">Price</div>
                            <div class="detail-value">$<?php echo number_format($package['price'], 2); ?></div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-calendar-alt"></i>
                            <div class="detail-label">Availability</div>
                            <div class="detail-value">Year Round</div>
                        </div>
                    </div>
                </div>
            </div>
            
<!-- Recommended Hotels & Cars Section -->
<div class="recommendations-section">
    <h2 class="recommendations-title">
        <i class="fas fa-hotel"></i> Recommended Hotels in <?php echo htmlspecialchars($package['destination']); ?>
    </h2>
    <div id="recommendedHotels" class="recommendations-grid">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading recommended hotels...</p>
        </div>
    </div>
    
    <h2 class="recommendations-title" style="margin-top: 40px;">
        <i class="fas fa-car"></i> Recommended Cars in <?php echo htmlspecialchars($package['destination']); ?>
    </h2>
    <div id="recommendedCars" class="recommendations-grid">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading available cars...</p>
        </div>
    </div><!-- Replace the existing booking button in package_details.php -->
<div class="action-section">
    <a href="book_package.php?package_id=<?php echo $packageId; ?>&package=<?php echo urlencode($package['name']); ?>&price=<?php echo $package['price']; ?>" 
       class="book-btn-large">
        <i class="fas fa-calendar-check"></i> Book Package with Hotel & Car
    </a>
    <p style="margin-top: 15px; color: #666;">Complete your travel experience with accommodation and transportation!</p>
</div>
</div>
        <!-- Related Packages -->
        <?php if (!empty($relatedPackages)): ?>
        <div class="related-packages">
            <h2 class="related-title">Related Packages</h2>
            <div class="packages-grid">
                <?php foreach ($relatedPackages as $relatedPackage): ?>
                    <div class="package-card">
                        <div class="package-image">
                            <?php
                            $relatedImagePath = 'images/' . ($relatedPackage['image'] ?? '');
                            $relatedImageAlt = $relatedPackage['image_alt'] ?? $relatedPackage['name'];
                            
                            if (isset($relatedPackage['image']) && file_exists($relatedImagePath)) {
                                echo "<img src='$relatedImagePath' alt='$relatedImageAlt'>";
                            } else {
                                echo "<div style='background: linear-gradient(135deg, var(--primary), var(--dark-blue)); height: 100%; display: flex; align-items: center; justify-content: center; color: white;'>";
                                echo "<i class='fas fa-map-marked-alt' style='font-size: 48px;'></i>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                        <div class="package-card-content">
                            <h3 class="package-card-title"><?php echo htmlspecialchars($relatedPackage['name']); ?></h3>
                            <div class="package-card-meta">
                                <div class="package-card-price">$<?php echo number_format($relatedPackage['price'], 2); ?></div>
                                <div class="package-card-duration"><?php echo htmlspecialchars($relatedPackage['duration']); ?></div>
                            </div>
                            <p style="color: #666; font-size: 14px; margin-bottom: 10px;"><?php echo htmlspecialchars(substr($relatedPackage['description'], 0, 100)); ?>...</p>
                            <a href="package_details.php?id=<?php echo $relatedPackage['_id']; ?>" class="view-details-btn">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
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

// Load recommendations when page loads
document.addEventListener('DOMContentLoaded', function() {
    const destination = "<?php echo htmlspecialchars($package['destination']); ?>";
    if (destination) {
        loadRecommendations(destination);
    }
});

async function loadRecommendations(destination) {
    try {
        showLoading('recommendedHotels', 'Loading recommended hotels...');
        showLoading('recommendedCars', 'Loading available cars...');
        
        const response = await fetch(`suggestions.php?area=${encodeURIComponent(destination)}`);
        const data = await response.json();
        
        if (data.success) {
            displayHotels(data.hotels);
            displayCars(data.cars);
        } else {
            throw new Error(data.error || 'Failed to load recommendations');
        }
    } catch (error) {
        console.error('Error loading recommendations:', error);
        showError('recommendedHotels', 'Failed to load hotels. Please try again later.');
        showError('recommendedCars', 'Failed to load cars. Please try again later.');
    }
}

function displayHotels(hotels) {
    const container = document.getElementById('recommendedHotels');
    
    if (!hotels || hotels.length === 0) {
        container.innerHTML = `
            <div class="no-recommendations">
                <i class="fas fa-bed"></i>
                <h3>No Hotels Found</h3>
                <p>We couldn't find any hotels in <?php echo htmlspecialchars($package['destination']); ?></p>
                <small>Please contact us for accommodation options in this area.</small>
            </div>
        `;
        return;
    }
    
    container.innerHTML = hotels.map(hotel => {
        // Clean the hotel name and create a proper image path
        const cleanHotelName = hotel.name.replace(/[^a-zA-Z0-9 ]/g, '').trim();
        const imagePath = hotel.image ? `images/${hotel.image}` : '';
        
        return `
        <div class="recommendation-card">
            <div class="recommendation-image-container">
                ${hotel.image && hotel.image !== '' ? 
                    `<img src="${imagePath}" alt="${cleanHotelName}" class="recommendation-image" 
                         onerror="this.style.display='none'; this.parentNode.innerHTML='<i class=\"fas fa-hotel recommendation-icon\"></i>';">` : 
                    `<i class="fas fa-hotel recommendation-icon"></i>`
                }
            </div>
            
            <div class="recommendation-name">${cleanHotelName}</div>
            <div class="recommendation-location">
                <i class="fas fa-map-marker-alt"></i> ${hotel.location || 'Unknown Location'}
            </div>
            <div class="recommendation-price">
                $${hotel.price || '0'}/night
            </div>
            
            ${hotel.rating ? `
            <div class="recommendation-features">
                <div class="recommendation-feature">
                    <i class="fas fa-star"></i>
                    Rating: ${hotel.rating}${hotel.rating > 1 ? ' stars' : ' star'}
                </div>
            </div>
            ` : ''}
            
            ${hotel.amenities && hotel.amenities.length > 0 ? `
            <div class="recommendation-tags">
                ${hotel.amenities.slice(0, 3).map(amenity => `
                    <span class="recommendation-tag">${amenity}</span>
                `).join('')}
                ${hotel.amenities.length > 3 ? `<span class="recommendation-tag">+${hotel.amenities.length - 3} more</span>` : ''}
            </div>
            ` : ''}
            
            <a href="hotel_details.php?id=${hotel._id}" class="view-btn">
                <i class="fas fa-eye"></i> View Hotel Details
            </a>
        </div>
        `;
    }).join('');
}

function displayCars(cars) {
    const container = document.getElementById('recommendedCars');
    
    if (!cars || cars.length === 0) {
        container.innerHTML = `
            <div class="no-recommendations">
                <i class="fas fa-car"></i>
                <h3>No Cars Available</h3>
                <p>We couldn't find any cars in <?php echo htmlspecialchars($package['destination']); ?></p>
                <small>Please contact us for transportation options in this area.</small>
            </div>
        `;
        return;
    }
    
    container.innerHTML = cars.map(car => {
        // Clean the car name and create a proper image path
        const cleanCarName = car.name.replace(/[^a-zA-Z0-9 ]/g, '').trim();
        const imagePath = car.image ? `images/${car.image}` : '';
        
        return `
        <div class="recommendation-card">
            <div class="recommendation-image-container">
                ${car.image && car.image !== '' ? 
                    `<img src="${imagePath}" alt="${cleanCarName}" class="recommendation-image" 
                         onerror="this.style.display='none'; this.parentNode.innerHTML='<i class=\"fas fa-car recommendation-icon\"></i>';">` : 
                    `<i class="fas fa-car recommendation-icon"></i>`
                }
            </div>
            
            <div class="recommendation-name">${cleanCarName}</div>
            <div class="recommendation-location">
                <i class="fas fa-car-side"></i> ${car.type || 'Vehicle'}
            </div>
            <div class="recommendation-price">
                $${car.price || '0'}/day
            </div>
            
            <div class="recommendation-features">
                <div class="recommendation-feature">
                    <i class="fas fa-users"></i>
                    Seats: ${car.seating_capacity || 'N/A'}
                </div>
                <div class="recommendation-feature">
                    <i class="fas fa-car"></i>
                    Model: ${car.model || 'N/A'}
                </div>
            </div>
            
            ${car.features && car.features.length > 0 ? `
            <div class="recommendation-tags">
                ${car.features.slice(0, 3).map(feature => `
                    <span class="recommendation-tag">${feature}</span>
                `).join('')}
                ${car.features.length > 3 ? `<span class="recommendation-tag">+${car.features.length - 3} more</span>` : ''}
            </div>
            ` : ''}
            
            <a href="car_details.php?id=${car._id}" class="view-btn">
                <i class="fas fa-eye"></i> View Car Details
            </a>
        </div>
        `;
    }).join('');
}

function showLoading(elementId, message) {
    const container = document.getElementById(elementId);
    container.innerHTML = `
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>${message}</p>
        </div>
    `;
}

function showError(elementId, message) {
    const container = document.getElementById(elementId);
    container.innerHTML = `
        <div class="no-recommendations">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Loading Error</h3>
            <p>${message}</p>
        </div>
    `;
    
    
}
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
    const destination = "<?php echo htmlspecialchars($package['destination']); ?>";
    if (destination) {
        loadRecommendations(destination);
    }
});
</script>
</body>
</html>
