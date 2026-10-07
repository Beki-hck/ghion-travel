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

    // Handle package deletion
    if (isset($_POST['delete_package'])) {
        $packageId = $_POST['package_id'];
        
        if (class_exists('MongoDB\BSON\ObjectId')) {
            $objectId = new MongoDB\BSON\ObjectId($packageId);
        } else {
            $objectId = $packageId;
        }
        
        $deleteResult = $packagesCollection->deleteOne(['_id' => $objectId]);
        
        if ($deleteResult->getDeletedCount() > 0) {
            $_SESSION['success_message'] = 'Package deleted successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to delete package. Package not found.';
        }
        
        header("Location: admin_packages.php");
        exit;
    }

    // Get all packages with proper error handling
    $packages = [];
    $cursor = $packagesCollection->find([], ['sort' => ['created_at' => -1]]);
    
    if (method_exists($cursor, 'toArray')) {
        $packages = $cursor->toArray();
    } else {
        foreach ($cursor as $document) {
            $packages[] = $document;
        }
    }

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Database connection failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tour Packages - Ghion Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="adminstyles.css">
   <style>
        /* Custom Confirmation Modal Styles */
        .confirmation-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .confirmation-modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 400px;
            overflow: hidden;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .modal-header i {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .modal-body {
            padding: 25px;
            text-align: center;
        }

        .modal-body h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .modal-body p {
            color: #666;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .modal-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 100px;
        }

        .modal-btn-cancel {
            background: #95a5a6;
            color: white;
        }

        .modal-btn-cancel:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }

        .modal-btn-confirm {
            background: #e74c3c;
            color: white;
        }

        .modal-btn-confirm:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        /* Package card styles for better presentation */
        .package-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #2c5530;
        }

        .package-highlight {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
   </style>
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
                <li><a href="admin_car.php"><i class="fas fa-car"></i>Car</a></li>
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
                <h1>Manage Tour Packages</h1>
                <div>
                    <a href="admin_add_package.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add New Package
                    </a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <!-- Sample Package Highlights -->
            <div class="card">
                <div class="card-header">
                    <h3>Featured Packages</h3>
                </div>
                <div style="padding: 20px;">
                    <div class="package-card package-highlight">
                        <h4>Addis Ababa City Tour</h4>
                        <p><strong>Duration:</strong> 4 days</p>
                        <p><strong>Price:</strong> $450.00</p>
                        <p><strong>Status:</strong> <span class="status-badge status-active">ACTIVE</span></p>
                        <p><strong>Type:</strong> Regular</p>
                        <p><strong>Created:</strong> Oct 21, 2025</p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Tour Packages (<?php echo count($packages); ?>)</h3>
                </div>
                
                <?php if (empty($packages)): ?>
                    <div class="no-data">
                        <i class="fas fa-suitcase"></i>
                        <h3>No Packages Found</h3>
                        <p>There are no tour packages in the system yet.</p>
                        <a href="admin_add_package.php" class="btn btn-success" style="margin-top: 15px;">
                            <i class="fas fa-plus"></i> Add Your First Package
                        </a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Package</th>
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
                                <?php foreach ($packages as $package): 
                                    $packageId = '';
                                    if (isset($package['_id'])) {
                                        if (is_object($package['_id']) && method_exists($package['_id'], '__toString')) {
                                            $packageId = $package['_id']->__toString();
                                        } else {
                                            $packageId = (string)$package['_id'];
                                        }
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <div class="package-info">   
                                            <?php if (isset($package['image']) && !empty($package['image'])): ?>
                                                <img src="images/<?php echo htmlspecialchars($package['image']); ?>" 
                                                      alt="<?php echo htmlspecialchars($package['image_alt'] ?? $package['name']); ?>" 
                                                     class="package-image">
                                            <?php else: ?>
                                                <div class="image-placeholder">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="package-details">
                                                <div class="package-name"><?php echo htmlspecialchars($package['name'] ?? 'Unnamed Package'); ?></div>
                                                <div class="package-category"><?php echo htmlspecialchars($package['category'] ?? 'General'); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($package['destination'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($package['duration'] ?? 'N/A'); ?></td>
                                    <td>
                                        <strong>$<?php echo number_format($package['price'] ?? 0, 2); ?></strong>
                                        <?php if (isset($package['discount_price']) && $package['discount_price'] > 0): ?>
                                            <div style="color: #e74c3c; font-size: 12px;">
                                                <s>$<?php echo number_format($package['price'], 2); ?></s>
                                                $<?php echo number_format($package['discount_price'], 2); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo htmlspecialchars($package['status'] ?? 'active'); ?>">
                                            <?php echo ucfirst(htmlspecialchars($package['status'] ?? 'active')); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($package['featured'] ?? false): ?>
                                            <i class="fas fa-star" style="color: #ffc107;"></i>
                                            <span style="color: #666; font-size: 12px;">Featured</span>
                                        <?php else: ?>
                                            <span style="color: #666; font-size: 12px;">Regular</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($package['created_at'])) {
                                            if (is_object($package['created_at']) && method_exists($package['created_at'], 'toDateTime')) {
                                                echo $package['created_at']->toDateTime()->format('M j, Y');
                                            } else {
                                                echo date('M j, Y', strtotime($package['created_at']));
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="actions-column">
                                            <a href="admin_view_package.php?id=<?php echo $packageId; ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="admin_edit_package.php?id=<?php echo $packageId; ?>" 
                                               class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm delete-btn" 
                                                    data-package-id="<?php echo $packageId; ?>"
                                                    data-package-name="<?php echo htmlspecialchars($package['name'] ?? 'Unnamed Package'); ?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
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

    <!-- Custom Confirmation Modal -->
    <div class="confirmation-modal" id="confirmationModal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h2>Confirm Deletion</h2>
            </div>
            <div class="modal-body">
                <h3>Are you sure you want to delete this package?</h3>
                <p>This action cannot be undone. The package will be permanently removed from the system.</p>
                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-btn-cancel" id="cancelDelete">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <form method="POST" id="deleteForm" style="display: inline;">
                        <input type="hidden" name="package_id" id="deletePackageId">
                        <button type="submit" name="delete_package" class="modal-btn modal-btn-confirm">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('confirmationModal');
            const deleteForm = document.getElementById('deleteForm');
            const deletePackageId = document.getElementById('deletePackageId');
            const cancelBtn = document.getElementById('cancelDelete');
            
            // Get all delete buttons
            const deleteButtons = document.querySelectorAll('.delete-btn');
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const packageId = this.getAttribute('data-package-id');
                    const packageName = this.getAttribute('data-package-name');
                    
                    const modalBody = modal.querySelector('.modal-body h3');
                    modalBody.textContent = `Are you sure you want to delete "${packageName}"?`;
                    
                    // Set the package ID in the form
                    deletePackageId.value = packageId;
                    
                    // Show the modal
                    modal.classList.add('active');
                });
            });
            
            cancelBtn.addEventListener('click', function() {
                modal.classList.remove('active');
            });
            
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.classList.contains('active')) {
                    modal.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>