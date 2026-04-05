<?php

namespace App\Http\Controllers;

use App\Models\ProductFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductFileDownloadController extends Controller
{
    public function __invoke(Request $request, ProductFile $file): StreamedResponse
    {
        $this->authorize('download', $file->product);

        if (! Storage::disk($file->disk)->exists($file->path)) {
            abort(404);
        }

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }
}
