<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// File upload routes (no authentication required)
Route::get('/upload-file', [App\Http\Controllers\FileUploadController::class, 'index'])->name('upload-file');
Route::post('/upload-file', [App\Http\Controllers\FileUploadController::class, 'upload'])->name('upload-file.upload');

require __DIR__.'/settings.php';
