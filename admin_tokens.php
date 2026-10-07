<?php
session_start();
require_once 'vendor/autoload.php';

// Check if user is admin
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

try {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase('ghion_travel');
    $passwordResetsCollection = $database->selectCollection('password_resets');
    
    // Get all active reset tokens
    $tokens = $passwordResetsCollection->find([], [
        'sort' => ['created_at' => -1]
    ])->toArray();
    
} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Password Reset Tokens - Admin</title>
    <style>
        .token-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .token-table th, .token-table td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        .token-table th { background: #f5f5f5; }
        .token-code { font-family: monospace; background: #f9f9f9; padding: 4px 8px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Password Reset Tokens</h1>
    <table class="token-table">
        <thead>
            <tr>
                <th>Email</th>
                <th>Token</th>
                <th>Created</th>
                <th>Expires</th>
                <th>Reset Link</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tokens as $token): ?>
            <tr>
                <td><?php echo htmlspecialchars($token['email']); ?></td>
                <td><code class="token-code"><?php echo htmlspecialchars($token['token']); ?></code></td>
                <td><?php echo date('Y-m-d H:i:s', $token['created_at']->toDateTime()->getTimestamp()); ?></td>
                <td><?php echo date('Y-m-d H:i:s', $token['expires']->toDateTime()->getTimestamp()); ?></td>
                <td><a href="reset_password.php?token=<?php echo $token['token']; ?>">Reset Link</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>