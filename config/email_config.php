<?php
// Email configuration using PHPMailer (more reliable)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendBookingEmailPHPMailer($bookingData) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Or your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USERNAME') ?: ''; // set in environment
        $mail->Password = getenv('SMTP_PASSWORD') ?: ''; // set in environment
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('noreply@ghiontours.com', 'Ghion Travel');
        $mail->addAddress($bookingData['customer_email'], $bookingData['customer_name']);
        $mail->addReplyTo('info@ghiontours.com', 'Ghion Travel');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Tour Booking Confirmation - ' . $bookingData['booking_reference'];
        
        $mail->Body = generateEmailTemplate($bookingData);
        $mail->AltBody = generatePlainTextEmail($bookingData);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function generateEmailTemplate($bookingData) {
    // Same HTML template as above
    return "...";
}

function generatePlainTextEmail($bookingData) {
    return "
    BOOKING CONFIRMATION - {$bookingData['booking_reference']}
    
    Dear {$bookingData['customer_name']},
    
    Thank you for booking with Ghion Travel!
    
    TOUR DETAILS:
    Package: {$bookingData['package_name']}
    Destination: {$bookingData['package_destination']}
    Duration: {$bookingData['package_duration']}
    Tour Date: {$bookingData['tour_date']}
    Passengers: {$bookingData['number_of_passengers']}
    Total Amount: \${$bookingData['total_amount']}
    
    Please save your booking reference: {$bookingData['booking_reference']}
    
    Contact us: +251 11 123 4567 | info@ghiontours.com
    
    Best regards,
    Ghion Travel Team
    ";
}
?>