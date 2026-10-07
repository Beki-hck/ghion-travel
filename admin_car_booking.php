<?php
session_start();
require_once 'vendor/autoload.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $bookingsCollection = $database->selectCollection('bookings');

    // Handle status updates
    if (isset($_POST['update_status'])) {
        $bookingId = $_POST['booking_id'];
        $newStatus = $_POST['status'];
        
        $currentBooking = $bookingsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($bookingId)]);
        $oldStatus = $currentBooking['status'] ?? '';
        
        $bookingsCollection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($bookingId)],
            ['$set' => ['status' => $newStatus]]
        );

        if ($oldStatus !== $newStatus && !empty($currentBooking['email'])) {
            try {
                if (file_exists('email_functions.php')) {
                    require_once 'email_functions.php';
                    sendStatusUpdateEmail($currentBooking, $oldStatus, $newStatus);
                    error_log("Status update email sent for booking: " . $currentBooking['bookingReference']);
                }
            } catch (Exception $e) {
                error_log("Failed to send status update email: " . $e->getMessage());
            }
        }
        
        $_SESSION['success_message'] = 'Booking status updated successfully!';
        header("Location: admin_bookings.php");
        exit;
    }

    // Handle booking deletion
    if (isset($_POST['delete_booking'])) {
        $bookingId = $_POST['booking_id'];
        
        $booking = $bookingsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($bookingId)]);
        
        $bookingsCollection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($bookingId)]);
        
        $_SESSION['success_message'] = 'Booking #' . ($booking['bookingReference'] ?? '') . ' deleted successfully!';
        header("Location: admin_bookings.php");
        exit;
    }

    // Handle bulk actions
    if (isset($_POST['bulk_action'])) {
        $selectedBookings = $_POST['selected_bookings'] ?? [];
        $action = $_POST['bulk_action'];
        
        if (!empty($selectedBookings)) {
            $objectIds = array_map(function($id) {
                return new MongoDB\BSON\ObjectId($id);
            }, $selectedBookings);
            
            switch ($action) {
                case 'delete':
                    $bookingsCollection->deleteMany(['_id' => ['$in' => $objectIds]]);
                    $_SESSION['success_message'] = count($selectedBookings) . ' bookings deleted successfully!';
                    break;
                    
                case 'mark_confirmed':
                    $bookingsCollection->updateMany(
                        ['_id' => ['$in' => $objectIds]],
                        ['$set' => ['status' => 'confirmed']]
                    );
                    $_SESSION['success_message'] = count($selectedBookings) . ' bookings marked as confirmed!';
                    break;
                    
                case 'mark_paid':
                    $bookingsCollection->updateMany(
                        ['_id' => ['$in' => $objectIds]],
                        ['$set' => ['status' => 'paid']]
                    );
                    $_SESSION['success_message'] = count($selectedBookings) . ' bookings marked as paid!';
                    break;
                    
                case 'mark_completed':
                    $bookingsCollection->updateMany(
                        ['_id' => ['$in' => $objectIds]],
                        ['$set' => ['status' => 'completed']]
                    );
                    $_SESSION['success_message'] = count($selectedBookings) . ' bookings marked as completed!';
                    break;
            }
            
            header("Location: admin_bookings.php");
            exit;
        } else {
            $_SESSION['error_message'] = 'Please select at least one booking to perform bulk action.';
        }
    }

    // Get all bookings sorted by most recent first
    $bookings = $bookingsCollection->find(
        [],
        ['sort' => ['booking_date' => -1]]
    )->toArray();

} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Ghion Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="adminstyles.css?v=<?php echo time(); ?>">
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
                <li><a href="admin_car.php"><i class="fas fa-car"></i>car</a></li>
                <li><a href="admin_bookings.php" class="active"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
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
                <div>
                    <span style="font-weight: 500; color: var(--dark);">
                        Total: <?php echo $bookingsCollection->countDocuments(); ?> bookings
                    </span>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> 
                    <span><?php echo $_SESSION['success_message']; ?></span>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> 
                    <span><?php echo $_SESSION['error_message']; ?></span>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <form method="POST" id="bulkActionForm" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <select name="bulk_action" class="bulk-select" required>
                        <option value="">Bulk Actions</option>
                        <option value="mark_confirmed">Mark as Confirmed</option>
                        <option value="mark_paid">Mark as Paid</option>
                        <option value="mark_completed">Mark as Completed</option>
                        <option value="delete">Delete Selected</option>
                    </select>
                    <button type="submit" class="btn btn-primary" onclick="return confirmBulkAction()">
                        <i class="fas fa-play"></i> Apply
                    </button>
                    <span style="color: #666; font-size: 14px;">
                        Select bookings to perform bulk actions
                    </span>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Bookings</h3>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <span style="color: #666; font-size: 14px;">
                            Showing <?php echo count($bookings); ?> bookings
                        </span>
                    </div>
                </div>
                
                <?php if (empty($bookings)): ?>
                    <div class="no-bookings">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No Bookings Found</h3>
                        <p>There are no bookings in the system yet.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 30px;">
                                        <input type="checkbox" id="selectAll" class="select-all-checkbox">
                                    </th>
                                    <th>Reference</th>
                                    <th>Package</th>
                                    <th>Customer</th>
                                    <th>Contact</th>
                                    <th>Travel Date</th>
                                    <th>Passengers</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Booking Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selected_bookings[]" value="<?php echo $booking['_id']; ?>" class="row-checkbox">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['bookingReference'] ?? 'N/A'); ?></strong>
                                        <?php if (isset($booking['paymentMethod'])): ?>
                                        <div class="payment-method">
                                            <?php echo htmlspecialchars($booking['paymentMethod']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['package'] ?? $booking['package_name'] ?? 'N/A'); ?></strong>
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-name">
                                                <?php echo htmlspecialchars($booking['name'] ?? $booking['customer_name'] ?? 'N/A'); ?>
                                            </div>
                                            <?php if (isset($booking['user_id'])): ?>
                                            <div class="user-id">
                                                User ID: <?php echo $booking['user_id']; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="contact-info">
                                            <div>
                                                <i class="fas fa-envelope" style="color: var(--primary); margin-right: 5px;"></i>
                                                <?php echo htmlspecialchars($booking['email'] ?? 'N/A'); ?>
                                            </div>
                                            <div>
                                                <i class="fas fa-phone" style="color: var(--primary); margin-right: 5px;"></i>
                                                <?php echo htmlspecialchars($booking['phone'] ?? 'N/A'); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['tourDate'] ?? $booking['travel_date'] ?? 'N/A'); ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <strong><?php echo $booking['passengers'] ?? 1; ?></strong>
                                    </td>
                                    <td>
                                        <strong style="color: var(--success);">
                                            $<?php echo number_format($booking['total_price'] ?? 0, 2); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <form method="POST" class="status-form">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['_id']; ?>">
                                            <select name="status" class="status-select" onchange="this.form.submit()">
                                                <option value="pending" <?php echo ($booking['status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="confirmed" <?php echo ($booking['status'] ?? 'pending') === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="paid" <?php echo ($booking['status'] ?? 'pending') === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                <option value="completed" <?php echo ($booking['status'] ?? 'pending') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="cancelled" <?php echo ($booking['status'] ?? 'pending') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($booking['booking_date'])) {
                                            if ($booking['booking_date'] instanceof MongoDB\BSON\UTCDateTime) {
                                                echo $booking['booking_date']->toDateTime()->format('M j, Y');
                                            } else {
                                                echo date('M j, Y', strtotime($booking['booking_date']));
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="actions-column">
                                            <a href="admin_view_booking.php?id=<?php echo $booking['_id']; ?>" 
                                               class="btn btn-primary btn-sm"
                                               title="View Booking Details">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="admin_edit_booking.php?id=<?php echo $booking['_id']; ?>" 
                                               class="btn btn-warning btn-sm"
                                               title="Edit Booking">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['_id']; ?>">
                                                <button type="submit" name="delete_booking" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Are you sure you want to delete this booking? This action cannot be undone.')"
                                                        title="Delete Booking">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
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

    <script>
        // Select all checkbox functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Bulk action confirmation
        function confirmBulkAction() {
            const selectedCount = document.querySelectorAll('.row-checkbox:checked').length;
            const action = document.querySelector('select[name="bulk_action"]').value;
            
            if (selectedCount === 0) {
                alert('Please select at least one booking to perform bulk action.');
                return false;
            }
            
            let message = '';
            switch (action) {
                case 'delete':
                    message = `Are you sure you want to delete ${selectedCount} booking(s)? This action cannot be undone.`;
                    break;
                case 'mark_confirmed':
                    message = `Are you sure you want to mark ${selectedCount} booking(s) as confirmed?`;
                    break;
                case 'mark_paid':
                    message = `Are you sure you want to mark ${selectedCount} booking(s) as paid?`;
                    break;
                case 'mark_completed':
                    message = `Are you sure you want to mark ${selectedCount} booking(s) as completed?`;
                    break;
                default:
                    message = `Are you sure you want to perform this action on ${selectedCount} booking(s)?`;
            }
            
            return confirm(message);
        }

        // Update select all checkbox when individual checkboxes change
        document.querySelectorAll('.row-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = document.querySelectorAll('.row-checkbox:checked').length === document.querySelectorAll('.row-checkbox').length;
                document.getElementById('selectAll').checked = allChecked;
            });
        });
    </script>
</body>
</html>