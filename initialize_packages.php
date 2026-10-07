<?php
// initialize_packages.php
require_once 'vendor/autoload.php';

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $packagesCollection = $database->selectCollection('packages');
    
    // Drop existing collection to start fresh
    $packagesCollection->drop();
    
    // Sample tour packages data
    $samplePackages = [
        [
            'name' => 'Lalibela Rock-Hewn Churches Tour',
            'description' => 'Explore the magnificent rock-hewn churches of Lalibela, a UNESCO World Heritage site and one of Ethiopia\'s most famous historical landmarks. Visit all 11 medieval monolithic cave churches dating back to the 13th century.',
            'destination' => 'Lalibela',
            'price' => 650,
            'duration' => '3 days',
            'image' => 'lalibela.jpg',
            'highlights' => ['Rock churches', 'Religious sites', 'UNESCO Heritage', 'Medieval architecture'],
            'category' => 'historical',
            'region' => 'northern',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Simien Mountains Trekking Adventure',
            'description' => 'Trek through the stunning Simien Mountains National Park, home to unique wildlife including the Walia Ibex, Gelada Baboons, and Ethiopian wolves. Experience breathtaking views from Africa\'s largest continuous mountain range.',
            'destination' => 'Simien Mountains',
            'price' => 850,
            'duration' => '5 days',
            'image' => 'simien.jpg',
            'highlights' => ['Mountain trekking', 'Wildlife viewing', 'Scenic landscapes', 'Camping'],
            'category' => 'adventure',
            'region' => 'northern',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Danakil Depression Expedition',
            'description' => 'Visit one of the hottest places on Earth with surreal landscapes, active volcanoes, and colorful salt lakes. Witness the incredible Erta Ale volcano and the colorful hydrothermal fields of Dallol.',
            'destination' => 'Danakil Depression',
            'price' => 750,
            'duration' => '4 days',
            'image' => 'danakil.jpg',
            'highlights' => ['Active volcanoes', 'Salt lakes', 'Extreme landscapes', 'Geothermal activity'],
            'category' => 'adventure',
            'region' => 'eastern',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Omo Valley Cultural Experience',
            'description' => 'Experience the diverse cultures and traditions of the indigenous tribes in the Lower Omo Valley. Meet the Mursi, Hamar, Karo, and other tribes known for their unique customs and body decorations.',
            'destination' => 'Omo Valley',
            'price' => 950,
            'duration' => '6 days',
            'image' => 'omo.jpg',
            'highlights' => ['Tribal cultures', 'Traditional villages', 'Photography', 'Cultural immersion'],
            'category' => 'cultural',
            'region' => 'southern',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Axum Ancient Kingdom Tour',
            'description' => 'Discover the ancient city of Axum, home to monumental obelisks, ancient ruins, and the Church of St. Mary of Zion. Explore the birthplace of Ethiopian civilization and see the legendary Ark of the Covenant site.',
            'destination' => 'Axum',
            'price' => 550,
            'duration' => '2 days',
            'image' => 'axum.jpg',
            'highlights' => ['Ancient obelisks', 'Historical ruins', 'Religious sites', 'Archaeological sites'],
            'category' => 'historical',
            'region' => 'northern',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Blue Nile Falls & Lake Tana Monasteries',
            'description' => 'Witness the magnificent Blue Nile Falls and explore the ancient monasteries on Lake Tana islands. Visit the source of the Blue Nile and discover medieval churches with priceless religious artifacts.',
            'destination' => 'Bahir Dar',
            'price' => 450,
            'duration' => '3 days',
            'image' => 'blue-nile.jpg',
            'highlights' => ['Waterfalls', 'Lake monasteries', 'Bird watching', 'Boat trips'],
            'category' => 'natural',
            'region' => 'central',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Harar Ancient Walled City',
            'description' => 'Explore the ancient walled city of Harar, a UNESCO World Heritage site known for its unique architecture and hyena feeding tradition. Discover 82 mosques and 102 shrines within the old city walls.',
            'destination' => 'Harar',
            'price' => 480,
            'duration' => '2 days',
            'image' => 'harar.jpg',
            'highlights' => ['Walled city', 'Hyena feeding', 'Islamic heritage', 'Traditional houses'],
            'category' => 'cultural',
            'region' => 'eastern',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Bale Mountains National Park',
            'description' => 'Explore the diverse ecosystems of Bale Mountains National Park, from Afro-alpine moorlands to Harenna Forest. Spot endemic wildlife including the Ethiopian wolf and mountain nyala.',
            'destination' => 'Bale Mountains',
            'price' => 720,
            'duration' => '4 days',
            'image' => 'bale.jpg',
            'highlights' => ['Wildlife safari', 'Mountain scenery', 'Endemic species', 'Hiking trails'],
            'category' => 'natural',
            'region' => 'southern',
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]
    ];
    
    // Insert sample packages
    $result = $packagesCollection->insertMany($samplePackages);
    
    echo "<h1>Database Initialized Successfully!</h1>";
    echo "<p>Added " . $result->getInsertedCount() . " sample packages to MongoDB.</p>";
    echo "<p><a href='packages_mongo.php'>View Packages</a></p>";
    
} catch (Exception $e) {
    echo "<h1>Error Initializing Database</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Make sure MongoDB is running on localhost:27017</p>";
}
?>