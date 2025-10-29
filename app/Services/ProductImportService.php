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
            Excel::queueImport(new ProductsImport, $filePath, null, \Maatwebsite\Excel\Excel::CSV);
        } catch (\Throwable $e) {
            Log::error('CSV Import failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
