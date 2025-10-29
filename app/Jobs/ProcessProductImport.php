<?php

namespace App\Jobs;

use App\Events\FileProcessingEvent;
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
        // ini_set('memory_limit', '512M');
        // set_time_limit(0);
        
        // Extract filename from path
        $fileName = basename($this->filePath);
        
        // Update history status to processing
        History::updateStatusByFileName($fileName, 'processing');
        
        event(new FileProcessingEvent($fileName));
        
        // Convert relative path to absolute path using Storage facade
        $absolutePath = Storage::path($this->filePath);

        if (!Storage::exists($this->filePath)) {
            throw new \Exception("File does not exist: {$this->filePath}");
        }

        // $startTime = microtime(true);
        
        $importService->import($absolutePath);
        
        // $endTime = microtime(true);
        // $duration = round($endTime - $startTime, 2);
    }

    public function failed(Throwable $exception): void
    {
        
    }
}
