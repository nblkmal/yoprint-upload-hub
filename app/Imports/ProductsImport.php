<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class ProductsImport implements ToModel, WithHeadingRow, WithUpserts, WithChunkReading, WithBatchInserts, ShouldQueue
{
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
}
