<?php
session_start();
require_once 'vendor/autoload.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Handle form submission to add new car
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
        $database = $mongoClient->selectDatabase('ghion_travel');
        $carsCollection = $database->selectCollection('car');
        
       
        $highlights = !empty($_POST['highlights']) ? 
            array_map('trim', explode(',', $_POST['highlights'])) : [];
        $includes = !empty($_POST['includes']) ? 
            array_map('trim', explode(',', $_POST['includes'])) : [];
        
        $carData = [
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description']),
            'destination' => $_POST['destination'],
            'duration' => trim($_POST['duration']),
            'price' => (float)$_POST['price'],
            'discount_price' => !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null,
            'category' => $_POST['category'] ?? 'cultural',
            'image' => trim($_POST['image']),
            'image_alt' => !empty($_POST['image_alt']) ? trim($_POST['image_alt']) : trim($_POST['name']),
            'highlights' => $highlights,
            'includes' => $includes,
            'status' => $_POST['status'] ?? 'active',
            'featured' => isset($_POST['featured']),
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ];
        
        $carsCollection->insertOne($carData);
        $_SESSION['success_message'] = 'car added successfully!';
        header("Location: admin_add_car.php");
        exit;
        
    } catch (Exception $e) {
        $error = "Failed to add car: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New car - Ghion Travel</title>
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
                <h1>Add New Tour Package</h1>
                <a href="admin_packages.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Packages
                </a>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-plus-circle"></i> car Information</h2>
                </div>
                <div class="card-body">
                    <div class="form-container">
                        <form method="POST" id="carForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">car Name *</label>
                                    <input type="text" id="name" name="name" required 
                                           placeholder="e.g., Northern Ethiopia Historical Tour">
                                </div>
                                <div class="form-group">
                                    <label for="price">Price ($) *</label>
                                    <input type="number" id="price" name="price" step="0.01" min="0" required 
                                           placeholder="e.g., 1200.00">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="duration">Duration *</label>
                                    <input type="text" id="duration" name="duration" required 
                                           placeholder="e.g., 7 days / 6 nights">
                                </div>
                                <div class="form-group">
                                    <label for="destination">Destination *</label>
                                    <select id="destination" name="destination" required>
                                        <option value="">Select Destination</option>
                                        <option value="Northern Ethiopia">Northern Ethiopia</option>
                                        <option value="Southern Ethiopia">Southern Ethiopia</option>
                                        <option value="Eastern Ethiopia">Eastern Ethiopia</option>
                                        <option value="Western Ethiopia">Western Ethiopia</option>
                                        <option value="Central Ethiopia">Central Ethiopia</option>
                                        <option value="Addis Ababa">Addis Ababa</option>
                                        <option value="Danakil Depression">Danakil Depression</option>
                                        <option value="festivals">festivals</option>
                                        <option value="Bale Mountains">Bale Mountains</option>
                                        <option value="Simien Mountains">Simien Mountains</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description *</label>
                                <textarea id="description" name="description" rows="5" required 
                                          placeholder="Describe the tour package in detail including attractions, activities, and unique experiences..."></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="category">Category</label>
                                    <select id="category" name="category">
                                        <option value="cultural">Cultural</option>
                                        <option value="adventure">Adventure</option>
                                        <option value="historical">Historical</option>
                                        <option value="nature">Nature</option>
                                        <option value="religious">Religious</option>
                                        <option value="luxury">Luxury</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select id="status" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="draft">Draft</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="image">Image File Name *</label>
                                <input type="text" id="image" name="image" required 
                                       placeholder="e.g., northern_tour.jpg">
                                <div class="form-help">
                                    Enter the filename of the image (must be in images/ folder)
                                </div>
                                
                                <!-- Available Images -->
                                <div class="available-images" id="availableImages">
                                    <!-- Images will be loaded via JavaScript -->
                                </div>
                                
                                <div class="image-preview" id="imagePreview">
                                    <div style="color: #6b7280; text-align: center; padding: 20px;">
                                        <i class="fas fa-image" style="font-size: 48px; margin-bottom: 10px; display: block;"></i>
                                        Image Preview
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="image_alt">Image Alt Text</label>
                                <input type="text" id="image_alt" name="image_alt" 
                                       placeholder="Alternative text for accessibility (optional)">
                            </div>
                            
                            <div class="form-group">
                                <label for="highlights">Highlights</label>
                                <input type="text" id="highlights" name="highlights" 
                                       placeholder="Lalibela, Axum, Gondar, Simien Mountains">
                                <div class="form-help">
                                    Separate multiple highlights with commas
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="includes">What's Included</label>
                                <input type="text" id="includes" name="includes" 
                                       placeholder="Hotel accommodation, Guide services, Transportation, Meals">
                                <div class="form-help">
                                    Separate multiple items with commas
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="discount_price">Discount Price ($)</label>
                                <input type="number" id="discount_price" name="discount_price" step="0.01" min="0" 
                                       placeholder="e.g., 999.00 (optional)">
                                <div class="form-help">
                                    Special discounted price (leave empty if no discount)
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" id="featured" name="featured" value="1">
                                    <label for="featured">Featured car</label>
                                </div>
                                <div class="form-help">
                                    Featured cars will be highlighted on the homepage
                                </div>
                            </div>
                            
                            <button type="submit" class="submit-btn">
                                <i class="fas fa-plus-circle"></i> Add car
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Image preview functionality
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('imagePreview');
        const availableImages = document.getElementById('availableImages');
        
        // Sample images 
        const availableImageList = [
            'northern_tour.jpg',
            'southern_tour.jpg', 
            'danakil_depression.jpg',
            'addis_ababa.jpg',
            'bale_mountains.jpg',
            'simien_mountains.jpg',
            'omo_valley.jpg',
            'lalibela.jpg',
            'axum.jpg',
            'gondar.jpg'
        ];
        
        // Load available images
        function loadAvailableImages() {
            availableImages.innerHTML = '';
            availableImageList.forEach(image => {
                const imgDiv = document.createElement('div');
                imgDiv.className = 'image-option';
                imgDiv.innerHTML = `
                    <img src="images/${image}" alt="${image}" onerror="this.style.display='none'">
                    <div class="image-name">${image}</div>
                `;
                imgDiv.onclick = () => {
                    imageInput.value = image;
                    updateImagePreview();
                    // Remove selected class from all
                    document.querySelectorAll('.image-option').forEach(opt => opt.classList.remove('selected'));
                    // Add to clicked
                    imgDiv.classList.add('selected');
                };
                availableImages.appendChild(imgDiv);
            });
        }
        
        function updateImagePreview() {
            const imageName = imageInput.value.trim();
            if (imageName) {
                imagePreview.innerHTML = `<img src="images/${imageName}" alt="Preview" onerror="showPlaceholder()">`;
            } else {
                showPlaceholder();
            }
        }
        
        function showPlaceholder() {
            imagePreview.innerHTML = `
                <div style="color: #6b7280; text-align: center; padding: 20px;">
                    <i class="fas fa-image" style="font-size: 48px; margin-bottom: 10px; display: block;"></i>
                    Image Preview
                </div>
            `;
        }
        
        // Auto-generate image alt text from package name
        document.getElementById('name').addEventListener('input', function() {
            const imageAltInput = document.getElementById('image_alt');
            if (!imageAltInput.value) {
                imageAltInput.value = this.value;
            }
        });
        
        // Form validation
        document.getElementById('carForm').addEventListener('submit', function(e) {
            const price = parseFloat(document.getElementById('price').value);
            const discountPrice = document.getElementById('discount_price').value;
            
            if (discountPrice && parseFloat(discountPrice) >= price) {
                e.preventDefault();
                alert('Discount price must be less than regular price');
                return false;
            }
        });
        
        // Initialize
        loadAvailableImages();
        updateImagePreview();
        
        // Auto-update preview when typing
        imageInput.addEventListener('input', updateImagePreview);
    </script>
</body>
</html>