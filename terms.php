<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - Ghion Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
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
            background: white;
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
        
        .terms-hero {
            background: linear-gradient(rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9)), url('https://images.unsplash.com/photo-1539635278303-d4002c07eae3?ixlib=rb-1.2.1&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            padding: 100px 0;
            text-align: center;
            color: white;
        }
        
        .terms-hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .terms-hero p {
            font-size: 20px;
            max-width: 700px;
            margin: 0 auto;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        .terms-content {
            background: white;
            padding: 60px;
            margin: -50px auto 50px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 1000px;
        }
        
        .last-updated {
            color: #666;
            font-style: italic;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .terms-section {
            margin-bottom: 40px;
        }
        
        .terms-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .terms-section h3 {
            color: #555;
            margin: 25px 0 15px;
        }
        
        .terms-section p {
            margin-bottom: 15px;
            color: #444;
        }
        
        .terms-section ul, .terms-section ol {
            margin: 15px 0 15px 30px;
            color: #444;
        }
        
        .terms-section li {
            margin-bottom: 10px;
        }
        
        .highlight-box {
            background: #f8f9ff;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }
        
        .acceptance-section {
            background: linear-gradient(135deg, #f8f9ff 0%, #eef1ff 100%);
            padding: 30px;
            border-radius: 10px;
            margin-top: 40px;
            text-align: center;
            border: 2px dashed #667eea;
        }
        
        .table-container {
            overflow-x: auto;
            margin: 20px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #667eea;
            color: white;
        }
        
        tr:hover {
            background: #f5f5f5;
        }
        
        .footer {
            background: #333;
            color: white;
            padding: 50px 0;
            text-align: center;
            margin-top: 80px;
        }
        
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #667eea;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s;
        }
        
        .back-to-top:hover {
            background: #5a6fd8;
            transform: translateY(-5px);
        }
        
        @media (max-width: 768px) {
            .terms-content {
                padding: 30px 20px;
                margin: -30px auto 30px;
            }
            
            .terms-hero h1 {
                font-size: 36px;
            }
            
            .nav-links {
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="nav">
                <div class="logo">Ghion Travel</div>
                <div class="nav-links">
                    <a href="index.php">Home</a>
                    <a href="packages_mongo.php">Tour Packages</a>
                    <a href="about.php">About Us</a>
                    <a href="terms.php" class="active">Terms & Conditions</a>
                    <a href="contact.php">Contact</a>
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                        <span style="color: #ffd700;">Hi, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                        <a href="logout.php" class="btn">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn">Login</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="terms-hero">
        <div class="container">
            <h1>Terms & Conditions</h1>
            <p>Please read these terms carefully before using our services</p>
        </div>
    </section>

    <!-- Terms Content -->
    <div class="container">
        <div class="terms-content">
            <div class="last-updated">
                <i class="fas fa-history"></i> Last Updated: December 2024
            </div>

            <!-- Introduction -->
            <div class="terms-section">
                <h2>1. Introduction</h2>
                <p>Welcome to Ghion Travel. These Terms and Conditions govern your use of our website and services. By accessing or using our services, you agree to be bound by these Terms.</p>
                
                <div class="highlight-box">
                    <p><strong>Important:</strong> These terms constitute a legally binding agreement. If you disagree with any part, you may not access our services.</p>
                </div>
            </div>

            <!-- Definitions -->
            <div class="terms-section">
                <h2>2. Definitions</h2>
                <p>In these Terms:</p>
                <ul>
                    <li><strong>"We", "Us", "Our"</strong> refers to Ghion Travel</li>
                    <li><strong>"You", "User", "Client"</strong> refers to the individual using our services</li>
                    <li><strong>"Services"</strong> refers to tour packages, booking services, and related offerings</li>
                    <li><strong>"Website"</strong> refers to www.ghiontravel.com and related domains</li>
                </ul>
            </div>

            <!-- Booking Terms -->
            <div class="terms-section">
                <h2>3. Booking Terms</h2>
                
                <h3>3.1 Booking Process</h3>
                <p>All bookings are subject to availability and confirmation. A booking is considered confirmed only when:</p>
                <ul>
                    <li>Full payment or required deposit is received</li>
                    <li>Booking confirmation email is sent to you</li>
                    <li>All required documents are submitted</li>
                </ul>
                
                <h3>3.2 Payment Terms</h3>
                <div class="table-container">
                    <table>
                        <tr>
                            <th>Time Before Departure</th>
                            <th>Payment Required</th>
                        </tr>
                        <tr>
                            <td>60+ days</td>
                            <td>30% deposit</td>
                        </tr>
                        <tr>
                            <td>30-59 days</td>
                            <td>50% payment</td>
                        </tr>
                        <tr>
                            <td>15-29 days</td>
                            <td>75% payment</td>
                        </tr>
                        <tr>
                            <td>0-14 days</td>
                            <td>100% payment</td>
                        </tr>
                    </table>
                </div>
                
                <h3>3.3 Price Changes</h3>
                <p>We reserve the right to adjust prices due to:</p>
                <ul>
                    <li>Changes in government taxes or fees</li>
                    <li>Currency fluctuations</li>
                    <li>Fuel surcharges</li>
                    <li>Changes in supplier costs</li>
                </ul>
            </div>

            <!-- Cancellation Policy -->
            <div class="terms-section">
                <h2>4. Cancellation & Refund Policy</h2>
                
                <h3>4.1 Client Cancellation</h3>
                <div class="table-container">
                    <table>
                        <tr>
                            <th>Cancellation Period</th>
                            <th>Refund Amount</th>
                            <th>Administrative Fee</th>
                        </tr>
                        <tr>
                            <td>60+ days before departure</td>
                            <td>90% of total amount</td>
                            <td>10% of total amount</td>
                        </tr>
                        <tr>
                            <td>30-59 days before departure</td>
                            <td>75% of total amount</td>
                            <td>25% of total amount</td>
                        </tr>
                        <tr>
                            <td>15-29 days before departure</td>
                            <td>50% of total amount</td>
                            <td>50% of total amount</td>
                        </tr>
                        <tr>
                            <td>0-14 days before departure</td>
                            <td>No refund</td>
                            <td>100% of total amount</td>
                        </tr>
                    </table>
                </div>
                
                <h3>4.2 Force Majeure</h3>
                <p>We are not liable for cancellations due to circumstances beyond our control including but not limited to:</p>
                <ul>
                    <li>Natural disasters</li>
                    <li>Political unrest</li>
                    <li>Terrorist activities</li>
                    <li>Pandemics or health emergencies</li>
                    <li>Government travel restrictions</li>
                </ul>
            </div>

            <!-- Responsibilities -->
            <div class="terms-section">
                <h2>5. Responsibilities</h2>
                
                <h3>5.1 Client Responsibilities</h3>
                <p>You are responsible for:</p>
                <ol>
                    <li>Valid travel documents (passport, visa, permits)</li>
                    <li>Travel insurance coverage</li>
                    <li>Vaccinations and health requirements</li>
                    <li>Arriving on time for scheduled activities</li>
                    <li>Respecting local customs and laws</li>
                </ol>
                
                <h3>5.2 Our Responsibilities</h3>
                <p>We are responsible for:</p>
                <ol>
                    <li>Providing services as described in the itinerary</li>
                    <li>Maintaining appropriate safety standards</li>
                    <li>Providing qualified guides and staff</li>
                    <li>Maintaining proper insurance for our operations</li>
                </ol>
            </div>

            <!-- Privacy & Data -->
            <div class="terms-section">
                <h2>6. Privacy & Data Protection</h2>
                <p>We collect and process personal data in accordance with our Privacy Policy. By using our services, you consent to:</p>
                <ul>
                    <li>Collection of personal information for booking purposes</li>
                    <li>Use of cookies on our website</li>
                    <li>Marketing communications (opt-out available)</li>
                    <li>Data sharing with necessary third parties (hotels, airlines, etc.)</li>
                </ul>
                <p>View our complete <a href="privacy.php">Privacy Policy</a> for more details.</p>
            </div>

            <!-- Intellectual Property -->
            <div class="terms-section">
                <h2>7. Intellectual Property</h2>
                <p>All content on our website including but not limited to:</p>
                <ul>
                    <li>Logos, trademarks, and service marks</li>
                    <li>Text, images, and graphics</li>
                    <li>Tour descriptions and itineraries</li>
                    <li>Website design and layout</li>
                </ul>
                <p>are owned by Ghion Travel and protected by copyright laws. Unauthorized use is prohibited.</p>
            </div>

            <!-- Limitation of Liability -->
            <div class="terms-section">
                <h2>8. Limitation of Liability</h2>
                <p>To the maximum extent permitted by law:</p>
                <ul>
                    <li>Our liability is limited to the total cost of your booking</li>
                    <li>We are not liable for indirect, incidental, or consequential damages</li>
                    <li>We are not responsible for third-party services (airlines, hotels, etc.)</li>
                    <li>We recommend purchasing comprehensive travel insurance</li>
                </ul>
            </div>

            <!-- Dispute Resolution -->
            <div class="terms-section">
                <h2>9. Dispute Resolution</h2>
                <h3>9.1 Complaints</h3>
                <p>Any complaints must be submitted in writing within 30 days of tour completion.</p>
                
                <h3>9.2 Governing Law</h3>
                <p>These Terms are governed by the laws of Ethiopia. Any disputes shall be subject to the exclusive jurisdiction of the courts in Addis Ababa.</p>
                
                <h3>9.3 Mediation</h3>
                <p>We prefer to resolve disputes through mediation before pursuing legal action.</p>
            </div>

            <!-- Amendments -->
            <div class="terms-section">
                <h2>10. Amendments & Updates</h2>
                <p>We reserve the right to modify these Terms at any time. Changes will be effective immediately upon posting on our website. It is your responsibility to review these Terms periodically.</p>
            </div>

            <!-- Contact Information -->
            <div class="terms-section">
                <h2>11. Contact Information</h2>
                <p>For questions about these Terms, contact us:</p>
                <ul>
                    <li><i class="fas fa-envelope"></i> legal@ghiontravel.com</li>
                    <li><i class="fas fa-phone"></i> +251 11 123 4567</li>
                    <li><i class="fas fa-map-marker-alt"></i> Addis Ababa, Ethiopia</li>
                    <li><i class="fas fa-clock"></i> Mon-Fri: 9:00 AM - 5:00 PM (EAT)</li>
                </ul>
            </div>

            <!-- Acceptance Section -->
            <div class="acceptance-section">
                <h3><i class="fas fa-check-circle"></i> Acceptance of Terms</h3>
                <p>By using our website and services, you acknowledge that you have read, understood, and agree to be bound by these Terms & Conditions.</p>
                <p>If you are booking on behalf of others, you confirm that you have their authority to accept these Terms on their behalf.</p>
                <p><strong>These Terms supersede all prior agreements and understandings.</strong></p>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <a href="#" class="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </a>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Ghion Travel. All rights reserved.</p>
            <p>
                <a href="terms.php" style="color: #bbb;">Terms & Conditions</a> | 
                <a href="privacy.php" style="color: #bbb;">Privacy Policy</a> | 
                <a href="cookie_policy.php" style="color: #bbb;">Cookie Policy</a>
            </p>
        </div>
    </footer>

    <script>
        // Back to Top functionality
        const backToTopButton = document.querySelector('.back-to-top');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopButton.style.display = 'flex';
            } else {
                backToTopButton.style.display = 'none';
            }
        });
        
        backToTopButton.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Print functionality
        const printButton = document.createElement('button');
        printButton.innerHTML = '<i class="fas fa-print"></i> Print Terms';
        printButton.style.cssText = `
            position: fixed;
            bottom: 90px;
            right: 30px;
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
        `;
        
        printButton.addEventListener('click', () => {
            window.print();
        });
        
        document.body.appendChild(printButton);

        // Add section navigation
        const termsSections = document.querySelectorAll('.terms-section h2');
        const sectionNav = document.createElement('div');
        sectionNav.innerHTML = `
            <div style="
                position: fixed;
                left: 30px;
                top: 50%;
                transform: translateY(-50%);
                background: white;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.1);
                max-width: 250px;
                display: none;
            ">
                <h4 style="margin-bottom: 15px; color: #667eea;">Quick Navigation</h4>
                <div id="section-links" style="max-height: 300px; overflow-y: auto;"></div>
            </div>
        `;
        
        document.body.appendChild(sectionNav);
        const sectionLinks = document.getElementById('section-links');
        
        termsSections.forEach((section, index) => {
            const link = document.createElement('a');
            link.href = '#';
            link.textContent = section.textContent;
            link.style.cssText = `
                display: block;
                padding: 8px 0;
                color: #555;
                text-decoration: none;
                border-bottom: 1px solid #eee;
                font-size: 14px;
            `;
            
            link.addEventListener('click', (e) => {
                e.preventDefault();
                section.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });
            
            sectionLinks.appendChild(link);
        });

        // Show/hide navigation based on scroll
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 500 && window.innerWidth > 992) {
                sectionNav.style.display = 'block';
            } else {
                sectionNav.style.display = 'none';
            }
        });

        // Highlight current section
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.3
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const id = entry.target.querySelector('h2').textContent;
                const correspondingLink = Array.from(sectionLinks.querySelectorAll('a')).find(
                    link => link.textContent === id
                );
                
                if (entry.isIntersecting) {
                    correspondingLink.style.color = '#667eea';
                    correspondingLink.style.fontWeight = 'bold';
                } else {
                    correspondingLink.style.color = '#555';
                    correspondingLink.style.fontWeight = 'normal';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.terms-section').forEach(section => {
            observer.observe(section);
        });
    </script>
</body>
</html>