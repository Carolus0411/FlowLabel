<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\OrderLabel;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use setasign\Fpdi\Fpdi;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Throwable;

class ProcessOrderLabelImport implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $filePath;
    public $originalName;
    public $userId;
    public $batchNo;
    public $threePlId;
    public $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct($filePath, $originalName, $userId, $threePlId = null)
    {
        $this->filePath = $filePath;
        $this->originalName = $originalName;
        $this->userId = $userId;
        $this->threePlId = $threePlId;
        // Generate batch number: FLOW/YY/MM/DD/XXX
        $this->batchNo = $this->generateBatchNumber();
    }

    /**
     * Generate batch number with format FLOW/YY/MM/DD/XXX
     */
    private function generateBatchNumber(): string
    {
        $today = date('Y-m-d');
        $year = date('y');
        $month = date('m');
        $day = date('d');

        // Get last batch number for today
        $lastBatch = OrderLabel::whereDate('created_at', $today)
            ->whereNotNull('batch_no')
            ->where('batch_no', 'like', "FLOW/$year/$month/$day/%")
            ->orderBy('batch_no', 'desc')
            ->value('batch_no');

        // Extract sequence number from last batch
        $sequence = 1;
        if ($lastBatch) {
            $parts = explode('/', $lastBatch);
            if (count($parts) === 5) {
                $sequence = intval($parts[4]) + 1;
            }
        }

        return sprintf('FLOW/%s/%s/%s/%03d', $year, $month, $day, $sequence);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Add ignore user abort
        ignore_user_abort(true);
        set_time_limit(3600);
        ini_set('memory_limit', '6114M');

        $this->splitPDF($this->filePath, $this->originalName);

        // Cleanup main temp file
        Storage::delete($this->filePath);
    }

    private function splitPDF($filePath, $originalName): void
    {
        // Fix path for storage: The job receives a path relative to storage/app usually, or we use full path.
        // Let's assume content is passed as relative path to Storage drive.
        // However, import.blade.php used Storage::path($tempPath), which is absolute.
        // We need to work with absolute paths for FPDI/Parser often.

        $absPath = Storage::path($filePath);

        // Create directory with batch number as folder name
        // Convert FLOW/26/01/15/001 to FLOW-26-01-15-001 for folder name
        $batchFolderName = str_replace('/', '-', $this->batchNo);
        $outputDir = storage_path('app/public/order-label-splits/' . $batchFolderName . '/');
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Always save the original file first as a backup/source
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $savedOriginalName = $baseName . '_original.pdf';
        $savedOriginalPath = $outputDir . $savedOriginalName;
        copy($absPath, $savedOriginalPath);

        try {
            // First try to count pages with parser
            $parser = new Parser();
            $pdf = $parser->parseFile($absPath);
            $pages = $pdf->getPages();
            $pageCount = count($pages);

            \Log::info("PDF Parser found $pageCount pages for $originalName");

            // Try FPDI approach first
            $this->splitWithFPDI($absPath, $originalName, $outputDir, $pages, $pageCount);

        } catch (\Exception $fpdiError) {
            \Log::error("FPDI Error: " . $fpdiError->getMessage());

            // If FPDI fails, try to repair with Ghostscript if available
            if ($this->tryGhostscriptRepair($absPath, $originalName, $outputDir, $pages, $pageCount)) {
                return;
            }

            // If repair not possible, use virtual fallback
            $this->fallbackPDFProcessing($absPath, $originalName, $outputDir);
        }
    }

    private function getGhostscriptPath(): ?string
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        // List of common paths for Ghostscript
        $paths = $isWindows
            ? [
                'gswin64c.exe',
                'gswin32c.exe',
                'C:\Program Files\gs\gs10.04.0\bin\gswin64c.exe',
                'C:\Program Files\gs\gs10.06.0\bin\gswin64c.exe',
            ]
            : [
                'gs', // Linux/Ubuntu standard path
                '/usr/bin/gs',
                '/usr/local/bin/gs',
            ];

        foreach ($paths as $path) {
            // Check absolute paths directly
            if (strpos($path, ':') !== false || strpos($path, '/') === 0) {
                if (file_exists($path)) return $path;
            } else {
                // Check in PATH using platform-specific command
                $output = [];
                $returnVar = 0;
                $cmd = $isWindows ? "where $path 2>nul" : "which $path 2>/dev/null";
                exec($cmd, $output, $returnVar);
                if ($returnVar === 0 && !empty($output)) return trim($output[0]);
            }
        }
        return null;
    }

    private function tryGhostscriptRepair($filePath, $originalName, $outputDir, $pages, $pageCount): bool
    {
        $gsPath = $this->getGhostscriptPath();
        if (!$gsPath) return false;

        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $repairedPath = $outputDir . $baseName . '_repaired.pdf';
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        try {
            // Proper escaping for both Windows and Linux
            $escapedGsPath = $isWindows ? '"' . $gsPath . '"' : escapeshellarg($gsPath);
            $escapedOutput = $isWindows ? '"' . $repairedPath . '"' : escapeshellarg($repairedPath);
            $escapedInput = $isWindows ? '"' . $filePath . '"' : escapeshellarg($filePath);

            $cmd = sprintf('%s -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/default -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s 2>&1',
                $escapedGsPath, $escapedOutput, $escapedInput);

            exec($cmd, $output, $returnVar);

            if ($returnVar === 0 && file_exists($repairedPath)) {
                try {
                    $this->splitWithFPDI($repairedPath, $originalName, $outputDir, $pages, $pageCount);
                    return true;
                } catch (\Exception $e) {
                    \Log::error("Failed to split repaired PDF: " . $e->getMessage());
                    return false;
                }
            } else {
                return false;
            }
        } catch (\Exception $e) {
            \Log::error("Ghostscript exception: " . $e->getMessage());
            return false;
        }
    }

    private function splitWithFPDI($filePath, $originalName, $outputDir, $pages, $pageCount): void
    {
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);

        // Pre-check
        if ($pageCount > 0) {
            // Attempt to import the first page to fail fast
            try {
                $testFpdi = new Fpdi();
                $testFpdi->setSourceFile($filePath);
                $testFpdi->importPage(1);
            } catch(\Exception $e) {
                // If this fails, let it propagate to fallback or parent catch
                throw $e;
            }
        }

        $usedFilenames = [];
        $startTime = microtime(true);
        $timeLimitSafe = 550; // Use a comfortable limit slightly less than job timeout
        $failedPages = []; // Track failed pages for Ghostscript fallback

        for ($i = 1; $i <= $pageCount; $i++) {

            // Check status for batch cancellation
            if ($this->batch() && $this->batch()->canceled()) {
                return;
            }

            try {
                // Text extraction
                 if ((microtime(true) - $startTime) > $timeLimitSafe) {
                    $pageText = "Time limit safe reached.";
                 } else {
                    $pageText = isset($pages[$i - 1]) ? $pages[$i - 1]->getText() : '';
                 }

                $orderId = $this->extractOrderId($pageText);

                // If no order id found, attempt OCR on the page (use Ghostscript or Imagick to render)
                if (!$orderId) {
                    try {
                        $ocrText = $this->ocrPageText($filePath, $i, 'eng');
                        if ($ocrText) {
                            $pageText .= "\n" . $ocrText;
                            $orderId = $this->extractOrderId($pageText);
                        }
                    } catch (\Exception $e) {
                        \Log::warning("OCR failed for page $i: " . $e->getMessage());
                    }
                }

                $fpdi = new Fpdi();
                $fpdi->setSourceFile($filePath);

                $tplId = $fpdi->importPage($i);
                $size = $fpdi->getTemplateSize($tplId);

                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';

                // Generate unique code and filename
                if ($orderId) {
                     $fileNameBase = $orderId;
                     if (isset($usedFilenames[$fileNameBase])) {
                        $usedFilenames[$fileNameBase]++;
                        $finalName = $fileNameBase . '_' . $usedFilenames[$fileNameBase];
                        $uniqueCode = $orderId . '_' . $usedFilenames[$fileNameBase];
                     } else {
                        $usedFilenames[$fileNameBase] = 0;
                        $finalName = $fileNameBase;
                        $uniqueCode = $orderId;
                     }
                    $splitFileName = $finalName . '.pdf';
                } else {
                    $splitFileName = $baseName . '_page_' . $i . '.pdf';
                    $uniqueCode = $baseName . '_' . $i . '_' . time();
                }

                $fpdi->AddPage($orientation, [$size['width'], $size['height']]);
                $fpdi->useTemplate($tplId);

                $splitFilePath = $outputDir . $splitFileName;
                $fpdi->Output('F', $splitFilePath);

                // Get batch folder name for file_path
                $batchFolderName = str_replace('/', '-', $this->batchNo);

                OrderLabel::create([
                    'batch_no' => $this->batchNo,
                    'three_pl_id' => $this->threePlId,
                    'code' => $uniqueCode,
                    'order_date' => now(),
                    'note' => $orderId ? "Order ID: $orderId" : ('PDF Split: ' . $splitFileName),
                    'original_filename' => $originalName,
                    'split_filename' => $splitFileName,
                    'page_number' => $i,
                    'file_path' => 'order-label-splits/' . $batchFolderName . '/' . $splitFileName,
                    'extracted_text' => $this->sanitizeText($pageText),
                    'status' => 'open',
                    'saved' => 1,
                    'created_by' => $this->userId,
                    'updated_by' => $this->userId,
                ]);

            } catch (\Exception $pageError) {
                \Log::warning("Page $i failed in FPDI: " . $pageError->getMessage());

                // Store failed page info for fallback processing
                try {
                    $pageText = isset($pages[$i - 1]) ? $pages[$i - 1]->getText() : '';
                } catch (\Exception $textError) {
                    \Log::warning("Page $i text extraction also failed: " . $textError->getMessage());
                    $pageText = 'Text extraction failed';
                }

                $failedPages[$i] = [
                    'page_number' => $i,
                    'text' => $pageText,
                    'order_id' => $this->extractOrderId($pageText),
                    'error' => $pageError->getMessage()
                ];

                \Log::info("Page $i added to failed pages queue for Ghostscript fallback");
            }
        }

        // Process failed pages with Ghostscript fallback
        if (!empty($failedPages)) {
            \Log::info("Processing " . count($failedPages) . " failed pages with Ghostscript fallback");
            $this->processFailedPagesWithGhostscript($filePath, $outputDir, $failedPages, $baseName, $usedFilenames);
        }
    }

    private function fallbackPDFProcessing($filePath, $originalName, $outputDir): void
    {
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $fallbackFileName = $baseName . '_original.pdf';
        $fallbackFilePath = $outputDir . $fallbackFileName;

        copy($filePath, $fallbackFilePath);

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $pages = $pdf->getPages();
            $pageCount = count($pages);

            if ($pageCount > 1) {
                $records = [];
                $now = now();
                $usedFilenames = [];

                // Try Ghostscript for splitting if available
                $ghostscriptSuccess = false;
                if ($this->isGhostscriptAvailable()) {
                    \Log::info("Fallback: Attempting Ghostscript split for {$pageCount} pages");
                    try {
                        $ghostscriptSuccess = $this->splitWithGhostscript($filePath, $outputDir, $pageCount);
                    } catch (\Exception $e) {
                        \Log::warning("Ghostscript split failed in fallback: " . $e->getMessage());
                    }
                }

                foreach ($pages as $pageIndex => $page) {
                    $pageNumber = $pageIndex + 1;

                    try {
                        $text = $page->getText();
                    } catch (\Exception $e) {
                         $text = 'Text extraction failed';
                    }

                    $orderId = $this->extractOrderId($text);

                    // If we still don't have an order ID, try OCR (use a simple English default)
                    if (!$orderId) {
                        try {
                            $ocr = $this->ocrPageText($filePath, $pageNumber, 'eng');
                            if ($ocr) {
                                $text .= "\n" . $ocr;
                                $orderId = $this->extractOrderId($text);
                            }
                        } catch (\Exception $e) {
                            \Log::warning("Fallback OCR failed for page $pageNumber: " . $e->getMessage());
                        }
                    }

                    // Generate unique code and filename
                    if ($orderId) {
                         $fileNameBase = $orderId;
                         if (isset($usedFilenames[$fileNameBase])) {
                            $usedFilenames[$fileNameBase]++;
                            $finalName = $fileNameBase . '_' . $usedFilenames[$fileNameBase];
                            $uniqueCode = $orderId . '_' . $usedFilenames[$fileNameBase];
                         } else {
                            $usedFilenames[$fileNameBase] = 0;
                            $finalName = $fileNameBase;
                            $uniqueCode = $orderId;
                         }
                        $displayFilename = $finalName . '.pdf';
                    } else {
                        $displayFilename = $baseName . '_page_' . $pageNumber . '.pdf';
                        $uniqueCode = $baseName . '_page_' . $pageNumber . '_' . time();
                    }

                    // Get batch folder name for file_path
                    $batchFolderName = str_replace('/', '-', $this->batchNo);

                    // If Ghostscript succeeded, rename the split file to proper filename
                    $ghostscriptPageFile = $outputDir . 'page_' . $pageNumber . '.pdf';
                    $actualFilePath = $fallbackFileName; // Default to original

                    if ($ghostscriptSuccess && file_exists($ghostscriptPageFile)) {
                        // Rename from page_X.pdf to actual order ID filename
                        $targetFilePath = $outputDir . $displayFilename;

                        // If target file already exists, keep the temp name
                        if (file_exists($targetFilePath)) {
                            $displayFilename = 'page_' . $pageNumber . '.pdf';
                            $actualFilePath = $displayFilename;
                        } else {
                            rename($ghostscriptPageFile, $targetFilePath);
                            $actualFilePath = $displayFilename; // Use renamed file
                        }
                    }

                    $records[] = [
                        'batch_no' => $this->batchNo,
                        'three_pl_id' => $this->threePlId,
                        'code' => $uniqueCode,
                        'order_date' => $now,
                        'note' => $orderId ? "Order ID: $orderId" : ("PDF Page (Fallback): Page " . $pageNumber),
                        'original_filename' => $originalName,
                        'split_filename' => $displayFilename,
                        'page_number' => $pageNumber,
                        'file_path' => 'order-label-splits/' . $batchFolderName . '/' . $actualFilePath,
                        'extracted_text' => $this->sanitizeText($text),
                        'status' => 'open',
                        'saved' => 1,
                        'created_by' => $this->userId,
                        'updated_by' => $this->userId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($records, 100) as $chunk) {
                    OrderLabel::insert($chunk);
                }
            } else {
                // Single page logic...
                $batchFolderName = str_replace('/', '-', $this->batchNo);

                 OrderLabel::create([
                    'batch_no' => $this->batchNo,
                    'three_pl_id' => $this->threePlId,
                    'code' => $baseName . '_original_' . time(),
                    'order_date' => now(),
                    'note' => 'Single page PDF',
                    'original_filename' => $originalName,
                    'split_filename' => $fallbackFileName,
                    'page_number' => 1,
                    'file_path' => 'order-label-splits/' . $batchFolderName . '/' . $fallbackFileName,
                    'extracted_text' => $this->sanitizeText($pdf->getText()),
                    'status' => 'open',
                    'saved' => 1,
                    'created_by' => $this->userId,
                    'updated_by' => $this->userId,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error("Fallback failed completely: " . $e->getMessage());
        }
    }

    private function extractOrderId(string $text): ?string
    {
        // Get 3PL name to determine extraction logic
        $threePlName = null;
        if ($this->threePlId) {
            $threePl = \App\Models\ThreePl::find($this->threePlId);
            $threePlName = $threePl ? strtolower($threePl->name) : null;
        }

        // First, try to extract from filename for image-based PDFs
        $orderIdFromFilename = $this->extractOrderIdFromFilename($this->originalName, $threePlName);
        if ($orderIdFromFilename) {
            \Log::info("Order ID extracted from filename: $orderIdFromFilename");
            return $orderIdFromFilename;
        }

        // If filename extraction fails, try text extraction
        // SHOPEE format: Order ID like "260101T6XF69GN" (alphanumeric, usually 14 chars)
        if ($threePlName && str_contains($threePlName, 'shopee')) {
            // Pattern 1: "Order ID" followed by alphanumeric code
            if (preg_match('/Order\s*ID\s*[:\.]?\s*([A-Z0-9]{12,16})/i', $text, $matches)) {
                return $matches[1];
            }

            // Pattern 2: Standalone alphanumeric code (date prefix + alphanumeric)
            // Format: YYMMDD + alphanumeric (e.g., 260101T6XF69GN)
            if (preg_match('/\b(\d{6}[A-Z0-9]{8,10})\b/', $text, $matches)) {
                return $matches[1];
            }

            // Pattern 3: Generic "Order" with alphanumeric
            if (preg_match('/Order\s*(?:No|#|Number)?\s*[:\.]?\s*([A-Z0-9]{10,16})/i', $text, $matches)) {
                return $matches[1];
            }
        }

        // LAZADA format: Order ID like "2758420016283453" (numeric 16 digits)
        if ($threePlName && str_contains($threePlName, 'lazada')) {
            // Pattern 1: "Order No" or "Order ID" followed by 16 digit number
            if (preg_match('/Order\s*(?:No|ID)\s*[:\.]?\s*(\d{16})/i', $text, $matches)) {
                return $matches[1];
            }

            // Pattern 2: Standalone 16 digit number (Lazada specific length)
            if (preg_match('/\b(\d{16})\b/', $text, $matches)) {
                return $matches[1];
            }

            // Pattern 3: Generic "Order" with 14-17 digits
            if (preg_match('/Order\s*[:\.]?\s*(\d{14,17})/i', $text, $matches)) {
                return $matches[1];
            }
        }

        // TIKTOK or default format: Numeric Order ID
        // 1. PRIORITY: TT Order ID or Order Id with long numeric value (18+ digits)
        // Matches: "TT Order ID : 582108769742652773" or "TT Order ID ：582107960589780854" (with full-width colon)
        // Also matches: "Order Id : 582107959496574940"
        if (preg_match('/(?:TT\s*)?Order\s*Id\s*[:\s：\.]*\s*(\d{15,})/iu', $text, $matches)) {
            return $matches[1];
        }

        // 2. Fallback: Very long number sequence (15+ digits) usually found at bottom of labels
        if (preg_match('/\b(\d{15,})\b/', $text, $matches)) {
            return $matches[1];
        }

        // 3. Generic Order patterns with shorter numbers (phone numbers, etc.)
        // Matches: "Order No 123", "Order #123", "Order Number: 123"
        if (preg_match('/Order\s*(?:No|#|Number)\s*[:\.]?\s*(\d{8,})/i', $text, $matches)) {
            return $matches[1];
        }

        // 4. Loose match for text that might be linearized weirdly
        if (preg_match('/Order\s*(?:Id|No|#)?\s*.{0,50}?\s*(\d{10,})/is', $text, $matches)) {
             return $matches[1];
        }

        return null;
    }

    /**
     * Sanitize text to prevent database encoding errors
     * Removes or replaces special characters that cause WIN1252 encoding issues
     */
    private function sanitizeText(string $text): string
    {
        // Replace full-width characters with ASCII equivalents
        $text = str_replace('：', ':', $text);
        $text = str_replace('（', '(', $text);
        $text = str_replace('）', ')', $text);
        $text = str_replace('　', ' ', $text); // Full-width space

        // Remove any remaining non-ASCII characters that might cause issues
        // Keep common punctuation and alphanumeric
        $text = preg_replace('/[^\x20-\x7E\r\n\t]/u', '', $text);

        return $text;
    }

    /**
     * OCR a single PDF page to plain text using Ghostscript (preferred) or Imagick as fallback
     */
    private function ocrPageText(string $filePath, int $pageNumber, string $lang = 'eng'): ?string
    {
        $tempDir = sys_get_temp_dir();
        $pngPath = tempnam($tempDir, 'ocr_page_') . '.png';

        $gsPath = $this->getGhostscriptPath();
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        // Try Ghostscript first if available
        if ($gsPath) {
            $escapedGsPath = $isWindows ? '"' . $gsPath . '"' : escapeshellarg($gsPath);
            $escapedOutput = $isWindows ? '"' . $pngPath . '"' : escapeshellarg($pngPath);
            $escapedInput = $isWindows ? '"' . $filePath . '"' : escapeshellarg($filePath);

            // Render at 300 DPI for better OCR accuracy
            $cmd = sprintf('%s -sDEVICE=png16m -r300 -dFirstPage=%d -dLastPage=%d -dNOPAUSE -dBATCH -sOutputFile=%s %s 2>&1',
                $escapedGsPath,
                $pageNumber,
                $pageNumber,
                $escapedOutput,
                $escapedInput
            );

            exec($cmd, $output, $returnVar);

            if ($returnVar !== 0 || !file_exists($pngPath)) {
                // clean any partial file
                @unlink($pngPath);
            }
        }

        // If Ghostscript not available or failed, try Imagick if installed
        if (!file_exists($pngPath)) {
            if (!extension_loaded('imagick')) {
                return null;
            }

            try {
                $im = new \Imagick();
                $im->setResolution(300, 300);
                // Read zero-based page index
                $im->readImage($filePath . '[' . ($pageNumber - 1) . ']');
                $im->setImageFormat('png32');
                $im->writeImage($pngPath);
                $im->clear();
                $im->destroy();
            } catch (\Exception $e) {
                @unlink($pngPath);
                return null;
            }
        }

        // Run tesseract via PHP wrapper
        try {
            $ocr = (new TesseractOCR($pngPath))
                ->executable(config('ocr.tesseract_path', 'tesseract'))
                ->lang($lang);

            $text = $ocr->run();
        } catch (\Exception $e) {
            \Log::warning('Tesseract OCR failed: ' . $e->getMessage());
            $text = null;
        }

        @unlink($pngPath);

        return $text ? trim($text) : null;
    }

    /**
     * Extract order ID from filename
     * This is especially useful for image-based PDFs where text extraction fails
     */
    private function extractOrderIdFromFilename(string $filename, ?string $threePlName): ?string
    {
        // Remove file extension
        $baseName = pathinfo($filename, PATHINFO_FILENAME);

        // LAZADA: Look for 16-digit numeric order ID in filename
        if ($threePlName && str_contains($threePlName, 'lazada')) {
            if (preg_match('/(\d{16})/', $baseName, $matches)) {
                return $matches[1];
            }
            // Also try 14-17 digits
            if (preg_match('/(\d{14,17})/', $baseName, $matches)) {
                return $matches[1];
            }
        }

        // SHOPEE: Look for alphanumeric order ID (YYMMDD + alphanumeric)
        if ($threePlName && str_contains($threePlName, 'shopee')) {
            if (preg_match('/(\d{6}[A-Z0-9]{8,10})/i', $baseName, $matches)) {
                return $matches[1];
            }
        }

        // TIKTOK or general: Look for 15+ digit numeric ID
        if (preg_match('/(\d{15,})/', $baseName, $matches)) {
            return $matches[1];
        }

        // Generic: Any long sequence of digits (10+ for general platforms)
        if (preg_match('/(\d{10,})/', $baseName, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Check if Ghostscript is available on the system
     */
    private function isGhostscriptAvailable(): bool
    {
        return $this->getGhostscriptPath() !== null;
    }

    /**
     * Process failed FPDI pages using Ghostscript
     */
    private function processFailedPagesWithGhostscript($filePath, $outputDir, $failedPages, $baseName, &$usedFilenames): void
    {
        $gsPath = $this->getGhostscriptPath();
        $batchFolderName = str_replace('/', '-', $this->batchNo);
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        foreach ($failedPages as $pageInfo) {
            $pageNumber = $pageInfo['page_number'];
            $pageText = $pageInfo['text'];
            $orderId = $pageInfo['order_id'];

            // If no order ID from text, try OCR on the specific page
            if (!$orderId) {
                try {
                    $ocr = $this->ocrPageText($filePath, $pageNumber, 'eng');
                    if ($ocr) {
                        $pageText .= "\n" . $ocr;
                        $orderId = $this->extractOrderId($pageText);
                    }
                } catch (\Exception $e) {
                    \Log::warning("Failed OCR for recovered page $pageNumber: " . $e->getMessage());
                }
            }

            try {
                // Generate filename based on order ID
                if ($orderId) {
                    $fileNameBase = $orderId;
                    if (isset($usedFilenames[$fileNameBase])) {
                        $usedFilenames[$fileNameBase]++;
                        $finalName = $fileNameBase . '_' . $usedFilenames[$fileNameBase];
                        $uniqueCode = $orderId . '_' . $usedFilenames[$fileNameBase];
                    } else {
                        $usedFilenames[$fileNameBase] = 0;
                        $finalName = $fileNameBase;
                        $uniqueCode = $orderId;
                    }
                    $splitFileName = $finalName . '.pdf';
                } else {
                    $splitFileName = $baseName . '_page_' . $pageNumber . '.pdf';
                    $uniqueCode = $baseName . '_' . $pageNumber . '_' . time();
                }

                $outputFile = $outputDir . $splitFileName;
                $saved = 0; // Mark as not saved if Ghostscript fails

                // Try Ghostscript split if available
                if ($gsPath) {
                    $escapedGsPath = $isWindows ? '"' . $gsPath . '"' : escapeshellarg($gsPath);
                    $escapedOutput = $isWindows ? '"' . $outputFile . '"' : escapeshellarg($outputFile);
                    $escapedInput = $isWindows ? '"' . $filePath . '"' : escapeshellarg($filePath);

                    $cmd = sprintf(
                        '%s -sDEVICE=pdfwrite -dFirstPage=%d -dLastPage=%d -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s 2>&1',
                        $escapedGsPath,
                        $pageNumber,
                        $pageNumber,
                        $escapedOutput,
                        $escapedInput
                    );

                    $output = [];
                    exec($cmd, $output, $returnVar);

                    if ($returnVar === 0 && file_exists($outputFile)) {
                        $saved = 1;
                        \Log::info("Ghostscript successfully split page $pageNumber");
                    } else {
                        \Log::error("Ghostscript failed for page $pageNumber");
                    }
                }

                // Create record even if file save failed
                OrderLabel::create([
                    'batch_no' => $this->batchNo,
                    'three_pl_id' => $this->threePlId,
                    'code' => $uniqueCode,
                    'order_date' => now(),
                    'note' => $orderId ? "Order ID: $orderId (Recovered)" : ('PDF Page (Recovered): ' . $pageNumber),
                    'original_filename' => $this->originalName,
                    'split_filename' => $splitFileName,
                    'page_number' => $pageNumber,
                    'file_path' => 'order-label-splits/' . $batchFolderName . '/' . ($saved ? $splitFileName : 'FAILED_' . $splitFileName),
                    'extracted_text' => $this->sanitizeText($pageText),
                    'status' => 'open',
                    'saved' => $saved,
                    'created_by' => $this->userId,
                    'updated_by' => $this->userId,
                ]);

            } catch (\Exception $e) {
                \Log::error("Failed to process page $pageNumber in Ghostscript fallback: " . $e->getMessage());
            }
        }
    }

    /**
     * Split PDF using Ghostscript command
     * This is used as fallback when FPDI fails
     */
    private function splitWithGhostscript($filePath, $outputDir, $pageCount): bool
    {
        $gsPath = $this->getGhostscriptPath();
        if (!$gsPath) {
            \Log::warning("Ghostscript not found for fallback split");
            return false;
        }

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        try {
            \Log::info("Splitting PDF with Ghostscript: $pageCount pages");

            // Split each page individually
            for ($i = 1; $i <= $pageCount; $i++) {
                $outputFile = $outputDir . 'page_' . $i . '.pdf';

                // Escape paths for shell command
                $escapedGsPath = $isWindows ? '"' . $gsPath . '"' : escapeshellarg($gsPath);
                $escapedOutput = $isWindows ? '"' . $outputFile . '"' : escapeshellarg($outputFile);
                $escapedInput = $isWindows ? '"' . $filePath . '"' : escapeshellarg($filePath);

                $cmd = sprintf(
                    '%s -sDEVICE=pdfwrite -dFirstPage=%d -dLastPage=%d -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s 2>&1',
                    $escapedGsPath,
                    $i,
                    $i,
                    $escapedOutput,
                    $escapedInput
                );

                exec($cmd, $output, $returnVar);

                if ($returnVar !== 0 || !file_exists($outputFile)) {
                    \Log::error("Ghostscript failed to split page $i. Return code: $returnVar. Output: " . implode("\n", $output));
                    return false;
                }
            }

            \Log::info("Ghostscript successfully split $pageCount pages");
            return true;
        } catch (\Exception $e) {
            \Log::error("Ghostscript split exception: " . $e->getMessage());
            return false;
        }
    }
}
