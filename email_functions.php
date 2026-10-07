<?php
// Include config only once
if (!defined('SMTP_HOST')) {
    require 'email_config.php';
}

function sendBookingConfirmationEmail($booking) {
    return sendBookingEmail($booking, 'confirmation');
}

function sendBookingCancellationEmail($booking, $reason = '') {
    $booking['admin_notes'] = $reason;
    return sendBookingEmail($booking, 'cancellation');
}

function sendBookingEmail($booking, $type = 'confirmation') {
    // Convert MongoDB BSONDocument to array if needed
    if (is_object($booking)) {
        $booking = convertBSONToArray($booking);
    }
    
    // Extract data from booking array
    $customerInfo = isset($booking['customer_info']) ? 
                   (is_object($booking['customer_info']) ? convertBSONToArray($booking['customer_info']) : $booking['customer_info']) : [];
    $tourDetails = isset($booking['tour_details']) ? 
                  (is_object($booking['tour_details']) ? convertBSONToArray($booking['tour_details']) : $booking['tour_details']) : [];
    $paymentInfo = isset($booking['payment_info']) ? 
                  (is_object($booking['payment_info']) ? convertBSONToArray($booking['payment_info']) : $booking['payment_info']) : [];
    $additionalServices = isset($booking['additional_services']) ? 
                         (is_object($booking['additional_services']) ? convertBSONToArray($booking['additional_services']) : $booking['additional_services']) : [];
    
    $customerEmail = $customerInfo['email'] ?? '';
    $customerName = ($customerInfo['first_name'] ?? '') . ' ' . ($customerInfo['last_name'] ?? '');
    $bookingReference = $booking['booking_reference'] ?? '';
    $packageName = $tourDetails['package_name'] ?? '';
    $packagePrice = floatval($tourDetails['package_price'] ?? 0);
    $tourDate = $tourDetails['tour_date'] ?? '';
    $pickupLocation = $tourDetails['pickup_location'] ?? 'To be confirmed'; // ADDED THIS LINE
    $totalAmount = floatval($paymentInfo['total_amount'] ?? 0);
    $passengers = intval($tourDetails['passengers'] ?? 1);
    $adminNotes = $booking['admin_notes'] ?? '';
    
    // Extract hotel and car details - handle both object and array formats
    $selectedHotel = [];
    $selectedCar = [];
    
    if (isset($additionalServices['selected_hotel'])) {
        $selectedHotel = is_object($additionalServices['selected_hotel']) ? 
                        convertBSONToArray($additionalServices['selected_hotel']) : 
                        $additionalServices['selected_hotel'];
    }
    
    if (isset($additionalServices['selected_car'])) {
        $selectedCar = is_object($additionalServices['selected_car']) ? 
                      convertBSONToArray($additionalServices['selected_car']) : 
                      $additionalServices['selected_car'];
    }
    
    $hotelPrice = floatval($selectedHotel['price'] ?? 0);
    $carPrice = floatval($selectedCar['price'] ?? 0);
    $hotelName = $selectedHotel['name'] ?? '';
    $carName = $selectedCar['name'] ?? '';
    
    if (empty($customerEmail)) {
        error_log("No email address found for booking: " . $bookingReference);
        return false;
    }
    
    if (EMAIL_DEBUG) {
        error_log("Attempting to send $type email to: $customerEmail");
    }
    
    // Prepare data for email templates with all details - ADDED PICKUP LOCATION
    $emailData = [
        'customer_email' => $customerEmail,
        'customer_name' => trim($customerName) ?: 'Customer',
        'booking_reference' => $bookingReference,
        'package_name' => $packageName,
        'package_price' => $packagePrice,
        'tour_date' => $tourDate,
        'pickup_location' => $pickupLocation, // ADDED THIS LINE
        'total_amount' => $totalAmount,
        'passengers' => $passengers,
        'hotel_name' => $hotelName,
        'hotel_price' => $hotelPrice,
        'car_name' => $carName,
        'car_price' => $carPrice,
        'admin_notes' => $adminNotes,
        'selected_hotel' => $selectedHotel,
        'selected_car' => $selectedCar
    ];
    
    return sendEmailWithRetry($customerEmail, trim($customerName) ?: 'Customer', $emailData, $type);
}
// Helper function to convert MongoDB BSON objects to arrays
function convertBSONToArray($bson) {
    if (is_object($bson)) {
        // Convert to JSON and back to array to handle all BSON types
        return json_decode(json_encode($bson), true);
    }
    return $bson;
}

