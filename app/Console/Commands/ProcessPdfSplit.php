<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrderLabel;
use Smalot\PdfParser\Parser;
use setasign\Fpdi\Fpdi;

class ProcessPdfSplit extends Command
{
    protected $signature = 'pdf:split {file}';
    protected $description = 'Split a PDF file into individual pages and create OrderLabel records';

    public function handle()
    {
        $file = $this->argument('file');
        $pdfPath = storage_path('app/public/order-label-splits/' . $file);

        if (!file_exists($pdfPath)) {
            $this->error("File not found: {$pdfPath}");
            return 1;
        }

        $this->info("Processing PDF: {$file}");

        // Parse the PDF first
        $parser = new Parser();
        $pdf = $parser->parseFile($pdfPath);
        $pages = $pdf->getPages();
        $pageCount = count($pages);

        $this->info("Found {$pageCount} pages");

        $baseName = pathinfo($file, PATHINFO_FILENAME);
        $outputDir = storage_path('app/public/order-label-splits/');

        $processed = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($pageCount);
        $progressBar->start();

        for ($i = 1; $i <= $pageCount; $i++) {
            try {
                // Try FPDI approach for individual file
                $fpdi = new Fpdi();
                $fpdi->setSourceFile($pdfPath);
                $fpdi->AddPage();
                $tplId = $fpdi->importPage($i);
                $fpdi->useTemplate($tplId);

                $splitFileName = $baseName . '_page_' . $i . '.pdf';
                $splitFilePath = $outputDir . $splitFileName;

                $fpdi->Output('F', $splitFilePath);

                // Extract text from this page
                $pageText = isset($pages[$i - 1]) ? $pages[$i - 1]->getText() : '';

                // Save to database
                OrderLabel::create([
                    'code' => $baseName . '_' . $i . '_' . time(),
                    'order_date' => now(),
                    'note' => 'PDF Split: ' . $splitFileName,
                    'original_filename' => $file,
                    'split_filename' => $splitFileName,
                    'page_number' => $i,
                    'file_path' => 'order-label-splits/' . $splitFileName,
                    'extracted_text' => $pageText,
                    'status' => 'open',
                    'saved' => 1,
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);

                $processed++;

            } catch (\Exception $e) {
                $this->warn("Error processing page {$i}: " . $e->getMessage());

                // Create fallback record for this page
                OrderLabel::create([
                    'code' => $baseName . '_' . $i . '_fallback_' . time(),
                    'order_date' => now(),
                    'note' => 'PDF Page Split Failed: ' . $e->getMessage(),
                    'original_filename' => $file,
                    'split_filename' => null,
                    'page_number' => $i,
                    'file_path' => 'order-label-splits/' . $file,
                    'extracted_text' => isset($pages[$i - 1]) ? $pages[$i - 1]->getText() : '',
                    'status' => 'open',
                    'saved' => 1,
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);

                $errors++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("Processing Complete!");
        $this->info("Total pages processed: {$processed}");
        $this->info("Errors encountered: {$errors}");
        $this->info("Database records created: " . OrderLabel::count());

        return 0;
    }
}
