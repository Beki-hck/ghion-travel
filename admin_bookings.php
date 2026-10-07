<?php
session_start();
require_once 'vendor/autoload.php';
// Include email functions
require_once 'email_functions.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $bookingsCollection = $database->selectCollection('bookings');
    
     // Handle booking status updates
    if (isset($_POST['update_booking_status'])) {
        $bookingId = $_POST['booking_id'];
        $newStatus = $_POST['booking_status'];
        $adminNotes = $_POST['admin_notes'] ?? '';
        
        $updateData = [
            'booking_status' => $newStatus,
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ];
        
        if ($newStatus === 'confirmed') {
            $updateData['payment_info.payment_status'] = 'approved';
        } elseif ($newStatus === 'cancelled') {
            $updateData['payment_info.payment_status'] = 'rejected';
        }
        
        if (!empty($adminNotes)) {
            $updateData['payment_info.admin_notes'] = $adminNotes;
        }
        
        $result = $bookingsCollection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($bookingId)],
            ['$set' => $updateData]
        );
        
        if ($result->getModifiedCount() > 0) {
            // Send appropriate email notification
            $updatedBooking = $bookingsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($bookingId)]);
            if ($updatedBooking) {
                $emailSent = false;
                $emailMessage = '';
                
                if ($newStatus === 'confirmed') {
                    $emailSent = sendBookingConfirmationEmail($updatedBooking);
                    $emailMessage = $emailSent ? ' Confirmation email sent to customer.' : ' Failed to send confirmation email.';
                } elseif ($newStatus === 'cancelled') {
                    $emailSent = sendBookingCancellationEmail($updatedBooking, $adminNotes);
                    $emailMessage = $emailSent ? ' Cancellation email sent to customer.' : ' Failed to send cancellation email.';
                }
                
                $_SESSION['success_message'] = 'Booking status updated successfully!' . $emailMessage;
            }
        } else {
            $_SESSION['error_message'] = 'Failed to update booking status.';
        }
        
        header("Location: admin_bookings.php");
        exit;
    }
     // Handle booking status updates
    if (isset($_POST['update_booking_status'])) {
        $bookingId = $_POST['booking_id'];
        $newStatus = $_POST['booking_status'];
        $adminNotes = $_POST['admin_notes'] ?? '';
        
        $updateData = [
            'booking_status' => $newStatus,
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ];
        
        if ($newStatus === 'confirmed') {
            $updateData['payment_info.payment_status'] = 'approved';
        } elseif ($newStatus === 'cancelled') {
            $updateData['payment_info.payment_status'] = 'rejected';
        }
        
        if (!empty($adminNotes)) {
            $updateData['payment_info.admin_notes'] = $adminNotes;
        }
        
        $result = $bookingsCollection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($bookingId)],
            ['$set' => $updateData]
        );
        
        if ($result->getModifiedCount() > 0) {
            // Send email notification if status changed to confirmed
            if ($newStatus === 'confirmed') {
                $updatedBooking = $bookingsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($bookingId)]);
                if ($updatedBooking) {
                    sendBookingConfirmationEmail($updatedBooking);
                }
            }
            
            $_SESSION['success_message'] = 'Booking status updated successfully!' . 
                ($newStatus === 'confirmed' ? ' Confirmation email sent to customer.' : '');
        } else {
            $_SESSION['error_message'] = 'Failed to update booking status.';
        }
        
        header("Location: admin_bookings.php");
        exit;
    }
    // Handle booking deletion
    if (isset($_POST['delete_booking'])) {
        $bookingId = $_POST['booking_id'];
        
        if (class_exists('MongoDB\BSON\ObjectId')) {
            $objectId = new MongoDB\BSON\ObjectId($bookingId);
        } else {
            $objectId = $bookingId;
        }
        
        $deleteResult = $bookingsCollection->deleteOne(['_id' => $objectId]);
        
        if ($deleteResult->getDeletedCount() > 0) {
            $_SESSION['success_message'] = 'Booking deleted successfully!';
            
            $booking = $bookingsCollection->findOne(['_id' => $objectId]);
            if ($booking && isset($booking['payment_info']['receipt_path']) && file_exists($booking['payment_info']['receipt_path'])) {
                unlink($booking['payment_info']['receipt_path']);
            }
        } else {
            $_SESSION['error_message'] = 'Failed to delete booking. Booking not found.';
        }
        
        header("Location: admin_bookings.php");
        exit;
    }
    
    // Get all bookings with proper error handling
    $bookings = [];
    $cursor = $bookingsCollection->find([], ['sort' => ['created_at' => -1]]);
    
    // Convert cursor to array safely
    if (method_exists($cursor, 'toArray')) {
        $bookings = $cursor->toArray();
    } else {
        // Manual conversion for older drivers
        foreach ($cursor as $document) {
            $bookings[] = $document;
        }
    }

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Database connection failed: " . $e->getMessage();
}

