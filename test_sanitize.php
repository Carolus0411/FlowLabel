<?php

// Test sanitizeText function

function sanitizeText(string $text): string
{
    // Replace full-width characters with ASCII equivalents
    $text = str_replace('：', ':', $text);
    $text = str_replace('（', '(', $text);
    $text = str_replace('）', ')', $text);
    $text = str_replace('　', ' ', $text); // Full-width space

    // Remove any remaining non-ASCII characters that might cause issues
    // Keep common punctuation and alphanumeric
    $sanitized = preg_replace('/[^\x20-\x7E\r\n\t]/u', '', $text);

    // Ensure we always return a string
    return $sanitized !== null ? $sanitized : $text;
}

echo "Testing Text Sanitization\n";
echo "=========================\n\n";

$testCases = [
    "TT Order ID：582107960589780854",
    "Order ID: 123456 （test）",
    "FastTrack　Express",
    "Normal text with spaces",
    "Mixed：regular and ：full-width",
];

foreach ($testCases as $index => $text) {
    $testNum = $index + 1;
    echo "Test $testNum:\n";
    echo "Original: \"$text\"\n";

    $sanitized = sanitizeText($text);
    echo "Sanitized: \"$sanitized\"\n";

    // Check for non-ASCII
    $hasNonAscii = preg_match('/[^\x20-\x7E\r\n\t]/', $sanitized);
    echo "Has non-ASCII: " . ($hasNonAscii ? "YES ❌" : "NO ✓") . "\n";

    echo "\n";
}

echo "Byte sequence test:\n";
$fullWidthColon = "："; // The problematic character
echo "Full-width colon bytes: ";
for ($i = 0; $i < strlen($fullWidthColon); $i++) {
    echo "0x" . str_pad(dechex(ord($fullWidthColon[$i])), 2, '0', STR_PAD_LEFT) . " ";
}
echo "\n";

$sanitized = sanitizeText($fullWidthColon);
echo "After sanitization: \"$sanitized\"\n";
echo "Length: " . strlen($sanitized) . "\n";
