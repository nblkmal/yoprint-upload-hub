<?php

namespace App\Http\Controllers;

use App\Http\Resources\HistoryResource;
use App\Jobs\ProcessProductImport;
use App\Models\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class FileUploadController extends Controller
{
    public function index()
    {
        $histories = History::recent()->get();
        return Inertia::render('UploadFile', [
            'histories' => HistoryResource::collection($histories)
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls', // 10MB max
        ]);

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('uploads', $fileName, 'local');

        // Create or update history record
        History::createOrUpdateByFileName($fileName, 'pending');
        $histories = History::recent()->get();

        // Dispatch the job to process the file (pass relative path instead of absolute)
        ProcessProductImport::dispatch($filePath);

        return response()->json([
            'success' => true,
            'message' => 'File uploaded and processing started',
            'histories' => HistoryResource::collection($histories),
        ]);
    }
}