<?php

return [
    // Path to tesseract executable. Default uses system PATH ('tesseract').
    'tesseract_path' => env('TESSERACT_PATH', 'tesseract'),

    // Default OCR language(s)
    'lang' => env('OCR_LANG', 'eng'),

    // Additional tesseract options if needed
    'options' => [
        // e.g. --psm 6
    ],
];
