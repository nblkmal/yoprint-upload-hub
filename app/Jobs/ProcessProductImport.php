<?php

namespace App\Jobs;

use App\Events\FileFailedEvent;
use App\Events\FileProcessingEvent;
use App\Events\FileUploadedEvent;
use App\Models\History;
use App\Services\ProductImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessProductImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $filePath;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [1, 5, 10]; // Wait 1s, then 5s, then 10s between retries
    }

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function handle(ProductImportService $importService): void
    {
        // Increase memory limit and time limit for large imports
        ini_set('memory_limit', '512M');
        set_time_limit(0);
        
        // Extract filename from path
        $fileName = basename($this->filePath);
        
        // Update history status to processing
        $history = History::updateStatusByFileName($fileName, 'processing');
        
        event(new FileProcessingEvent($fileName));
        
        // Convert relative path to absolute path using Storage facade
        $absolutePath = Storage::path($this->filePath);

        // Debug logging
        Log::info('Job processing file', [
            'relative_path' => $this->filePath,
            'absolute_path' => $absolutePath,
            'file_exists' => file_exists($absolutePath),
            'storage_exists' => Storage::exists($this->filePath),
            'file_size' => file_exists($absolutePath) ? filesize($absolutePath) : 'N/A',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ]);

        if (!Storage::exists($this->filePath)) {
            Log::error("File does not exist: {$this->filePath}");
            throw new \Exception("File does not exist: {$this->filePath}");
        }

        Log::info('Starting import process', ['file' => $this->filePath]);
        $startTime = microtime(true);
        
        $importService->import($absolutePath);
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        Log::info('Import completed successfully', [
            'file' => $this->filePath,
            'duration_seconds' => $duration,
            'memory_peak' => memory_get_peak_usage(true) / 1024 / 1024 . 'MB'
        ]);
        
        // Update history status to completed
        $history = History::findByFileName($fileName);
        if ($history) {
            History::updateStatusByFileName($fileName, 'completed');
            
            // Trigger the file uploaded event with the actual file ID
            event(new FileUploadedEvent($fileName, $history->id));
        } else {
            // Fallback - create new history record if not found
            $history = History::createOrUpdateByFileName($fileName, 'completed');
            event(new FileUploadedEvent($fileName, $history->id));
        }
    }

    public function failed(Throwable $exception): void
    {
        $fileName = basename($this->filePath);
        
        Log::error('ProcessProductImport job failed', [
            'file_path' => $this->filePath,
            'file_name' => $fileName,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Update history status to failed
        $history = History::findByFileName($fileName);
        $fileId = $history ? $history->id : 0;
        
        History::updateStatusByFileName($fileName, 'failed');
        
        event(new FileFailedEvent($fileName, $fileId, $exception->getMessage()));
    }
}
