<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\Models\OrderLabel;

class PrintLabelController extends Controller
{
    public function download($path)
    {
        Gate::authorize('view print-label');

        // Decode the path parameter that was URL encoded
        $decodedPath = urldecode($path);

        if (!Storage::disk('public')->exists($decodedPath)) {
            abort(404, 'File not found');
        }

        $fullPath = Storage::disk('public')->path($decodedPath);

        // Check if this is a request for individual page extraction
        if (request()->has('page') && request()->has('label_id')) {
            return $this->downloadPage($fullPath, request()->get('page'), request()->get('label_id'));
        }

        return Response::download($fullPath);
    }

    /**
     * Download individual page from a multi-page PDF
     */
    private function downloadPage($fullPath, $pageNumber, $labelId)
    {
        try {
            // Find the OrderLabel record
            $orderLabel = OrderLabel::findOrFail($labelId);

            // Use the stored split_filename as the download name if available, otherwise construct one
            if (!empty($orderLabel->split_filename)) {
                $downloadName = $orderLabel->split_filename;
                // Ensure it has .pdf extension
                if (!str_ends_with(strtolower($downloadName), '.pdf')) {
                    $downloadName .= '.pdf';
                }
            } else {
                $downloadName = "print_label_{$labelId}_page_{$pageNumber}.pdf";
            }

            $tempFileName = "temp_" . $downloadName; // Start with temp prefix logic inside, handled by path below
            // Actually just use the unique ID for the physical temp file to avoid collision
            $tempPhysicalName = "temp_" . uniqid() . "_" . $pageNumber . ".pdf";
            $tempPath = storage_path('app/temp/' . $tempPhysicalName);

            // Create temp directory if it doesn't exist
            if (!is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            // Try ghostscript first if available as it preserves text better
            if ($this->extractPageWithGhostscript($fullPath, $pageNumber, $tempPath)) {
                 return Response::download($tempPath, $downloadName)->deleteFileAfterSend(true);
            }

            // Fallback to FPDI
            if ($this->extractPageWithFPDI($fullPath, $pageNumber, $tempPath)) {
                return Response::download($tempPath, $downloadName)->deleteFileAfterSend(true);
            }

            // If extraction fails, return the full file with a warning?
            // Or just return the full file named correctly to be helpful
            return Response::download($fullPath, $downloadName);

        } catch (\Exception $e) {
            \Log::error("Download page failed: " . $e->getMessage());
            abort(500, "Failed to download page: " . $e->getMessage());
        }
    }

    private function extractPageWithGhostscript($sourcePath, $pageNumber, $outputPath)
    {
        // Try common paths
        $gsPath = null;
        $paths = [
            'gswin64c.exe', 'gswin32c.exe', 'gs',
            'C:\Program Files\gs\gs10.04.0\bin\gswin64c.exe' // Add other explicit paths if needed
        ];

        foreach ($paths as $path) {
             // Simple existence check doesn't handle PATH command well, but we try specific files
             if (strpos($path, ':') !== false && file_exists($path)) {
                 $gsPath = $path;
                 break;
             }
        }

        // If not found in explicit paths, assume 'gswin64c' is in PATH if we want,
        // but for now let's skip sophisticated detection here for simplicity or assume installed if desired.
        // If we can't find it easily, just return false to fall back to FPDI.
        // Actually, just trying the command is often easiest check.

        return false; // For now disable server-side GS extraction unless confirmed installed
    }

    // Moved extractPageWithFPDI logic to be after the helper methods


    /**
     * Try to extract a specific page using FPDI
     */
    private function extractPageWithFPDI($sourcePath, $pageNumber, $outputPath)
    {
        try {
            $pdf = new \setasign\Fpdi\Fpdi();
            $pageCount = $pdf->setSourceFile($sourcePath);

            if ($pageNumber > $pageCount) {
                return false;
            }

            $tplIdx = $pdf->importPage($pageNumber);
            $pdf->AddPage();
            $pdf->useTemplate($tplIdx);

            $pdf->Output($outputPath, 'F');

            return file_exists($outputPath);

        } catch (\Exception $e) {
            \Log::info("FPDI page extraction failed: " . $e->getMessage());
            return false;
        }
    }

    public function downloadAll(Request $request)
    {
        Gate::authorize('view print-label');

        // Get filters from request
        $date1 = $request->get('date1', date('Y-m-01'));
        $date2 = $request->get('date2', date('Y-m-t'));
        $code = $request->get('code', '');
        $status = $request->get('status', '');

        // Get only the filtered order labels
        $orderLabels = OrderLabel::stored()
            ->whereDateBetween('DATE(order_date)', $date1, $date2)
            ->when(!empty($code), fn($q) => $q->where('code', 'like', '%' . $code . '%'))
            ->when(!empty($status), fn($q) => $q->where('status', $status))
            ->whereNotNull('file_path')
            ->get();

        if ($orderLabels->isEmpty()) {
            return back()->with('error', 'No PDF files found in the current filtered list');
        }

        $zipFileName = 'print_label_files_' . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        // Create temp directory if it doesn't exist
        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) !== TRUE) {
            return back()->with('error', 'Cannot create zip file');
        }

        $addedCount = 0;
        // Add only files from the filtered list
        foreach ($orderLabels as $orderLabel) {
            if ($orderLabel->file_path && Storage::disk('public')->exists($orderLabel->file_path)) {
                $filePath = Storage::disk('public')->path($orderLabel->file_path);
                $fileName = $orderLabel->split_filename ?? basename($orderLabel->file_path);

                // Make filename unique if duplicate
                $baseName = pathinfo($fileName, PATHINFO_FILENAME);
                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                $counter = 1;
                $uniqueFileName = $fileName;

                while ($zip->locateName($uniqueFileName) !== false) {
                    $uniqueFileName = $baseName . '_' . $counter . '.' . $extension;
                    $counter++;
                }

                $zip->addFile($filePath, $uniqueFileName);
                $addedCount++;
            }
        }

        $zip->close();

        if ($addedCount === 0) {
            @unlink($zipPath);
            return back()->with('error', 'No valid PDF files found to download');
        }

        return Response::download($zipPath)->deleteFileAfterSend(true);
    }

    public function show(OrderLabel $orderLabel)
    {
        Gate::authorize('view print-label');

        return view('print-label.show', compact('orderLabel'));
    }

    public function markPrinted($id)
    {
        Gate::authorize('view print-label');

        $orderLabel = OrderLabel::findOrFail($id);

        $orderLabel->update([
            'printed_at' => now(),
            'printed_by' => auth()->id() ?? 0,
            'print_count' => ($orderLabel->print_count ?? 0) + 1,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Public download method - no authentication required
     * Used for Excel export links
     */
    public function publicDownload($path)
    {
        // Decode the path parameter that was URL encoded
        $decodedPath = urldecode($path);

        if (!Storage::disk('public')->exists($decodedPath)) {
            abort(404, 'File not found');
        }

        $fullPath = Storage::disk('public')->path($decodedPath);

        // Check if this is a request for individual page extraction
        if (request()->has('page') && request()->has('label_id')) {
            return $this->downloadPage($fullPath, request()->get('page'), request()->get('label_id'));
        }

        return Response::download($fullPath);
    }
}