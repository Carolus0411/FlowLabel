<?php

$tests = [
    'No.Pesanan: 260227SUNRXHAY',
    'No.Pesanan: 260227STYVUWFA',
    'No Pesanan 260227SUNRXHAY',
    'No.Pesanan : 260227SUNRXHAY',
    'No.Pesanan: 260227 SUNRXHAY',       // OCR adds space inside ID
    "No.Pesanan:\n260227SUNRXHAY",        // OCR puts ID on next line
    'NoPesanan260227SUNRXHAY',            // OCR drops punctuation
];

foreach ($tests as $text) {
    echo 'Input: ' . json_encode($text) . PHP_EOL;

    $found = null;

    // Pattern 0: flexible separator between "No.Pesanan" and the code
    if (preg_match('/No\.?\s*Pesanan\s*[:\.\s]*([A-Z0-9]{12,16})/i', $text, $m)) {
        $found = strtoupper(trim($m[1]));
        echo "  Pattern 0: $found" . PHP_EOL;

    // Pattern 0b: spaces inside ID (OCR artifact) – collapse them, then validate
    } elseif (preg_match('/No\.?\s*Pesanan\s*[:\.\s]*([\dA-Z][0-9A-Z\s]{10,18})/i', $text, $m)) {
        $candidate = strtoupper(preg_replace('/\s+/', '', $m[1]));
        if (preg_match('/^\d{6}[A-Z0-9]{6,12}$/', $candidate)) {
            $found = $candidate;
            echo "  Pattern 0b: $found" . PHP_EOL;
        } else {
            echo '  NO MATCH (0b candidate invalid: ' . $candidate . ')' . PHP_EOL;
        }

    // Pattern 2: standalone YYMMDD+alphanum
    } elseif (preg_match('/\b(\d{6}[A-Z0-9]{8,10})\b/i', $text, $m)) {
        $found = strtoupper($m[1]);
        echo "  Pattern 2: $found" . PHP_EOL;

    } else {
        echo '  NO MATCH' . PHP_EOL;
    }

    echo PHP_EOL;
}
