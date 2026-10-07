<?php
header('Content-Type: application/json');
require_once '../vendor/autoload.php';

// CBE API Configuration
$cbeConfig = [
    'api_key' => 'your_cbe_api_key',
    'merchant_id' => 'your_merchant_id',
    'base_url' => 'https://api.cbe.com.et' // CBE API endpoint
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $amount = $input['amount'] ?? 0;
    $reference = $input['reference'] ?? '';
    $account = $input['account'] ?? '';
    
    try {
        // Generate CBE QR code using their API
        $qrData = generateCBEQRData($amount, $reference, $account);
        $qrCodeUrl = generateQRCodeImage($qrData);
        
        echo json_encode([
            'success' => true,
            'qrCodeUrl' => $qrCodeUrl,
            'qrData' => $qrData,
            'reference' => $reference
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function generateCBEQRData($amount, $reference, $account) {
    // CBE QR code standard format for Ethiopia
    $data = [
        'version' => '1',
        'type' => 'CBE',
        'merchant' => 'Ghion Travel',
        'account' => $account,
        'amount' => $amount,
        'currency' => 'ETB',
        'reference' => $reference,
        'timestamp' => time()
    ];
    
    return base64_encode(json_encode($data));
}

function generateQRCodeImage($data) {
    // Use a QR code generation library
    require_once '../vendor/endroid/qr-code/src/Builder/Builder.php';
    require_once '../vendor/endroid/qr-code/src/Writer/PngWriter.php';
    
    $builder = new \Endroid\QrCode\Builder\Builder();
    $result = $builder
        ->data($data)
        ->size(300)
        ->margin(10)
        ->build();
    
    // Save QR code to file
    $filename = 'qr_codes/' . uniqid() . '.png';
    $result->saveToFile($filename);
    
    return $filename;
}
?>