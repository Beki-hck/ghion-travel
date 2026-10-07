<?php
require_once 'vendor/autoload.php';

class MongoDBConnection {
    private $client;
    private $database;
    
    public function __construct() {
        try {
            // Connect to MongoDB
            $this->client = new MongoDB\Client("mongodb://localhost:27017");
            $this->database = $this->client->selectDatabase('ghion_travel');
            echo "✅ Connected to MongoDB successfully!<br>";
        } catch (Exception $e) {
            die("❌ MongoDB connection failed: " . $e->getMessage());
        }
    }
    
    public function getDatabase() {
        return $this->database;
    }
    
    public function getClient() {
        return $this->client;
    }
}

// Create global connection
try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $mongoDatabase = $mongoClient->selectDatabase('ghion_travel');
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>