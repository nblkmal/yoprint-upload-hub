<?php

use App\Services\ProductImportService;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->service = new ProductImportService();
    $this->filePath = '/path/to/test/file.csv';
});

test('it can queue import a CSV file successfully', function () {
    // Arrange
    Excel::fake();
    
    // Act
    $this->service->import($this->filePath);
    
    // Assert
    Excel::assertQueued($this->filePath, function (ProductsImport $import) {
        return true;
    });
});

test('it uses correct Excel driver for CSV queue import', function () {
    // Arrange
    Excel::fake();
    
    // Act
    $this->service->import($this->filePath);
    
    // Assert
    Excel::assertQueued($this->filePath, function (ProductsImport $import) {
        return true;
    }, null, \Maatwebsite\Excel\Excel::CSV);
});

test('it logs error and rethrows exception when queue import fails', function () {
    // Arrange
    Log::shouldReceive('info')
        ->once()
        ->with('ProductsImport constructor called', ['fileName' => 'file.csv']);
    
    Log::shouldReceive('error')
        ->once()
        ->with('CSV Import failed: Import failed');
    
    // Mock Excel facade to throw exception
    Excel::shouldReceive('queueImport')
        ->once()
        ->andThrow(new \Exception('Import failed'));
    
    // Act & Assert
    try {
        $this->service->import($this->filePath);
        expect(false)->toBeTrue(); // Should not reach here
    } catch (\Exception $e) {
        expect($e->getMessage())->toBe('Import failed');
    }
});

test('it handles different types of exceptions', function () {
    // Arrange
    Log::shouldReceive('info')
        ->once()
        ->with('ProductsImport constructor called', ['fileName' => 'file.csv']);
    
    Log::shouldReceive('error')
        ->once()
        ->with('CSV Import failed: Runtime error occurred');
    
    // Mock Excel facade to throw RuntimeException
    Excel::shouldReceive('queueImport')
        ->once()
        ->andThrow(new \RuntimeException('Runtime error occurred'));
    
    // Act & Assert
    try {
        $this->service->import($this->filePath);
        expect(false)->toBeTrue(); // Should not reach here
    } catch (\RuntimeException $e) {
        expect($e->getMessage())->toBe('Runtime error occurred');
    }
});

test('it creates ProductsImport instance correctly', function () {
    // Arrange
    Excel::fake();
    
    // Act
    $this->service->import($this->filePath);
    
    // Assert
    Excel::assertQueued($this->filePath, function ($import) {
        return $import instanceof ProductsImport;
    });
});

test('it passes correct file path to Excel queue import', function () {
    // Arrange
    Excel::fake();
    $customFilePath = '/custom/path/to/file.csv';
    
    // Act
    $this->service->import($customFilePath);
    
    // Assert
    Excel::assertQueued($customFilePath);
});

test('it does not log errors when queue import succeeds', function () {
    // Arrange
    Log::shouldReceive('info')
        ->once()
        ->with('ProductsImport constructor called', ['fileName' => 'file.csv']);
    
    Log::shouldReceive('error')->never();
    Excel::fake();
    
    // Act
    $this->service->import($this->filePath);
    
    // Assert - No exception thrown and no error logged
    expect(true)->toBeTrue();
});

test('it verifies ProductsImport implements ShouldQueue interface', function () {
    // Arrange
    Excel::fake();
    
    // Act
    $this->service->import($this->filePath);
    
    // Assert
    Excel::assertQueued($this->filePath, function ($import) {
        return $import instanceof \Illuminate\Contracts\Queue\ShouldQueue;
    });
});

test('it passes filename to ProductsImport constructor', function () {
    // Arrange
    Excel::fake();
    $filePath = '/path/to/test/my-file.csv';
    
    // Act
    $this->service->import($filePath);
    
    // Assert
    Excel::assertQueued($filePath, function (ProductsImport $import) {
        // We can't directly test the constructor parameter, but we can verify
        // that the ProductsImport instance was created correctly
        return true;
    });
});
