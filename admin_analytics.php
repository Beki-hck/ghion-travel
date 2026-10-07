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
    
    $bookingsCollection = $database->selectCollection('bookings');
    
    // Get ALL bookings as array for manual processing
    $allBookings = $bookingsCollection->find()->toArray();
    
    // Initialize variables
    $totalBookings = count($allBookings);
    $totalRevenue = 0;
    $confirmedBookings = 0;
    $completedBookings = 0;
    $pendingBookings = 0;
    $cancelledBookings = 0;
    
    // Payment methods tracking
    $paymentMethods = [];
    $paymentDistribution = [];
    
    $packageStats = [];
    
    $customerStats = [];
    
    $statusStats = [];
    
    
    $foundFields = [];
    
    // Process each booking
    foreach ($allBookings as $booking) {
        $bookingArray = iterator_to_array($booking);
        
        // Collect all field names from the first booking
        if (empty($foundFields)) {
            $foundFields = array_keys($bookingArray);
        }
        
        if (!isset($debugInfo['first_booking'])) {
            $debugInfo['first_booking'] = json_encode($bookingArray, JSON_PRETTY_PRINT);
        }
        
        $amount = 0;
        $amountFields = [
            'total_amount',
            'total_price', 
            'amount',
            'price',
            'payment_info.total_amount',
            'payment_info.amount',
            'payment_info.total_price',
            'tour_details.total_price',
            'tour_details.total_amount',
            'payment.total_amount',
            'payment.amount'
        ];
        
        foreach ($amountFields as $field) {
            if (strpos($field, '.') !== false) {
                $fieldParts = explode('.', $field);
                $value = $bookingArray;
                foreach ($fieldParts as $part) {
                    if (isset($value[$part])) {
                        $value = $value[$part];
                    } else {
                        $value = null;
                        break;
                    }
                }
                if ($value !== null && is_numeric($value)) {
                    $amount = (float)$value;
                    break;
                }
            } elseif (isset($bookingArray[$field]) && is_numeric($bookingArray[$field])) {
                $amount = (float)$bookingArray[$field];
                break;
            }
        }
        
        if ($amount == 0) {
            foreach ($bookingArray as $key => $value) {
                if (is_numeric($value) && $value > 0 && 
                    (stripos($key, 'price') !== false || 
                     stripos($key, 'amount') !== false ||
                     stripos($key, 'total') !== false)) {
                    $amount = (float)$value;
                    break;
                }
            }
        }
        
        $totalRevenue += $amount;
        
        // Get status
        $status = 'unknown';
        if (isset($bookingArray['status'])) {
            $status = $bookingArray['status'];
        } elseif (isset($bookingArray['booking_status'])) {
            $status = $bookingArray['booking_status'];
        }
        
        if (!isset($statusStats[$status])) {
            $statusStats[$status] = ['count' => 0, 'revenue' => 0];
        }
        $statusStats[$status]['count']++;
        $statusStats[$status]['revenue'] += $amount;
        
        // Count by status
        $statusLower = strtolower($status);
        switch ($statusLower) {
            case 'confirmed':
                $confirmedBookings++;
                break;
            case 'completed':
                $completedBookings++;
                break;
            case 'pending':
                $pendingBookings++;
                break;
            case 'cancelled':
                $cancelledBookings++;
                break;
        }
        
        // Get payment method
        $paymentMethod = 'Unknown';
        if (isset($bookingArray['payment_method'])) {
            $paymentMethod = $bookingArray['payment_method'];
        } elseif (isset($bookingArray['payment_info']['payment_method'])) {
            $paymentMethod = $bookingArray['payment_info']['payment_method'];
        } elseif (isset($bookingArray['payment_info']['method'])) {
            $paymentMethod = $bookingArray['payment_info']['method'];
        } elseif (isset($bookingArray['paymentMethod'])) {
            $paymentMethod = $bookingArray['paymentMethod'];
        }
        
        if (!isset($paymentMethods[$paymentMethod])) {
            $paymentMethods[$paymentMethod] = ['count' => 0, 'revenue' => 0];
        }
        $paymentMethods[$paymentMethod]['count']++;
        $paymentMethods[$paymentMethod]['revenue'] += $amount;
        
        // Get package name
        $packageName = 'Unknown Package';
        if (isset($bookingArray['package_name'])) {
            $packageName = $bookingArray['package_name'];
        } elseif (isset($bookingArray['package'])) {
            $packageName = $bookingArray['package'];
        } elseif (isset($bookingArray['tour_details']['package_name'])) {
            $packageName = $bookingArray['tour_details']['package_name'];
        } elseif (isset($bookingArray['tour_package'])) {
            $packageName = $bookingArray['tour_package'];
        }
        
        if (!isset($packageStats[$packageName])) {
            $packageStats[$packageName] = ['count' => 0, 'revenue' => 0];
        }
        $packageStats[$packageName]['count']++;
        $packageStats[$packageName]['revenue'] += $amount;
        
        // Get customer info
        $customerEmail = 'unknown@example.com';
        $customerName = 'Unknown Customer';
        
        if (isset($bookingArray['customer_info']['email'])) {
            $customerEmail = $bookingArray['customer_info']['email'];
            $customerName = ($bookingArray['customer_info']['first_name'] ?? '') . ' ' . 
                           ($bookingArray['customer_info']['last_name'] ?? '');
        } elseif (isset($bookingArray['email'])) {
            $customerEmail = $bookingArray['email'];
            $customerName = $bookingArray['name'] ?? 'Customer';
        } elseif (isset($bookingArray['customer_email'])) {
            $customerEmail = $bookingArray['customer_email'];
            $customerName = $bookingArray['customer_name'] ?? 'Customer';
        }
        
        if (empty(trim($customerName))) {
            $customerName = 'Customer ' . substr($customerEmail, 0, strpos($customerEmail, '@'));
        }
        
        if (!isset($customerStats[$customerEmail])) {
            $customerStats[$customerEmail] = [
                'name' => $customerName,
                'email' => $customerEmail,
                'count' => 0,
                'revenue' => 0
            ];
        }
        $customerStats[$customerEmail]['count']++;
        $customerStats[$customerEmail]['revenue'] += $amount;
    }
    
    $debugInfo['found_fields'] = $foundFields;
    
    // Convert payment methods to array format
    foreach ($paymentMethods as $method => $data) {
        $paymentDistribution[] = [
            '_id' => $method,
            'count' => $data['count'],
            'total_amount' => $data['revenue']
        ];
    }
    
    // Convert package stats to array format
    $tourBookingSummary = [];
    foreach ($packageStats as $package => $data) {
        $tourBookingSummary[] = [
            'package' => ['name' => $package],
            'totalBookings' => $data['count'],
            'sumRevenue' => $data['revenue']
        ];
    }
    
    // Convert status stats to array format
    $bookingStatusDistribution = [];
    foreach ($statusStats as $status => $data) {
        $bookingStatusDistribution[] = [
            '_id' => $status,
            'count' => $data['count'],
            'total_revenue' => $data['revenue']
        ];
    }
    
    // Get top customers
    usort($customerStats, function($a, $b) {
        return $b['revenue'] <=> $a['revenue'];
    });
    $topCustomers = array_slice($customerStats, 0, 5);
    
    // Date range calculations
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $rangeBookings = 0;
    $rangeRevenue = 0;
    $uniqueUsersInRange = [];
    
    foreach ($allBookings as $booking) {
        $bookingArray = iterator_to_array($booking);
        
        // Try to get booking date
        $bookingDate = null;
        $dateFields = ['booking_date', 'created_at', 'date', 'tour_date', 'created_date'];
        
        foreach ($dateFields as $field) {
            if (isset($bookingArray[$field])) {
                $bookingDate = $bookingArray[$field];
                break;
            }
        }
        
        // Convert to timestamp
        $bookingTimestamp = null;
        if ($bookingDate instanceof MongoDB\BSON\UTCDateTime) {
            $bookingTimestamp = $bookingDate->toDateTime()->getTimestamp();
        } elseif (is_string($bookingDate)) {
            $bookingTimestamp = strtotime($bookingDate);
        } elseif (is_numeric($bookingDate)) {
            $bookingTimestamp = $bookingDate;
        }
        
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');
        
        if ($bookingTimestamp && $bookingTimestamp >= $startTimestamp && $bookingTimestamp <= $endTimestamp) {
            $rangeBookings++;
            
            // Get amount
            $amount = 0;
            if (isset($bookingArray['total_amount'])) {
                $amount = (float)$bookingArray['total_amount'];
            } elseif (isset($bookingArray['total_price'])) {
                $amount = (float)$bookingArray['total_price'];
            }
            $rangeRevenue += $amount;
            
            // Track user
            $userEmail = $bookingArray['customer_info']['email'] ?? $bookingArray['email'] ?? null;
            if ($userEmail) {
                $uniqueUsersInRange[$userEmail] = true;
            }
        }
    }
    
    $usersBookedCount = count($uniqueUsersInRange);
    
    // Monthly revenue (simplified)
    $monthlyRevenue = [];
    $currentMonth = date('n');
    $currentYear = date('Y');
    
    for ($i = 5; $i >= 0; $i--) {
        $month = $currentMonth - $i;
        $year = $currentYear;
        
        if ($month < 1) {
            $month += 12;
            $year--;
        }
        
        $monthRevenue = 0;
        $monthBookings = 0;
        
        foreach ($allBookings as $booking) {
            $bookingArray = iterator_to_array($booking);
            $bookingDate = $bookingArray['booking_date'] ?? $bookingArray['created_at'] ?? null;
            
            if ($bookingDate instanceof MongoDB\BSON\UTCDateTime) {
                $bookingDateTime = $bookingDate->toDateTime();
                if ($bookingDateTime->format('n') == $month && $bookingDateTime->format('Y') == $year) {
                    $monthBookings++;
                    
                    $amount = 0;
                    if (isset($bookingArray['total_amount'])) {
                        $amount = (float)$bookingArray['total_amount'];
                    } elseif (isset($bookingArray['total_price'])) {
                        $amount = (float)$bookingArray['total_price'];
                    }
                    $monthRevenue += $amount;
                }
            }
        }
        
        $monthlyRevenue[] = [
            '_id' => ['year' => $year, 'month' => $month],
            'revenue' => $monthRevenue,
            'bookings' => $monthBookings
        ];
    }

} catch (Exception $e) {
    $errorMessage = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports - Ghion Travel</title>
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
                <li><a href="admin_analytics.php" class="active"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                <li><a href="admin_notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
                <li><a href="admin_settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Analytics & Reports</h1>
                <div>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                    <button onclick="toggleDebug()" class="btn btn-primary" style="background: #e74c3c;">
                        <i class="fas fa-bug"></i> Show Debug
                    </button>
                </div>
            </div>
            
            <!-- Debug Info -->
            <div id="debugInfo" class="debug-info" style="display: none;">
                <h3 style="color: #e74c3c; margin-bottom: 15px;">Debug Information</h3>
                
                <div style="margin-bottom: 15px;">
                    <strong>Total Bookings Found:</strong> <?php echo $totalBookings; ?>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <strong>Total Revenue Calculated:</strong> $<?php echo number_format($totalRevenue, 2); ?>
                </div>
                
                <?php if (isset($debugInfo['found_fields'])): ?>
                <div style="margin-bottom: 15px;">
                    <strong>Fields Found in Bookings:</strong>
                    <ul style="margin-left: 20px; margin-top: 5px;">
                        <?php foreach ($debugInfo['found_fields'] as $field): ?>
                        <li><?php echo htmlspecialchars($field); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if (isset($debugInfo['first_booking'])): ?>
                <div>
                    <strong>First Booking Structure:</strong>
                    <pre style="background: #fff; padding: 10px; border-radius: 5px; border: 1px solid #ddd; margin-top: 5px;"><?php echo htmlspecialchars($debugInfo['first_booking']); ?></pre>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($totalRevenue == 0 && $totalBookings > 0): ?>
            <div class="data-alert">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Note:</strong> Revenue is showing as $0.00 but we have <?php echo $totalBookings; ?> bookings. 
                This means the price/amount field might have a different name. Check the debug information above.
            </div>
            <?php endif; ?>
            
            <!-- Date Filter -->
            <div class="date-filter">
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="admin_analytics.php" class="btn btn-primary" style="background: #95a5a6;">
                            <i class="fas fa-sync"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Summary Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalBookings; ?></div>
                    <div class="stat-label">Total Bookings</div>
                    <div class="stat-help">All Time</div>
                </div>
                <div class="stat-card revenue">
                    <div class="stat-number">$<?php echo number_format($totalRevenue, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-help">All Bookings</div>
                </div>
                <div class="stat-card payments">
                    <div class="stat-number"><?php echo $rangeBookings; ?></div>
                    <div class="stat-label">Bookings in Range</div>
                    <div class="stat-help"><?php echo date('M j, Y', strtotime($startDate)) . ' to ' . date('M j, Y', strtotime($endDate)); ?></div>
                </div>
                <div class="stat-card bookings">
                    <div class="stat-number">$<?php echo number_format($rangeRevenue, 2); ?></div>
                    <div class="stat-label">Revenue in Range</div>
                    <div class="stat-help"><?php echo date('M j, Y', strtotime($startDate)) . ' to ' . date('M j, Y', strtotime($endDate)); ?></div>
                </div>
            </div>
            
            <!-- Booking Status Distribution -->
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-chart-pie"></i> Booking Status Distribution
                </h2>
                <div class="status-grid">
                    <?php foreach ($bookingStatusDistribution as $status): ?>
                    <?php 
                    $statusClass = 'unknown';
                    $statusLabel = ucfirst($status['_id']);
                    switch(strtolower($status['_id'])) {
                        case 'confirmed': $statusClass = 'confirmed'; break;
                        case 'completed': $statusClass = 'completed'; break;
                        case 'pending': $statusClass = 'pending'; break;
                        case 'cancelled': $statusClass = 'cancelled'; break;
                    }
                    ?>
                    <div class="status-card <?php echo $statusClass; ?>">
                        <div class="status-count"><?php echo $status['count']; ?></div>
                        <div class="status-label"><?php echo $statusLabel; ?></div>
                        <div style="font-size: 12px; color: #95a5a6; margin-top: 5px;">
                            $<?php echo number_format($status['total_revenue'], 2); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Payment Methods -->
            <?php if (!empty($paymentDistribution)): ?>
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-credit-card"></i> Payment Methods
                </h2>
                <div class="payment-methods">
                    <?php 
                    $totalPayments = array_sum(array_column($paymentDistribution, 'count'));
                    $totalPaymentAmount = array_sum(array_column($paymentDistribution, 'total_amount'));
                    ?>
                    <?php foreach ($paymentDistribution as $payment): 
                        $percentage = $totalPayments > 0 ? round(($payment['count'] / $totalPayments) * 100) : 0;
                    ?>
                    <div class="payment-method">
                        <div class="payment-name"><?php echo htmlspecialchars($payment['_id']); ?></div>
                        <div class="payment-count"><?php echo $payment['count']; ?></div>
                        <div class="payment-amount">$<?php echo number_format($payment['total_amount'], 2); ?></div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <div style="font-size: 12px; color: #7f8c8d;"><?php echo $percentage; ?>% of payments</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Tour Packages -->
            <?php if (!empty($tourBookingSummary)): ?>
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-suitcase"></i> Tour Packages Performance
                </h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Package Name</th>
                                <th>Bookings</th>
                                <th>Total Revenue</th>
                                <th>Avg. Per Booking</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tourBookingSummary as $item): 
                                $avgRevenue = $item['totalBookings'] > 0 ? $item['sumRevenue'] / $item['totalBookings'] : 0;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['package']['name']); ?></td>
                                <td><?php echo $item['totalBookings']; ?></td>
                                <td>$<?php echo number_format($item['sumRevenue'], 2); ?></td>
                                <td>$<?php echo number_format($avgRevenue, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Top Customers -->
            <?php if (!empty($topCustomers)): ?>
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-users"></i> Top Customers
                </h2>
                <div>
                    <?php foreach ($topCustomers as $customer): ?>
                    <div class="customer-row">
                        <div class="customer-info">
                            <div class="customer-name"><?php echo htmlspecialchars($customer['name']); ?></div>
                            <div class="customer-email"><?php echo htmlspecialchars($customer['email']); ?></div>
                        </div>
                        <div class="customer-stats">
                            <div class="customer-amount">$<?php echo number_format($customer['revenue'], 2); ?></div>
                            <div class="customer-bookings"><?php echo $customer['count']; ?> bookings</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Monthly Revenue -->
            <?php if (!empty($monthlyRevenue)): ?>
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-chart-line"></i> Monthly Revenue (Last 6 Months)
                </h2>
                <div>
                    <?php 
                    $maxRevenue = max(array_column($monthlyRevenue, 'revenue'));
                    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    ?>
                    <?php foreach ($monthlyRevenue as $month): 
                        $percentage = $maxRevenue > 0 ? ($month['revenue'] / $maxRevenue) * 100 : 0;
                    ?>
                    <div class="month-row">
                        <div class="month-name">
                            <?php echo $monthNames[$month['_id']['month'] - 1] . ' ' . $month['_id']['year']; ?>
                        </div>
                        <div class="month-stats">
                            <div class="month-revenue">$<?php echo number_format($month['revenue'], 2); ?></div>
                            <div class="month-bookings"><?php echo $month['bookings']; ?> bookings</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleDebug() {
            var debugInfo = document.getElementById('debugInfo');
            if (debugInfo.style.display === 'none') {
                debugInfo.style.display = 'block';
                window.scrollTo(0, 0);
            } else {
                debugInfo.style.display = 'none';
            }
        }
    </script>
</body>
</html>