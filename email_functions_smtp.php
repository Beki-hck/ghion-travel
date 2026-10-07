<?php
/**
 * Enhanced Email Notification System with SMTP Support
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EnhancedEmailNotifier {
    private $siteName;
    private $siteEmail;
    private $useSMTP;
    private $smtpConfig;
    
    public function __construct($siteName = 'Ghion Travel', $siteEmail = 'noreply@ghiontours.com') {
        $this->siteName = $siteName;
        $this->siteEmail = $siteEmail;
        $this->useSMTP = false;
        $this->smtpConfig = [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => getenv('SMTP_USERNAME') ?: '',
            'password' => getenv('SMTP_PASSWORD') ?: '',
            'encryption' => 'tls'
        ];
        
        // Load PHPMailer if available
        if (file_exists('vendor/autoload.php')) {
            require 'vendor/autoload.php';
            $this->useSMTP = true;
        }
    }
    
    /**
     * Configure SMTP settings
     */
    public function setSMTPConfig($host, $port, $username, $password, $encryption = 'tls') {
        $this->smtpConfig = [
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
            'encryption' => $encryption
        ];
        $this->useSMTP = true;
    }
    
    /**
     * Send booking confirmation to customer
     */
    public function sendBookingConfirmation($bookingData, $customerEmail) {
        $subject = "Booking Confirmation - {$this->siteName}";
        $message = $this->getBookingConfirmationTemplate($bookingData);
        
        return $this->sendEmail($customerEmail, $subject, $message);
    }
    
    /**
     * Enhanced email sending with SMTP support
     */
    private function sendEmail($to, $subject, $message, $headers = '') {
        // Log email attempt
        $this->logEmail($to, $subject, 'attempt');
        
        if ($this->useSMTP && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return $this->sendEmailSMTP($to, $subject, $message);
        } else {
            return $this->sendEmailBasic($to, $subject, $message, $headers);
        }
    }
    
    /**
     * Send email using PHPMailer with SMTP
     */
    private function sendEmailSMTP($to, $subject, $message) {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtpConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpConfig['username'];
            $mail->Password = $this->smtpConfig['password'];
            $mail->SMTPSecure = $this->smtpConfig['encryption'];
            $mail->Port = $this->smtpConfig['port'];
            
            // Recipients
            $mail->setFrom($this->siteEmail, $this->siteName);
            $mail->addAddress($to);
            $mail->addReplyTo($this->siteEmail, $this->siteName);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = strip_tags($message);
            
            $mail->send();
            $this->logEmail($to, $subject, 'sent_smtp');
            return true;
            
        } catch (Exception $e) {
            error_log("SMTP Email error: " . $e->getMessage());
            $this->logEmail($to, $subject, 'failed_smtp');
            return false;
        }
    }
    
    /**
     * Send email using basic PHP mail()
     */
    private function sendEmailBasic($to, $subject, $message, $headers = '') {
        $defaultHeaders = [
            'From: ' . $this->siteName . ' <' . $this->siteEmail . '>',
            'Reply-To: ' . $this->siteEmail,
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion(),
            'MIME-Version: 1.0'
        ];
        
        if ($headers) {
            if (is_array($headers)) {
                $defaultHeaders = array_merge($defaultHeaders, $headers);
            } else {
                $defaultHeaders[] = $headers;
            }
        }
        
        $headers = implode("\r\n", $defaultHeaders);
        
        try {
            $result = mail($to, $subject, $message, $headers);
            
            if ($result) {
                $this->logEmail($to, $subject, 'sent_basic');
            } else {
                $this->logEmail($to, $subject, 'failed_basic');
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Basic Email error: " . $e->getMessage());
            $this->logEmail($to, $subject, 'error');
            return false;
        }
    }
    
    /**
     * Log email attempts
     */
    private function logEmail($to, $subject, $status) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $to,
            'subject' => $subject,
            'status' => $status,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $logFile = 'email_log.txt';
        file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    
    // Email templates (same as before)
    private function getBookingConfirmationTemplate($bookingData) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #2a6496, #4b86b4); color: white; padding: 30px; text-align: center; }
                .content { background: #f9f9f9; padding: 30px; }
                .booking-details { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Booking Confirmed!</h1>
                    <p>Thank you for choosing {$this->siteName}</p>
                </div>
                
                <div class='content'>
                    <p>Dear {$bookingData['name']},</p>
                    <p>Your tour booking has been confirmed. Here are your booking details:</p>
                    
                    <div class='booking-details'>
                        <div class='detail-row'>
                            <strong>Booking Reference:</strong>
                            <span>{$bookingData['bookingReference']}</span>
                        </div>
                        <div class='detail-row'>
                            <strong>Tour Package:</strong>
                            <span>{$bookingData['package']}</span>
                        </div>
                        <div class='detail-row'>
                            <strong>Travel Date:</strong>
                            <span>{$bookingData['tourDate']}</span>
                        </div>
                        <div class='detail-row'>
                            <strong>Passengers:</strong>
                            <span>{$bookingData['passengers']}</span>
                        </div>
                        <div class='detail-row'>
                            <strong>Total Amount:</strong>
                            <span>\${$bookingData['total_price']}</span>
                        </div>
                        <div class='detail-row'>
                            <strong>Payment Method:</strong>
                            <span>{$bookingData['paymentMethod']}</span>
                        </div>
                    </div>
                    
                    <p><strong>What's Next?</strong></p>
                    <ul>
                        <li>You will receive a reminder email 3 days before your tour</li>
                        <li>Our team will contact you to confirm pickup details</li>
                        <li>Keep this booking reference for any inquiries</li>
                    </ul>
                    
                    <p>If you have any questions, please contact us at {$this->siteEmail}</p>
                </div>
                
                <div class='footer'>
                    <p>&copy; " . date('Y') . " {$this->siteName}. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

// Alternative: Simple email function without dependencies
function sendSimpleEmail($to, $subject, $message, $from_email = null, $from_name = null) {
    $from_email = $from_email ?: 'noreply@ghiontours.com';
    $from_name = $from_name ?: 'Ghion Travel';
    
    $headers = [
        'From: ' . $from_name . ' <' . $from_email . '>',
        'Reply-To: ' . $from_email,
        'Content-Type: text/html; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion(),
        'MIME-Version: 1.0'
    ];
    
    $headers = implode("\r\n", $headers);
    
    // Log attempt
    file_put_contents('email_log.txt', 
        date('Y-m-d H:i:s') . " - Attempting to send to: $to, Subject: $subject\n", 
        FILE_APPEND | LOCK_EX
    );
    
    $result = mail($to, $subject, $message, $headers);
    
    if ($result) {
        file_put_contents('email_log.txt', 
            date('Y-m-d H:i:s') . " - ✅ Email sent successfully to: $to\n", 
            FILE_APPEND | LOCK_EX
        );
    } else {
        file_put_contents('email_log.txt', 
            date('Y-m-d H:i:s') . " - ❌ Failed to send email to: $to\n", 
            FILE_APPEND | LOCK_EX
        );
    }
    
    return $result;
}

// Simple booking email function
function sendSimpleBookingEmail($bookingData, $customerEmail) {
    $subject = "Booking Confirmation - Ghion Travel";
    
    $message = "
    Dear {$bookingData['name']},

    Your tour booking has been confirmed!

    Booking Reference: {$bookingData['bookingReference']}
    Tour Package: {$bookingData['package']}
    Travel Date: {$bookingData['tourDate']}
    Passengers: {$bookingData['passengers']}
    Total Amount: \${$bookingData['total_price']}
    Payment Method: {$bookingData['paymentMethod']}

    What's Next?
    - You will receive a reminder email before your tour
    - Our team will contact you for pickup details
    - Keep this booking reference for inquiries

    Thank you for choosing Ghion Travel!

    Best regards,
    Ghion Travel Team
    ";
    
    return sendSimpleEmail($customerEmail, $subject, $message);
}
?>