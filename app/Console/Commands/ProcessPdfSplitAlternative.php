<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrderLabel;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use setasign\Fpdf\Fpdf;
use Smalot\PdfParser\Parser;
use Exception;

class ProcessPdfSplitAlternative extends Command
{
    protected $signature = 'pdf:split-alternative {filename}';
    protected $description = 'Split PDF using alternative methods when FPDI fails';

    public function handle()
    {
        $filename = $this->argument('filename');
        $publicDisk = Storage::disk('public');
        $filePath = 'order-label-splits/' . $filename;

        if (!$publicDisk->exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $fullPath = $publicDisk->path($filePath);

        $this->info("Processing PDF: {$filename}");

        // Try multiple approaches for PDF splitting
        if ($this->trySplitWithPoppler($fullPath, $filename)) {
            return 0;
        }

        if ($this->trySplitWithGS($fullPath, $filename)) {
            return 0;
        }

        if ($this->trySplitWithPDFtk($fullPath, $filename)) {
            return 0;
        }

        $this->warn("All splitting methods failed. Individual page downloads will use page extraction from the main PDF.");
        return 0;
    }

    /**
     * Try splitting with Poppler utils (pdftk alternative)
     */
    private function trySplitWithPoppler($fullPath, $filename)
    {
        $this->info("Trying Poppler utils...");

        // Check if pdfseparate is available
        $output = [];
        $return = 0;
        exec('pdfseparate --version 2>&1', $output, $return);

        if ($return !== 0) {
            $this->warn("Poppler utils (pdfseparate) not available");
            return false;
        }

        $tempDir = storage_path('app/temp/pdf_split');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Use pdfseparate to split PDF
        $outputPattern = $tempDir . '/page_%d.pdf';
        $command = "pdfseparate \"$fullPath\" \"$outputPattern\"";

        exec($command, $output, $return);

        if ($return === 0) {
            $this->info("Successfully split PDF with Poppler utils");
            return $this->moveAndCreateRecords($tempDir, $filename);
        }

        $this->warn("Poppler utils splitting failed");
        return false;
    }

    /**
     * Try splitting with Ghostscript
     */
    private function trySplitWithGS($fullPath, $filename)
    {
        $this->info("Trying Ghostscript...");

        // Check if Ghostscript is available
        $output = [];
        $return = 0;
        exec('gs -version 2>&1', $output, $return);

        if ($return !== 0) {
            $this->warn("Ghostscript not available");
            return false;
        }

        $tempDir = storage_path('app/temp/pdf_split');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Get page count first
        $pageCount = $this->getPageCountWithGS($fullPath);
        if (!$pageCount) {
            return false;
        }

        $this->info("Splitting {$pageCount} pages with Ghostscript...");

        // Split each page
        $bar = $this->output->createProgressBar($pageCount);
        $bar->start();

        for ($page = 1; $page <= $pageCount; $page++) {
            $outputFile = $tempDir . "/page_{$page}.pdf";
            $command = "gs -sDEVICE=pdfwrite -dNOPAUSE -dBATCH -dSAFER " .
                      "-dFirstPage={$page} -dLastPage={$page} " .
                      "-sOutputFile=\"{$outputFile}\" \"$fullPath\"";

            exec($command, $output, $return);

            if ($return !== 0) {
                $this->warn("\nFailed to extract page {$page}");
                continue;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info("\nGhostscript splitting completed");

        return $this->moveAndCreateRecords($tempDir, $filename);
    }

    /**
     * Try splitting with PDFtk
     */
    private function trySplitWithPDFtk($fullPath, $filename)
    {
        $this->info("Trying PDFtk...");

        // Check if PDFtk is available
        $output = [];
        $return = 0;
        exec('pdftk --version 2>&1', $output, $return);

        if ($return !== 0) {
            $this->warn("PDFtk not available");
            return false;
        }

        $tempDir = storage_path('app/temp/pdf_split');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Use PDFtk burst to split PDF
        $command = "pdftk \"$fullPath\" burst output \"$tempDir/page_%02d.pdf\"";

        exec($command, $output, $return);

        if ($return === 0) {
            $this->info("Successfully split PDF with PDFtk");
            return $this->moveAndCreateRecords($tempDir, $filename);
        }

        $this->warn("PDFtk splitting failed");
        return false;
    }

    /**
     * Get page count using Ghostscript
     */
    private function getPageCountWithGS($fullPath)
    {
        $command = "gs -q -dNODISPLAY -c \"($fullPath) (r) file runpdfbegin pdfpagecount = quit\"";
        $output = [];
        exec($command, $output, $return);

        if ($return === 0 && !empty($output)) {
            return (int)trim($output[0]);
        }

        return null;
    }

    /**
     * Move split files and create database records
     */
    private function moveAndCreateRecords($tempDir, $originalFilename)
    {
        $publicDisk = Storage::disk('public');
        $splitFiles = glob($tempDir . '/*.pdf');

        if (empty($splitFiles)) {
            $this->warn("No split files found in temp directory");
            return false;
        }

        $this->info("Moving " . count($splitFiles) . " split files and creating records...");

        // Clear existing records for this file
        OrderLabel::where('original_filename', $originalFilename)->delete();

        $parser = new Parser();
        $createdCount = 0;

        $bar = $this->output->createProgressBar(count($splitFiles));
        $bar->start();

        foreach ($splitFiles as $splitFile) {
            $pageNumber = $this->extractPageNumber(basename($splitFile));
            $splitFilename = pathinfo($originalFilename, PATHINFO_FILENAME) . "_page_{$pageNumber}.pdf";
            $splitPath = 'order-label-splits/' . $splitFilename;

            // Move file to storage
            $publicDisk->put($splitPath, file_get_contents($splitFile));

            // Extract text
            $extractedText = '';
            try {
                $pdf = $parser->parseFile($splitFile);
                $extractedText = $pdf->getText();
            } catch (Exception $e) {
                $extractedText = "Text extraction failed: " . $e->getMessage();
            }

            // Create OrderLabel record
            OrderLabel::create([
                'code' => pathinfo($originalFilename, PATHINFO_FILENAME) . "_{$pageNumber}_" . time(),
                'order_date' => now()->format('Y-m-d'),
                'original_filename' => $originalFilename,
                'split_filename' => $splitFilename,
                'page_number' => $pageNumber,
                'file_path' => $splitPath,
                'extracted_text' => trim($extractedText) ?: 'No text content found',
                'saved' => true,
                'status' => 'open',
                'payment_status' => 'unpaid',
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            // Clean up temp file
            unlink($splitFile);
            $createdCount++;
            $bar->advance();
        }

        $bar->finish();

        // Clean up temp directory
        rmdir($tempDir);

        $this->info("\nSuccessfully created {$createdCount} individual PDF files and database records");
        return true;
    }

    /**
     * Extract page number from filename
     */
    private function extractPageNumber($filename)
    {
        if (preg_match('/page_(\d+)\.pdf/', $filename, $matches)) {
            return (int)$matches[1];
        }

        if (preg_match('/(\d+)\.pdf/', $filename, $matches)) {
            return (int)$matches[1];
        }

        return 1;
    }
}
