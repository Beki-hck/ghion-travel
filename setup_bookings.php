<?php
require_once 'vendor/autoload.php';

echo "<h1>Setting up MongoDB Bookings Collection</h1>";
echo "<div style='font-family: Arial; padding: 20px;'>";

try {
    // Connect to MongoDB
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    
    // Step 1: Create bookings collection
    echo "<h2>Step 1: Creating Bookings Collection</h2>";
    try {
        $database->createCollection('bookings');
        echo "<p style='color: green;'>✅ Bookings collection created successfully!</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠ Bookings collection already exists.</p>";
    }
    
    $bookingsCollection = $database->selectCollection('bookings');
    
    // Step 2: Create indexes for bookings
    echo "<h2>Step 2: Creating Indexes</h2>";
    $bookingsCollection->createIndex(['bookingReference' => 1], ['unique' => true]);
    $bookingsCollection->createIndex(['user_id' => 1]);
    $bookingsCollection->createIndex(['booking_date' => -1]);
    $bookingsCollection->createIndex(['status' => 1]);
    echo "<p style='color: green;'>✅ Indexes created successfully!</p>";
    
    // Step 3: Check if we have sample data to insert
    echo "<h2>Step 3: Checking for Sample Data</h2>";
    
    // Check if packages exist to create realistic bookings
    $packagesCollection = $database->selectCollection('packages');
    $packageCount = $packagesCollection->countDocuments();
    
    if ($packageCount > 0) {
        // Get some packages to create sample bookings
        $packages = $packagesCollection->find()->toArray();
        
        $sampleBookings = [
            [
                'bookingReference' => 'BOOK-' . uniqid(),
                'package' => $packages[0]['name'],
                'name' => 'John Smith',
                'email' => 'john.smith@email.com',
                'phone' => '+251911223344',
                'tourDate' => '2024-03-15',
                'passengers' => 2,
                'paymentMethod' => 'CBE Bank',
                'transactionId' => 'TXN-' . uniqid(),
                'total_price' => $packages[0]['price'] * 2,
                'status' => 'paid',
                'user_id' => 1,
                'booking_date' => new MongoDB\BSON\UTCDateTime(strtotime('-5 days') * 1000),
                'created_at' => new MongoDB\BSON\UTCDateTime(strtotime('-5 days') * 1000)
            ],
            [
                'bookingReference' => 'BOOK-' . uniqid(),
                'package' => $packages[1]['name'],
                'name' => 'Sarah Johnson',
                'email' => 'sarah.j@email.com',
                'phone' => '+251922334455',
                'tourDate' => '2024-04-20',
                'passengers' => 4,
                'paymentMethod' => 'TeleBirr',
                'transactionId' => 'TXN-' . uniqid(),
                'total_price' => $packages[1]['price'] * 4,
                'status' => 'confirmed',
                'user_id' => 2,
                'booking_date' => new MongoDB\BSON\UTCDateTime(strtotime('-2 days') * 1000),
                'created_at' => new MongoDB\BSON\UTCDateTime(strtotime('-2 days') * 1000)
            ],
            [
                'bookingReference' => 'BOOK-' . uniqid(),
                'package' => $packages[2]['name'],
                'name' => 'Mike Davis',
                'email' => 'mike.davis@email.com',
                'phone' => '+251933445566',
                'tourDate' => '2024-05-10',
                'passengers' => 1,
                'paymentMethod' => 'CBE Bank',
                'transactionId' => 'TXN-' . uniqid(),
                'total_price' => $packages[2]['price'],
                'status' => 'pending',
                'user_id' => 3,
                'booking_date' => new MongoDB\BSON\UTCDateTime(),
                'created_at' => new MongoDB\BSON\UTCDateTime()
            ]
        ];
        
        // Check if bookings already exist
        $existingBookings = $bookingsCollection->countDocuments();
        
        if ($existingBookings === 0) {
            $result = $bookingsCollection->insertMany($sampleBookings);
            echo "<p style='color: green;'>✅ " . $result->getInsertedCount() . " sample bookings inserted successfully!</p>";
            
            // Display inserted bookings
            echo "<h3>Sample Bookings Created:</h3>";
            foreach ($sampleBookings as $booking) {
                echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
                echo "<strong>Reference:</strong> {$booking['bookingReference']}<br>";
                echo "<strong>Package:</strong> {$booking['package']}<br>";
                echo "<strong>Customer:</strong> {$booking['name']}<br>";
                echo "<strong>Travel Date:</strong> {$booking['tourDate']}<br>";
                echo "<strong>Total:</strong> $" . number_format($booking['total_price'], 2) . "<br>";
                echo "<strong>Status:</strong> <span style='color: " . getStatusColor($booking['status']) . "; font-weight: bold;'>{$booking['status']}</span>";
                echo "</div>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ Bookings collection already contains $existingBookings bookings. No new bookings inserted.</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ No packages found. Please run packages setup first.</p>";
    }
    
    // Step 4: Verification
    echo "<h2>Step 4: Verification</h2>";
    $totalBookings = $bookingsCollection->countDocuments();
    echo "<p style='color: green;'>✅ Total bookings in database: $totalBookings</p>";
    
    // Show booking statistics
    $statusStats = $bookingsCollection->aggregate([
        ['$group' => [
            '_id' => '$status',
            'count' => ['$sum' => 1],
            'totalRevenue' => ['$sum' => '$total_price']
        ]]
    ])->toArray();
    
    echo "<h3>Booking Statistics:</h3>";
    foreach ($statusStats as $stat) {
        echo "<p><strong>" . ucfirst($stat['_id']) . ":</strong> {$stat['count']} bookings ($" . number_format($stat['totalRevenue'], 2) . ")</p>";
    }
    
    echo "<h2 style='color: green;'>🎉 Bookings Setup Completed Successfully!</h2>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

function getStatusColor($status) {
    switch ($status) {
        case 'paid': return '#28a745';
        case 'confirmed': return '#17a2b8';
        case 'pending': return '#ffc107';
        case 'cancelled': return '#dc3545';
        default: return '#6c757d';
    }
}

echo "</div>";
?>