function sendEmailWithRetry($to, $name, $data, $type) {
    // Method 1: Try PHPMailer with port 587
    if (sendEmailWithPHPMailer($to, $name, $data, $type, SMTP_PORT)) {
        return true;
    }
    
    // Method 2: Try PHPMailer with port 465 (SSL)
    if (sendEmailWithPHPMailer($to, $name, $data, $type, 465)) {
        return true;
    }
    
    // Method 3: Try file-based fallback
    return saveEmailToFile($to, $name, $data, $type);
}

function loadPHPMailer() {
    $paths = [
        'vendor/autoload.php',
        'phpmailer/src/PHPMailer.php',
        'PHPMailer-master/src/PHPMailer.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            if ($path === 'vendor/autoload.php') {
                require_once $path;
            } else {
                require_once 'phpmailer/src/PHPMailer.php';
                require_once 'phpmailer/src/SMTP.php';
                require_once 'phpmailer/src/Exception.php';
            }
            return true;
        }
    }
    return false;
}

function sendEmailWithPHPMailer($to, $name, $data, $type, $port = 587) {
    // Load PHPMailer
    if (!loadPHPMailer()) {
        error_log("❌ PHPMailer not found.");
        return false;
    }
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->SMTPDebug = 0; // Set to 2 for debugging, 0 for production
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->Port = $port;
        
        // Set encryption based on port
        if ($port == 465) {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        $mail->Timeout = 15;
        
        // SSL options for local development
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Recipients
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to, $name);
        $mail->addReplyTo(FROM_EMAIL, FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        
        if ($type === 'confirmation') {
            $mail->Subject = 'Booking Confirmation - ' . ($data['booking_reference'] ?? '');
            $mail->Body = generateConfirmationEmail($data);
        } else {
            $mail->Subject = 'Booking Cancellation - ' . ($data['booking_reference'] ?? '');
            $mail->Body = generateCancellationEmail($data);
        }
        
        $mail->AltBody = strip_tags($mail->Body);
        
        $mail->send();
        
        error_log("✅ Email sent successfully to: $to using port $port");
        return true;
        
    } catch (Exception $e) {
        $errorMsg = "❌ Email failed on port $port: " . $e->getMessage();
        error_log($errorMsg);
        
        // Save debug information
        saveDebugInfo($to, $errorMsg, $port);
        
        return false;
    }
}

function saveEmailToFile($to, $name, $data, $type) {
    // Fallback: Save email to file for manual sending
    $emailContent = $type === 'confirmation' ? generateConfirmationEmail($data) : generateCancellationEmail($data);
    $filename = 'pending_emails/email_' . date('Y-m-d_H-i-s') . '.html';
    
    // Create pending_emails directory if it doesn't exist
    if (!is_dir('pending_emails')) {
        mkdir('pending_emails', 0777, true);
    }
    
    $fileContent = "TO: $to\n";
    $fileContent .= "NAME: $name\n";
    $fileContent .= "SUBJECT: " . ($type === 'confirmation' ? 'Booking Confirmation' : 'Booking Cancellation') . "\n";
    $fileContent .= "DATE: " . date('Y-m-d H:i:s') . "\n";
    $fileContent .= "CONTENT:\n" . $emailContent;
    
    if (file_put_contents($filename, $fileContent)) {
        error_log("✅ Email saved to file: $filename");
        return true;
    }
    
    return false;
}

