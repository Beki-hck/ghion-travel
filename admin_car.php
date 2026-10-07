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
    $carsCollection = $database->selectCollection('car');

    // Handle car deletion
    if (isset($_POST['delete_car'])) {
        $carId = $_POST['car_id'];
        $carsCollection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($carId)]);
        $_SESSION['success_message'] = 'car deleted successfully!';
        header("Location: admin_car.php");
        exit;
    }

    // Get all car
    $cars = $carsCollection->find([], ['sort' => ['created_at' => -1]])->toArray();

} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tour cars -  Travel</title>
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
                <li><a href="admin_car.php" class="active"><i class="fas fa-car"></i> cars</a></li>
                <li><a href="admin_hotel.php"><i class="fas fa-hotel"></i> Hotels</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="admin_contacts.php"><i class="fas fa-comments"></i> Contacts & Feedback</a></li>
                <li><a href="admin_analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                <li><a href="admin_notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                <li><a href="admin_settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Manage Tour Cars</h1>
                <div>
                    <a href="admin_add_car.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add New car
                    </a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="car-title">All Tour cars (<?php echo count($cars); ?>)</h3>
                </div>
            
                <?php if (empty($cars)): ?>
                    <div class="no-data">
                        <i class="fas fa-suitcase"></i>
                        <h3>No cars Found</h3>
                        <p>There are no tour cars in the system yet.</p>
                        <a href="admin_add_car.php" class="btn btn-success" style="margin-top: 15px;">
                            <i class="fas fa-plus"></i> Add Your First car
                        </a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Car</th>
                                    <th>Destination</th>
                                    <th>Duration</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Featured</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cars as $car): ?>
                                <tr>
                                    <td>
                                        <div class="car-info">
                                            <?php if (isset($car['image']) && !empty($car['image'])): ?>
                                                 <img src="images/<?php echo htmlspecialchars($car['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($car['image_alt'] ?? $car['name']);; ?>" 
                                                     class="car-image">
                                            <?php else: ?>
                                                <div class="image-placeholder">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="car-details">
                                                <div class="car-name"><?php echo htmlspecialchars($car['name']); ?></div>
                                                <div class="car-category"><?php echo htmlspecialchars($car['category'] ?? 'General'); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($car['destination'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($car['duration'] ?? 'N/A'); ?></td>
                                    <td>
                                        <strong>$<?php echo number_format($car['price'] ?? 0, 2); ?></strong>
                                        <?php if (isset($car['discount_price']) && $car['discount_price'] > 0): ?>
                                            <div style="color: #e74c3c; font-size: 12px;">
                                                <s>$<?php echo number_format($car['price'], 2); ?></s>
                                                $<?php echo number_format($car['discount_price'], 2); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo htmlspecialchars($car['status'] ?? 'active'); ?>">
                                            <?php echo ucfirst(htmlspecialchars($car['status'] ?? 'active')); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($car['featured'] ?? false): ?>
                                            <i class="fas fa-star" style="color: #ffc107;"></i>
                                            <span style="color: #666; font-size: 12px;">Featured</span>
                                        <?php else: ?>
                                            <span style="color: #666; font-size: 12px;">Regular</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($car['created_at'])) {
                                            if ($car['created_at'] instanceof MongoDB\BSON\UTCDateTime) {
                                                echo $car['created_at']->toDateTime()->format('M j, Y');
                                            } else {
                                                echo date('M j, Y', strtotime($car['created_at']));
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="actions-column">
                                            <a href="admin_view_car.php?id=<?php echo $car['_id']; ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="admin_edit_car.php?id=<?php echo $car['_id']; ?>" 
                                               class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="car_id" value="<?php echo $car['_id']; ?>">
                                                <button type="submit" name="delete_car" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Are you sure you want to delete this car?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>