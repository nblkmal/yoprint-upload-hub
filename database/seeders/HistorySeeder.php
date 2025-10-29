<?php

namespace Database\Seeders;

use App\Models\History;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sampleFiles = [
            [
                'file_name' => '1698567890_products_inventory.xlsx',
                'status' => 'completed',
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5)->addMinutes(3),
            ],
            [
                'file_name' => '1698654290_new_products_batch.csv',
                'status' => 'completed',
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3)->addMinutes(2),
            ],
            [
                'file_name' => '1698740690_product_updates.xlsx',
                'status' => 'failed',
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2)->addMinutes(1),
            ],
            [
                'file_name' => '1698827090_quarterly_data.csv',
                'status' => 'processing',
                'created_at' => now()->subHours(6),
                'updated_at' => now()->subHours(6)->addMinutes(30),
            ],
            [
                'file_name' => '1698834290_daily_import.xlsx',
                'status' => 'pending',
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'file_name' => '1698836890_product_catalog.csv',
                'status' => 'completed',
                'created_at' => now()->subHour(),
                'updated_at' => now()->subHour()->addMinutes(5),
            ],
            [
                'file_name' => '1698838490_test_upload.xlsx',
                'status' => 'pending',
                'created_at' => now()->subMinutes(30),
                'updated_at' => now()->subMinutes(30),
            ],
        ];

        foreach ($sampleFiles as $file) {
            History::create($file);
        }
    }
}
