<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrderLabel;
use Illuminate\Support\Str;

class RescanOrderLabelIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order-label:rescan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rescan existing Order Label records to extract Order IDs from text';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting rescan of Order Labels...');

        // Find records that don't look like they have an Order ID (contain '_page_' or '_original_')
        // or just scan everything that doesn't look like a pure number ID
        $query = OrderLabel::where('split_filename', 'like', '%_page_%')
            ->orWhere('split_filename', 'like', '%_original%')
            ->orWhereNull('split_filename');

        $count = $query->count();
        $this->info("Found {$count} records to check.");

        $bar = $this->output->createProgressBar($count);
        $updated = 0;

        $query->chunk(100, function ($labels) use ($bar, &$updated) {
            foreach ($labels as $label) {
                if (empty($label->extracted_text)) {
                    $bar->advance();
                    continue;
                }

                $orderId = $this->extractOrderId($label->extracted_text);

                if ($orderId) {
                    // Update filename and code
                    $oldName = $label->split_filename;
                    $extension = pathinfo($oldName, PATHINFO_EXTENSION) ?: 'pdf';
                    
                    // Handle duplicates for filename
                    $baseName = $orderId;
                    
                    // Check if code exists (excluding self)
                    $exists = OrderLabel::where('code', $orderId)
                        ->where('id', '!=', $label->id)
                        ->exists();

                    $finalCode = $orderId;
                    if ($exists) {
                        // Append count or random just to make unique if we really want to save it as ID
                        // Or maybe simple append timestamp or iterator
                        $finalCode = $orderId . '_' . $label->id;
                    }
                    
                    $newFilename = $finalCode . '.' . $extension;
                    
                    try {
                        $label->update([
                            'code' => $finalCode,
                            'note' => "Order ID: $orderId (Rescanned)",
                            'split_filename' => $newFilename
                        ]);
                        $updated++;
                    } catch (\Exception $e) {
                         // Skip if still duplicate or other error
                    }
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Rescan complete. Updated {$updated} records.");
    }

    private function extractOrderId(string $text): ?string
    {
        // 1. Precise Match with flexibility
        if (preg_match('/Order\s*(?:Id|No|#|Number)?\s*[:\.]?\s*(\d{8,})/i', $text, $matches)) {
            return $matches[1];
        }
        
        // 2. Loose match
        if (preg_match('/Order\s*(?:Id|No|#)?\s*.{0,50}?\s*(\d{10,})/is', $text, $matches)) {
             return $matches[1];
        }

        // 3. Fallback: Identify very long number sequence
        if (preg_match('/\b(\d{15,})\b/', $text, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
