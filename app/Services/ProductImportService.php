<?php

namespace App\Services;

use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ProductImportService
{
    public function import(string $filePath): void
    {
        try {
            $fileName = basename($filePath);
            Excel::queueImport(new ProductsImport($fileName), $filePath, null, \Maatwebsite\Excel\Excel::CSV);
        } catch (\Throwable $e) {
            Log::error('CSV Import failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
