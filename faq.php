<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Ghion Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
       <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
   <style>
       
    
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #ffd700;
        }
        
        .btn {
            background:yellowgreen;
            color: #667eea;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .faq-hero {
            background: linear-gradient(rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9)), url('https://images.unsplash.com/photo-1488646953014-85cb44e25828?ixlib=rb-1.2.1&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            padding: 100px 0;
            text-align: center;
            color: white;
        }
    
        
        .faq-hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .faq-hero p {
            font-size: 20px;
            max-width: 700px;
            margin: 0 auto;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        .search-faq {
            max-width: 600px;
            margin: 40px auto;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 15px 20px;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .search-box button {
            position: absolute;
            right: 5px;
            top: 5px;
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .faq-container {
            padding: 80px 0;
        }
        
        .faq-categories {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 50px;
            justify-content: center;
        }
        
        .category-btn {
            background: white;
            padding: 15px 30px;
            border-radius: 10px;
            border: 2px solid #667eea;
            color: #667eea;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .category-btn:hover,
        .category-btn.active {
            background: #667eea;
            color: white;
        }
        
        .faq-section {
            margin-bottom: 60px;
        }
        
        .faq-section h2 {
            color: #333;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
            display: inline-block;
        }
        
        .faq-item {
            background: white;
            margin-bottom: 15px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            transition: box-shadow 0.3s;
        }
        
        .faq-item:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .faq-question {
            padding: 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            font-size: 18px;
            color: #333;
        }
        
        .faq-question i {
            transition: transform 0.3s;
        }
        
        .faq-answer {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out, padding 0.3s ease-out;
        }
        
        .faq-item.active .faq-answer {
            padding: 20px;
            max-height: 500px;
        }
        
        .faq-item.active .faq-question i {
            transform: rotate(180deg);
        }
        
        .contact-support {
            background: #667eea;
            color: white;
            padding: 60px 0;
            text-align: center;
            border-radius: 15px;
            margin-top: 50px;
        }
        
        .contact-support h2 {
            margin-bottom: 20px;
        }
        
        .contact-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .contact-btn {
            background: white;
            color: #667eea;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s;
        }
        
        .contact-btn:hover {
            transform: translateY(-3px);
        }
        
        .footer {
            background: #333;
            color: white;
            padding: 50px 0;
            text-align: center;
            margin-top: 80px;
        }
        
        @media (max-width: 768px) {
            .faq-hero h1 {
                font-size: 36px;
            }
            
            .nav-links {
                gap: 15px;
            }
            
            .contact-buttons {
                flex-direction: column;
                align-items: center;
            }
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
                <a href="about.php">About Us</a>
                <a href="contact.php">Contact & feedback</a>
                <a href="my_bookings_mongo.php">My Bookings</a>
             <a href="faq.php"class="active">FAQ</a>
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                        <span style="color: #ffd700;">Hi, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                        <a href="logout.php" class="btn">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn">Login</a>
                    <?php endif; ?>
                </div>
                    <button id="darkModeToggle" class="dark-toggle">
                        <i class="fas fa-moon"></i> Dark Mode
                    </button>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="faq-hero">
        <div class="container">
            <h1>Frequently Asked Questions</h1>
            <p>Find quick answers to common questions about booking, travel, accommodations, and more.</p>
            
            <div class="search-faq">
                <div class="search-box">
                    <input type="text" placeholder="Search for questions...">
                    <button><i class="fas fa-search"></i> Search</button>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Categories -->
    <div class="container">
        <div class="faq-categories">
            <button class="category-btn active" data-category="all">All Questions</button>
            <button class="category-btn" data-category="booking">Booking & Payment</button>
            <button class="category-btn" data-category="travel">Travel & Logistics</button>
            <button class="category-btn" data-category="accommodation">Accommodation</button>
            <button class="category-btn" data-category="cancellation">Cancellation & Refunds</button>
        </div>
    </div>

    <!-- FAQ Content -->
    <div class="container faq-container">
        <!-- Booking & Payment Section -->
        <div class="faq-section" id="booking">
            <h2><i class="fas fa-calendar-check"></i> Booking & Payment</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    How do I book a tour package?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Booking is easy! Simply browse our tour packages, select your preferred package, choose your dates, and proceed to checkout. You can book online 24/7 or contact our customer service during business hours.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    What payment methods do you accept?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>We accept various payment methods including:</p>
                    <ul>
                        <li>Credit/Debit Cards (Visa, MasterCard, American Express)</li>
                        <li>Bank Transfers</li>
                        <li>Mobile Payments (M-Pesa, Telebirr)</li>
                        <li>PayPal</li>
                    </ul>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    Is my payment information secure?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Yes! We use SSL encryption and comply with PCI DSS standards to ensure your payment information is completely secure. We never store your full credit card details on our servers.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    Do I need to pay a deposit?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Most packages require a 30% deposit to confirm your booking. The remaining balance is typically due 30 days before your travel date. Some last-minute bookings may require full payment.</p>
                </div>
            </div>
        </div>

        <!-- Travel & Logistics Section -->
        <div class="faq-section" id="travel">
            <h2><i class="fas fa-plane"></i> Travel & Logistics</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    What documents do I need for travel?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Required documents vary by destination, but typically include:</p>
                    <ul>
                        <li>Valid passport (6+ months validity)</li>
                        <li>Visa (if required)</li>
                        <li>Travel insurance documents</li>
                        <li>Vaccination certificates (if required)</li>
                        <li>Booking confirmation from Ghion Travel</li>
                    </ul>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    Are flights included in tour packages?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Most of our packages are land-only. However, we can arrange flights for you upon request. When flights are included, it will be clearly mentioned in the package details.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    What is included in the tour price?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Typically included:</p>
                    <ul>
                        <li>Accommodation</li>
                        <li>Transportation during the tour</li>
                        <li>Guided tours and activities</li>
                        <li>Some meals (as specified)</li>
                        <li>Tour guide services</li>
                    </ul>
                    <p>Not included: Flights, travel insurance, personal expenses, optional activities.</p>
                </div>
            </div>
        </div>

        <!-- Accommodation Section -->
        <div class="faq-section" id="accommodation">
            <h2><i class="fas fa-hotel"></i> Accommodation</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    What type of hotels do you use?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>We partner with a range of accommodations from 3-star to 5-star hotels, depending on the package. All our partner hotels are carefully selected for quality, location, and service standards.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    Can I request specific room types?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Yes! You can request specific room types (single, double, twin, suite) during booking. We'll do our best to accommodate your preferences, subject to availability.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    Are meals included?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Meal inclusions vary by package. Most packages include breakfast, and some include additional meals. Check the "Inclusions" section of your specific package for details.</p>
                </div>
            </div>
        </div>

        <!-- Cancellation & Refunds Section -->
        <div class="faq-section" id="cancellation">
            <h2><i class="fas fa-times-circle"></i> Cancellation & Refunds</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    What is your cancellation policy?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Our cancellation policy is as follows:</p>
                    <ul>
                        <li>60+ days before departure: Full refund (minus processing fee)</li>
                        <li>30-59 days before departure: 50% refund</li>
                        <li>15-29 days before departure: 25% refund</li>
                        <li>Less than 15 days: No refund</li>
                    </ul>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    How long does it take to process refunds?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Refunds are typically processed within 7-14 business days. The time it takes for the refund to appear in your account depends on your bank or payment provider.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    What happens if the tour is cancelled by Ghion Travel?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>If we cancel a tour for any reason, you will receive a full refund or the option to reschedule for another date. We will notify you immediately if any changes occur.</p>
                </div>
            </div>
        </div>

        <!-- Contact Support Section -->
        <div class="contact-support">
            <h2>Still Have Questions?</h2>
            <p>Can't find what you're looking for? Our support team is here to help!</p>
            
            <div class="contact-buttons">
                <a href="contact.php" class="contact-btn">
                    <i class="fas fa-envelope"></i> Contact Us
                </a>
                <a href="tel:+251111234567" class="contact-btn">
                    <i class="fas fa-phone"></i> Call Us: +251 11 123 4567
                </a>
                <a href="https://wa.me/251911234567" class="contact-btn" target="_blank">
                    <i class="fab fa-whatsapp"></i> WhatsApp Chat
                </a>
            </div>
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
        // FAQ Accordion Functionality
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const item = question.parentElement;
                item.classList.toggle('active');
            });
        });

        // Category Filtering
        document.querySelectorAll('.category-btn').forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons
                document.querySelectorAll('.category-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Add active class to clicked button
                button.classList.add('active');
                
                const category = button.dataset.category;
                
                // Show/hide FAQ sections
                if (category === 'all') {
                    document.querySelectorAll('.faq-section').forEach(section => {
                        section.style.display = 'block';
                    });
                } else {
                    document.querySelectorAll('.faq-section').forEach(section => {
                        if (section.id === category) {
                            section.style.display = 'block';
                        } else {
                            section.style.display = 'none';
                        }
                    });
                }
                
                // Scroll to first FAQ of selected category
                if (category !== 'all') {
                    document.getElementById(category).scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Search Functionality
        const searchInput = document.querySelector('.search-box input');
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            
            document.querySelectorAll('.faq-item').forEach(item => {
                const question = item.querySelector('.faq-question').textContent.toLowerCase();
                const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
                
                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    item.style.display = 'block';
                    
                    // Highlight matching text
                    if (searchTerm) {
                        highlightText(item, searchTerm);
                    }
                } else {
                    item.style.display = 'none';
                }
            });
        });

        function highlightText(element, term) {
            const question = element.querySelector('.faq-question');
            const answer = element.querySelector('.faq-answer');
            
            // Remove previous highlights
            question.innerHTML = question.textContent;
            answer.innerHTML = answer.textContent;
            
            // Add highlights
            const regex = new RegExp(`(${term})`, 'gi');
            question.innerHTML = question.textContent.replace(regex, '<span style="background-color: #ffeb3b; padding: 2px 4px; border-radius: 3px;">$1</span>');
            answer.innerHTML = answer.textContent.replace(regex, '<span style="background-color: #ffeb3b; padding: 2px 4px; border-radius: 3px;">$1</span>');
        }
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