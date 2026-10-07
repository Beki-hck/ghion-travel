<?php
session_start();
require_once 'vendor/autoload.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Include your existing email functions
if (!defined('SMTP_HOST')) {
    require 'email_config.php';
}
require 'email_functions.php'; 

// Simple email function for contact replies
function sendContactReplyEmail($toEmail, $toName, $subject, $message, $adminName = "Admin") {
    $emailData = [
        'customer_email' => $toEmail,
        'customer_name' => $toName,
        'subject' => $subject,
        'message' => $message,
        'admin_name' => $adminName
    ];
    
    return sendContactEmailWithPHPMailer($toEmail, $toName, $emailData, SMTP_PORT);
}

function sendContactEmailWithPHPMailer($to, $name, $data, $port = 587) {
    if (!loadPHPMailer()) {
        error_log("❌ PHPMailer not found for contact reply.");
        return false;
    }
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings 
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->Port = $port;
        
        if ($port == 465) {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        $mail->Timeout = 15;
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
        $mail->Subject = 'Re: ' . $data['subject'];
        $mail->Body = generateContactReplyEmail($data);
        $mail->AltBody = strip_tags($mail->Body);
        
        $mail->send();
        
        error_log("✅ Contact reply email sent successfully to: $to");
        return true;
        
    } catch (Exception $e) {
        error_log("❌ Contact reply email failed: " . $e->getMessage());
        return false;
    }
}

