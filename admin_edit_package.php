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
    $packagesCollection = $database->selectCollection('packages');
    
    // Get package ID from URL
    $packageId = $_GET['id'] ?? '';
    if (!$packageId) {
        header("Location: admin_packages.php");
        exit;
    }
    
    // Get package data
    $package = $packagesCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($packageId)]);
    if (!$package) {
        header("Location: admin_packages.php");
        exit;
    }
    
    function bsonToArray($bsonArray) {
        if ($bsonArray instanceof MongoDB\Model\BSONArray) {
            return $bsonArray->getArrayCopy();
        }
        return is_array($bsonArray) ? $bsonArray : [];
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $packageData = [
            'name' => $_POST['name'],
            'price' => floatval($_POST['price']),
            'duration' => $_POST['duration'],
            'description' => $_POST['description'],
            'destination' => $_POST['destination'],
            'category' => $_POST['category'],
            'image' => $_POST['image'],
            'image_alt' => $_POST['image_alt'],
            'highlights' => array_filter(array_map('trim', explode(',', $_POST['highlights']))),
            'includes' => array_filter(array_map('trim', explode(',', $_POST['includes']))),
            'status' => $_POST['status'],
            'featured' => isset($_POST['featured']),
            'discount_price' => !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null,
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ];
        
        $result = $packagesCollection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($packageId)],
            ['$set' => $packageData]
        );
        
        if ($result->getModifiedCount() > 0) {
            $_SESSION['success_message'] = 'Package updated successfully!';
            header("Location: admin_packages.php");
            exit;
        } else {
            $error = "No changes made or failed to update package.";
        }
    }
    
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

