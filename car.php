<?php
require_once 'auth_check.php';
requireLogin();

// Page content for logged-in users only
$user = getUserInfo();

require_once 'vendor/autoload.php';

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $carsCollection = $database->selectCollection('car');
    
    // Get search and filter parameters
    $search = $_GET['search'] ?? '';
    $destination = $_GET['destination'] ?? '';
    $minPrice = $_GET['min_price'] ?? '';
    $maxPrice = $_GET['max_price'] ?? '';
    $duration = $_GET['duration'] ?? '';
    
    // Build query
    $query = [];
    
    if (!empty($search)) {
        $query['$or'] = [
            ['name' => ['$regex' => $search, '$options' => 'i']],
            ['description' => ['$regex' => $search, '$options' => 'i']],
            ['destination' => ['$regex' => $search, '$options' => 'i']]
        ];
    }
    
    if (!empty($destination)) {
        $query['destination'] = $destination;
    }
    
    // Price range filter
    $priceFilter = [];
    if (!empty($minPrice)) {
        $priceFilter['$gte'] = floatval($minPrice);
    }
    if (!empty($maxPrice)) {
        $priceFilter['$lte'] = floatval($maxPrice);
    }
    if (!empty($priceFilter)) {
        $query['price'] = $priceFilter;
    }
    
    // Duration filter
    if (!empty($duration)) {
        if ($duration === 'short') {
            $query['$or'] = [
                ['duration' => ['$regex' => '1 day']],
                ['duration' => ['$regex' => '2 days']],
                ['duration' => ['$regex' => '3 days']]
            ];
        } elseif ($duration === 'medium') {
            $query['$or'] = [
                ['duration' => ['$regex' => '4 days']],
                ['duration' => ['$regex' => '5 days']],
                ['duration' => ['$regex' => '6 days']]
            ];
        } elseif ($duration === 'long') {
            $query['$or'] = [
                ['duration' => ['$regex' => '7 days']],
                ['duration' => ['$regex' => '8 days']],
                ['duration' => ['$regex' => '9 days']],
                ['duration' => ['$regex' => '10 days']]
            ];
        }
    }
    
    // Get all packages with filters
    $cars = $carsCollection->find($query, ['sort' => ['price' => 1]]);
    $totalcars = $carsCollection->countDocuments($query);
    
    // Get unique destinations for filter dropdown
    $destinations = $carsCollection->distinct('destination');
    
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Packages - Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
       <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