function generateContactReplyEmail($data) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2a6496; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .admin-reply { background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Ghion Travel - Response to Your Inquiry</h2>
            </div>
            <div class='content'>
                <p>Dear {$data['customer_name']},</p>
                
                <p>Thank you for contacting Ghion Travel. Here is our response to your message:</p>
                
                <div class='admin-reply'>
                    " . nl2br(htmlspecialchars($data['message'])) . "
                </div>
                
                <p>If you have any further questions, please don't hesitate to contact us.</p>
                
                <p>Best regards,<br>
                <strong>{$data['admin_name']}</strong><br>
                Ghion Travel Team</p>
                
                <hr>
                <p><small>Phone: +251 11 123 4567<br>
                Email: info@ghiontravel.com<br>
                Address: Addis Ababa, Ethiopia</small></p>
            </div>
        </div>
    </body>
    </html>
    ";
}

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $contactsCollection = $database->selectCollection('contacts');
    $usersCollection = $database->selectCollection('users');
    $bookingsCollection = $database->selectCollection('bookings');
    
    // Handle actions
    if (isset($_GET['action']) && isset($_GET['id'])) {
        $contactId = new MongoDB\BSON\ObjectId($_GET['id']);
        
        switch ($_GET['action']) {
            case 'mark_read':
                $contactsCollection->updateOne(
                    ['_id' => $contactId],
                    ['$set' => ['status' => 'read']]
                );
                break;
                
            case 'mark_replied':
                $contactsCollection->updateOne(
                    ['_id' => $contactId],
                    ['$set' => ['status' => 'replied']]
                );
                break;
                
            case 'delete':
                $contactsCollection->deleteOne(['_id' => $contactId]);
                break;
        }
        
        header("Location: admin_contacts.php");
        exit;
    }

    // Handle reply submission - SEND EMAIL ONLY
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
        $contactId = new MongoDB\BSON\ObjectId($_POST['contact_id']);
        $replyMessage = trim($_POST['reply_message']);
        $adminName = "Admin";
        
        if (!empty($replyMessage)) {
            $contact = $contactsCollection->findOne(['_id' => $contactId]);
            
            if ($contact) {
                $emailSent = sendContactReplyEmail(
                    $contact['email'],
                    $contact['name'],
                    $contact['subject'],
                    $replyMessage,
                    $adminName
                );
                
                // Mark as replied in database
                $contactsCollection->updateOne(
                    ['_id' => $contactId],
                    [
                        '$set' => [
                            'status' => 'replied',
                            'admin_reply' => [
                                'message' => $replyMessage,
                                'admin_name' => $adminName,
                                'replied_at' => new MongoDB\BSON\UTCDateTime(),
                                'email_sent' => $emailSent
                            ]
                        ]
                    ]
                );
                
                if ($emailSent) {
                    $_SESSION['success_message'] = 'Reply sent successfully via email!';
                } else {
                    $_SESSION['success_message'] = 'Reply saved but email failed to send.';
                }
            }
            
            header("Location: admin_contacts.php");
            exit;
        }
    }
    
    // Get all contacts
    $contacts = $contactsCollection->find([], [
        'sort' => ['created_at' => -1]
    ])->toArray();

    $users = $usersCollection->find([], [
        'sort' => ['name' => 1]
    ])->toArray();
    
    // Statistics
    $totalContacts = $contactsCollection->countDocuments();
    $newContacts = $contactsCollection->countDocuments(['status' => 'new']);
    $readContacts = $contactsCollection->countDocuments(['status' => 'read']);
    $repliedContacts = $contactsCollection->countDocuments(['status' => 'replied']);
    $totalUsers = $usersCollection->countDocuments();
    
    // Rating statistics
    $ratedContacts = $contactsCollection->countDocuments(['rating' => ['$gt' => 0]]);
    $averageRating = 0;
    if ($ratedContacts > 0) {
        $ratingStats = $contactsCollection->aggregate([
            ['$match' => ['rating' => ['$gt' => 0]]],
            ['$group' => ['_id' => null, 'averageRating' => ['$avg' => '$rating']]]
        ])->toArray();
        $averageRating = round($ratingStats[0]['averageRating'] ?? 0, 1);
    }
    
    // Rating distribution
    $ratingDistribution = [];
    for ($i = 1; $i <= 5; $i++) {
        $ratingDistribution[$i] = $contactsCollection->countDocuments(['rating' => $i]);
    }

} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="stylesheet" href="adminstyles.css?v=<?php echo time(); ?>">
    <style>
        .rating-stars {
            display: inline-flex;
            gap: 2px;
            margin: 0;
            align-items: center;
        }
        
        .rating-stars .star {
            color: #ddd;
            font-size: 16px;
            display: inline-block;
        }
        
        .rating-stars .star.filled {
            color: #ffc107;
        }
        
        .rating-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            border: 1px solid #dee2e6;
            margin-left: 8px;
        }
        
        .rating-value {
            font-weight: bold;
            color: #495057;
        }
        
        .stat-rating {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #8c6d1f;
        }
        
        .rating-distribution {
            margin-top: 10px;
        }
        
        .rating-bar {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            font-size: 12px;
        }
        
        .rating-bar-label {
            width: 80px;
        }
        
        .rating-bar-progress {
            flex: 1;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 0 10px;
        }
        
        .rating-bar-fill {
            height: 100%;
            background: #ffc107;
            border-radius: 4px;
        }
        
        .rating-bar-count {
            width: 30px;
            text-align: right;
            font-weight: bold;
        }
        
        .contact-rating {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
        }
        
        .reply-section {
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .reply-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }
        
        .reply-actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
        
        .admin-reply {
            margin: 15px 0;
            padding: 15px;
            background: #e7f3ff;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }
        
        .email-status {
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .email-sent {
            background: #d4edda;
            color: #155724;
        }
        
        .email-failed {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-crown"></i> Admin Panel</h2>
                <p>Glisten Travel</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="admin_packages.php"><i class="fas fa-suitcase"></i> Tour Packages</a></li>
                <li><a href="admin_bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="admin_contacts.php" class="active"><i class="fas fa-envelope"></i> Contact Messages</a></li>
                <li><a href="admin_analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                <li><a href="admin_settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Contact Messages</h1>
                <div>
                    <?php if ($newContacts > 0): ?>
                        <span class="badge badge-danger"><?php echo $newContacts; ?> New Messages</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Success Message -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success_message']; ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card stat-total">
                    <div class="stat-number"><?php echo $totalContacts; ?></div>
                    <div class="stat-label">Total Messages</div>
                </div>
                <div class="stat-card stat-new">
                    <div class="stat-number"><?php echo $newContacts; ?></div>
                    <div class="stat-label">New Messages</div>
                </div>
                <div class="stat-card stat-read">
                    <div class="stat-number"><?php echo $readContacts; ?></div>
                    <div class="stat-label">Read Messages</div>
                </div>
                <div class="stat-card stat-replied">
                    <div class="stat-number"><?php echo $repliedContacts; ?></div>
                    <div class="stat-label">Replied Messages</div>
                </div>
                <div class="stat-card stat-rating">
                    <div class="stat-number"><?php echo $averageRating; ?>/5</div>
                    <div class="stat-label">Avg Rating</div>
                    <div class="stat-sub">(<?php echo $ratedContacts; ?> ratings)</div>
                </div>
            </div>
            
            <!-- Rating Distribution -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Rating Distribution</h3>
                </div>
                <div style="padding: 20px;">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <div class="rating-bar">
                            <div class="rating-bar-label">
                                <?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?>
                            </div>
                            <div class="rating-bar-progress">
                                <div class="rating-bar-fill" style="width: <?php echo $ratedContacts > 0 ? ($ratingDistribution[$i] / $ratedContacts * 100) : 0; ?>%"></div>
                            </div>
                            <div class="rating-bar-count">
                                <?php echo $ratingDistribution[$i]; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <!-- Messages List -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Contact Messages</h3>
                </div>
                
                <?php if (empty($contacts)): ?>
                    <div class="empty-state">
                        <i class="fas fa-envelope-open"></i>
                        <h3>No Messages Yet</h3>
                        <p>Contact messages from users will appear here.</p>
                    </div>
                <?php else: ?>
                    <div style="padding: 20px;">
                        <?php foreach ($contacts as $contact): ?>
                            <div class="contact-item <?php echo $contact['status']; ?>" data-rating="<?php echo isset($contact['rating']) ? $contact['rating'] : 0; ?>">
                                <div class="contact-header">
                                    <div class="contact-info">
                                        <div class="contact-name">
                                            <?php echo htmlspecialchars($contact['name']); ?>
                                            <span class="status-badge status-<?php echo $contact['status']; ?>">
                                                <?php echo ucfirst($contact['status']); ?>
                                            </span>
                                            <?php if (isset($contact['rating']) && $contact['rating'] > 0): ?>
                                                <span class="rating-badge">
                                                    <div class="rating-stars">
                                                        <?php 
                                                        $contactRating = $contact['rating'];
                                                        for ($i = 1; $i <= 5; $i++): 
                                                            $isFilled = $i <= $contactRating;
                                                        ?>
                                                            <span class="star <?php echo $isFilled ? 'filled' : ''; ?>">★</span>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <span class="rating-value"><?php echo $contactRating; ?>/5</span>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="contact-email">
                                            <i class="fas fa-envelope"></i>
                                            <?php echo htmlspecialchars($contact['email']); ?>
                                        </div>
                                        <div class="contact-date">
                                            <i class="fas fa-clock"></i>
                                            <?php 
                                            if (isset($contact['created_at']) && $contact['created_at'] instanceof MongoDB\BSON\UTCDateTime) {
                                                echo date('M j, Y g:i A', $contact['created_at']->toDateTime()->getTimestamp());
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="contact-subject">
                                    <?php echo htmlspecialchars($contact['subject']); ?>
                                </div>
                                
                                <?php if (isset($contact['rating']) && $contact['rating'] > 0): ?>
                                    <div class="contact-rating">
                                        <strong>Customer Rating:</strong>
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star <?php echo $i <= $contact['rating'] ? 'filled' : ''; ?>">★</span>
                                            <?php endfor; ?>
                                            <span style="margin-left: 10px; font-weight: bold; color: #495057;">
                                                (<?php echo $contact['rating']; ?>/5)
                                            </span>
                                        </div>
                                        <?php
                                        $ratingTexts = [
                                            1 => 'Poor',
                                            2 => 'Fair', 
                                            3 => 'Good',
                                            4 => 'Very Good',
                                            5 => 'Excellent'
                                        ];
                                        if (isset($ratingTexts[$contact['rating']])) {
                                            echo '<span style="color: #6c757d; font-style: italic;">- ' . $ratingTexts[$contact['rating']] . '</span>';
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="contact-message">
                                    <?php echo nl2br(htmlspecialchars($contact['message'])); ?>
                                </div>
                                
                                <!-- Admin Reply Section -->
                                <?php if (isset($contact['admin_reply'])): ?>
                                    <div class="admin-reply">
                                        <div class="admin-reply-header">
                                            <span class="admin-reply-badge">
                                                <i class="fas fa-reply"></i> Replied via Email
                                            </span>
                                            <?php if (isset($contact['admin_reply']['email_sent'])): ?>
                                                <span class="email-status <?php echo $contact['admin_reply']['email_sent'] ? 'email-sent' : 'email-failed'; ?>">
                                                    <i class="fas fa-<?php echo $contact['admin_reply']['email_sent'] ? 'check' : 'exclamation-triangle'; ?>"></i>
                                                    Email <?php echo $contact['admin_reply']['email_sent'] ? 'Sent' : 'Failed'; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="admin-reply-message">
                                            <strong>Your reply:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($contact['admin_reply']['message'])); ?>
                                        </div>
                                        <div style="margin-top: 10px; font-size: 12px; color: #6c757d;">
                                            <i class="fas fa-clock"></i> 
                                            Sent on: 
                                            <?php 
                                            if (isset($contact['admin_reply']['replied_at']) && $contact['admin_reply']['replied_at'] instanceof MongoDB\BSON\UTCDateTime) {
                                                echo date('M j, Y g:i A', $contact['admin_reply']['replied_at']->toDateTime()->getTimestamp());
                                            }
                                            ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="reply-section">
                                        <h4 style="margin-bottom: 10px;"><i class="fas fa-reply"></i> Send Email Reply</h4>
                                        <form method="POST" class="reply-form">
                                            <input type="hidden" name="contact_id" value="<?php echo $contact['_id']; ?>">
                                            <textarea name="reply_message" placeholder="Type your reply message here. This will be sent directly to the user's email..." required></textarea>
                                            <div class="reply-actions">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="fas fa-paper-plane"></i> Send Email Reply
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="contact-actions">
                                    <?php if ($contact['status'] === 'new'): ?>
                                        <a href="admin_contacts.php?action=mark_read&id=<?php echo $contact['_id']; ?>" 
                                           class="btn btn-warning btn-sm">
                                            <i class="fas fa-eye"></i> Mark Read
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="admin_contacts.php?action=delete&id=<?php echo $contact['_id']; ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this message?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>