<?php
// Where counts will be stored
$countFile = 'download_counts.json';

// Valid files and their GitHub URLs
$files = [
    "svxlink.img.gz" => "https://github.com/f5vmr/svxlinkbuilder/releases/download/V2.1.4/svxlink.img.gz",
    "svxlink.instructions.pdf" => "https://github.com/f5vmr/svxlinkbuilder/releases/download/V2.1.4/svxlink.instructions.pdf",
    "DTMF.Help.Sheet.png" => "https://github.com/f5vmr/svxlinkbuilder/releases/download/V2.1.4/DTMF.Help.Sheet.png"
];

if (!isset($_GET['file']) || !isset($files[$_GET['file']])) {
    die("Invalid file.");
}

$filename = $_GET['file'];

// Load existing counts or start fresh
$counts = file_exists($countFile)
    ? json_decode(file_get_contents($countFile), true)
    : [];

// Increment the count
$counts[$filename] = ($counts[$filename] ?? 0) + 1;

// Save
file_put_contents($countFile, json_encode($counts));

// Redirect to actual download
header("Location: " . $files[$filename]);
exit;
