<?php

// Test TikTok Order ID extraction with various formats

$testCases = [
    'TT Order ID : 582108769742652773',          // Regular colon with space
    'TT Order ID: 582108769742652773',           // Regular colon no space
    'TT Order ID ：582107960589780854',          // Full-width colon (Chinese)
    'TT Order ID：582107960589780854',           // Full-width colon no space
    'Order Id : 582107959496574940',             // Without TT prefix
    'Order Id: 582107959496574940',              // Without TT prefix, no space
    'Order Id：582107959496574940',              // Without TT prefix, full-width
    'TT Order ID    :    582108769742652773',    // Multiple spaces
    'TT Order ID . 582108769742652773',          // Dot separator
    'Some text TT Order ID ：582107960589780854 more text', // Embedded
];

echo "Testing TikTok Order ID Extraction\n";
echo "===================================\n\n";

// The regex pattern used in the code
$pattern = '/(?:TT\s*)?Order\s*Id\s*[:\s：\.]*\s*(\d{15,})/iu';

foreach ($testCases as $index => $text) {
    $testNum = $index + 1;
    echo "Test $testNum:\n";
    echo "Input:  \"$text\"\n";

    if (preg_match($pattern, $text, $matches)) {
        echo "Result: ✓ Matched\n";
        echo "Order ID: {$matches[1]}\n";
    } else {
        echo "Result: ✗ NOT Matched\n";
    }

    echo "\n";
}

echo "===================================\n";
echo "Pattern used: $pattern\n";
echo "\nExplanation:\n";
echo "- (?:TT\\s*)? : Optional 'TT' with optional whitespace\n";
echo "- Order\\s*Id : 'Order' + whitespace + 'Id' (case insensitive)\n";
echo "- [:\\s：\\.]* : Match any combination of : (regular), ： (full-width), space, or dot\n";
echo "- \\s* : Optional whitespace\n";
echo "- (\\d{15,}) : Capture 15+ digit number\n";
echo "- /iu : Case insensitive + Unicode support\n";
