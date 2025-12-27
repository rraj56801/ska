<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!isset($_POST['pincode']) || strlen($_POST['pincode']) !== 6) {
    echo json_encode(['status' => false, 'message' => 'Pincode must be 6 digits']);
    exit;
}

$pincode = preg_replace('/\D/', '', $_POST['pincode']);
$url = "http://www.postalpincode.in/api/pincode/$pincode";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($httpCode == 200 && $data && isset($data['Status']) && $data['Status'] == 'Success' && !empty($data['PostOffice'])) {

    $po = $data['PostOffice'][0];
    foreach ($data['PostOffice'] as $office) {
        if ($office['BranchType'] == 'Sub Post Office') {
            $po = $office;
            break;
        }
    }

    echo json_encode([
        'status' => true,
        'state' => $po['State'],
        'district' => $po['District'],
        'city' => $po['Name'],
        'taluk' => $po['Taluk'],
        'pincode' => $pincode,
        'total_offices' => count($data['PostOffice'])
    ]);
} else {
    echo json_encode([
        'status' => false,
        'message' => 'No post offices found for this pincode',
        'http_code' => $httpCode
    ]);
}
?>