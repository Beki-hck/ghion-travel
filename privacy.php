<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Ghion Travel</title>
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
        
        .privacy-hero {
            background: linear-gradient(rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9)), url('https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-1.2.1&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            padding: 100px 0;
            text-align: center;
            color: white;
        }
        
        .privacy-hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .privacy-hero p {
            font-size: 20px;
            max-width: 700px;
            margin: 0 auto;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        .privacy-content {
            background: white;
            padding: 60px;
            margin: -50px auto 50px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 1000px;
        }
        
        .effective-date {
            background: #f0f7ff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }
        
        .privacy-section {
            margin-bottom: 40px;
        }
        
        .privacy-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .privacy-section h3 {
            color: #555;
            margin: 25px 0 15px;
        }
        
        .privacy-section p {
            margin-bottom: 15px;
            color: #444;
        }
        
        .privacy-section ul, .privacy-section ol {
            margin: 15px 0 15px 30px;
            color: #444;
        }
        
        .privacy-section li {
            margin-bottom: 10px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        .data-table th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        
        .data-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .data-table tr:hover {
            background: #f5f5f5;
        }
        
        .highlight-box {
            background: #f8f9ff;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border: 1px solid #e0e7ff;
        }
        
        .user-rights {
            background: linear-gradient(135deg, #f0f7ff 0%, #e6f7ff 100%);
            padding: 30px;
            border-radius: 10px;
            margin-top: 40px;
        }
        
        .right-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #d1e9ff;
        }
        
        .right-item:last-child {
            border-bottom: none;
        }
        
        .right-icon {
            background: #667eea;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .contact-privacy {
            background: #667eea;
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-top: 50px;
            text-align: center;
        }
        
        .contact-privacy a {
            color: #ffd700;
            text-decoration: none;
        }
        
        .contact-privacy a:hover {
            text-decoration: underline;
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
        
        .print-btn {
            position: fixed;
            bottom: 90px;
            right: 30px;
            background: #28a745;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .print-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .privacy-content {
                padding: 30px 20px;
                margin: -30px auto 30px;
            }
            
            .privacy-hero h1 {
                font-size: 36px;
            }
            
            .nav-links {
                gap: 15px;
            }
            
            .print-btn {
                bottom: 80px;
                right: 20px;
                padding: 10px 15px;
                font-size: 14px;
            }
        }
        
        @media print {
            .header, .footer, .back-to-top, .print-btn {
                display: none;
            }
            
            .privacy-content {
                margin: 0;
                padding: 0;
                box-shadow: none;
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
                    <a href="privacy.php" class="active">Privacy Policy</a>
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
    <section class="privacy-hero">
        <div class="container">
            <h1>Privacy Policy</h1>
            <p>Your privacy is important to us. Learn how we collect, use, and protect your personal information.</p>
        </div>
    </section>

    <!-- Privacy Content -->
    <div class="container">
        <div class="privacy-content">
            <!-- Effective Date -->
            <div class="effective-date">
                <h3><i class="fas fa-calendar-alt"></i> Effective Date: December 2024</h3>
                <p>This Privacy Policy describes how Ghion Travel collects, uses, and shares your personal information when you use our website and services.</p>
            </div>

            <!-- Introduction -->
            <div class="privacy-section">
                <h2><i class="fas fa-info-circle"></i> 1. Introduction</h2>
                <p>Ghion Travel ("we", "us", "our") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website or use our services.</p>
                <p>Please read this policy carefully. By using our services, you consent to the practices described in this Privacy Policy.</p>
            </div>

            <!-- Information We Collect -->
            <div class="privacy-section">
                <h2><i class="fas fa-database"></i> 2. Information We Collect</h2>
                
                <h3>2.1 Personal Information</h3>
                <p>We collect personal information that you voluntarily provide when:</p>
                <ul>
                    <li>Creating an account</li>
                    <li>Booking tours or services</li>
                    <li>Contacting customer support</li>
                    <li>Subscribing to newsletters</li>
                    <li>Participating in surveys or promotions</li>
                </ul>
                
                <h3>2.2 Types of Personal Information</h3>
                <div class="table-container">
                    <table class="data-table">
                        <tr>
                            <th>Information Type</th>
                            <th>Examples</th>
                            <th>Purpose</th>
                        </tr>
                        <tr>
                            <td>Identity Information</td>
                            <td>Full name, date of birth, passport details</td>
                            <td>Booking verification, travel documentation</td>
                        </tr>
                        <tr>
                            <td>Contact Information</td>
                            <td>Email address, phone number, physical address</td>
                            <td>Communication, booking confirmations</td>
                        </tr>
                        <tr>
                            <td>Payment Information</td>
                            <td>Credit card details, billing address (processed securely via payment processors)</td>
                            <td>Transaction processing</td>
                        </tr>
                        <tr>
                            <td>Travel Information</td>
                            <td>Travel dates, preferences, special requirements</td>
                            <td>Service customization</td>
                        </tr>
                        <tr>
                            <td>Health Information</td>
                            <td>Medical conditions, dietary restrictions (if provided)</td>
                            <td>Health and safety during travel</td>
                        </tr>
                    </table>
                </div>
                
                <h3>2.3 Automatically Collected Information</h3>
                <p>When you visit our website, we automatically collect:</p>
                <ul>
                    <li><strong>Device Information:</strong> IP address, browser type, device type</li>
                    <li><strong>Usage Data:</strong> Pages visited, time spent, clicks</li>
                    <li><strong>Location Data:</strong> General location (city/country level)</li>
                    <li><strong>Cookies & Tracking:</strong> See our <a href="cookie_policy.php">Cookie Policy</a></li>
                </ul>
            </div>

            <!-- How We Use Your Information -->
            <div class="privacy-section">
                <h2><i class="fas fa-cogs"></i> 3. How We Use Your Information</h2>
                
                <div class="highlight-box">
                    <p><strong>Primary Uses:</strong></p>
                    <ul>
                        <li>Process and manage your bookings</li>
                        <li>Provide customer support</li>
                        <li>Send booking confirmations and updates</li>
                        <li>Process payments and prevent fraud</li>
                        <li>Comply with legal obligations</li>
                    </ul>
                </div>
                
                <h3>3.1 Communication</h3>
                <p>We may use your contact information to:</p>
                <ul>
                    <li>Send important booking updates</li>
                    <li>Respond to inquiries</li>
                    <li>Send marketing communications (with your consent)</li>
                    <li>Request feedback or reviews</li>
                </ul>
                
                <h3>3.2 Service Improvement</h3>
                <p>We analyze usage data to:</p>
                <ul>
                    <li>Improve website functionality</li>
                    <li>Personalize user experience</li>
                    <li>Develop new features and services</li>
                    <li>Conduct research and analysis</li>
                </ul>
            </div>

            <!-- Information Sharing -->
            <div class="privacy-section">
                <h2><i class="fas fa-share-alt"></i> 4. Information Sharing & Disclosure</h2>
                
                <h3>4.1 Third-Party Service Providers</h3>
                <p>We share information with trusted partners who assist in providing our services:</p>
                <ul>
                    <li><strong>Tour Operators & Guides:</strong> Necessary travel information</li>
                    <li><strong>Hotels & Accommodations:</strong> Booking details</li>
                    <li><strong>Transportation Providers:</strong> Travel itinerary</li>
                    <li><strong>Payment Processors:</strong> Secure payment processing</li>
                    <li><strong>Marketing Platforms:</strong> Marketing communications (with consent)</li>
                </ul>
                
                <h3>4.2 Legal Requirements</h3>
                <p>We may disclose your information if required by:</p>
                <ul>
                    <li>Court order or legal process</li>
                    <li>Government authorities</li>
                    <li>To protect our rights or property</li>
                    <li>To prevent fraud or security issues</li>
                </ul>
                
                <h3>4.3 Business Transfers</h3>
                <p>In the event of a merger, acquisition, or sale of assets, your information may be transferred as part of the transaction.</p>
            </div>

            <!-- Data Security -->
            <div class="privacy-section">
                <h2><i class="fas fa-shield-alt"></i> 5. Data Security</h2>
                
                <p>We implement appropriate security measures to protect your personal information:</p>
                <ul>
                    <li><strong>Encryption:</strong> SSL/TLS encryption for data transmission</li>
                    <li><strong>Access Controls:</strong> Limited access to authorized personnel</li>
                    <li><strong>Secure Storage:</strong> Encrypted database storage</li>
                    <li><strong>Regular Audits:</strong> Security vulnerability assessments</li>
                    <li><strong>PCI Compliance:</strong> Payment card industry standards</li>
                </ul>
                
                <div class="highlight-box">
                    <p><strong>Important:</strong> While we implement robust security measures, no method of transmission over the internet or electronic storage is 100% secure. We cannot guarantee absolute security.</p>
                </div>
            </div>

            <!-- Data Retention -->
            <div class="privacy-section">
                <h2><i class="fas fa-history"></i> 6. Data Retention</h2>
                
                <p>We retain your personal information only as long as necessary:</p>
                <div class="table-container">
                    <table class="data-table">
                        <tr>
                            <th>Data Type</th>
                            <th>Retention Period</th>
                            <th>Reason</th>
                        </tr>
                        <tr>
                            <td>Booking Information</td>
                            <td>7 years from travel completion</td>
                            <td>Legal and tax requirements</td>
                        </tr>
                        <tr>
                            <td>Account Information</td>
                            <td>Until account deletion request</td>
                            <td>Service provision</td>
                        </tr>
                        <tr>
                            <td>Marketing Data</td>
                            <td>Until consent withdrawal</td>
                            <td>Marketing communications</td>
                        </tr>
                        <tr>
                            <td>Website Analytics</td>
                            <td>26 months</td>
                            <td>Service improvement</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Your Rights -->
            <div class="user-rights">
                <h2><i class="fas fa-user-check"></i> 7. Your Rights & Choices</h2>
                
                <div class="right-item">
                    <div class="right-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div>
                        <h3>Right to Access</h3>
                        <p>You can request a copy of the personal information we hold about you.</p>
                    </div>
                </div>
                
                <div class="right-item">
                    <div class="right-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div>
                        <h3>Right to Correction</h3>
                        <p>You can request correction of inaccurate or incomplete information.</p>
                    </div>
                </div>
                
                <div class="right-item">
                    <div class="right-icon">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <div>
                        <h3>Right to Deletion</h3>
                        <p>You can request deletion of your personal information in certain circumstances.</p>
                    </div>
                </div>
                
                <div class="right-item">
                    <div class="right-icon">
                        <i class="fas fa-ban"></i>
                    </div>
                    <div>
                        <h3>Right to Object</h3>
                        <p>You can object to processing of your data for marketing or other purposes.</p>
                    </div>
                </div>
                
                <div class="right-item">
                    <div class="right-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <div>
                        <h3>Right to Data Portability</h3>
                        <p>You can request your data in a structured, commonly used format.</p>
                    </div>
                </div>
                
                <div class="right-item">
                    <div class="right-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <h3>Marketing Preferences</h3>
                        <p>You can opt-out of marketing communications at any time using the unsubscribe link in emails or contacting us directly.</p>
                    </div>
                </div>
            </div>

            <!-- Cookies & Tracking -->
            <div class="privacy-section">
                <h2><i class="fas fa-cookie-bite"></i> 8. Cookies & Tracking Technologies</h2>
                
                <p>We use cookies and similar technologies to:</p>
                <ul>
                    <li>Remember your preferences</li>
                    <li>Analyze website traffic</li>
                    <li>Personalize content</li>
                    <li>Improve user experience</li>
                </ul>
                
                <p>For detailed information about the cookies we use, please see our <a href="cookie_policy.php">Cookie Policy</a>.</p>
                
                <div class="highlight-box">
                    <p>You can control cookies through your browser settings. However, disabling cookies may affect website functionality.</p>
                </div>
            </div>

            <!-- International Data Transfers -->
            <div class="privacy-section">
                <h2><i class="fas fa-globe"></i> 9. International Data Transfers</h2>
                
                <p>As a travel service provider, we may transfer your data to:</p>
                <ul>
                    <li>Service providers in other countries</li>
                    <li>Travel partners worldwide</li>
                    <li>Cloud storage with global servers</li>
                </ul>
                
                <p>We ensure appropriate safeguards are in place for international data transfers, including:</p>
                <ul>
                    <li>Standard contractual clauses</li>
                    <li>Adequacy decisions</li>
                    <li>Consent for specific transfers</li>
                </ul>
            </div>

            <!-- Children's Privacy -->
            <div class="privacy-section">
                <h2><i class="fas fa-child"></i> 10. Children's Privacy</h2>
                
                <p>Our services are not directed to children under 16. We do not knowingly collect personal information from children under 16.</p>
                
                <p>If you believe we have collected information from a child under 16, please contact us immediately so we can take appropriate action.</p>
            </div>

            <!-- Changes to This Policy -->
            <div class="privacy-section">
                <h2><i class="fas fa-sync-alt"></i> 11. Changes to This Policy</h2>
                
                <p>We may update this Privacy Policy periodically. We will notify you of significant changes by:</p>
                <ul>
                    <li>Posting the new policy on our website</li>
                    <li>Sending email notifications (if required by law)</li>
                    <li>Updating the "Effective Date" at the top</li>
                </ul>
                
                <p>We encourage you to review this policy periodically to stay informed about how we protect your information.</p>
            </div>

            <!-- Contact Information -->
            <div class="contact-privacy">
                <h2><i class="fas fa-headset"></i> 12. Contact Us</h2>
                
                <p>If you have questions about this Privacy Policy or your personal information:</p>
                
                <div style="margin: 30px 0;">
                    <p><strong>Data Protection Officer:</strong></p>
                    <p>Ghion Travel Privacy Team</p>
                    <p><i class="fas fa-envelope"></i> <a href="mailto:privacy@ghiontravel.com">privacy@ghiontravel.com</a></p>
                    <p><i class="fas fa-phone"></i> +251 11 123 4567</p>
                    <p><i class="fas fa-map-marker-alt"></i> Addis Ababa, Ethiopia</p>
                </div>
                
                <p>For data access requests or privacy concerns, please use our <a href="contact.php">Contact Form</a> and select "Privacy Request".</p>
                
                <p style="margin-top: 20px; font-size: 14px;">
                    <i class="fas fa-clock"></i> Response Time: We aim to respond to privacy requests within 30 days.
                </p>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <a href="#" class="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </a>

    <!-- Print Button -->
    <button class="print-btn" onclick="window.print()">
        <i class="fas fa-print"></i> Print Policy
    </button>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Ghion Travel. All rights reserved.</p>
            <p>
                <a href="terms.php" style="color: #bbb;">Terms & Conditions</a> | 
                <a href="privacy.php" style="color: #bbb;">Privacy Policy</a> | 
                <a href="cookie_policy.php" style="color: #bbb;">Cookie Policy</a> |
                <a href="gdpr.php" style="color: #bbb;">GDPR Compliance</a>
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

        // Section navigation
        const privacySections = document.querySelectorAll('.privacy-section h2');
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
                z-index: 999;
            ">
                <h4 style="margin-bottom: 15px; color: #667eea;">
                    <i class="fas fa-stream"></i> Quick Navigation
                </h4>
                <div id="section-links" style="max-height: 400px; overflow-y: auto; font-size: 14px;"></div>
            </div>
        `;
        
        document.body.appendChild(sectionNav);
        const sectionLinks = document.getElementById('section-links');
        
        privacySections.forEach((section) => {
            const sectionNumber = section.textContent.match(/^\d+/)[0];
            const sectionTitle = section.textContent.replace(/^\d+\.\s*/, '');
            
            const link = document.createElement('a');
            link.href = '#';
            link.innerHTML = `<span style="color: #667eea; font-weight: bold;">${sectionNumber}.</span> ${sectionTitle}`;
            link.style.cssText = `
                display: block;
                padding: 8px 0;
                color: #555;
                text-decoration: none;
                border-bottom: 1px solid #eee;
                font-size: 13px;
                line-height: 1.4;
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

        // Show/hide navigation on desktop
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
            threshold: 0.2
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const sectionId = entry.target.querySelector('h2').textContent;
                const correspondingLinks = sectionLinks.querySelectorAll('a');
                
                correspondingLinks.forEach(link => {
                    if (link.textContent.includes(sectionId.replace(/^\d+\.\s*/, ''))) {
                        if (entry.isIntersecting) {
                            link.style.color = '#667eea';
                            link.style.fontWeight = '600';
                            link.style.background = '#f0f7ff';
                            link.style.padding = '8px 10px';
                            link.style.borderRadius = '5px';
                        } else {
                            link.style.color = '#555';
                            link.style.fontWeight = 'normal';
                            link.style.background = 'transparent';
                            link.style.padding = '8px 0';
                        }
                    }
                });
            });
        }, observerOptions);

        document.querySelectorAll('.privacy-section').forEach(section => {
            observer.observe(section);
        });

        // Copy email functionality
        const emailLinks = document.querySelectorAll('a[href^="mailto:"]');
        emailLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                const email = link.href.replace('mailto:', '');
                navigator.clipboard.writeText(email).then(() => {
                    const originalText = link.innerHTML;
                    link.innerHTML = '<i class="fas fa-check"></i> Copied!';
                    setTimeout(() => {
                        link.innerHTML = originalText;
                    }, 2000);
                });
            });
        });

        // Print styles
        const style = document.createElement('style');
        style.textContent = `
            @media print {
                @page {
                    margin: 2cm;
                }
                
                body {
                    font-size: 12pt;
                    line-height: 1.5;
                }
                
                h1, h2, h3 {
                    page-break-after: avoid;
                }
                
                .highlight-box, .user-rights, .contact-privacy {
                    page-break-inside: avoid;
                }
                
                table {
                    page-break-inside: avoid;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>