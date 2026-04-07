<?php

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://oras.pagasa.dost.gov.ph/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$html = curl_exec($ch);
curl_close($ch);

echo "=== PAGASA Page Content ===\n\n";

if ($html) {
    // Find writeln lines
    $lines = explode("\n", $html);
    foreach ($lines as $line) {
        if (stripos($line, 'writeln') !== false) {
            echo trim($line) . "\n";
        }
    }
} else {
    echo "Failed to fetch PAGASA page\n";
}

echo "\n=== Regex Testing ===\n\n";

// Test regex patterns
$timePattern = '/document\.writeln\("(\d{2}:\d{2}:\d{2}\s[AP]M)"\)/';
$datePattern = '/document\.writeln\("([^"]+,\s\d{2}\s\w+\s\d{4})"\)/';

if (preg_match($timePattern, $html, $timeMatches)) {
    echo "Time matched: " . $timeMatches[1] . "\n";
} else {
    echo "Time pattern did NOT match\n";
}

if (preg_match($datePattern, $html, $dateMatches)) {
    echo "Date matched: " . $dateMatches[1] . "\n";
} else {
    echo "Date pattern did NOT match\n";
}

// Check if variables are present
if (preg_match('/timestring\s*=\s*"([^"]+)"/', $html, $m)) {
    echo "timestring variable: " . $m[1] . "\n";
}
if (preg_match('/datestring\s*=\s*"([^"]+)"/', $html, $m)) {
    echo "datestring variable: " . $m[1] . "\n";
}
