<?php
session_start();
require_once 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
        $database = $mongoClient->selectDatabase('ghion_travel');
        $bookingsCollection = $database->selectCollection('bookings');
        
        // Handle file upload
        $receiptPath = '';
        if (isset($_FILES['payment_receipt']) && $_FILES['payment_receipt']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/receipts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExtension = pathinfo($_FILES['payment_receipt']['name'], PATHINFO_EXTENSION);
            $fileName = 'receipt_' . uniqid() . '_' . time() . '.' . $fileExtension;
            $receiptPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['payment_receipt']['tmp_name'], $receiptPath)) {
                // File uploaded successfully
            } else {
                throw new Exception('Failed to upload receipt file.');
            }
        }
        
        // Calculate total amount
        $totalAmount = floatval($_POST['package_price']) * intval($_POST['passengers']);
        
        // Add hotel cost if selected
        if (!empty($_POST['selected_hotel'])) {
            $hotelsCollection = $database->selectCollection('hotel');
            $hotel = $hotelsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($_POST['selected_hotel'])]);
            if ($hotel) {
                $totalAmount += floatval($hotel['price']) * intval($_POST['passengers']);
            }
        }
        
        // Add car cost if selected
        if (!empty($_POST['selected_car'])) {
            $carsCollection = $database->selectCollection('car');
            $car = $carsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($_POST['selected_car'])]);
            if ($car) {
                $totalAmount += floatval($car['price']) * intval($_POST['passengers']);
            }
        }
        
        // Create booking document
        $bookingData = [
            'booking_reference' => $_POST['booking_reference'],
            'customer_info' => [
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone'],
                'address' => $_POST['address'],
                'city' => $_POST['city'],
                'country' => $_POST['country'],
                'emergency_contact' => $_POST['emergency_contact'],
                'emergency_phone' => $_POST['emergency_phone'],
                'special_requirements' => $_POST['special_requirements'] ?? ''
            ],
            'tour_details' => [
                'package_id' => new MongoDB\BSON\ObjectId($_POST['package_id']),
                'package_name' => $_POST['package_name'],
                'package_area' => $_POST['package_area'],
                'tour_date' => $_POST['tour_date'],
                'passengers' => intval($_POST['passengers']),
                'pickup_location' => $_POST['pickup_location'],
                'additional_notes' => $_POST['additional_notes'] ?? ''
            ],
            'additional_services' => [
                'selected_hotel' => $_POST['selected_hotel'] ? new MongoDB\BSON\ObjectId($_POST['selected_hotel']) : null,
                'selected_car' => $_POST['selected_car'] ? new MongoDB\BSON\ObjectId($_POST['selected_car']) : null
            ],
            'payment_info' => [
                'total_amount' => $totalAmount,
                'payment_method' => $_POST['payment_method'],
                'receipt_path' => $receiptPath,
                'payment_status' => 'pending', // pending, approved, rejected
                'admin_notes' => ''
            ],
            'booking_status' => 'pending', // pending, confirmed, cancelled, completed
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ];
        
        $result = $bookingsCollection->insertOne($bookingData);
        
        if ($result->getInsertedCount() > 0) {
            $_SESSION['booking_success'] = true;
            $_SESSION['booking_reference'] = $_POST['booking_reference'];
            header("Location: booking_confirmation.php");
            exit;
        } else {
            throw new Exception('Failed to create booking.');
        }
        
    } catch (Exception $e) {
        $_SESSION['booking_error'] = 'Booking failed: ' . $e->getMessage();
        header("Location: book_package.php?package=" . urlencode($_POST['package_name']));
        exit;
    }
} else {
    header("Location: packages_mongo.php");
    exit;
}
?>