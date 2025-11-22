<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// URL to fetch the JSON data from
$url = "127.0.0.1:8080/status";

// Fetch the JSON data using cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$json_data = curl_exec($ch);

if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch);
    exit();
}

curl_close($ch);

// Decode the JSON data into an associative array
$data = json_decode($json_data, true);

// Check if the data was successfully decoded
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON Error: " . json_last_error_msg();
    exit();
}

// Function to format and display the data
function display_data($url, $data) {
    echo "<h1>JSON Data from $url</h1>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

// Display the formatted data
display_data($url, $data);
?>
