<?php
session_start();
require_once 'vendor/autoload.php';

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $contactsCollection = $database->selectCollection('contacts');
} catch (Exception $e) {
    $mongoClient = null;
    $contactsCollection = null;
}

$message = '';
$message_type = '';
$name = $email = $subject = $message_text = '';
$rating = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message_text = trim($_POST['message']);
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    
    // Validation
    if (empty($name) || empty($email) || empty($subject) || empty($message_text)) {
        $message = 'Please fill in all fields.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        try {
            $contactData = [
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message_text,
                'rating' => $rating,
                'status' => 'new',
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            ];
            
            if (isset($_SESSION['user_id'])) {
                $contactData['user_id'] = $_SESSION['user_id'];
            }
            
            $result = $contactsCollection->insertOne($contactData);
            
            if ($result->getInsertedCount() > 0) {
                $message = 'Thank you for your message! We will get back to you soon.';
                $message_type = 'success';
                
                // Clear form
                $name = $email = $subject = $message_text = '';
                $rating = 0;
            } else {
                $message = 'Sorry, there was an error sending your message. Please try again.';
                $message_type = 'error';
            }
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Explore Ethiopia</title>
    <link rel="icon" type="image/png" href="images/rrr.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
    
    <!-- EMERGENCY DARK MODE FIX -->
    <style>
    /* Force dark mode text colors */
    [data-theme="dark"] h1,
    [data-theme="dark"] h2,
    [data-theme="dark"] h3 {
        color: #ecf0f1 !important;
    }

    [data-theme="dark"] p {
        color: #bdc3c7 !important;
    }

    [data-theme="dark"] .contact-container h1 {
        color: #ecf0f1 !important;
    }

    [data-theme="dark"] .contact-info h2 {
        color: #e74c3c !important;
    }

    [data-theme="dark"] .contact-form h2 {
        color: #ecf0f1 !important;
    }

    [data-theme="dark"] .contact-container {
        background: #1a1a1a !important;
    }

    [data-theme="dark"] .contact-info {
        background: linear-gradient(135deg, #2c3e50, #34495e) !important;
    }

    [data-theme="dark"] .contact-form {
        background: #2d2d2d !important;
        border: 1px solid #404040 !important;
    }

    [data-theme="dark"] .form-control {
        background: #1a1a1a !important;
        color: #ecf0f1 !important;
        border-color: #404040 !important;
    }

    [data-theme="dark"] .form-control::placeholder {
        color: #bdc3c7 !important;
    }

    [data-theme="dark"] .form-group label {
        color: #ecf0f1 !important;
    }

    [data-theme="dark"] .rating-label {
        color: #ecf0f1 !important;
    }

    [data-theme="dark"] .rating-text {
        color: #bdc3c7 !important;
    }

    [data-theme="dark"] .selected-rating {
        color: #ffc107 !important;
    }

    [data-theme="dark"] .info-content h3 {
        color: #e74c3c !important;
    }

    [data-theme="dark"] .info-content p {
        color: #bdc3c7 !important;
    }

    [data-theme="dark"] .alert {
        background: #2d2d2d !important;
        border: 1px solid #404040 !important;
        color: #ecf0f1 !important;
    }

    [data-theme="dark"] .alert-success {
        border-left: 4px solid #27ae60 !important;
    }

    [data-theme="dark"] .alert-error {
        border-left: 4px solid #e74c3c !important;
    }

    /* Star rating styles */
    .rating-container {
        margin-bottom: 20px;
    }
    
    .rating-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--text);
    }
    
    .star-rating {
        display: flex;
        gap: 5px;
        margin-bottom: 5px;
    }
    
    .star-rating input {
        display: none;
    }
    
    .star-rating label {
        font-size: 24px;
        color: #ddd;
        cursor: pointer;
        transition: color 0.2s;
    }
    
    .star-rating label:hover,
    .star-rating input:checked ~ label,
    .star-rating label.active {
        color: #ffc107;
    }
    
    .rating-text {
        font-size: 14px;
        color: var(--text-light);
        margin-top: 5px;
    }
    
    .required {
        color: #e74c3c;
    }
    
    .selected-rating {
        margin-top: 5px;
        font-weight: bold;
        color: #ffc107;
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
                <a href="about.php" >About Us</a>
                <a href="contact.php"class="active">Contact & feedback</a>
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
    <main class="contact-container">
        <h1 style="text-align: center; margin-bottom: 10px;">Contact Us and Give Feedback</h1>
        <p style="text-align: center; margin-bottom: 40px;">
            Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.
        </p>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="contact-grid">
            <!-- Contact Information -->
            <div class="contact-info">
                <h2 style="margin-bottom: 30px;">Get In Touch</h2>
                
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div class="info-content">
                        <h3>Our Office</h3>
                        <p>Addis Ababa, Ethiopia<br>Bole Road, Next to Friendship Center</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <div class="info-content">
                        <h3>Phone Number</h3>
                        <p>+251 11 123 4567<br>+251 91 234 5678</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <div class="info-content">
                        <h3>Email Address</h3>
                        <p>info@exploreethiopia.com<br>support@exploreethiopia.com</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <div class="info-content">
                        <h3>Working Hours</h3>
                        <p>Monday - Friday: 8:00 AM - 6:00 PM<br>Saturday: 9:00 AM - 4:00 PM</p>
                    </div>
                </div>
                
                <div class="social-links-contact">
                    <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
                    <a href="https://twitter.com" target="_blank"><i class="fab fa-twitter"></i></a>
                    <a href="https://t.me" target="_blank"><i class="fab fa-telegram"></i></a>
                    <a href="https://linkedin.com" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            
            <!-- Contact Form -->
            <div class="contact-form">
                <h2 style="margin-bottom: 30px;">Send Us a Message</h2>
                
                <form method="POST" action="" id="contactForm">
                    <div class="form-group">
                        <label for="name">Full Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($name); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($email); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject <span class="required">*</span></label>
                        <input type="text" id="subject" name="subject" class="form-control" 
                               value="<?php echo htmlspecialchars($subject); ?>" 
                               required>
                    </div>
                    
                    <!-- Star Rating System -->
                    <div class="rating-container">
                        <label class="rating-label">Your Rating (Optional)</label>
                        <div class="star-rating" id="starRating">
                            <input type="radio" id="star5" name="rating" value="5" <?php echo ($rating == 5) ? 'checked' : ''; ?>>
                            <label for="star5" title="5 stars - Excellent">★</label>
                            
                            <input type="radio" id="star4" name="rating" value="4" <?php echo ($rating == 4) ? 'checked' : ''; ?>>
                            <label for="star4" title="4 stars - Very Good">★</label>
                            
                            <input type="radio" id="star3" name="rating" value="3" <?php echo ($rating == 3) ? 'checked' : ''; ?>>
                            <label for="star3" title="3 stars - Good">★</label>
                            
                            <input type="radio" id="star2" name="rating" value="2" <?php echo ($rating == 2) ? 'checked' : ''; ?>>
                            <label for="star2" title="2 stars - Fair">★</label>
                            
                            <input type="radio" id="star1" name="rating" value="1" <?php echo ($rating == 1) ? 'checked' : ''; ?>>
                            <label for="star1" title="1 star - Poor">★</label>
                        </div>
                        <div class="rating-text">
                            Select a rating to help us improve our services (1 = Poor, 5 = Excellent)
                        </div>
                        <div class="selected-rating" id="selectedRating">
                            <?php if ($rating > 0): ?>
                                <?php
                                $ratingTexts = [
                                    1 => 'Poor',
                                    2 => 'Fair',
                                    3 => 'Good', 
                                    4 => 'Very Good',
                                    5 => 'Excellent'
                                ];
                                ?>
                                You selected: <?php echo $rating; ?> star<?php echo $rating > 1 ? 's' : ''; ?> (<?php echo $ratingTexts[$rating]; ?>)
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Your Message <span class="required">*</span></label>
                        <textarea id="message" name="message" class="form-control" rows="5" required><?php echo htmlspecialchars($message_text); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
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
                        <li><i class="fas fa-envelope"></i> info@exploreethiopia.com</li>
                        <li><i class="fas fa-clock"></i> Mon - Sun: 24/7</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Explore Ethiopia. All rights reserved. | 
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
        }

        function updateDarkModeButton(theme) {
            const darkModeToggle = document.getElementById('darkModeToggle');
            if (theme === 'dark') {
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
            } else {
                darkModeToggle.innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
            }
        }

        // Star Rating System
        document.addEventListener('DOMContentLoaded', function() {
            const starRating = document.getElementById('starRating');
            const selectedRating = document.getElementById('selectedRating');
            const stars = starRating.querySelectorAll('input[type="radio"]');
            const starLabels = starRating.querySelectorAll('label');
            
            const ratingTexts = {
                1: 'Poor',
                2: 'Fair', 
                3: 'Good',
                4: 'Very Good',
                5: 'Excellent'
            };

            // Update star display based on checked radio
            function updateStarDisplay() {
                let checkedValue = 0;
                
                stars.forEach((star, index) => {
                    if (star.checked) {
                        checkedValue = parseInt(star.value);
                    }
                });

                // Update star colors
                starLabels.forEach((label, index) => {
                    if (index < checkedValue) {
                        label.classList.add('active');
                    } else {
                        label.classList.remove('active');
                    }
                });

                // Update selected rating text
                if (checkedValue > 0) {
                    selectedRating.innerHTML = `You selected: ${checkedValue} star${checkedValue > 1 ? 's' : ''} (${ratingTexts[checkedValue]})`;
                } else {
                    selectedRating.innerHTML = '';
                }
            }

            // Initialize star display
            updateStarDisplay();

            // Add click event listeners to stars
            stars.forEach((star, index) => {
                star.addEventListener('change', updateStarDisplay);
            });

            // Add hover effects
            starLabels.forEach((label, index) => {
                label.addEventListener('mouseenter', function() {
                    const hoverValue = index + 1;
                    starLabels.forEach((l, i) => {
                        if (i < hoverValue) {
                            l.style.color = '#ffc107ff';
                        } else {
                            l.style.color = '#ddd';
                        }
                    });
                });

                label.addEventListener('mouseleave', updateStarDisplay);
            });

            // Initialize dark mode
            initializeDarkMode();
        });

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
    </script>
</body>
</html>