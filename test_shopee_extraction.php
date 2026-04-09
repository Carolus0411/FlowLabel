<?php

require __DIR__.'/vendor/autoload.php';

// Simulate text that smalot/pdfparser might extract from the Shopee PDF attachment
$texts = [
    // Case 1: Clean full text extraction (normal flow)
    "Resi:SPXID067633824604\nPero Indonesia\nSPXID067633824604 SPXID067633824604 SPXID067633824604\nKAB. TANGERANG\nPenerima:\n628878899020\nDian Noviar\nJln Gamelan Timur, RT 01 RW 30\nBerat: 1000 gr\nPesan: (260402QWRU4XAW)\nTIR-A-02\nBY - 6\nSTD\nHOME\nKAB. SLEMAN BERBAH Sendang Tirto\nPengirim:\nNo.Pesanan: 260402QWRU4XAW\nCOD Cek Dulu: Tidak",
    // Case 2: Newline between label and order ID
    "SPXID067633824604\nNo.Pesanan:\n260402QWRU4XAW\n",
    // Case 3: Space inserted inside order ID by PDF parser
    "No.Pesanan: 260402 QWRU4XAW",
    // Case 4: Only 'Pesan' visible without 'No.Pesanan'
    "Pesan: (260402QWRU4XAW)\nSPXID067633824604",
    // Case 5: Empty text (image-based PDF)
    "",
    // Case 6: Shopee rotated sidebar text concatenated - smalot/pdfparser might extract this way
    "SPXID067633824604SPXID067633824604SPXID067633824604\nSTD\nSPXID067633824604\nTIR-A-02\nBY - 6\nKAB. SLEMAN\nBERBAH\nSendang Tirto\nPenerima: Dian Noviar\n628878899020\nHOME\nJln Gamelan Timur, RT 01 RW 30\nBERBAH, KAB. SLEMAN\nPengirim: Pero Indonesia\nKAB. TANGERANG\nBerat: 1000 gr\nBatas Kirim: 06-04-2026\nCOD Cek Dulu: Tidak\nNo.Pesanan: 260402QWRU4XAW\nSPXID067633824604 SPXID067633824604",
    // Case 7: PDF Parser produces 'No. Pesanan' (with space after 'No')
    "No. Pesanan: 260402QWRU4XAW",
    // Case 8: smalot/pdfparser misreads 'No.Pesanan' across line break
    "No.Pesanan: \n260402QWRU4XAW\nCOD Cek Dulu: Tidak",
];

$fixShopeeO = function($code) {
    if (strlen($code) >= 12) {
        $prefix = substr($code, 0, 6);
        $suffix = substr($code, 6);
        $suffix = str_replace('O', '0', $suffix);
        return $prefix . $suffix;
    }
    return $code;
};

foreach ($texts as $i => $text) {
    echo "=== Case " . ($i+1) . " ===\n";
    echo "Text: " . json_encode(substr($text, 0, 100)) . "\n";

    $orderId = null;

    if (preg_match('/No\.?\s*Pesanan\s*[:\.\s]*([A-Z0-9]{12,16})/i', $text, $matches)) {
        $orderId = $fixShopeeO(strtoupper(trim($matches[1])));
        echo "  -> Pattern 0 (No.Pesanan): '$orderId'\n";
    } elseif (preg_match('/No\.?\s*Pesanan\s*[:\.\s]*([\dA-Z][0-9A-Z\s]{10,18})/i', $text, $matches)) {
        $candidate = strtoupper(preg_replace('/\s+/', '', $matches[1]));
        if (preg_match('/^\d{6}[A-Z0-9]{6,12}$/', $candidate)) {
            $orderId = $fixShopeeO($candidate);
            echo "  -> Pattern 0b (spaces inside): '$orderId'\n";
        } else {
            echo "  -> Pattern 0b candidate invalid: '$candidate'\n";
        }
    } elseif (preg_match('/N\.?\s*P\.?\s*[:\s]+([A-Z0-9]{12,16})/i', $text, $matches)) {
        $orderId = $fixShopeeO(strtoupper(trim($matches[1])));
        echo "  -> Pattern 0c (N.P.): '$orderId'\n";
    } elseif (preg_match('/Pesan\s*[:\s]+\(?\s*([A-Z0-9]{12,16})\s*\)?/i', $text, $matches)) {
        $orderId = $fixShopeeO(strtoupper(trim($matches[1])));
        echo "  -> Pattern 0e (Pesan): '$orderId'\n";
    } elseif (preg_match('/\bP:\s*\(?\s*([A-Z0-9]{12,16})\s*\)?/i', $text, $matches)) {
        $candidate = strtoupper(trim($matches[1]));
        if (preg_match('/^\d{6}[A-Z0-9]{6,10}$/', $candidate)) {
            $orderId = $fixShopeeO($candidate);
            echo "  -> Pattern 0f (P:): '$orderId'\n";
        }
    } elseif (preg_match('/Order\s*ID\s*[:\.\s]*([A-Z0-9]{12,16})/i', $text, $matches)) {
        $orderId = $fixShopeeO(strtoupper(trim($matches[1])));
        echo "  -> Pattern 1 (Order ID): '$orderId'\n";
    } elseif (preg_match('/\b(\d{6}[A-Z0-9]{8,10})\b/i', $text, $matches)) {
        $orderId = $fixShopeeO(strtoupper($matches[1]));
        echo "  -> Pattern 2 (standalone): '$orderId'\n";
    } else {
        echo "  -> NO MATCH\n";
    }

    $expected = '260402QWRU4XAW';
    if ($orderId === null && strlen($text) === 0) {
        echo "  RESULT: NULL (empty text - expected when PDF has no text layer)\n";
    } elseif ($orderId === $expected) {
        echo "  RESULT: CORRECT\n";
    } elseif ($orderId !== null) {
        echo "  RESULT: WRONG - got '$orderId', expected '$expected'\n";
    } else {
        echo "  RESULT: FAILED to extract\n";
    }
    echo "\n";
}

// Now test a critical edge case: what if PDF parser concatenates SPXID with order line?
echo "=== Critical Edge Case: SPXID directly adjacent to numeric string ===\n";
$edgeCase = "SPXID067633824604260402QWRU4XAW";
echo "Text: '$edgeCase'\n";
if (preg_match('/\b(\d{6}[A-Z0-9]{8,10})\b/i', $edgeCase, $m)) {
    echo "Pattern 2 match: '" . $m[1] . "'\n";
} else {
    echo "Pattern 2: no match\n";
}

// Check if 067633824604 could match Pattern 2 when preceded by SPXID
$possibleFalseMatch = "SPXID067633824604\n";
echo "\n=== False Match Check: SPXID digits ===\n";
echo "Text: '$possibleFalseMatch'\n";
if (preg_match('/\b(\d{6}[A-Z0-9]{8,10})\b/i', $possibleFalseMatch, $m)) {
    echo "Pattern 2 WRONGLY matches: '" . $m[1] . "'\n";
} else {
    echo "Pattern 2: no match (correct - SPXID digits should not match)\n";
}