// Helper function to safely get array values
function getSafeValue($array, $key, $default = '') {
    return isset($array[$key]) ? $array[$key] : $default;
}

// Helper function to safely get nested array values
function getNestedValue($array, $keys, $default = '') {
    $current = $array;
    foreach ($keys as $key) {
        if (!isset($current[$key])) {
            return $default;
        }
        $current = $current[$key];
    }
    return $current;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Ghion Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="adminstyles.css">
    <style>
        .receipt-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .receipt-preview:hover {
            transform: scale(1.05);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }
        
        .modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90%;
        }
        
        .close-modal {
            position: absolute;
            top: 15px;
            right: 35px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .payment-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .payment-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .payment-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .text-muted {
            color: #6c757d;
            font-style: italic;
        }
        
        .customer-info {
            line-height: 1.4;
        }
        
        .customer-contact {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        
        .header-stats {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-card {
            display: flex;
            align-items: center;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            min-width: 120px;
        }
        
        .stat-card i {
            font-size: 24px;
            margin-right: 10px;
            color: #2a6496;
        }
        
        .stat-info {
            display: flex;
            flex-direction: column;
        }
        
        .stat-number {
            font-size: 18px;
            font-weight: bold;
            color: #2a6496;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
        }
        
        .confirmation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .confirmation-modal.active {
            display: flex;
        }

        .confirmation-modal-content {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 400px;
            overflow: hidden;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .confirmation-modal-header {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .confirmation-modal-header i {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .confirmation-modal-body {
            padding: 25px;
            text-align: center;
        }

        .confirmation-modal-body h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .confirmation-modal-body p {
            color: #666;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .confirmation-modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .confirmation-modal-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 100px;
        }

        .confirmation-modal-btn-cancel {
            background: #95a5a6;
            color: white;
        }

        .confirmation-modal-btn-cancel:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }

        .confirmation-modal-btn-confirm {
            background: #e74c3c;
            color: white;
        }

        .confirmation-modal-btn-confirm:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .actions-column {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            min-width: auto;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-crown"></i> Admin Panel</h2>
                <p>Ghion Travel</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="admin_packages.php"><i class="fas fa-suitcase"></i> Tour Packages</a></li>
                <li><a href="admin_bookings.php" class="active"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="admin_car.php"><i class="fas fa-car"></i>Car</a></li>
                <li><a href="admin_hotel.php"><i class="fas fa-hotel"></i> Hotels</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="admin_contacts.php"><i class="fas fa-comments"></i> Contacts & Feedback</a></li>
                <li><a href="admin_analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                <li><a href="admin_notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                <li><a href="admin_settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Manage Bookings</h1>
                <div class="header-stats">
                    <div class="stat-card">
                        <i class="fas fa-clock"></i>
                        <div class="stat-info">
                            <span class="stat-number"><?php 
                                echo count(array_filter($bookings, function($b) { 
                                    return getSafeValue($b, 'booking_status') === 'pending'; 
                                })); 
                            ?></span>
                            <span class="stat-label">Pending</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-check-circle"></i>
                        <div class="stat-info">
                            <span class="stat-number"><?php 
                                echo count(array_filter($bookings, function($b) { 
                                    return getSafeValue($b, 'booking_status') === 'confirmed'; 
                                })); 
                            ?></span>
                            <span class="stat-label">Confirmed</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-times-circle"></i>
                        <div class="stat-info">
                            <span class="stat-number"><?php 
                                echo count(array_filter($bookings, function($b) { 
                                    return getSafeValue($b, 'booking_status') === 'cancelled'; 
                                })); 
                            ?></span>
                            <span class="stat-label">Cancelled</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Bookings (<?php echo count($bookings); ?>)</h3>
                </div>
                
                <?php if (empty($bookings)): ?>
                    <div class="no-data">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No Bookings Found</h3>
                        <p>There are no bookings in the system yet.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Booking Ref</th>
                                    <th>Customer</th>
                                    <th>Package</th>
                                    <th>Tour Date</th>
                                    <th>Passengers</th>
                                    <th>Total Amount</th>
                                    <th>Payment Method</th>
                                    <th>Receipt</th>
                                    <th>Payment Status</th>
                                    <th>Booking Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): 
                                    // Safely get booking ID
                                    $bookingId = '';
                                    if (isset($booking['_id'])) {
                                        if (is_object($booking['_id']) && method_exists($booking['_id'], '__toString')) {
                                            $bookingId = $booking['_id']->__toString();
                                        } else {
                                            $bookingId = (string)$booking['_id'];
                                        }
                                    }
                                    
                                    // Safely get all values with defaults
                                    $bookingReference = getSafeValue($booking, 'booking_reference', 'N/A');
                                    $customerInfo = getSafeValue($booking, 'customer_info', []);
                                    $tourDetails = getSafeValue($booking, 'tour_details', []);
                                    $paymentInfo = getSafeValue($booking, 'payment_info', []);
                                    $bookingStatus = getSafeValue($booking, 'booking_status', 'pending');
                                    $paymentStatus = getNestedValue($paymentInfo, ['payment_status'], 'pending');
                                    
                                    // Customer details
                                    $customerName = getSafeValue($customerInfo, 'first_name', '') . ' ' . getSafeValue($customerInfo, 'last_name', '');
                                    $customerEmail = getSafeValue($customerInfo, 'email', '');
                                    $customerPhone = getSafeValue($customerInfo, 'phone', '');
                                    
                                    // Tour details
                                    $packageName = getSafeValue($tourDetails, 'package_name', 'Unknown Package');
                                    $tourDate = getSafeValue($tourDetails, 'tour_date', '');
                                    $passengers = getSafeValue($tourDetails, 'passengers', 0);
                                    
                                    // Payment details
                                    $totalAmount = getNestedValue($paymentInfo, ['total_amount'], 0);
                                    $paymentMethod = getNestedValue($paymentInfo, ['payment_method'], 'Unknown');
                                    $receiptPath = getNestedValue($paymentInfo, ['receipt_path'], '');
                                    
                                    // Format dates
                                    $formattedTourDate = $tourDate ? date('M j, Y', strtotime($tourDate)) : 'N/A';
                                    $formattedCreatedDate = isset($booking['created_at']) ? 
                                        (is_object($booking['created_at']) && method_exists($booking['created_at'], 'toDateTime') ? 
                                            $booking['created_at']->toDateTime()->format('M j, Y') : 
                                            date('M j, Y', strtotime($booking['created_at']))) : 
                                        'N/A';
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($bookingReference); ?></strong>
                                    </td>
                                    <td>
                                        <div class="customer-info">
                                            <?php if (!empty(trim($customerName))): ?>
                                                <strong><?php echo htmlspecialchars($customerName); ?></strong>
                                                <div class="customer-contact">
                                                    <?php echo htmlspecialchars($customerEmail); ?><br>
                                                    <?php echo htmlspecialchars($customerPhone); ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">No customer info</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($packageName); ?></td>
                                    <td><?php echo htmlspecialchars($formattedTourDate); ?></td>
                                    <td><?php echo htmlspecialchars($passengers); ?></td>
                                    <td>$<?php echo number_format($totalAmount, 2); ?></td>
                                    <td><?php echo htmlspecialchars($paymentMethod); ?></td>
                                    <td>
                                        <?php if (!empty($receiptPath) && file_exists($receiptPath)): ?>
                                            <img src="<?php echo htmlspecialchars($receiptPath); ?>" 
                                                 alt="Payment Receipt" 
                                                 class="receipt-preview"
                                                 onclick="openModal('<?php echo htmlspecialchars($receiptPath); ?>')">
                                        <?php else: ?>
                                            <span class="text-muted">No receipt</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge payment-<?php echo htmlspecialchars($paymentStatus); ?>">
                                            <?php echo ucfirst(htmlspecialchars($paymentStatus)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo htmlspecialchars($bookingStatus); ?>">
                                            <?php echo ucfirst(htmlspecialchars($bookingStatus)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($formattedCreatedDate); ?>
                                    </td>
                                    <td>
                                        <div class="actions-column">
                                            <button type="button" class="btn btn-primary btn-sm" 
                                                    onclick="openBookingModal('<?php echo $bookingId; ?>')">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button type="button" class="btn btn-warning btn-sm" 
                                                    onclick="openStatusModal('<?php echo $bookingId; ?>', '<?php echo htmlspecialchars($bookingStatus); ?>')">
                                                <i class="fas fa-edit"></i> Update
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm delete-btn" 
                                                    data-booking-id="<?php echo $bookingId; ?>"
                                                    data-booking-reference="<?php echo htmlspecialchars($bookingReference); ?>"
                                                    data-customer-name="<?php echo htmlspecialchars($customerName); ?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="receiptModal" class="modal">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <!-- Booking Details Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content" style="background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 50px auto; max-height: 80vh; overflow-y: auto;">
            <span class="close-modal" onclick="closeBookingModal()" style="color: #333; position: absolute; top: 10px; right: 20px;">&times;</span>
            <div id="bookingModalContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content" style="background: white; padding: 30px; border-radius: 10px; max-width: 500px; margin: 50px auto;">
            <span class="close-modal" onclick="closeStatusModal()" style="color: #333; position: absolute; top: 10px; right: 20px;">&times;</span>
            <h3>Update Booking Status</h3>
            <form id="statusForm" method="POST">
                <input type="hidden" name="booking_id" id="statusBookingId">
                
                <div class="form-group">
                    <label for="booking_status">Booking Status</label>
                    <select id="booking_status" name="booking_status" class="form-control" required>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="cancelled">Cancelled</option>
                        
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="admin_notes">Admin Notes</label>
                    <textarea id="admin_notes" name="admin_notes" class="form-control" rows="4" 
                              placeholder="Add any notes or comments about this booking..."></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="update_booking_status" class="btn btn-success">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="confirmation-modal" id="deleteConfirmationModal">
        <div class="confirmation-modal-content">
            <div class="confirmation-modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h2>Confirm Deletion</h2>
            </div>
            <div class="confirmation-modal-body">
                <h3>Are you sure you want to delete this booking?</h3>
                <p id="deleteConfirmationText">This action cannot be undone. The booking will be permanently removed from the system.</p>
                <div class="confirmation-modal-actions">
                    <button type="button" class="confirmation-modal-btn confirmation-modal-btn-cancel" id="cancelDelete">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <form method="POST" id="deleteForm" style="display: inline;">
                        <input type="hidden" name="booking_id" id="deleteBookingId">
                        <button type="submit" name="delete_booking" class="confirmation-modal-btn confirmation-modal-btn-confirm">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openModal(imageSrc) {
            document.getElementById('receiptModal').style.display = 'block';
            document.getElementById('modalImage').src = imageSrc;
        }
        
        function closeModal() {
            document.getElementById('receiptModal').style.display = 'none';
        }
        
        function openBookingModal(bookingId) {
            // Load booking details via AJAX
            fetch('get_booking_details.php?id=' + bookingId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(data => {
                    document.getElementById('bookingModalContent').innerHTML = data;
                    document.getElementById('bookingModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error loading booking details:', error);
                    document.getElementById('bookingModalContent').innerHTML = 
                        '<div class="error-message">Error loading booking details. Please try again.</div>';
                    document.getElementById('bookingModal').style.display = 'block';
                });
        }
        
        function closeBookingModal() {
            document.getElementById('bookingModal').style.display = 'none';
        }
        
        function openStatusModal(bookingId, currentStatus) {
            document.getElementById('statusBookingId').value = bookingId;
            document.getElementById('booking_status').value = currentStatus;
            document.getElementById('admin_notes').value = '';
            document.getElementById('statusModal').style.display = 'block';
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }
        
        // Delete Confirmation Modal
        const deleteModal = document.getElementById('deleteConfirmationModal');
        const deleteForm = document.getElementById('deleteForm');
        const deleteBookingId = document.getElementById('deleteBookingId');
        const deleteConfirmationText = document.getElementById('deleteConfirmationText');
        const cancelDeleteBtn = document.getElementById('cancelDelete');
        
        // Get all delete buttons
        const deleteButtons = document.querySelectorAll('.delete-btn');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const bookingId = this.getAttribute('data-booking-id');
                const bookingReference = this.getAttribute('data-booking-reference');
                const customerName = this.getAttribute('data-customer-name');
                
                // Update the modal content with booking details
                deleteConfirmationText.innerHTML = `
                    Are you sure you want to delete booking <strong>${bookingReference}</strong> 
                    for customer <strong>${customerName}</strong>?<br><br>
                    This action cannot be undone. The booking will be permanently removed from the system.
                `;
                
                // Set the booking ID in the form
                deleteBookingId.value = bookingId;
                
                // Show the modal
                deleteModal.classList.add('active');
            });
        });
        
        // Cancel button event
        cancelDeleteBtn.addEventListener('click', function() {
            deleteModal.classList.remove('active');
        });
        
        deleteModal.addEventListener('click', function(e) {
            if (e.target === deleteModal) {
                deleteModal.classList.remove('active');
            }
        });
        
        window.onclick = function(event) {
            const receiptModal = document.getElementById('receiptModal');
            const bookingModal = document.getElementById('bookingModal');
            const statusModal = document.getElementById('statusModal');
            
            if (event.target === receiptModal) {
                closeModal();
            }
            if (event.target === bookingModal) {
                closeBookingModal();
            }
            if (event.target === statusModal) {
                closeStatusModal();
            }
        }
        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
                closeBookingModal();
                closeStatusModal();
                deleteModal.classList.remove('active');
            }
        });
    </script>
</body>
</html>