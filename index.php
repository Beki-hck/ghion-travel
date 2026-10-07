<?php
session_start();
require_once 'vendor/autoload.php';

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $packagesCollection = $database->selectCollection('packages');
    $bookingsCollection = $database->selectCollection('bookings');
    
    // Get featured packages (limit to 6) - Convert cursor to array
    $featuredPackagesCursor = $packagesCollection->find([], [
        'sort' => ['created_at' => -1],
        'limit' => 6
    ]);
    $featuredPackages = $featuredPackagesCursor->toArray();
    
    // Get statistics for the homepage
    $totalPackages = $packagesCollection->countDocuments();
    $totalBookings = $bookingsCollection->countDocuments();
    
    // Get popular packages (most booked)
    $popularPackagesCursor = $bookingsCollection->aggregate([
        ['$group' => [
            '_id' => '$package',
            'bookingCount' => ['$sum' => 1]
        ]],
        ['$sort' => ['bookingCount' => -1]],
        ['$limit' => 3]
    ]);
    $popularPackages = $popularPackagesCursor->toArray();
    
} catch (Exception $e) {
    // Continue without database connection for demo
    $featuredPackages = [];
    $totalPackages = 0;
    $totalBookings = 0;
    $popularPackages = [];
    error_log("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour and Travel - Discover Ethiopia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
</head>
<body>
  <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="logo">Tour <span>Travel</div>
            <nav class="nav">
                <a href="index.php" class="active">Home</a>
                <a href="packages_mongo.php">Tour Packages</a>
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
            
                    <a href="login.php" class="btn-outline">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
<style>
    
</style>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-title">
                <h2>Why Choose Tour Travel?</h2>
                <p>We provide exceptional travel experiences with our dedicated services</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Safe & Secure</h3>
                    <p>Your safety is our priority. All our tours are carefully planned and executed with the highest safety standards.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tag"></i>
                    </div>
                    <h3>Best Prices</h3>
                    <p>Get the most value for your money with our competitive pricing and exclusive deals.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>24/7 Support</h3>
                    <p>Our dedicated support team is available around the clock to assist you with any queries.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <h3>Expert Guides</h3>
                    <p>Explore with our experienced local guides who know the hidden gems and rich history.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Packages -->
    <section class="packages" id="featured-packages">
        <div class="container">
            <div class="section-title">
                <h2>Featured Tour Packages <span class="mongo-badge">Live from MongoDB</span></h2>
                <p>Discover our most popular tours handpicked for unforgettable experiences</p>
            </div>
            
            <div class="packages-grid">
                <?php if (is_array($featuredPackages) && count($featuredPackages) > 0): ?>
                    <?php foreach ($featuredPackages as $package): ?>
                        <div class="package-card">
                            <div class="package-image">
                                <?php
                                $imagePath = 'images/' . ($package['image'] ?? 'default.jpg');
                                $imageAlt = $package['image_alt'] ?? ($package['name'] ?? 'Tour Package');
                                ?>
                                <?php if (file_exists($imagePath) && !empty($package['image'])): ?>
                                    <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($imageAlt); ?>">
                                <?php else: ?>
                                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: white; text-align: center; padding: 20px;">
                                        <div>
                                            <i class="fas fa-map-marked-alt" style="font-size: 48px; margin-bottom: 10px;"></i>
                                            <br>
                                            <?php echo htmlspecialchars($package['destination'] ?? 'Ethiopia'); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="package-content">
                                <h3 class="package-title"><?php echo htmlspecialchars($package['name'] ?? 'Tour Package'); ?></h3>
                                <div class="package-meta">
                                    <div class="package-price">$<?php echo number_format($package['price'] ?? 0, 2); ?></div>
                                    <div class="package-duration"><?php echo htmlspecialchars($package['duration'] ?? 'N/A'); ?></div>
                                </div>
                                <p class="package-description">
                                    <?php 
                                    $description = $package['description'] ?? 'No description available.';
                                    echo strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description;
                                    ?>
                                </p>
                                <div style="display: flex; gap: 10px;">
                                    <a href="book_package.php?package=<?php echo urlencode($package['name'] ?? ''); ?>&price=<?php echo $package['price'] ?? 0; ?>" 
                                       class="btn" style="flex: 1; text-align: center;">
                                        Book Now
                                    </a>
                                    <a href="package_details.php?id=<?php echo $package['_id'] ?? ''; ?>" 
                                       class="btn-outline" style="flex: 1; text-align: center; background: transparent; color: var(--primary); border-color: var(--primary);">
                                        Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                        <i class="fas fa-suitcase" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i>
                        <h3 style="color: #666; margin-bottom: 15px;">No Packages Available</h3>
                        <p style="color: #888;">Tour packages will be available soon. Please check back later.</p>
                        <?php if (isset($_SESSION['admin_logged_in'])): ?>
                            <a href="admin_add_package.php" class="btn" style="margin-top: 20px;">
                                <i class="fas fa-plus"></i> Add First Package
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="view-all">
                <a href="packages_mongo.php" class="btn" style="padding: 12px 40px;">
                    <i class="fas fa-eye"></i> View All Packages
                </a>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $totalPackages; ?>+</div>
                    <div class="stat-label">Tour Packages</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $totalBookings; ?>+</div>
                    <div class="stat-label">Happy Travelers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">15+</div>
                    <div class="stat-label">Destinations</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Customer Support</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials">
        <div class="container">
            <div class="section-title">
                <h2>What Our Travelers Say</h2>
                <p>Read authentic experiences from our satisfied customers</p>
            </div>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "The Northern Ethiopia tour was absolutely breathtaking! The guides were knowledgeable and the accommodations were excellent. Can't wait to book my next adventure with Tour Travel!"
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">SJ</div>
                        <div class="author-info">
                            <h4>Eyuel zarihun</h4>
                            <p>Northern Ethiopia Tour</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "Amazing experience in the Omo Valley! The cultural immersion was incredible. Tour Travel made everything so easy and comfortable. Highly recommended!"
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">MD</div>
                        <div class="author-info">
                            <h4>Abenzer Samuel</h4>
                            <p>Southern Cultural Tour</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "The Danakil Depression adventure was unlike anything I've ever experienced. Professional guides, safe transportation, and unforgettable memories. Thank you Tour Travel!"
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">ET</div>
                        <div class="author-info">
                            <h4>Elena Torres</h4>
                            <p>Danakil Adventure</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta">
        <div class="container">
            <h2>Ready for Your Ethiopian Adventure?</h2>
            <p>Join thousands of satisfied travelers who have explored Ethiopia with us. Book your dream tour today!</p>
            <div class="hero-buttons">
                <a href="packages_mongo.php" class="btn" style="padding: 15px 40px; font-size: 18px;">
                    <i class="fas fa-rocket"></i> Start Exploring
                </a>
                <a href="contact.php" class="btn-outline" style="padding: 15px 40px; font-size: 18px;">
                    <i class="fas fa-question-circle"></i> Get Help
                </a>
            </div>
        </div>
    </section>

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
                header.style.background = 'linear-gradient(135deg, var(--primary) 0%, #4b86b4 100%)';
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

    const menuToggle = document.querySelector('.menu-toggle');
    const nav = document.querySelector('.nav');

    if(menuToggle) {
        menuToggle.addEventListener('click', () => {
            nav.classList.toggle('active');
        });
    }

    </script>
</body>
</html>