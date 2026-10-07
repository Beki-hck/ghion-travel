<?php
require_once 'vendor/autoload.php';

echo "<h1>Setting up MongoDB Packages Collection</h1>";
echo "<div style='font-family: Arial; padding: 20px;'>";

try {
    // Connect to MongoDB
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    
    // Step 1: Create packages collection
    echo "<h2>Step 1: Creating Packages Collection</h2>";
    try {
        $database->createCollection('packages');
        echo "<p style='color: green;'>✅ Packages collection created successfully!</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠ Packages collection already exists.</p>";
    }
    
    $packagesCollection = $database->selectCollection('packages');
    
    // Step 2: Clear existing packages to avoid duplicates
    $packagesCollection->deleteMany([]);
    echo "<p style='color: green;'>✅ Cleared existing packages</p>";
    
    // Step 3: Insert sample packages with proper image paths
    echo "<h2>Step 3: Inserting Sample Packages with Images</h2>";
    
    $samplePackages = [
        [
            'name' => 'Northern Ethiopia Historical Tour',
            'price' => 1200.00,
            'duration' => '7 days',
            'description' => 'Explore the historical sites of Northern Ethiopia including Lalibela, Axum, and Gondar. Visit rock-hewn churches and ancient castles.',
            'destination' => 'Northern Ethiopia',
            'image' => 'northern_tour.jpg', // This file should be in images/ folder
            'image_alt' => 'Northern Ethiopia Historical Sites',
            'highlights' => ['Lalibela', 'Axum', 'Gondar', 'Simien Mountains'],
            'includes' => ['Hotel accommodation', 'Guide services', 'Transportation', 'Entrance fees'],
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Southern Ethiopia Cultural Tour',
            'price' => 950.00,
            'duration' => '5 days',
            'description' => 'Experience the diverse cultures and tribes of Southern Ethiopia. Visit the Omo Valley and meet local communities.',
            'destination' => 'Southern Ethiopia',
            'image' => 'southern_tour.jpg', // This file should be in images/ folder
            'image_alt' => 'Southern Ethiopia Cultural Experience',
            'highlights' => ['Omo Valley', 'Tribal villages', 'Cultural experiences', 'Local markets'],
            'includes' => ['Hotel accommodation', 'Cultural guide', 'Transportation', 'Permits'],
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Danakil Depression Adventure',
            'price' => 850.00,
            'duration' => '3 days',
            'description' => 'Adventure tour to the hottest place on earth - Danakil Depression. See volcanic landscapes and salt lakes.',
            'destination' => 'Eastern Ethiopia',
            'image' => 'danakil_tour.jpg', // This file should be in images/ folder
            'image_alt' => 'Danakil Depression Volcanic Landscape',
            'highlights' => ['Erta Ale Volcano', 'Dallol Sulfur Springs', 'Salt Lakes', 'Desert Landscape'],
            'includes' => ['Camping equipment', 'Security escort', 'Transportation', 'Meals'],
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Addis Ababa City Tour',
            'price' => 150.00,
            'duration' => '1 day',
            'description' => 'Explore the capital city of Ethiopia with its rich history and culture. Visit museums, churches, and markets.',
            'destination' => 'Addis Ababa',
            'image' => 'addis_tour.jpg', // This file should be in images/ folder
            'image_alt' => 'Addis Ababa City Tour',
            'highlights' => ['National Museum', 'Holy Trinity Cathedral', 'Merkato Market', 'Entoto Mountain'],
            'includes' => ['Professional guide', 'Transportation', 'Entrance fees', 'Lunch'],
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Bale Mountains National Park',
            'price' => 700.00,
            'duration' => '4 days',
            'description' => 'Explore the beautiful Bale Mountains with unique wildlife and stunning landscapes. Home to Ethiopian wolves.',
            'destination' => 'Southern Ethiopia',
            'image' => 'bale_mountains.jpg', // This file should be in images/ folder
            'image_alt' => 'Bale Mountains National Park',
            'highlights' => ['Ethiopian Wolves', 'Sanetti Plateau', 'Harenna Forest', 'Wildlife Viewing'],
            'includes' => ['Lodge accommodation', 'Park fees', 'Transportation', 'Guide services'],
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]
    ];
    
    $result = $packagesCollection->insertMany($samplePackages);
    echo "<p style='color: green;'>✅ " . $result->getInsertedCount() . " sample packages inserted successfully!</p>";
    
    // Check if image files exist
    echo "<h3>Image File Status:</h3>";
    foreach ($samplePackages as $package) {
        $imagePath = 'images/' . $package['image'];
        if (file_exists($imagePath)) {
            echo "<p style='color: green;'>✅ {$package['image']} - File exists</p>";
        } else {
            echo "<p style='color: orange;'>⚠ {$package['image']} - File missing (create this image in images/ folder)</p>";
        }
    }
    
    echo "<h2 style='color: green;'>🎉 Packages Setup Completed Successfully!</h2>";
    echo "<p><a href='packages_mongo.php' style='color: blue;'>View Packages Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "</div>";
?>