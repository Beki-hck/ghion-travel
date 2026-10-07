<?php
session_start();
require_once 'vendor/autoload.php';

// Simulate user session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Current User';
}

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $bookingsCollection = $database->selectCollection('bookings');
    $packagesCollection = $database->selectCollection('packages');
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $packageName = $_POST['package_name'] ?? '';
    $customerName = $_POST['customer_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $tourDate = $_POST['tour_date'] ?? '';
    $passengers = intval($_POST['passengers'] ?? 1);
    $paymentMethod = $_POST['payment_method'] ?? 'CBE Bank';
    
    // Get package details
    $package = $packagesCollection->findOne(['name' => $packageName]);
    
    if ($package) {
        $totalPrice = $package['price'] * $passengers;
        $bookingReference = 'BOOK-' . strtoupper(uniqid());
        $transactionId = 'TXN-' . strtoupper(uniqid());
        
        // Create booking document
        $bookingData = [
            'bookingReference' => $bookingReference,
            'package' => $packageName,
            'name' => $customerName,
            'email' => $email,
            'phone' => $phone,
            'tourDate' => $tourDate,
            'passengers' => $passengers,
            'paymentMethod' => $paymentMethod,
            'transactionId' => $transactionId,
            'total_price' => $totalPrice,
            'status' => 'paid',
            'user_id' => $_SESSION['user_id'],
            'booking_date' => new MongoDB\BSON\UTCDateTime(),
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ];
        
        // Insert into MongoDB
        $result = $bookingsCollection->insertOne($bookingData);
        
        if ($result->getInsertedCount() > 0) {
            // Store in session for immediate access
            $_SESSION['last_booking'] = $bookingData;
            if (!isset($_SESSION['bookings'])) {
                $_SESSION['bookings'] = [];
            }
            $_SESSION['bookings'][] = $bookingData;
            
            // Redirect to success page
            header("Location: booking_success.php?ref=" . urlencode($bookingReference));
            exit;
        } else {
            $error = "Failed to create booking. Please try again.";
        }
    } else {
        $error = "Package not found.";
    }
}

// If no POST data, redirect to packages
header("Location: packages_mongo.php");
exit;
?>