</head>
</head>
<body>
   <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="logo">Tour <span>Travel</div>
            <nav class="nav">
                <a href="index.php">Home</a>
                <a href="packages_mongo.php">Tour Packages</a>
                <a href="car.php" class="active">Car</a>
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
        <!-- Search and Filter Section -->
        <div class="search-filter-section">
            <form method="GET" id="searchForm">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search cars, destinations, descriptions..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Destination</label>
                        <select name="destination">
                            <option value="">All Destinations</option>
                            <?php foreach ($destinations as $dest): ?>
                                <option value="<?php echo htmlspecialchars($dest); ?>" 
                                    <?php echo $destination === $dest ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dest); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Min Price ($)</label>
                        <input type="number" name="min_price" placeholder="Min" 
                               value="<?php echo htmlspecialchars($minPrice); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Max Price ($)</label>
                        <input type="number" name="max_price" placeholder="Max" 
                               value="<?php echo htmlspecialchars($maxPrice); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Duration</label>
                        <select name="duration">
                            <option value="">Any Duration</option>
                            <option value="short" <?php echo $duration === 'short' ? 'selected' : ''; ?>>Short (1-3 days)</option>
                            <option value="medium" <?php echo $duration === 'medium' ? 'selected' : ''; ?>>Medium (4-6 days)</option>
                            <option value="long" <?php echo $duration === 'long' ? 'selected' : ''; ?>>Long (7+ days)</option>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="search-btn">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="car.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
            
            <!-- Active Filters -->
            <?php if ($search || $destination || $minPrice || $maxPrice || $duration): ?>
            <div class="search-results-info">
                <div>
                    <strong><?php echo $totalcars; ?> cars found</strong>
                    <div class="active-filters">
                        <?php if ($search): ?>
                            <span class="filter-tag">
                                Search: "<?php echo htmlspecialchars($search); ?>"
                                <span class="remove" onclick="removeFilter('search')">×</span>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($destination): ?>
                            <span class="filter-tag">
                                Destination: <?php echo htmlspecialchars($destination); ?>
                                <span class="remove" onclick="removeFilter('destination')">×</span>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($minPrice): ?>
                            <span class="filter-tag">
                                Min: $<?php echo htmlspecialchars($minPrice); ?>
                                <span class="remove" onclick="removeFilter('min_price')">×</span>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($maxPrice): ?>
                            <span class="filter-tag">
                                Max: $<?php echo htmlspecialchars($maxPrice); ?>
                                <span class="remove" onclick="removeFilter('max_price')">×</span>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($duration): ?>
                            <span class="filter-tag">
                                Duration: <?php echo ucfirst($duration); ?>
                                <span class="remove" onclick="removeFilter('duration')">×</span>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="car.php" class="btn btn-secondary btn-sm">
                    Clear All Filters
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Packages Grid -->
        <div class="cars-grid">
            <?php foreach ($cars as $car): ?>
                <div class="car-card">
                    <div class="car-image">
                        <?php
                        $imagePath = 'images/' . $car['image'];
                        $imageAlt = $car['image_alt'] ?? $car['name'];
                        
                        if (file_exists($imagePath)) {
                            echo "<img src='$imagePath' alt='$imageAlt' title='$imageAlt'>";
                        } else {
                            echo "<div class='image-placeholder'>";
                            echo "<div>";
                            echo "<i class='fas fa-map-marked-alt' style='font-size: 48px; margin-bottom: 10px;'></i>";
                            echo "<br>";
                            echo htmlspecialchars($car['destination']);
                            echo "</div>";
                            echo "</div>";
                        }
                        ?>
                    </div>
                    
                    <div class="car-content">
                        <h3 class="car-title"><?php echo htmlspecialchars($car['name']); ?></h3>
                        
                        <div class="car-meta">
                            <div class="car-price">$<?php echo number_format($car['price'], 2); ?></div>
                            <div class="car-duration"><?php echo htmlspecialchars($car['duration']); ?></div>
                        </div>
                        
                        <p class="car-description"><?php echo htmlspecialchars($car['description']); ?></p>
                        
                        <div class="car-destination">
                            <i class="fas fa-location-arrow"></i>
                            Destination: <?php echo htmlspecialchars($car['destination']); ?>
                        </div>
                        
                        <?php if (isset($car['highlights'])): ?>
                            <div class="car-highlights">
                                <?php foreach ($car['highlights'] as $highlight): ?>
                                    <span class="highlight-tag"><?php echo htmlspecialchars($highlight); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <a href="book_car.php?car=<?php echo urlencode($car['name']); ?>&price=<?php echo $car['price']; ?>" 
                           class="book-btn">
                            <i class="fas fa-calendar-check"></i> Book This car Now
                        </a>
                        <a href="car_details.php?id=<?php echo $car['_id'] ?? ''; ?>" 
                                       class="btn-outline">
                                        Details
                                    </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($totalcars === 0): ?>
            <div class="empty-state">
                <i class="fas fa-search" style="font-size: 64px; color: #6c757d; margin-bottom: 20px;"></i>
                <h3>No cars Found</h3>
                <p>Try adjusting your search criteria or filters to find more results.</p>
                <a href="car.php" class="btn-primary">
                    <i class="fas fa-times"></i> Clear All Filters
                </a>
            </div>
        <?php endif; ?>
        
        <!-- MongoDB Status -->
        <div class="mongo-status">
            <h3>📊 MongoDB Data Status</h3>
            <p>Total cars: <strong><?php echo $carsCollection->countDocuments(); ?></strong></p>
            <p>Showing: <strong><?php echo $totalcars; ?> cars</strong></p>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h3>Ghion Travel</h3>
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
                        <li><a href="car.php">car</a></li>
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
        function removeFilter(filterName) {
            const url = new URL(window.location.href);
            url.searchParams.delete(filterName);
            window.location.href = url.toString();
        }
        
        // Auto-submit form when certain filters change
        document.querySelector('select[name="destination"]').addEventListener('change', function() {
            document.getElementById('searchForm').submit();
        });
        
        document.querySelector('select[name="duration"]').addEventListener('change', function() {
            document.getElementById('searchForm').submit();
        });
        
        // Price range validation
        document.querySelector('input[name="min_price"]').addEventListener('change', function() {
            const maxPrice = document.querySelector('input[name="max_price"]').value;
            if (maxPrice && parseFloat(this.value) > parseFloat(maxPrice)) {
                alert('Minimum price cannot be greater than maximum price');
                this.value = '';
            }
        });
        
        document.querySelector('input[name="max_price"]').addEventListener('change', function() {
            const minPrice = document.querySelector('input[name="min_price"]').value;
            if (minPrice && parseFloat(this.value) < parseFloat(minPrice)) {
                alert('Maximum price cannot be less than minimum price');
                this.value = '';
            }
        });

        // Dark Mode Functionality
function initializeDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const currentTheme = localStorage.getItem('theme') || 'light';
    
    // Apply saved theme
    document.documentElement.setAttribute('data-theme', currentTheme);
    updateDarkModeButton(currentTheme);
    
    // Toggle dark mode
    darkModeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateDarkModeButton(newTheme);
    });
}

function updateDarkModeButton(theme) {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const icon = darkModeToggle.querySelector('i');
    const text = darkModeToggle.querySelector('span') || document.createElement('span');
    
    if (theme === 'dark') {
        icon.className = 'fas fa-sun';
        text.textContent = ' Light Mode';
        darkModeToggle.innerHTML = '';
        darkModeToggle.appendChild(icon);
        darkModeToggle.appendChild(text);
    } else {
        icon.className = 'fas fa-moon';
        text.textContent = ' Dark Mode';
        darkModeToggle.innerHTML = '';
        darkModeToggle.appendChild(icon);
        darkModeToggle.appendChild(text);
    }
}

// Initialize dark mode when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeDarkMode();
    
    // Your existing JavaScript code...
    // Smooth scrolling for anchor links
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

    // Header background on scroll
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

    // Simple animation for stats counter
    function animateCounter(element, target, duration) {
        let start = 0;
        const increment = target / (duration / 16);
        const timer = setInterval(() => {
            start += increment;
            if (start >= target) {
                element.textContent = target + '+';
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(start) + '+';
            }
        }, 16);
    }

    // Initialize counters when they come into view
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const statNumber = entry.target.querySelector('.stat-number');
                const target = parseInt(statNumber.textContent);
                animateCounter(statNumber, target, 2000);
                observer.unobserve(entry.target);
            }
        });
    });

    document.querySelectorAll('.stat-item').forEach(item => {
        observer.observe(item);
    });
});
    </script>
</body>
</html>