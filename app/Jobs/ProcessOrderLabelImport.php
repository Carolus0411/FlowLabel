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
        set_time_limit(600);
        ini_set('memory_limit', '1024M');

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
        // List of common paths for Ghostscript on Windows
        $paths = [
            'gswin64c.exe', // If in PATH
            'gswin32c.exe',
            'gs',
            'C:\Program Files\gs\gs10.04.0\bin\gswin64c.exe',
            // Add other paths as seen in previous steps if needed
        ];

        foreach ($paths as $path) {
            if (strpos($path, ':') !== false) {
                 if (file_exists($path)) return $path;
            } else {
                $output = [];
                $returnVar = 0;
                exec("where $path 2>nul", $output, $returnVar);
                if ($returnVar === 0 && !empty($output)) return $output[0];
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

        try {
            $cmd = sprintf('"%s" -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/default -dNOPAUSE -dQUIET -dBATCH -sOutputFile="%s" "%s"',
                $gsPath, $repairedPath, $filePath);

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
                    'extracted_text' => $pageText,
                    'status' => 'open',
                    'saved' => 1,
                    'created_by' => $this->userId,
                    'updated_by' => $this->userId,
                ]);

            } catch (\Exception $pageError) {
                // Log and simple fallback logic for individual page fail inside FPDI loop?
                // For simplicity, rely on parent catch for major errors, but maybe we should separate logic
                \Log::warning("Page $i failed in Job: " . $pageError->getMessage());
            }
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

                foreach ($pages as $pageIndex => $page) {
                    $pageNumber = $pageIndex + 1;

                    try {
                        $text = $page->getText();
                    } catch (\Exception $e) {
                         $text = 'Text extraction failed';
                    }

                    $orderId = $this->extractOrderId($text);

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

                    $records[] = [
                        'batch_no' => $this->batchNo,
                        'three_pl_id' => $this->threePlId,
                        'code' => $uniqueCode,
                        'order_date' => $now,
                        'note' => $orderId ? "Order ID: $orderId" : ("PDF Page (Fallback): Page " . $pageNumber),
                        'original_filename' => $originalName,
                        'split_filename' => $displayFilename,
                        'page_number' => $pageNumber,
                        'file_path' => 'order-label-splits/' . $batchFolderName . '/' . $fallbackFileName,
                        'extracted_text' => $text,
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
                    'extracted_text' => $pdf->getText(),
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
        // Matches: "TT Order ID : 582108769742652773" or "Order Id : 582107959496574940"
        if (preg_match('/(?:TT\s*)?Order\s*Id\s*[:\.]?\s*(\d{15,})/i', $text, $matches)) {
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
}