$highlightsArray = bsonToArray($package['highlights'] ?? []);
$includesArray = bsonToArray($package['includes'] ?? []);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Package - Ghion Travel</title>
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
                 <li><a href="admin_packages.php" class="active"><i class="fas fa-suitcase"></i> Tour Packages</a></li>
                <li><a href="admin_bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="admin_analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                <li><a href="admin_settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Edit Tour Package</h1>
                <a href="admin_packages.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Packages
                </a>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="form-container">
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Package Name *</label>
                                <input type="text" id="name" name="name" required 
                                       value="<?php echo htmlspecialchars($package['name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="price">Price ($) *</label>
                                <input type="number" id="price" name="price" step="0.01" min="0" required 
                                       value="<?php echo $package['price'] ?? 0; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="duration">Duration *</label>
                                <input type="text" id="duration" name="duration" required 
                                       value="<?php echo htmlspecialchars($package['duration'] ?? ''); ?>"
                                       placeholder="e.g., 3 days / 2 nights">
                            </div>
                            <div class="form-group">
                                <label for="destination">Destination *</label>
                                <select id="destination" name="destination" required>
                                    <option value="">Select Destination</option>
                                    <option value="Northern Ethiopia" <?php echo ($package['destination'] ?? '') === 'Northern Ethiopia' ? 'selected' : ''; ?>>Northern Ethiopia</option>
                                    <option value="Southern Ethiopia" <?php echo ($package['destination'] ?? '') === 'Southern Ethiopia' ? 'selected' : ''; ?>>Southern Ethiopia</option>
                                    <option value="Eastern Ethiopia" <?php echo ($package['destination'] ?? '') === 'Eastern Ethiopia' ? 'selected' : ''; ?>>Eastern Ethiopia</option>
                                    <option value="Western Ethiopia" <?php echo ($package['destination'] ?? '') === 'Western Ethiopia' ? 'selected' : ''; ?>>Western Ethiopia</option>
                                    <option value="Central Ethiopia" <?php echo ($package['destination'] ?? '') === 'Central Ethiopia' ? 'selected' : ''; ?>>Central Ethiopia</option>
                                    <option value="Cultural Ethiopia" <?php echo ($package['destination'] ?? '') === 'Cultural Ethiopia' ? 'selected' : ''; ?>>Cultural Ethiopia</option>
                                    <option value="festivals" <?php echo ($package['destination'] ?? '') === 'festivals' ? 'selected' : ''; ?>>festivals</option>
                                    <option value="Addis Ababa" <?php echo ($package['destination'] ?? '') === 'Addis Ababa' ? 'selected' : ''; ?>>Addis Ababa</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="category">Category *</label>
                                <select id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Adventure" <?php echo ($package['category'] ?? '') === 'Adventure' ? 'selected' : ''; ?>>Adventure</option>
                                    <option value="Cultural" <?php echo ($package['category'] ?? '') === 'Cultural' ? 'selected' : ''; ?>>Cultural</option>
                                    <option value="Historical" <?php echo ($package['category'] ?? '') === 'Historical' ? 'selected' : ''; ?>>Historical</option>
                                    <option value="Nature" <?php echo ($package['category'] ?? '') === 'Nature' ? 'selected' : ''; ?>>Nature</option>
                                    <option value="religious" <?php echo ($package['category'] ?? '') === 'religious' ? 'selected' : ''; ?>>religious</option>
                                    <option value="Luxury" <?php echo ($package['category'] ?? '') === 'Luxury' ? 'selected' : ''; ?>>Luxury</option>
                                    <option value="Budget" <?php echo ($package['category'] ?? '') === 'Budget' ? 'selected' : ''; ?>>Budget</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="discount_price">Discount Price ($)</label>
                                <input type="number" id="discount_price" name="discount_price" step="0.01" min="0" 
                                       value="<?php echo $package['discount_price'] ?? ''; ?>"
                                       placeholder="Optional">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description *</label>
                            <textarea id="description" name="description" rows="4" required
                                      placeholder="Describe the tour package in detail..."><?php echo htmlspecialchars($package['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="image">Image File Name *</label>
                                <input type="text" id="image" name="image" required 
                                       value="<?php echo htmlspecialchars($package['image'] ?? ''); ?>"
                                       placeholder="e.g., blue-nile-falls.jpg">
                                <div class="form-help">
                                    Enter the filename of the image (must be in images/ folder)
                                </div>
                                
                                <div class="image-preview" id="imagePreview">
                                    <?php if (isset($package['image']) && !empty($package['image'])): ?>
                                        <img src="images/<?php echo htmlspecialchars($package['image']); ?>" 
                                             alt="Current image" 
                                             onerror="this.style.display='none'; document.getElementById('placeholderContent').style.display='flex';">
                                        <div id="placeholderContent" style="display: none; color: #666; text-align: center; flex-direction: column; align-items: center;">
                                            <i class="fas fa-image" style="font-size: 48px; margin-bottom: 10px;"></i>
                                            <span>Image not found</span>
                                        </div>
                                    <?php else: ?>
                                        <div style="color: #666; text-align: center;">
                                            <i class="fas fa-image" style="font-size: 48px; margin-bottom: 10px;"></i><br>
                                            Image Preview
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="image_alt">Image Alt Text</label>
                                <input type="text" id="image_alt" name="image_alt" 
                                       value="<?php echo htmlspecialchars($package['image_alt'] ?? ''); ?>"
                                       placeholder="Alternative text for the image">
                                
                                <div class="form-row" style="margin-top: 15px;">
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select id="status" name="status">
                                            <option value="active" <?php echo ($package['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo ($package['status'] ?? 'active') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            <option value="draft" <?php echo ($package['status'] ?? 'active') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="checkbox-group">
                                            <input type="checkbox" id="featured" name="featured" value="1" 
                                                   <?php echo ($package['featured'] ?? false) ? 'checked' : ''; ?>>
                                            <label for="featured">Featured Package</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="highlights">Highlights</label>
                            <textarea id="highlights" name="highlights" rows="3"
                                      placeholder="Enter highlights separated by commas"><?php 
                                      echo !empty($highlightsArray) ? htmlspecialchars(implode(', ', $highlightsArray)) : ''; 
                                      ?></textarea>
                            <div class="form-help">
                                Separate multiple highlights with commas (e.g., Guided tour, Hotel pickup, Lunch included)
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="includes">What's Included</label>
                            <textarea id="includes" name="includes" rows="3"
                                      placeholder="Enter included items separated by commas"><?php 
                                      echo !empty($includesArray) ? htmlspecialchars(implode(', ', $includesArray)) : ''; 
                                      ?></textarea>
                            <div class="form-help">
                                Separate multiple items with commas (e.g., Accommodation, Meals, Transportation)
                            </div>
                        </div>
                        
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-save"></i> Update Package
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Image preview functionality
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('imagePreview');
        
        function updateImagePreview() {
            const imageName = imageInput.value.trim();
            if (imageName) {
                imagePreview.innerHTML = `
                    <img src="images/${imageName}" alt="Preview" 
                         onerror="this.style.display='none'; showPlaceholder('Image not found');"
                         onload="this.style.display='block'; hidePlaceholder();">
                    <div id="placeholderContent" style="display: none; color: #666; text-align: center; flex-direction: column; align-items: center;">
                        <i class="fas fa-image" style="font-size: 48px; margin-bottom: 10px;"></i>
                        <span>Image not found</span>
                    </div>
                `;
            } else {
                showPlaceholder('Image Preview');
            }
        }
        
        function showPlaceholder(message) {
            imagePreview.innerHTML = `
                <div style="color: #666; text-align: center;">
                    <i class="fas fa-image" style="font-size: 48px; margin-bottom: 10px;"></i><br>
                    ${message}
                </div>
            `;
        }
        
        function hidePlaceholder() {
            const placeholder = document.getElementById('placeholderContent');
            if (placeholder) {
                placeholder.style.display = 'none';
            }
        }
        
        imageInput.addEventListener('input', updateImagePreview);
        
        // Initialize preview on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateImagePreview();
        });
    </script>
</body>
</html>