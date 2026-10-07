<?php
require_once 'auth_check.php';
requireLogin();

// Page content for logged-in users only
$user = getUserInfo();

require_once 'vendor/autoload.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Explore Ethiopia</title>
    <link rel="icon" type="image/png" href="images/rrr.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
    
    <!--DARK MODE FIX -->
    <style>
    [data-theme="dark"] h1,
    [data-theme="dark"] h2,
    [data-theme="dark"] h3 {
        color: #ecf0f1 !important;
    }

    [data-theme="dark"] p {
        color: #bdc3c7 !important;
    }

    [data-theme="dark"] .content-container h1 {
        color: #ecf0f1 !important;
    }

    [data-theme="dark"] .about-section h2 {
        color: #ecf0f1 !important;
        border-left-color: #e74c3c !important;
    }

    [data-theme="dark"] .about-section p {
        color: #bdc3c7 !important;
    }

    [data-theme="dark"] .main-content {
        background: #1a1a1a !important;
    }

    [data-theme="dark"] .content-container h1::after {
        background: #e74c3c !important;
    }

    [data-theme="dark"] .about-section {
        background: #2d2d2d !important;
        border-color: #404040 !important;
    }
    </style>
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
                <a href="about.php" class="active">About Us</a>
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

    <main class="main-content">
        <div class="content-container">
            <h1>About Explore Ethiopia</h1>
            
            <div class="about-section">
                <h2>Our Mission</h2>
                <p>To revolutionize the Ethiopian tourism industry by providing a seamless, all-inclusive digital platform that connects travelers with the rich cultural heritage and breathtaking landscapes of our nation. We aim to replace outdated manual processes with a modern, efficient, and user-friendly online experience for tourists and operators alike.</p>
            </div>

            <div class="about-section">
                <h2>Our Story</h2>
                <p>Explore Ethiopia was born from a vision to solve a pressing problem. We saw that while Ethiopia's tourism potential is immense, the process for travelers to book tours was often cumbersome and inefficient, relying on face-to-face visits and phone calls. This project, conceived at St. Mary's University, was designed to bridge that gap. We built a centralized, web-based platform to bring convenience to tourists and operational efficiency to local tour operators, unlocking Ethiopia's treasures for a global audience.</p>
            </div>

            <div class="about-section">
                <h2>Meet the Team</h2>
                <p>This platform was developed by a dedicated team of Computer Science students from St. Mary's University:</p>
                <div class="team-grid">
                    <div class="name"><a href="https://t.me/RealAbeni" target="_blank"><i class="fab fa-telegram"></i> Abenezer Samuel</a></div>
                    <div class="name"><a href="https://t.me/Average_Fullporter" target="_blank"><i class="fab fa-telegram"></i> Bereket Abebe</a></div>
                    <div class="name"><a href="https://t.me/eyuealll" target="_blank"><i class="fab fa-telegram"></i> Eyuel Zerihun</a></div>
                    <div class="name"><a href="https://t.me/Kalab_28" target="_blank"><i class="fab fa-telegram"></i> Kalab Shmelis</a></div>
                    <div class="name"><a href="https://t.me/Kb_number1" target="_blank"><i class="fab fa-telegram"></i> Kalab Solomon</a></div>  
                </div>
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
        // Dark Mode Functionality
        function initializeDarkMode() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const currentTheme = localStorage.getItem('theme') || 'light';
            
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
        }

        function updateDarkModeButton(theme) {
            const darkModeToggle = document.getElementById('darkModeToggle');
            if (theme === 'dark') {
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
            } else {
                darkModeToggle.innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
            }
        }

        
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

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeDarkMode();
        });
    </script>
</body>
</html>