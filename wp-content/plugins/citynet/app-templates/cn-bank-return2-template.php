<?php

$logo_new_dir = ABSPATH . 'reservation-json/';
if (!file_exists($logo_new_dir)) {
    mkdir($logo_new_dir, 0755, true);
}


$storageFile =ABSPATH . 'reservation-json/data.json';

// ایجاد فایل داخل دایرکتوری
if (!file_exists($storageFile)) {
    if (touch($storageFile)) {
        // تنظیم مجوزها
        if (chmod($storageFile, 0755)) {
            // echo "File created inside the directory with permissions set successfully.";
        } else {
            // echo "Failed to set permissions.";
        }
    } else {
        // echo "Failed to create file.";
    }
}

$existingData = [];
if (file_exists($storageFile)) {
    $existingContent = file_get_contents($storageFile);
    $existingData = json_decode($existingContent, true) ?: [];
}


$inputData = file_get_contents('php://input');


$newData = json_decode($inputData, true);


if ($newData === null) {
    http_response_code(400);
    // echo json_encode(["error" => "Invalid JSON input"]);
    exit;
}


$existingData[] = $newData;


file_put_contents($storageFile, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));


http_response_code(200);
header('Content-Type: application/json');
// echo json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$x = 'REDIRECT=http://demoplus.citynet.ir/panelReturn?trackid=' . $newData['trackid'] . '&paymentid=' . $newData['paymentid'] . '&trandata=' . $newData['trandata'];

echo $x;
?>