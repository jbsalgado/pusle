<?php
$body = '{"instance_id":"teste","version":"0.7.1"}';
$secret = '429683C4C977415CAAFCCE10F7D57E11';

$signature = hash_hmac('sha256', $body, $secret);

$ch = curl_init('https://license.evolutionfoundation.com.br/v1/activate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Api-Key: ' . $secret,
    'X-Signature: ' . $signature
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpcode . "\n";
echo "Response: " . $response . "\n";
