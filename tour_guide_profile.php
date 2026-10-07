<?php
session_start();

// Sample tour guide data (In real application, this would come from database)
$tourGuides = [
    [
        'id' => 1,
        'name' => 'Alemayehu Bekele',
        'title' => 'Senior Historical Guide',
        'experience' => '15 years',
        'languages' => ['English', 'Amharic', 'French'],
        'specializations' => ['Historical Sites', 'Cultural Tours', 'UNESCO Heritage'],
        'rating' => 4.9,
        'reviews' => 287,
        'image' => 'https://randomuser.me/api/portraits/men/32.jpg',
        'description' => 'Expert in Ethiopian history and archaeology with extensive knowledge of Axum, Lalibela, and Gondar.',
        'hourly_rate' => '$35',
        'certifications' => ['ETTGA Certified', 'Archaeology Degree', 'First Aid Certified']
    ],
    [
        'id' => 2,
        'name' => 'Mekdes Tsegaye',
        'title' => 'Wildlife & Nature Expert',
        'experience' => '12 years',
        'languages' => ['English', 'Amharic', 'Spanish'],
        'specializations' => ['Simien Mountains', 'Bale Mountains', 'Wildlife Safaris'],
        'rating' => 4.8,
        'reviews' => 214,
        'image' => 'https://randomuser.me/api/portraits/women/44.jpg',
        'description' => 'Specialized in Ethiopian wildlife, national parks, and mountain trekking with photography skills.',
        'hourly_rate' => '$30',
        'certifications' => ['Wildlife Guide License', 'Mountain Rescue', 'Photography']
    ],
    [
        'id' => 3,
        'name' => 'Habtamu Girma',
        'title' => 'Cultural & Culinary Guide',
        'experience' => '8 years',
        'languages' => ['English', 'Amharic', 'Italian', 'Arabic'],
        'specializations' => ['Food Tours', 'Coffee Ceremonies', 'Local Markets'],
        'rating' => 4.7,
        'reviews' => 189,
        'image' => 'https://randomuser.me/api/portraits/men/65.jpg',
        'description' => 'Passionate about Ethiopian cuisine, coffee culture, and authentic local experiences.',
        'hourly_rate' => '$28',
        'certifications' => ['Culinary Tourism', 'Coffee Expert', 'Local Culture']
    ],
    [
        'id' => 4,
        'name' => 'Selamawit Assefa',
        'title' => 'Adventure & Trekking Guide',
        'experience' => '10 years',
        'languages' => ['English', 'Amharic', 'German'],
        'specializations' => ['Danakil Depression', 'Trekking', 'Adventure Sports'],
        'rating' => 4.9,
        'reviews' => 156,
        'image' => 'https://randomuser.me/api/portraits/women/68.jpg',
        'description' => 'Adventure specialist for extreme locations including the Danakil Depression and remote trekking routes.',
        'hourly_rate' => '$40',
        'certifications' => ['Mountain Guide', 'First Responder', '4x4 Driving']
    ],
    [
        'id' => 5,
        'name' => 'Yohannes Lemma',
        'title' => 'Photography & Scenic Tours',
        'experience' => '6 years',
        'languages' => ['English', 'Amharic', 'Japanese'],
        'specializations' => ['Photography Tours', 'Scenic Locations', 'Instagram Spots'],
        'rating' => 4.6,
        'reviews' => 132,
        'image' => 'https://randomuser.me/api/portraits/men/22.jpg',
        'description' => 'Professional photographer guiding tours to the most photogenic spots in Ethiopia.',
        'hourly_rate' => '$32',
        'certifications' => ['Professional Photography', 'Drone Operator', 'Videography']
    ],
    [
        'id' => 6,
        'name' => 'Tigist Mulugeta',
        'title' => 'Religious & Spiritual Tours',
        'experience' => '14 years',
        'languages' => ['English', 'Amharic', 'Hebrew', 'Greek'],
        'specializations' => ['Religious Sites', 'Spiritual Journeys', 'Monastery Tours'],
        'rating' => 4.8,
        'reviews' => 198,
        'image' => 'https://randomuser.me/api/portraits/women/33.jpg',
        'description' => 'Deep knowledge of Ethiopian Orthodox Christianity and religious history.',
        'hourly_rate' => '$38',
        'certifications' => ['Theology Degree', 'Religious Studies', 'Historical Research']
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Guides - Ghion Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
    <style>
        /* CSS Variables for Light/Dark Mode */
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --accent: #ffd700;
            --background: #f5f7ff;
            --card-bg: #ffffff;
            --text: #333333;
            --text-light: #666666;
            --header-bg: rgba(102, 126, 234, 0.95);
            --border: #e0e0e0;
            --shadow: rgba(0, 0, 0, 0.08);
            --shadow-hover: rgba(0, 0, 0, 0.15);
            --footer-bg: #222222;
            --footer-text: #bbbbbb;
        }

        /* Dark Theme Variables */
        [data-theme="dark"] {
            --primary: #7c93fb;
            --secondary: #8a63c4;
            --accent: #ffd700;
            --background: #121212;
            --card-bg: #1e1e1e;
            --text: #ffffff;
            --text-light: #aaaaaa;
            --header-bg: rgba(30, 30, 30, 0.95);
            --border: #333333;
            --shadow: rgba(0, 0, 0, 0.3);
            --shadow-hover: rgba(0, 0, 0, 0.5);
            --footer-bg: #111111;
            --footer-text: #888888;
        }

        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.3s, color 0.3s, border-color 0.3s;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background);
            color: var(--text);
            line-height: 1.6;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* Header Styles */
        .header {
            background: var(--header-bg);
            color: white;
            padding: 20px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
        }

        .logo span {
            color: var(--accent);
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            color: var(--accent);
        }

        .nav-links a.active {
            color: var(--accent);
            font-weight: 600;
        }

        .btn {
            background: var(--accent);
            color: var(--primary);
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s, background-color 0.3s;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
            background: #ffed4a;
        }

        /* Dark Mode Toggle Button */
        .dark-toggle {
            background: var(--accent);
            color: var(--primary);
            padding: 8px 15px;
            border-radius: 20px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.3s;
        }

        .dark-toggle:hover {
            transform: translateY(-2px);
        }

        /* Hero Section */
        .tour-guide-hero {
            background: linear-gradient(rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9)), url('https://images.unsplash.com/photo-1488646953014-85cb44e25828?ixlib=rb-1.2.1&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            padding: 150px 0 100px;
            text-align: center;
            color: white;
            margin-top: 60px;
        }

        .tour-guide-hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .tour-guide-hero p {
            font-size: 20px;
            max-width: 700px;
            margin: 0 auto;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        /* Search & Filter Section */
        .guide-filter {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px var(--shadow);
            margin: 40px 0;
        }

        .filter-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .filter-group {
            margin-bottom: 20px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text);
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 8px;
            background: var(--card-bg);
            color: var(--text);
            font-size: 16px;
        }

        .search-box {
            position: relative;
            margin-bottom: 30px;
        }

        .search-box input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid var(--border);
            border-radius: 30px;
            font-size: 16px;
            background: var(--card-bg);
            color: var(--text);
            padding-left: 50px;
        }

        .search-box i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
        }

        /* Tour Guides Grid */
        .tour-guides-grid {
            padding: 60px 0;
        }

        .guides-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        /* Guide Card */
        .guide-card {
            background: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px var(--shadow);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid var(--border);
        }

        .guide-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px var(--shadow-hover);
        }

        .guide-image {
            height: 250px;
            overflow: hidden;
        }

        .guide-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .guide-card:hover .guide-image img {
            transform: scale(1.05);
        }

        .guide-info {
            padding: 25px;
        }

        .guide-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .guide-name {
            font-size: 22px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 5px;
        }

        .guide-title {
            color: var(--primary);
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .guide-rating {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .guide-rating .stars {
            color: var(--accent);
        }

        .guide-rating .rating-number {
            font-weight: 600;
            color: var(--text);
        }

        .guide-rating .reviews {
            color: var(--text-light);
            font-size: 14px;
        }

        .guide-details {
            margin: 20px 0;
        }

        .guide-detail {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: var(--text-light);
        }

        .guide-detail i {
            color: var(--primary);
            width: 20px;
        }

        .guide-languages {
            margin: 15px 0;
        }

        .guide-languages span {
            background: var(--primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-right: 5px;
            margin-bottom: 5px;
            display: inline-block;
        }

        .guide-specializations {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 15px 0;
        }

        .specialization-tag {
            background: var(--background);
            color: var(--text);
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            border: 1px solid var(--border);
        }

        .guide-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .guide-price {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }

        .guide-price span {
            font-size: 14px;
            color: var(--text-light);
            font-weight: normal;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--card-bg);
            border-radius: 15px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 30px;
            color: var(--text);
            cursor: pointer;
            z-index: 10;
        }

        .modal-header {
            padding: 30px;
            border-bottom: 1px solid var(--border);
        }

        .modal-body {
            padding: 30px;
        }

        .modal-guide-info {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .modal-guide-image {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            overflow: hidden;
        }

        .modal-guide-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Booking Form */
        .booking-form {
            background: var(--background);
            padding: 30px;
            border-radius: 10px;
            margin-top: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 8px;
            background: var(--card-bg);
            color: var(--text);
            font-size: 16px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Footer */
        .footer {
            background: var(--footer-bg);
            color: white;
            padding: 50px 0;
            text-align: center;
            margin-top: 80px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-column h3 {
            margin-bottom: 20px;
            color: white;
        }

        .footer-links {
            list-style: none;
            text-align: left;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: var(--footer-text);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: var(--accent);
        }

        .footer-links i {
            margin-right: 10px;
            width: 20px;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            color: white;
            font-size: 20px;
            transition: color 0.3s;
        }

        .social-links a:hover {
            color: var(--accent);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
            color: var(--footer-text);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .modal-guide-info {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .modal-guide-image {
                margin: 0 auto;
            }
        }

        @media (max-width: 768px) {
            .tour-guide-hero h1 {
                font-size: 36px;
            }
            
            .nav-links {
                gap: 15px;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .guides-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .guide-header {
                flex-direction: column;
                gap: 10px;
            }
        }

        @media (max-width: 480px) {
            .tour-guide-hero {
                padding: 120px 0 60px;
            }
            
            .tour-guide-hero h1 {
                font-size: 28px;
            }
            
            .tour-guide-hero p {
                font-size: 16px;
            }
            
            .filter-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="logo">Ghion <span>Travel</span></div>
            <nav class="nav">
                <div class="nav-links">
                    <a href="index.php">Home</a>
                    <a href="packages_mongo.php">Tour Packages</a>
                    <a href="car.php">Car</a>
                    <a href="hotel.php">Hotel</a>
                    <a href="about.php">About Us</a>
                    <a href="contact.php">Contact & feedback</a>
                    <a href="my_bookings_mongo.php">My Bookings</a>
                    <a href="faq.php">FAQ</a>
                    <a href="tour_guide_profile.php" class="active">Tour Guides</a>
                    
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                        <span style="color: var(--accent);">Hi, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                        <a href="logout.php" class="btn">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn">Login</a>
                    <?php endif; ?>
                    
                    <button id="darkModeToggle" class="dark-toggle">
                        <i class="fas fa-moon"></i>
                        <span>Dark Mode</span>
                    </button>
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="tour-guide-hero">
        <div class="container">
            <h1>Expert Tour Guides</h1>
            <p>Meet our professional guides who will make your Ethiopian journey unforgettable with their knowledge, experience, and passion.</p>
        </div>
    </section>

    <!-- Search & Filter Section -->
    <div class="container">
        <div class="guide-filter">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search guides by name, specialization, or language...">
            </div>
            
            <div class="filter-section">
                <div class="filter-group">
                    <label for="specializationFilter"><i class="fas fa-map-marker-alt"></i> Specialization</label>
                    <select id="specializationFilter">
                        <option value="">All Specializations</option>
                        <option value="Historical">Historical Sites</option>
                        <option value="Wildlife">Wildlife & Nature</option>
                        <option value="Cultural">Cultural Tours</option>
                        <option value="Adventure">Adventure & Trekking</option>
                        <option value="Photography">Photography</option>
                        <option value="Religious">Religious Sites</option>
                        <option value="Food">Food & Culinary</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="languageFilter"><i class="fas fa-language"></i> Language</label>
                    <select id="languageFilter">
                        <option value="">All Languages</option>
                        <option value="English">English</option>
                        <option value="Amharic">Amharic</option>
                        <option value="French">French</option>
                        <option value="Spanish">Spanish</option>
                        <option value="German">German</option>
                        <option value="Italian">Italian</option>
                        <option value="Arabic">Arabic</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="experienceFilter"><i class="fas fa-award"></i> Experience Level</label>
                    <select id="experienceFilter">
                        <option value="">All Experience Levels</option>
                        <option value="5">5+ years</option>
                        <option value="10">10+ years</option>
                        <option value="15">15+ years</option>
                    </select>
                </div>
            </div>
            
            <div class="filter-group">
                <label for="ratingFilter"><i class="fas fa-star"></i> Minimum Rating</label>
                <select id="ratingFilter">
                    <option value="0">Any Rating</option>
                    <option value="4.0">4.0+ Stars</option>
                    <option value="4.5">4.5+ Stars</option>
                    <option value="4.7">4.7+ Stars</option>
                    <option value="4.9">4.9+ Stars</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Tour Guides Grid -->
    <section class="tour-guides-grid">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 40px; color: var(--text);">Available Guides</h2>
            
            <div class="guides-grid" id="guidesGrid">
                <?php foreach ($tourGuides as $guide): ?>
                <div class="guide-card" data-guide-id="<?php echo $guide['id']; ?>"
                     data-specializations="<?php echo htmlspecialchars(json_encode($guide['specializations'])); ?>"
                     data-languages="<?php echo htmlspecialchars(json_encode($guide['languages'])); ?>"
                     data-experience="<?php echo str_replace(' years', '', $guide['experience']); ?>"
                     data-rating="<?php echo $guide['rating']; ?>">
                    
                    <div class="guide-image">
                        <img src="<?php echo $guide['image']; ?>" alt="<?php echo $guide['name']; ?>">
                    </div>
                    
                    <div class="guide-info">
                        <div class="guide-header">
                            <div>
                                <h3 class="guide-name"><?php echo $guide['name']; ?></h3>
                                <div class="guide-title"><?php echo $guide['title']; ?></div>
                            </div>
                            <div class="guide-rating">
                                <div class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= floor($guide['rating'])): ?>
                                            <i class="fas fa-star"></i>
                                        <?php elseif ($i == ceil($guide['rating']) && $guide['rating'] != floor($guide['rating'])): ?>
                                            <i class="fas fa-star-half-alt"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <div class="rating-number"><?php echo $guide['rating']; ?></div>
                                <div class="reviews">(<?php echo $guide['reviews']; ?> reviews)</div>
                            </div>
                        </div>
                        
                        <p style="color: var(--text-light); margin-bottom: 15px; font-size: 14px;">
                            <?php echo $guide['description']; ?>
                        </p>
                        
                        <div class="guide-details">
                            <div class="guide-detail">
                                <i class="fas fa-briefcase"></i>
                                <span><?php echo $guide['experience']; ?> experience</span>
                            </div>
                            <div class="guide-detail">
                                <i class="fas fa-certificate"></i>
                                <span><?php echo count($guide['certifications']); ?> certifications</span>
                            </div>
                        </div>
                        
                        <div class="guide-languages">
                            <?php foreach ($guide['languages'] as $language): ?>
                                <span><?php echo $language; ?></span>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="guide-specializations">
                            <?php foreach ($guide['specializations'] as $specialization): ?>
                                <span class="specialization-tag"><?php echo $specialization; ?></span>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="guide-footer">
                            <div class="guide-price">
                                <?php echo $guide['hourly_rate']; ?> <span>/ hour</span>
                            </div>
                            <button class="btn book-guide-btn" data-guide-id="<?php echo $guide['id']; ?>">
                                <i class="fas fa-calendar-check"></i> Book Now
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="modal-header">
                <h2 id="modalGuideName"></h2>
                <p id="modalGuideTitle" style="color: var(--primary); margin-top: 5px;"></p>
            </div>
            <div class="modal-body">
                <div class="modal-guide-info" id="modalGuideInfo">
                    <!-- Guide info will be populated by JavaScript -->
                </div>
                
                <div class="booking-form">
                    <h3>Book This Guide</h3>
                    <form id="bookingForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="bookingName">Full Name *</label>
                                <input type="text" id="bookingName" name="bookingName" required>
                            </div>
                            <div class="form-group">
                                <label for="bookingEmail">Email Address *</label>
                                <input type="email" id="bookingEmail" name="bookingEmail" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="bookingPhone">Phone Number *</label>
                                <input type="tel" id="bookingPhone" name="bookingPhone" required>
                            </div>
                            <div class="form-group">
                                <label for="bookingDate">Tour Date *</label>
                                <input type="date" id="bookingDate" name="bookingDate" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="bookingDuration">Tour Duration (hours) *</label>
                                <select id="bookingDuration" name="bookingDuration" required>
                                    <option value="2">2 hours</option>
                                    <option value="4">4 hours</option>
                                    <option value="8">Full day (8 hours)</option>
                                    <option value="16">2 days (16 hours)</option>
                                    <option value="24">3 days (24 hours)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="bookingGroupSize">Group Size *</label>
                                <select id="bookingGroupSize" name="bookingGroupSize" required>
                                    <option value="1">1 person</option>
                                    <option value="2">2 people</option>
                                    <option value="4">3-4 people</option>
                                    <option value="8">5-8 people</option>
                                    <option value="15">9-15 people</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="bookingLocation">Tour Location / Destination *</label>
                            <input type="text" id="bookingLocation" name="bookingLocation" placeholder="e.g., Lalibela, Simien Mountains, Addis Ababa" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="bookingMessage">Special Requirements / Notes</label>
                            <textarea id="bookingMessage" name="bookingMessage" rows="4" placeholder="Tell us about your specific interests or requirements..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn" style="width: 100%; padding: 15px;">
                                <i class="fas fa-paper-plane"></i> Submit Booking Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h3>Ghion Travel</h3>
                    <p style="color: var(--footer-text); line-height: 1.6; margin-bottom: 20px;">
                        Your trusted partner for unforgettable Ethiopian adventures. Explore ancient history, vibrant cultures, and breathtaking landscapes with our expert guides.
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
                        <li><a href="tour_guide_profile.php">Tour Guides</a></li>
                        <li><a href="car.php">Car Rental</a></li>
                        <li><a href="hotel.php">Hotels</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Support</h3>
                    <ul class="footer-links">
                        <li><a href="faq.php">FAQ</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="my_bookings_mongo.php">My Bookings</a></li>
                        <li><a href="terms.php">Terms & Conditions</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contact Info</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt"></i> Addis Ababa, Ethiopia</li>
                        <li><i class="fas fa-phone"></i> +251 11 123 4567</li>
                        <li><i class="fas fa-envelope"></i> guides@ghiontravel.com</li>
                        <li><i class="fas fa-clock"></i> Mon - Sun: 8:00 AM - 8:00 PM</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Ghion Travel. All rights reserved. | 
                   <a href="admin_login.php" style="color: var(--footer-text);">Admin Panel</a> | 
                   Professional Tour Guides</p>
            </div>
        </div>
    </footer>

    <script>
        // Dark Mode Functionality
        function initializeDarkMode() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            
            // Check for saved theme or prefer-color-scheme
            const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
            const savedTheme = localStorage.getItem('theme');
            
            let currentTheme = 'light';
            
            if (savedTheme) {
                currentTheme = savedTheme;
            } else if (prefersDarkScheme.matches) {
                currentTheme = 'dark';
            }
            
            // Apply the theme
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
            const span = darkModeToggle.querySelector('span');
            
            if (theme === 'dark') {
                icon.className = 'fas fa-sun';
                span.textContent = ' Light Mode';
            } else {
                icon.className = 'fas fa-moon';
                span.textContent = ' Dark Mode';
            }
        }

        // Tour Guide Filtering
        document.addEventListener('DOMContentLoaded', function() {
            initializeDarkMode();
            
            // Initialize date picker with tomorrow as minimum
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('bookingDate').min = tomorrow.toISOString().split('T')[0];
            
            // Filter guides based on criteria
            function filterGuides() {
                const searchTerm = document.getElementById('searchInput').value.toLowerCase();
                const specialization = document.getElementById('specializationFilter').value;
                const language = document.getElementById('languageFilter').value;
                const experience = document.getElementById('experienceFilter').value;
                const rating = parseFloat(document.getElementById('ratingFilter').value);
                
                document.querySelectorAll('.guide-card').forEach(card => {
                    const name = card.querySelector('.guide-name').textContent.toLowerCase();
                    const description = card.querySelector('p').textContent.toLowerCase();
                    const specializations = JSON.parse(card.dataset.specializations);
                    const languages = JSON.parse(card.dataset.languages);
                    const guideExperience = parseInt(card.dataset.experience);
                    const guideRating = parseFloat(card.dataset.rating);
                    
                    let show = true;
                    
                    // Search filter
                    if (searchTerm && !name.includes(searchTerm) && !description.includes(searchTerm)) {
                        show = false;
                    }
                    
                    // Specialization filter
                    if (specialization && !specializations.some(s => s.toLowerCase().includes(specialization.toLowerCase()))) {
                        show = false;
                    }
                    
                    // Language filter
                    if (language && !languages.includes(language)) {
                        show = false;
                    }
                    
                    // Experience filter
                    if (experience && guideExperience < parseInt(experience)) {
                        show = false;
                    }
                    
                    // Rating filter
                    if (rating && guideRating < rating) {
                        show = false;
                    }
                    
                    card.style.display = show ? 'block' : 'none';
                });
            }
            
            // Add event listeners to filters
            document.getElementById('searchInput').addEventListener('input', filterGuides);
            document.getElementById('specializationFilter').addEventListener('change', filterGuides);
            document.getElementById('languageFilter').addEventListener('change', filterGuides);
            document.getElementById('experienceFilter').addEventListener('change', filterGuides);
            document.getElementById('ratingFilter').addEventListener('change', filterGuides);
            
            // Booking Modal Functionality
            const modal = document.getElementById('bookingModal');
            const closeModal = document.querySelector('.close-modal');
            const bookingForm = document.getElementById('bookingForm');
            
            // Store guide data for modal
            const guideData = <?php echo json_encode($tourGuides); ?>;
            
            // Handle Book Now button clicks
            document.querySelectorAll('.book-guide-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const guideId = parseInt(this.dataset.guideId);
                    const guide = guideData.find(g => g.id === guideId);
                    
                    if (guide) {
                        // Populate modal with guide info
                        document.getElementById('modalGuideName').textContent = guide.name;
                        document.getElementById('modalGuideTitle').textContent = guide.title;
                        
                        const modalInfo = document.getElementById('modalGuideInfo');
                        modalInfo.innerHTML = `
                            <div class="modal-guide-image">
                                <img src="${guide.image}" alt="${guide.name}">
                            </div>
                            <div>
                                <h3 style="margin-bottom: 15px;">About ${guide.name}</h3>
                                <p style="color: var(--text-light); margin-bottom: 20px;">${guide.description}</p>
                                
                                <div class="guide-details">
                                    <div class="guide-detail">
                                        <i class="fas fa-briefcase"></i>
                                        <span><strong>Experience:</strong> ${guide.experience}</span>
                                    </div>
                                    <div class="guide-detail">
                                        <i class="fas fa-star"></i>
                                        <span><strong>Rating:</strong> ${guide.rating}/5 (${guide.reviews} reviews)</span>
                                    </div>
                                    <div class="guide-detail">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span><strong>Hourly Rate:</strong> ${guide.hourly_rate}</span>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 20px;">
                                    <h4>Languages:</h4>
                                    <div class="guide-languages">
                                        ${guide.languages.map(lang => `<span>${lang}</span>`).join('')}
                                    </div>
                                </div>
                                
                                <div style="margin-top: 20px;">
                                    <h4>Specializations:</h4>
                                    <div class="guide-specializations">
                                        ${guide.specializations.map(spec => `<span class="specialization-tag">${spec}</span>`).join('')}
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Show modal
                        modal.style.display = 'flex';
                        document.body.style.overflow = 'hidden';
                    }
                });
            });
            
            // Close modal
            closeModal.addEventListener('click', function() {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
            
            // Handle booking form submission
            bookingForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Get form data
                const formData = {
                    guideName: document.getElementById('modalGuideName').textContent,
                    customerName: document.getElementById('bookingName').value,
                    email: document.getElementById('bookingEmail').value,
                    phone: document.getElementById('bookingPhone').value,
                    date: document.getElementById('bookingDate').value,
                    duration: document.getElementById('bookingDuration').value,
                    groupSize: document.getElementById('bookingGroupSize').value,
                    location: document.getElementById('bookingLocation').value,
                    message: document.getElementById('bookingMessage').value
                };
                
                // In a real application, you would send this data to your server
                console.log('Booking submitted:', formData);
                
                // Show success message
                alert('Booking request submitted successfully! Our team will contact you within 24 hours to confirm your booking.');
                
                // Close modal and reset form
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                bookingForm.reset();
            });
            
            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
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
                    header.style.background = 'var(--header-bg)';
                    header.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
                } else {
                    header.style.background = 'var(--header-bg)';
                    header.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
                }
            });
        });
    </script>
</body>
</html>