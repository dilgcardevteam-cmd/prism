<?php

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://oras.pagasa.dost.gov.ph/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$html = curl_exec($ch);
curl_close($ch);

echo "=== PAGASA Page Analysis ===\n\n";

// Try to extract datestring and timestring
$patterns = [
    'datestring' => '/datestring\s*=\s*"([^"]+)"/',
    'timestring' => '/timestring\s*=\s*"([^"]+)"/',
];

foreach ($patterns as $name => $pattern) {
    if (preg_match($pattern, $html, $matches)) {
        echo "$name: " . $matches[1] . "\n";
    }
}

// Try alternate patterns with single quotes
echo "\n=== Trying single quote patterns ===\n";
foreach ($patterns as $name => $pattern) {
    $altPattern = str_replace('"', "'", $pattern);
    if (preg_match($altPattern, $html, $matches)) {
        echo "$name (alt): " . $matches[1] . "\n";
    }
}

// Try to find any time-like strings in the HTML
echo "\n=== Looking for time patterns in HTML ===\n";
if (preg_match('/(\d{1,2}):(\d{2}):(\d{2})\s*([AP]M)/i', $html, $matches)) {
    echo "Found time: " . $matches[0] . "\n";
}

// Show a snippet of the HTML around datestring
echo "\n=== HTML snippet around datestring ===\n";
if (preg_match('/.{0,200}datestring.{0,200}/i', $html, $matches)) {
    echo $matches[0] . "\n";
}

// Show a snippet around timestring
echo "\n=== HTML snippet around timestring ===\n";
if (preg_match('/.{0,200}timestring.{0,200}/i', $html, $matches)) {
    echo $matches[0] . "\n";
}
