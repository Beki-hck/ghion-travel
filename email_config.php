<?php
// Prevent multiple definitions
if (!defined('SMTP_HOST')) {
    // Email Configuration with multiple options
    define('SMTP_HOST', 'smtp.gmail.com');
    define('SMTP_PORT', 587);
    define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
    define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
    define('FROM_EMAIL', getenv('FROM_EMAIL') ?: 'noreply@ghiontours.com');
    define('FROM_NAME', 'Ghion Travel');
    
    // Try different ports if 587 fails
    define('SMTP_PORT_ALT', 465);
    
    // Debug settings
    define('EMAIL_DEBUG', true);
}
?>