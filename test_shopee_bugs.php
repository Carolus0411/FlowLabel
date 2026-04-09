<?php
// Test specific edge cases that cause bugs

// Bug 1: Pattern 0 over-captures when order ID is directly adjacent to "COD"
echo "=== BUG 1: Pattern 0 over-capture ===\n";

$cases = [
    'no_sep'      => 'No.Pesanan: 260402QWRU4XAWCOD Cek Dulu: Tidak',  // No separator
    'with_space'  => 'No.Pesanan: 260402QWRU4XAW COD Cek Dulu: Tidak', // Space - OK
    'with_newline'=> "No.Pesanan: 260402QWRU4XAW\nCOD Cek Dulu: Tidak",// Newline - OK
    'Pesan_form'  => "Pesan: (260402QWRU4XAW)\nNo.Pesanan: 260402QWRU4XAWCOD Cek Dulu", // Pesan before No.Pesanan
];

foreach ($cases as $label => $text) {
    echo "\n[$label]\n";

    // Current buggy behavior
    if (preg_match('/No\.?\s*Pesanan\s*[:\.\s]*([A-Z0-9]{12,16})/i', $text, $m)) {
        $result = strtoupper(trim($m[1]));
        $ok = $result === '260402QWRU4XAW' ? 'OK' : 'WRONG captured: ' . $result;
        echo "  Pattern 0:       $ok  (matched '$result', len=".strlen($result).")\n";
    } else {
        echo "  Pattern 0:       NO MATCH\n";
    }

    // Pattern 0e as fallback
    if (preg_match('/Pesan\s*[:\s]+\(?\s*([A-Z0-9]{12,16})\s*\)?/i', $text, $m)) {
        $result = strtoupper(trim($m[1]));
        $ok = $result === '260402QWRU4XAW' ? 'OK' : 'WRONG: ' . $result;
        echo "  Pattern 0e (Pesan): $ok  (matched '$result')\n";
    }

    // Pattern 2 standalone
    if (preg_match('/\b(\d{6}[A-Z0-9]{8,10})\b/i', $text, $m)) {
        $result = strtoupper($m[1]);
        echo "  Pattern 2 (standalone): matched '$result'\n";
    }

    // PROPOSED FIX for Pattern 0: use specific Shopee format with non-alphanum lookahead
    if (preg_match('/No\.?\s*Pesanan\s*[:\.\s]*(\d{6}[A-Z0-9]{8,10})(?![A-Z0-9])/i', $text, $m)) {
        $result = strtoupper(trim($m[1]));
        $ok = $result === '260402QWRU4XAW' ? 'OK' : 'WRONG: ' . $result;
        echo "  Pattern 0 (FIXED):  $ok  (matched '$result')\n";
    } else {
        echo "  Pattern 0 (FIXED):  NO MATCH (would fall through to 0e)\n";
    }
}

// Bug 2: fixShopeeO doesn't fix O in prefix (date part)
echo "\n\n=== BUG 2: fixShopeeO doesn't replace O in 6-digit prefix ===\n";

$fixShopeeO_current = function($code) {
    if (strlen($code) >= 12) {
        $prefix = substr($code, 0, 6);
        $suffix = substr($code, 6);
        $suffix = str_replace('O', '0', $suffix);
        return $prefix . $suffix;
    }
    return $code;
};

$fixShopeeO_fixed = function($code) {
    // Shopee never uses letter O - replace ALL O with 0
    return str_replace('O', '0', $code);
};

$ocrCases = [
    '260402QWRU4XAW'   => 'Normal (no O)',
    '26O4O2QWRU4XAW'   => 'OCR misread 0->O in date prefix',
    '260402QWRU4XAWOO' => 'O in suffix only (extra chars)',
    '26O4O2QWRU0XTAW'  => 'O in both prefix and suffix',
];

foreach ($ocrCases as $input => $desc) {
    $current = $fixShopeeO_current($input);
    $fixed   = $fixShopeeO_fixed($input);
    $correct = '260402QWRU4XAW';
    echo "\nInput: $input ($desc)\n";
    echo "  Current (buggy): $current " . ($current === $correct ? '✓' : '✗ WRONG') . "\n";
    echo "  Fixed:           $fixed " .   ($fixed   === $correct ? '✓' : '(different, expected=$correct)') . "\n";
}
