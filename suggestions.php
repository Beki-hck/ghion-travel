<?php
// suggestions.php
require_once 'auth_check.php';
requireLogin();
require_once 'vendor/autoload.php';

header('Content-Type: application/json');

$area = $_GET['area'] ?? '';

if (empty($area)) {
    echo json_encode(['hotels' => [], 'cars' => []]);
    exit();
}

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    
    // Find hotels in the same area/destination - using 'hotel' collection
    $hotelsCollection = $database->selectCollection('hotel');
    
    // Try different field names that might contain location information
    $hotels = $hotelsCollection->find([
        '$or' => [
            ['location' => ['$regex' => $area, '$options' => 'i']],
            ['destination' => ['$regex' => $area, '$options' => 'i']],
            ['area' => ['$regex' => $area, '$options' => 'i']],
            ['city' => ['$regex' => $area, '$options' => 'i']],
            ['region' => ['$regex' => $area, '$options' => 'i']],
            ['address' => ['$regex' => $area, '$options' => 'i']]
        ]
    ], [
        'limit' => 6
    ])->toArray();
    
    // Find cars available in the same area - using 'car' collection
    $carsCollection = $database->selectCollection('car');
    
    // Try different field names for car locations
    $cars = $carsCollection->find([
        '$or' => [
            ['location' => ['$regex' => $area, '$options' => 'i']],
            ['destination' => ['$regex' => $area, '$options' => 'i']],
            ['area' => ['$regex' => $area, '$options' => 'i']],
            ['city' => ['$regex' => $area, '$options' => 'i']],
            ['region' => ['$regex' => $area, '$options' => 'i']],
            ['available_locations' => ['$regex' => $area, '$options' => 'i']],
            ['available_in' => ['$regex' => $area, '$options' => 'i']],
            ['pickup_location' => ['$regex' => $area, '$options' => 'i']]
        ]
    ], [
        'limit' => 6
    ])->toArray();
    
    // Convert MongoDB objects to arrays for hotels
    $hotelsArray = [];
    foreach ($hotels as $hotel) {
        $hotelsArray[] = [
            '_id' => (string)$hotel['_id'],
            'name' => $hotel['name'] ?? $hotel['hotel_name'] ?? 'Unknown Hotel',
            'location' => $hotel['location'] ?? $hotel['destination'] ?? $hotel['city'] ?? $hotel['area'] ?? 'Unknown Location',
            'price' => $hotel['price'] ?? $hotel['price_per_night'] ?? $hotel['rate'] ?? $hotel['cost'] ?? 0,
            'rating' => $hotel['rating'] ?? $hotel['stars'] ?? $hotel['grade'] ?? 0,
            'image' => $hotel['image'] ?? $hotel['image_url'] ?? $hotel['photo'] ?? $hotel['picture'] ?? '',
            'amenities' => $hotel['amenities'] ?? $hotel['facilities'] ?? $hotel['features'] ?? []
        ];
    }
    
    // Convert MongoDB objects to arrays for cars
    $carsArray = [];
    foreach ($cars as $car) {
        $carsArray[] = [
            '_id' => (string)$car['_id'],
            'name' => $car['name'] ?? $car['model'] ?? $car['car_name'] ?? $car['vehicle_name'] ?? 'Unknown Car',
            'model' => $car['model'] ?? $car['name'] ?? $car['vehicle_model'] ?? 'Unknown Model',
            'type' => $car['type'] ?? $car['category'] ?? $car['vehicle_type'] ?? $car['car_type'] ?? 'Standard',
            'price' => $car['price'] ?? $car['price_per_day'] ?? $car['rate'] ?? $car['daily_rate'] ?? $car['cost'] ?? 0,
            'seating_capacity' => $car['seating_capacity'] ?? $car['capacity'] ?? $car['seats'] ?? $car['passengers'] ?? 0,
            'image' => $car['image'] ?? $car['image_url'] ?? $car['photo'] ?? $car['picture'] ?? '',
            'features' => $car['features'] ?? $car['amenities'] ?? $car['specifications'] ?? []
        ];
    }
    
    echo json_encode([
        'success' => true,
        'hotels' => $hotelsArray,
        'cars' => $carsArray,
        'destination' => $area
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'hotels' => [],
        'cars' => []
    ]);
}
?>