<?php
// booking_error.php
$error = $_GET['error'] ?? 'An unknown error occurred';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Error - Ghion Travel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .error-container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            text-align: center;
            padding: 40px;
        }
        
        .error-icon {
            font-size: 80px;
            color: #e74c3c;
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 36px;
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .error-message {
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #e74c3c;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #2a6496;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1d4f7a;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .error-container {
                padding: 30px 20px;
            }
            
            .error-title {
                font-size: 28px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h1 class="error-title">Booking Error</h1>
        
        <div class="error-message">
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
        
        <div class="action-buttons">
            <a href="javascript:history.back()" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Go Back
            </a>
            <a href="packages_mongo.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> Browse Packages
            </a>
            <a href="contact.php" class="btn btn-primary">
                <i class="fas fa-headset"></i> Contact Support
            </a>
        </div>
    </div>
</body>
</html>