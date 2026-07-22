<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileProxyController extends Controller
{
    public function show(string $path): StreamedResponse
    {
        $fullPath = $path;
        
        if (!Storage::disk('public')->exists($fullPath)) {
            abort(404, 'File not found: ' . $fullPath);
        }
        
        $mimeType = Storage::disk('public')->mimeType($fullPath);
        
        return response()->stream(function () use ($fullPath) {
            $stream = Storage::disk('public')->readStream($fullPath);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mimeType,
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => '*',
        ]);
    }
}
