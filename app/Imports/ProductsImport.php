<?php

namespace App\Imports;

use App\Events\FileFailedEvent;
use App\Events\FileUploadedEvent;
use App\Models\History;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
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
    private static $currentFileName;
    private $fileName;
    
    public function __construct(string $fileName = null)
    {
        $this->fileName = $fileName;
        if ($fileName) {
            self::$currentFileName = $fileName;
            Log::info('ProductsImport constructor called', ['fileName' => $fileName]);
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
        Log::info('ProductsImport::afterImport called', ['currentFileName' => self::$currentFileName]);
        
        $fileName = self::$currentFileName;
        
        if ($fileName) {
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
    }

    public static function failed(ImportFailed $event)
    {
        Log::info('ProductsImport::failed called', ['currentFileName' => self::$currentFileName]);
        
        $fileName = self::$currentFileName;
        
        if ($fileName) {
            // Update history status to failed
            $history = History::findByFileName($fileName);
            $fileId = $history ? $history->id : 0;
            
            History::updateStatusByFileName($fileName, 'failed');
            
            // Get the exception message if available
            $errorMessage = $event->e ? $event->e->getMessage() : 'Import failed';
            
            event(new FileFailedEvent($fileName, $fileId, $errorMessage));
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
