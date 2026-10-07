<?php
header('Content-Type: application/json');
require_once '../vendor/autoload.php';

// TeleBirr API Configuration
$telebirrConfig = [
    'app_id' => 'your_telebirr_app_id',
    'app_key' => 'your_telebirr_app_key',
    'merchant_code' => 'your_merchant_code',
    'base_url' => 'https://api.telebirr.com' // TeleBirr API endpoint
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $amount = $input['amount'] ?? 0;
    $reference = $input['reference'] ?? '';
    $phone = $input['phone'] ?? '0912345678';
    
    try {
        // Generate TeleBirr QR code using their API
        $qrResponse = generateTeleBirrQRCode($amount, $reference, $phone);
        
        echo json_encode([
            'success' => true,
            'qrCodeUrl' => $qrResponse['qrCodeUrl'],
            'paymentUrl' => $qrResponse['paymentUrl'],
            'reference' => $reference
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function generateTeleBirrQRCode($amount, $reference, $phone) {
    global $telebirrConfig;
    
    // Convert USD to ETB (approximate conversion)
    $amountETB = $amount * 55; // 1 USD ≈ 55 ETB
    
    // TeleBirr API request data
    $requestData = [
        'appId' => $telebirrConfig['app_id'],
        'appKey' => $telebirrConfig['app_key'],
        'merchantCode' => $telebirrConfig['merchant_code'],
        'amount' => $amountETB,
        'currency' => 'ETB',
        'orderNo' => $reference,
        'orderTime' => date('YmdHis'),
        'notifyUrl' => 'https://yourdomain.com/api/telebirr-callback.php',
        'returnUrl' => 'https://yourdomain.com/booking_confirmation.php',
        'subject' => 'Tour Booking - ' . $reference,
        'body' => 'Payment for tour booking reference: ' . $reference
    ];
    
    // Generate signature
    $requestData['sign'] = generateTeleBirrSignature($requestData);
    
    // Call TeleBirr API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $telebirrConfig['base_url'] . '/api/v1/qrcode/create');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        
        if ($responseData['code'] === '200') {
            return [
                'qrCodeUrl' => $responseData['data']['qrCode'],
                'paymentUrl' => $responseData['data']['paymentUrl']
            ];
        } else {
            throw new Exception('TeleBirr API Error: ' . $responseData['msg']);
        }
    } else {
        throw new Exception('TeleBirr API Connection Failed');
    }
}

function generateTeleBirrSignature($data) {
    // TeleBirr signature generation logic
    ksort($data);
    $signString = '';
    foreach ($data as $key => $value) {
        if ($key !== 'sign' && $value !== '') {
            $signString .= $key . '=' . $value . '&';
        }
    }
    $signString = rtrim($signString, '&');
    
    return strtoupper(md5($signString));
}
?>