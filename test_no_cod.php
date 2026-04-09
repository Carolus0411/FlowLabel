<?php
require __DIR__ . '/vendor/autoload.php';

$fixShopeeO = function($code) { return str_replace('O', '0', $code); };

$cases = [
    'newline_sep'  => "No.Pesanan: 260402QWRU4XAW\nCOD Cek Dulu: Tidak",
    'space_sep'    => 'No.Pesanan: 260402QWRU4XAW COD Cek Dulu: Tidak',
    'no_sep'       => 'No.Pesanan: 260402QWRU4XAWCOD Cek Dulu: Tidak',
    'pesan_only'   => 'Pesan: (260402QWRU4XAW)',
    'both_present' => "Pesan: (260402QWRU4XAW)\nNo.Pesanan: 260402QWRU4XAWCOD Cek Dulu: Tidak",
    'spaced_id'    => 'No.Pesanan: 260402 QWRU 4XAW',
    'ocr_O_prefix' => "No.Pesanan: 26O4O2QWRU4XAW\nCOD Cek Dulu: Tidak",
];

$expected = '260402QWRU4XAW';

foreach ($cases as $label => $text) {
    // Pre-normalize: uppercase O -> 0 (mimics production code)
    $text = str_replace('O', '0', $text);

    $orderId = null;

    // Pattern 0 (exact Shopee format + lookahead)
    if (!$orderId && preg_match('/No\.?\s*Pesanan\s*[:\.\s]*(\d{6}[A-Z0-9]{8,10})(?![A-Z0-9])/i', $text, $m)) {
        $orderId = $fixShopeeO(strtoupper(trim($m[1])));
    }
    // Pattern 0b (spaces inside ID, exact Shopee validation)
    if (!$orderId && preg_match('/No\.?\s*Pesanan\s*[:\.\s]*([\dA-Z][0-9A-Z\s]{10,18})/i', $text, $m)) {
        $c = strtoupper(preg_replace('/\s+/', '', $m[1]));
        if (preg_match('/^\d{6}[A-Z0-9]{8,10}$/', $c)) {
            $orderId = $fixShopeeO($c);
        }
    }
    // Pattern 0e (Pesan: delimited)
    if (!$orderId && preg_match('/Pesan\s*[:\s]+\(?\s*([A-Z0-9]{12,16})\s*\)?/i', $text, $m)) {
        $orderId = $fixShopeeO(strtoupper(trim($m[1])));
    }
    // Pattern 2 (standalone word boundary)
    if (!$orderId && preg_match('/\b(\d{6}[A-Z0-9]{8,10})\b/i', $text, $m)) {
        $orderId = $fixShopeeO(strtoupper($m[1]));
    }

    $status = $orderId === $expected ? 'OK' : ($orderId ? 'WRONG: ' . $orderId : 'NO MATCH');
    echo str_pad($label, 15) . ' -> ' . $status . PHP_EOL;
}

