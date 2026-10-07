<?php
// create_users_collection.php
require_once 'vendor/autoload.php';

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $usersCollection = $database->selectCollection('users');
    
    // Create unique index on email
    $usersCollection->createIndex(['email' => 1], ['unique' => true]);
    
    echo "Users collection created successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>