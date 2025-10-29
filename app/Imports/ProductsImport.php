<?php

namespace App\Imports;

use App\Events\FileFailedEvent;
use App\Events\FileUploadedEvent;
use App\Models\History;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\ImportFailed;

class ProductsImport implements ToModel, WithHeadingRow, WithUpserts, WithChunkReading, WithBatchInserts, WithEvents, ShouldQueue
{
    private $fileName;
    private $cacheKey;
    
    public function __construct(string $fileName = null)
    {
        $this->fileName = $fileName;
        if ($fileName) {
            // Create a unique cache key for this import instance
            $this->cacheKey = 'import_filename_' . Str::uuid();
            Cache::put($this->cacheKey, $fileName, 3600); // Store for 1 hour
            Log::info('ProductsImport constructor called', ['fileName' => $fileName, 'cacheKey' => $this->cacheKey]);
        }
    }
    public function model(array $row)
    {
        // Clean up UTF-8
        $row = array_map(fn($value) => $this->cleanUtf8($value), $row);

        return new Product([
            'unique_key' => $row['unique_key'],
            'product_title' => $row['product_title'],
            'product_description' => $row['product_description'],
            'style' => $row['style#'] ?? null,
            'sanmar_mainframe_color' => $row['sanmar_mainframe_color'] ?? null,
            'size' => $row['size'] ?? null,
            'color_name' => $row['color_name'] ?? null,
            'piece_price' => $row['piece_price'] ?? null,
        ]);
    }

    public function uniqueBy()
    {
        return 'unique_key';
    }

    private function cleanUtf8($value)
    {
        return $value ? mb_convert_encoding($value, 'UTF-8', 'UTF-8') : null;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public static function afterImport(AfterImport $event)
    {
        // For now, let's get all recent "processing" files and update them to completed
        // This is a workaround since can't reliably get filename from static event handlers in queued jobs
        $recentProcessingFiles = History::where('status', 'processing')
            ->where('updated_at', '>=', now()->subMinutes(10))
            ->get();
        
        // Update the most recent processing file to completed
        if ($recentProcessingFiles->count() > 0) {
            $mostRecentFile = $recentProcessingFiles->sortByDesc('updated_at')->first();
            
            History::updateStatusByFileName($mostRecentFile->file_name, 'completed');
            event(new FileUploadedEvent($mostRecentFile->file_name, $mostRecentFile->id));
            
            Log::info('Updated file to completed', ['fileName' => $mostRecentFile->file_name]);
        }
    }

    public static function failed(ImportFailed $event)
    {
        // Get all recent "processing" files and update them to failed
        $recentProcessingFiles = History::where('status', 'processing')
            ->where('updated_at', '>=', now()->subMinutes(10))
            ->get();
        
        // Update the most recent processing file to failed
        if ($recentProcessingFiles->count() > 0) {
            $mostRecentFile = $recentProcessingFiles->sortByDesc('updated_at')->first();
            
            History::updateStatusByFileName($mostRecentFile->file_name, 'failed');
            
            // Get the exception message if available
            $errorMessage = property_exists($event, 'e') && $event->e ? $event->e->getMessage() : 'Import failed';
            
            event(new FileFailedEvent($mostRecentFile->file_name, $mostRecentFile->id, $errorMessage));
            
            Log::info('Updated file to failed', ['fileName' => $mostRecentFile->file_name, 'error' => $errorMessage]);
        }
    }

    public function registerEvents(): array
    {
        Log::info('ProductsImport::registerEvents called');
        
        return [
            AfterImport::class => [self::class, 'afterImport'],
            ImportFailed::class => [self::class, 'failed'],
        ];
    }
}