function saveDebugInfo($to, $error, $port) {
    $debugInfo = "
EMAIL DEBUG INFORMATION
======================
Timestamp: " . date('Y-m-d H:i:s') . "
To: $to
Error: $error
Port: $port

SERVER INFORMATION:
PHP Version: " . PHP_VERSION . "
OS: " . PHP_OS . "
SMTP Host: " . SMTP_HOST . "
SMTP Port: $port

ERROR DETAILS:
$error
";
    
    file_put_contents('email_connection_debug.txt', $debugInfo);
}
function generateConfirmationEmail($data) {
    // Extract the components for proper calculation
    $packagePrice = floatval($data['package_price'] ?? 0);
    $passengers = intval($data['passengers'] ?? 1);
    $hotelPrice = floatval($data['hotel_price'] ?? 0);
    $carPrice = floatval($data['car_price'] ?? 0);
    $hotelName = $data['hotel_name'] ?? '';
    $carName = $data['car_name'] ?? '';
    
    // Get pickup location from tour details
    $pickupLocation = $data['pickup_location'] ?? 'To be confirmed';
    $tourDate = $data['tour_date'] ?? 'To be confirmed';
    
    if ($tourDate !== 'To be confirmed' && !empty($tourDate)) {
        $tourDate = date('F j, Y', strtotime($tourDate));
    }
    
    // Calculate total correctly: (package * passengers) + hotel + car
    $calculatedTotal = ($packagePrice * $passengers) + $hotelPrice + $carPrice;
    $displayTotal = floatval($data['total_amount'] ?? $calculatedTotal);
    
    // Additional services section
    $servicesHtml = '';
    $hasServices = false;
    
    if (!empty($hotelName) || !empty($carName)) {
        $hasServices = true;
        $servicesHtml = '
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 10px; margin: 25px 0; color: white;">
            <h3 style="color: white; margin-bottom: 20px; font-size: 20px; display: flex; align-items: center; gap: 10px;">
                <span style="background: white; color: #764ba2; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px;">✨</span>
                Additional Services
            </h3>
            <div style="display: grid; gap: 15px;">';
        
        if (!empty($hotelName)) {
            $servicesHtml .= '
                <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px; backdrop-filter: blur(10px);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="background: white; color: #764ba2; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px;">🏨</span>
                            <span style="font-weight: 600;">Hotel Accommodation</span>
                        </div>
                        <span style="font-weight: 700; font-size: 18px;">$' . number_format($hotelPrice, 2) . '</span>
                    </div>
                    <div style="padding-left: 34px; font-size: 14px; opacity: 0.9;">
                        ' . $hotelName . '
                    </div>
                </div>';
        }
        
        if (!empty($carName)) {
            $servicesHtml .= '
                <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px; backdrop-filter: blur(10px);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="background: white; color: #764ba2; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px;">🚗</span>
                            <span style="font-weight: 600;">Car Rental</span>
                        </div>
                        <span style="font-weight: 700; font-size: 18px;">$' . number_format($carPrice, 2) . '</span>
                    </div>
                    <div style="padding-left: 34px; font-size: 14px; opacity: 0.9;">
                        ' . $carName . '
                    </div>
                </div>';
        }
        
        $servicesHtml .= '
            </div>
        </div>';
    }
    
    // Pickup information section
    $pickupHtml = '
    <div style="background: linear-gradient(135deg, #f093fb 0%, #1A2E2AFF 100%); padding: 25px; border-radius: 10px; margin: 25px 0; color: white;">
        <h3 style="color: white; margin-bottom: 20px; font-size: 20px; display: flex; align-items: center; gap: 10px;">
            <span style="background: white; color: #1A2E2AFF; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px;">📍</span>
            Pickup Information
        </h3>
        <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 8px; backdrop-filter: blur(10px);">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <div style="background: white; color: #322829FF; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                    🗺️
                </div>
                <div>
                    <div style="font-size: 14px; opacity: 0.9;">Pickup Location</div>
                    <div style="font-size: 18px; font-weight: 700;">' . $pickupLocation . '</div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="background: white; color: #4D3A3DFF; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                    ⏰
                </div>
                <div>
                    <div style="font-size: 14px; opacity: 0.9;">Arrival Time</div>
                    <div style="font-size: 18px; font-weight: 700;">15 minutes before scheduled pickup</div>
                </div>
            </div>
        </div>
    </div>';
    
   
    
    // Contact information section
    $contactHtml = '
    <div style="background: #f8f9fa; padding: 25px; border-radius: 10px; margin: 25px 0; border: 2px solid #e9ecef;">
        <h3 style="color: #2a6496; margin-bottom: 20px; font-size: 20px; display: flex; align-items: center; gap: 10px;">
            <span style="background: #2a6496; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px;">📞</span>
            Need Help? Contact Us
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div style="background: white; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #e9ecef;">
                <div style="font-size: 24px; margin-bottom: 10px; color: #2a6496;">📱</div>
                <div style="font-weight: 600; color: #2a6496; margin-bottom: 5px;">Phone</div>
                <div>+251 11 123 4567</div>
            </div>
            <div style="background: white; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #e9ecef;">
                <div style="font-size: 24px; margin-bottom: 10px; color: #2a6496;">✉️</div>
                <div style="font-weight: 600; color: #2a6496; margin-bottom: 5px;">Email</div>
                <div>Ghiontravel@gmail.com</div>
            </div>
            <div style="background: white; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #e9ecef;">
                <div style="font-size: 24px; margin-bottom: 10px; color: #2a6496;">🏢</div>
                <div style="font-weight: 600; color: #2a6496; margin-bottom: 5px;">Office Hours</div>
                <div>Mon - Sun<br>8:00 AM - 6:00 PM</div>
            </div>
        </div>
    </div>';
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap");
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: "Poppins", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 20px;
                min-height: 100vh;
            }
            
            .container {
                max-width: 650px;
                margin: 0 auto;
                background: white;
                border-radius: 20px;
                overflow: hidden;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            }
            
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 50px 40px;
                text-align: center;
                position: relative;
                overflow: hidden;
            }
            
            .header::before {
                content: "";
                position: absolute;
                top: -50%;
                left: -50%;
                width: 200%;
                height: 200%;
                background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
                background-size: 20px 20px;
                opacity: 0.1;
                animation: float 20s linear infinite;
            }
            
            @keyframes float {
                0% { transform: translate(0, 0) rotate(0deg); }
                100% { transform: translate(-20px, -20px) rotate(360deg); }
            }
            
            .confirmation-badge {
    background: linear-gradient(135deg, 
        rgba(255, 255, 255, 0.3) 0%, 
        rgba(255, 255, 255, 0.1) 100%);
    backdrop-filter: blur(20px);
    border-radius: 70px;
    padding: 22px 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 18px;
    margin: 0 auto 35px auto;
    font-weight: 800;
    font-size: 20px;
    letter-spacing: 0.5px;
    border: 3px solid rgba(255, 255, 255, 0.5);
    box-shadow: 
        0 15px 35px rgba(0, 0, 0, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.4),
        inset 0 -1px 0 rgba(0, 0, 0, 0.1);
    transform: translateY(0);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
    min-width: 350px;
    max-width: 450px;
    text-transform: uppercase;
    color: white;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}
            
            .header h1 {
                font-size: 36px;
                font-weight: 700;
                margin-bottom: 15px;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            }
            
            .header-subtitle {
                font-size: 18px;
                opacity: 0.9;
                max-width: 500px;
                margin: 0 auto;
            }
            
            .content {
                padding: 40px;
            }
            
            .greeting {
                font-size: 24px;
                color: #2a6496;
                margin-bottom: 20px;
                font-weight: 600;
            }
            
            .welcome-message {
                font-size: 16px;
                color: #555;
                margin-bottom: 30px;
                line-height: 1.8;
            }
            
            .booking-card {
                background: white;
                border-radius: 15px;
                padding: 30px;
                margin: 25px 0;
                border: 2px solid #e9ecef;
                box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            }
            
            .booking-card-title {
                color: #2a6496;
                font-size: 22px;
                margin-bottom: 25px;
                display: flex;
                align-items: center;
                gap: 12px;
                font-weight: 600;
            }
            
            .detail-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 15px 0;
                border-bottom: 1px solid #e9ecef;
            }
            
            .detail-row:last-child {
                border-bottom: none;
            }
            
            .detail-label {
                color: #666;
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .detail-value {
                color: #2a6496;
                font-weight: 600;
                font-size: 16px;
            }
            
            .total-amount {
                color: #e74c3c;
                font-size: 24px;
                font-weight: 700;
            }
            
            .icon-circle {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 18px;
                flex-shrink: 0;
            }
            
            .next-steps {
                background: linear-gradient(135deg, #294231FF 0%, #3C544FFF 100%);
                padding: 25px;
                border-radius: 10px;
                margin: 25px 0;
                color: white;
            }
            
            .next-steps-title {
                color: white;
                font-size: 10px;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .steps-list {
                list-style: none;
                padding-left: 0;
            }
            
            .steps-list li {
                margin-bottom: 15px;
                padding-left: 30px;
                position: relative;
            }
            
            .steps-list li::before {
                content: "✓";
                position: absolute;
                left: 0;
                top: 0;
                background: white;
                color: #43e97b;
                width: 20px;
                height: 20px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: bold;
            }
            
            .footer {
                background: #2c3e50;
                color: white;
                padding: 40px;
                text-align: center;
            }
            
            .footer-logo {
                font-size: 24px;
                font-weight: 700;
                margin-bottom: 15px;
                color: white;
            }
            
            .footer-contact {
                opacity: 0.8;
                font-size: 14px;
                margin-bottom: 10px;
            }
            
            @media (max-width: 600px) {
                .container {
                    border-radius: 15px;
                }
                
                .header {
                    padding: 30px 20px;
                }
                
                .header h1 {
                    font-size: 28px;
                }
                
                .content {
                    padding: 20px;
                }
                
                .detail-row {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 5px;
                }
                
                .booking-card {
                    padding: 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎉 Booking Confirmed!</h1>
                <p class="header-subtitle">Your adventure with Ghion Travel is officially confirmed!</p>
            </div>
            
            <div class="content">
                <h2 class="greeting">Hello ' . $data['customer_name'] . ',</h2>
                <p class="welcome-message">
                    We\'re thrilled to inform you that your booking has been confirmed and your payment has been processed successfully. 
                    Get ready for an unforgettable experience filled with adventure and amazing memories!
                </p>
                
                <div class="booking-card">
                    <h3 class="booking-card-title">
                        <div class="icon-circle" style="background: #2a6496; color: white;">📋</div>
                        Booking Summary
                    </h3>
                    
                    <div class="detail-row">
                        <span class="detail-label">
                            <span style="color: #2a6496;">🔖</span>
                            Booking Reference:
                        </span>
                        <span class="detail-value">' . $data['booking_reference'] . '</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">
                            <span style="color: #2a6496;">🏔️</span>
                            Tour Package:
                        </span>
                        <span class="detail-value">' . $data['package_name'] . '</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">
                            <span style="color: #2a6496;">📅</span>
                            Tour Date:
                        </span>
                        <span class="detail-value">' . $tourDate . '</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">
                            <span style="color: #2a6496;">👥</span>
                            Number of Passengers:
                        </span>
                        <span class="detail-value">' . $passengers . '</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">
                            <span style="color: #2C1E1EFF;">📍</span>
                            Pickup Location:
                        </span>
                        <span class="detail-value" style="color: #1C0F10FF; font-weight: 700;">' . $pickupLocation . '</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">
                            <span style="color: #e74c3c;">💰</span>
                            Total Payment Received:
                        </span>
                        <span class="detail-value total-amount">$' . number_format($displayTotal, 2) . '</span>
                    </div>
                </div>

                  <div class="confirmation-badge">
                    <span>✅</span>
                    Booking Confirmed
                </div>
                
                ' . ($hasServices ? $servicesHtml : '') . '
                
                ' . $pickupHtml . '
                
                
                <div class="next-steps">
                    <h3 class="next-steps-title">
                        <span style="background: white; color: #668872FF; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px;">📋</span>
                        What\'s Next?
                    </h3>
                    <ul class="steps-list">
                        <li>✓ You will receive a detailed itinerary 3 days before your tour</li>
                        <li>✓ Our tour guide will contact you 24 hours before departure</li>
                        <li>✓ Arrive at the pickup location 15 minutes before scheduled time</li>
                        <li>✓ Keep this booking reference for all communications</li>
                        <li>✓ Prepare your travel documents and get ready for adventure!</li>
                    </ul>
                </div>
                
                ' . $contactHtml . '
                
                <div style="text-align: center; margin: 30px 0; padding: 20px; background: linear-gradient(135deg, #fdfcfb 0%, #e2d1c3 100%); border-radius: 10px;">
                    <h3 style="color: #8B4513; margin-bottom: 15px; font-size: 20px; display: flex; align-items: center; justify-content: center; gap: 10px;">
                        <span>🎒</span>
                        Travel Preparation Tips
                    </h3>
                    <p style="color: #8B4513; margin-bottom: 10px;">
                        <strong>✓</strong> Wear comfortable walking shoes<br>
                        <strong>✓</strong> Bring sunscreen and a hat<br>
                        <strong>✓</strong> Keep hydrated throughout the tour<br>
                        <strong>✓</strong> Have your camera ready for amazing photos
                    </p>
                </div>
            </div>
            
            <div class="footer">
                <div class="footer-logo">Ghion Tour & Travel</div>
                <p class="footer-contact">
                    Addis Ababa, Ethiopia | +251 11 123 4567 | Ghiontravel@gmail.com
                </p>
                <p style="opacity: 0.6; font-size: 12px; margin-top: 15px;">
                    &copy; 2025 Ghion Tour and Travel. All rights reserved.<br>
                    Thank you for choosing us for your adventure!
                </p>
            </div>
        </div>
    </body>
    </html>';
}
function generateCancellationEmail($data) {
    $adminNotes = $data['admin_notes'] ?? '';
    
    // Get pickup location from tour details
    $pickupLocation = $data['pickup_location'] ?? 'To be confirmed';
    
    // Extract components for cancellation email too
    $packagePrice = floatval($data['package_price'] ?? 0);
    $passengers = intval($data['passengers'] ?? 1);
    $hotelPrice = floatval($data['hotel_price'] ?? 0);
    $carPrice = floatval($data['car_price'] ?? 0);
    $hotelName = $data['hotel_name'] ?? '';
    $carName = $data['car_name'] ?? '';
    $calculatedTotal = ($packagePrice * $passengers) + $hotelPrice + $carPrice;
    $displayTotal = floatval($data['total_amount'] ?? $calculatedTotal);
    
    // Generate services section for cancellation email
    $servicesHtml = '';
    if (!empty($hotelName) || !empty($carName)) {
        $servicesHtml = "
        <div style='background: #fff3cd; padding: 15px; border-radius: 6px; margin: 15px 0; border-left: 3px solid #ffc107;'>
            <h4 style='color: #856404; margin-bottom: 10px;'>Additional Services Included:</h4>";
        
        if (!empty($hotelName)) {
            $servicesHtml .= "<p style='margin: 5px 0;'><strong>Hotel:</strong> {$hotelName} ($" . number_format($hotelPrice, 2) . ")</p>";
        }
        
        if (!empty($carName)) {
            $servicesHtml .= "<p style='margin: 5px 0;'><strong>Car:</strong> {$carName} ($" . number_format($carPrice, 2) . ")</p>";
        }
        
        $servicesHtml .= "
        </div>";
    }
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <style>
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 0; 
                background: #f5f7fa;
            }
            .container { 
                max-width: 600px; 
                margin: 0 auto; 
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }
            .header { 
                background: #e74c3c; 
                color: white; 
                padding: 40px; 
                text-align: center; 
            }
            .content { 
                padding: 40px; 
            }
            .booking-details { 
                background: #f8f9fa; 
                padding: 25px; 
                border-radius: 8px; 
                margin: 25px 0; 
                border-left: 4px solid #e74c3c;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Booking Cancellation</h1>
                <p style='margin: 10px 0 0 0; opacity: 0.9;'>We're sorry to see you go</p>
            </div>
            
            <div class='content'>
                <h2 style='color: #2a6496; margin-bottom: 20px;'>Hello {$data['customer_name']},</h2>
                <p style='font-size: 16px; margin-bottom: 20px;'>We regret to inform you that your booking <strong>{$data['booking_reference']}</strong> for <strong>{$data['package_name']}</strong> has been cancelled.</p>
                
                " . (!empty($adminNotes) ? 
                "<div style='background: #fff3cd; padding: 15px; border-radius: 6px; margin: 20px 0;'>
                    <strong>Reason:</strong> {$adminNotes}
                </div>" : "") . "
                
                <div class='booking-details'>
                    <h3 style='color: #5F4A48FF; margin-bottom: 15px;'>Booking Details</h3>
                    <p><strong>Booking Reference:</strong> {$data['booking_reference']}</p>
                    <p><strong>Package:</strong> {$data['package_name']}</p>
                    <p><strong>Passengers:</strong> {$passengers}</p>
                    <p><strong>Pickup Location:</strong> {$pickupLocation}</p>
                    {$servicesHtml}
                    <p><strong>Total Amount:</strong> $" . number_format($displayTotal, 2) . "</p>
                    <p><strong>Status:</strong> <span style='color: #684844FF; font-weight: bold;'>CANCELLED</span></p>
                </div>
                
                <p>If you believe this is a mistake or have any questions, please contact us immediately:</p>
                <div style='background: #e8f4fc; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 5px 0;'><strong>Phone:</strong> +251 11 123 4567</p>
                    <p style='margin: 5px 0;'><strong>Email:</strong> Ghiontravel@gmail.com</p>
                </div>
                
                <p style='color: #666; font-size: 14px;'>If this cancellation was unexpected, please contact our support team for assistance.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>