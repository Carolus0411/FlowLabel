<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\OrderLabel;
use Carbon\Carbon;

class CleanupOldSplitPdfs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdf:cleanup-old-splits {--days=30 : Number of days to keep files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete split PDF files and their folders older than specified days (default: 30 days)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Cleaning up split PDF files older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')})...");

        $disk = Storage::disk('public');
        $basePath = 'order-label-splits';

        if (!$disk->exists($basePath)) {
            $this->warn("Directory {$basePath} does not exist.");
            return 0;
        }

        // Get all directories in order-label-splits
        $directories = $disk->directories($basePath);
        $deletedFiles = 0;
        $deletedFolders = 0;
        $errors = 0;

        // Track batch folders to delete
        $batchFoldersToDelete = [];

        // Get old order labels from database
        $oldOrderLabels = OrderLabel::where('created_at', '<', $cutoffDate)
            ->whereNotNull('file_path')
            ->get();

        if ($oldOrderLabels->isEmpty()) {
            $this->info("No old order labels found to clean up.");
            return 0;
        }

        $this->info("Found {$oldOrderLabels->count()} old order label records to process.");
        $progressBar = $this->output->createProgressBar($oldOrderLabels->count());
        $progressBar->start();

        foreach ($oldOrderLabels as $orderLabel) {
            try {
                $filePath = $orderLabel->file_path;

                // Delete the physical file if it exists
                if ($disk->exists($filePath)) {
                    $disk->delete($filePath);
                    $deletedFiles++;
                }

                // Extract batch folder from file path (e.g., "order-label-splits/FLOW-26-01-15-001/file.pdf")
                $pathParts = explode('/', $filePath);
                if (count($pathParts) >= 3) {
                    $batchFolder = $pathParts[0] . '/' . $pathParts[1];
                    if (!in_array($batchFolder, $batchFoldersToDelete)) {
                        $batchFoldersToDelete[] = $batchFolder;
                    }
                }

                // Update database record - set file_path to null instead of deleting
                $orderLabel->file_path = null;
                $orderLabel->save();

                $progressBar->advance();

            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error processing file {$orderLabel->file_path}: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->newLine();

        // Clean up empty batch folders
        $this->info("Checking and cleaning up empty batch folders...");

        foreach ($batchFoldersToDelete as $batchFolder) {
            try {
                // Check if folder still exists and is empty
                if ($disk->exists($batchFolder)) {
                    $files = $disk->files($batchFolder);

                    if (empty($files)) {
                        // Folder is empty, delete it
                        $disk->deleteDirectory($batchFolder);
                        $deletedFolders++;
                        $this->info("Deleted empty folder: {$batchFolder}");
                    } else {
                        $this->warn("Folder {$batchFolder} still has {" . count($files) . "} files, skipping deletion.");
                    }
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("Error deleting folder {$batchFolder}: " . $e->getMessage());
            }
        }

        // Also check for any other empty directories
        foreach ($directories as $directory) {
            try {
                if ($disk->exists($directory)) {
                    $files = $disk->files($directory);

                    if (empty($files)) {
                        $disk->deleteDirectory($directory);
                        $deletedFolders++;
                    }
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("Error processing directory {$directory}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Cleanup completed!");
        $this->info("Files deleted: {$deletedFiles}");
        $this->info("Folders deleted: {$deletedFolders}");

        if ($errors > 0) {
            $this->warn("Errors encountered: {$errors}");
        }

        return 0;
    }
}
