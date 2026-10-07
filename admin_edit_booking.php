<?php
session_start();
require_once 'vendor/autoload.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: admin_bookings.php");
    exit;
}

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $bookingsCollection = $database->selectCollection('bookings');
    
    $bookingId = $_GET['id'];
    $booking = $bookingsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($bookingId)]);
    
    if (!$booking) {
        $_SESSION['error_message'] = 'Booking not found!';
        header("Location: admin_bookings.php");
        exit;
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $updateData = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'package' => $_POST['package'],
            'tourDate' => $_POST['tourDate'],
            'passengers' => intval($_POST['passengers']),
            'total_price' => floatval($_POST['total_price']),
            'status' => $_POST['status'],
            'country' => $_POST['country'],
            'special_requests' => $_POST['special_requests'],
            'paymentMethod' => $_POST['paymentMethod'],
            'notes' => $_POST['notes'],
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ];
        
        $result = $bookingsCollection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($bookingId)],
            ['$set' => $updateData]
        );
        
        if ($result->getModifiedCount() > 0) {
            $_SESSION['success_message'] = 'Booking updated successfully!';
            header("Location: admin_view_booking.php?id=" . $bookingId);
            exit;
        } else {
            $error = "No changes made or failed to update booking.";
        }
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
    <title>Edit Booking - Ghion Travel</title>
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
                <h1>Edit Booking</h1>
                <div style="display: flex; gap: 10px;">
                    <a href="admin_view_booking.php?id=<?php echo $booking['_id']; ?>" class="btn btn-primary">
                        <i class="fas fa-eye"></i> View Booking
                    </a>
                    <a href="admin_bookings.php" class="btn btn-warning">
                        <i class="fas fa-arrow-left"></i> Back to Bookings
                    </a>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="form-container">
                    <div class="booking-ref">
                        <div class="form-help">Booking Reference</div>
                        <div class="ref-number"><?php echo htmlspecialchars($booking['bookingReference'] ?? 'N/A'); ?></div>
                    </div>
                    
                    <form method="POST">
                        <h3 class="section-title">Customer Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" required 
                                       value="<?php echo htmlspecialchars($booking['name'] ?? $booking['customer_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" required 
                                       value="<?php echo htmlspecialchars($booking['email'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" required 
                                       value="<?php echo htmlspecialchars($booking['phone'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="country">Country</label>
                                <input type="text" id="country" name="country" 
                                       value="<?php echo htmlspecialchars($booking['country'] ?? ''); ?>"
                                       placeholder="Enter customer's country">
                            </div>
                        </div>
                        
                        <h3 class="section-title">Booking Details</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="package">Tour Package *</label>
                                <input type="text" id="package" name="package" required 
                                       value="<?php echo htmlspecialchars($booking['package'] ?? $booking['package_name'] ?? ''); ?>"
                                       placeholder="Enter package name">
                            </div>
                            <div class="form-group">
                                <label for="tourDate">Travel Date *</label>
                                <input type="date" id="tourDate" name="tourDate" required 
                                       value="<?php echo htmlspecialchars($booking['tourDate'] ?? $booking['travel_date'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="passengers">Number of Passengers *</label>
                                <input type="number" id="passengers" name="passengers" min="1" max="20" required 
                                       value="<?php echo $booking['passengers'] ?? 1; ?>">
                            </div>
                            <div class="form-group">
                                <label for="total_price">Total Price ($) *</label>
                                <input type="number" id="total_price" name="total_price" step="0.01" min="0" required 
                                       value="<?php echo $booking['total_price'] ?? 0; ?>">
                            </div>
                        </div>
                        
                        <h3 class="section-title">Booking Status & Payment</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="status">Booking Status *</label>
                                <select id="status" name="status" required>
                                    <option value="pending" <?php echo ($booking['status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo ($booking['status'] ?? 'pending') === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="paid" <?php echo ($booking['status'] ?? 'pending') === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="completed" <?php echo ($booking['status'] ?? 'pending') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo ($booking['status'] ?? 'pending') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="paymentMethod">Payment Method</label>
                                <select id="paymentMethod" name="paymentMethod">
                                    <option value="">Select Payment Method</option>
                                    <option value="Credit Card" <?php echo ($booking['paymentMethod'] ?? '') === 'Credit Card' ? 'selected' : ''; ?>>Credit Card</option>
                                    <option value="PayPal" <?php echo ($booking['paymentMethod'] ?? '') === 'PayPal' ? 'selected' : ''; ?>>PayPal</option>
                                    <option value="Bank Transfer" <?php echo ($booking['paymentMethod'] ?? '') === 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                    <option value="Cash" <?php echo ($booking['paymentMethod'] ?? '') === 'Cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="Other" <?php echo ($booking['paymentMethod'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <h3 class="section-title">Additional Information</h3>
                        <div class="form-group">
                            <label for="special_requests">Special Requests</label>
                            <textarea id="special_requests" name="special_requests" rows="3"
                                      placeholder="Any special requests from the customer..."><?php echo htmlspecialchars($booking['special_requests'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Admin Notes</label>
                            <textarea id="notes" name="notes" rows="4"
                                      placeholder="Internal notes about this booking..."><?php echo htmlspecialchars($booking['notes'] ?? ''); ?></textarea>
                            <div class="form-help">
                                These notes are for internal use only and won't be visible to the customer.
                            </div>
                        </div>
                        
                        <div class="form-row" style="margin-top: 30px;">
                            <div style="display: flex; gap: 15px; width: 100%;">
                                <button type="submit" class="submit-btn">
                                    <i class="fas fa-save"></i> Update Booking
                                </button>
                                <a href="admin_view_booking.php?id=<?php echo $booking['_id']; ?>" 
                                   class="btn btn-primary" style="padding: 15px 30px;">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- System Information -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">System Information</h3>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div>
                        <div style="color: #666; font-size: 14px; margin-bottom: 5px;">Booking ID</div>
                        <div style="font-weight: 500; font-family: monospace;"><?php echo $booking['_id']; ?></div>
                    </div>
                    <div>
                        <div style="color: #666; font-size: 14px; margin-bottom: 5px;">User ID</div>
                        <div style="font-weight: 500;"><?php echo $booking['user_id'] ?? 'N/A'; ?></div>
                    </div>
                    <div>
                        <div style="color: #666; font-size: 14px; margin-bottom: 5px;">Created</div>
                        <div style="font-weight: 500;">
                            <?php 
                            if (isset($booking['booking_date'])) {
                                if ($booking['booking_date'] instanceof MongoDB\BSON\UTCDateTime) {
                                    echo $booking['booking_date']->toDateTime()->format('M j, Y g:i A');
                                } else {
                                    echo date('M j, Y g:i A', strtotime($booking['booking_date']));
                                }
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </div>
                    </div>
                    <?php if (isset($booking['updated_at'])): ?>
                    <div>
                        <div style="color: #666; font-size: 14px; margin-bottom: 5px;">Last Updated</div>
                        <div style="font-weight: 500;">
                            <?php 
                            if ($booking['updated_at'] instanceof MongoDB\BSON\UTCDateTime) {
                                echo $booking['updated_at']->toDateTime()->format('M j, Y g:i A');
                            } else {
                                echo date('M j, Y g:i A', strtotime($booking['updated_at']));
                            }
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set minimum date to today for travel date
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const tourDateInput = document.getElementById('tourDate');
            
            if (!tourDateInput.value) {
                tourDateInput.min = today;
            }
            
            const passengersInput = document.getElementById('passengers');
            const priceInput = document.getElementById('total_price');
            
            passengersInput.addEventListener('change', function() {
                const basePrice = 100; // Default base price
                const totalPrice = basePrice * parseInt(passengersInput.value);
                if (!priceInput.value || priceInput.value == <?php echo $booking['total_price'] ?? 0; ?>) {
                    priceInput.value = totalPrice.toFixed(2);
                }
            });
        });
    </script>
</body>
</html>