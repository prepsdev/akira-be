<?php

namespace App\Http\Controllers;

use App\Models\OcrTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OcrTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        $templates = OcrTemplate::with('namingRule')->get();
        return response()->json($templates);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kategori' => 'required|string|unique:ocr_templates,kategori',
            'description' => 'nullable|string',
            'fields' => 'required|array',
            'fields.*.name' => 'required|string',
            'fields.*.type' => 'required|string|in:text,date,number,email,phone',
            'fields.*.description' => 'nullable|string',
        ]);

        $template = OcrTemplate::create($validated);

        return response()->json($template, 201);
    }

    public function show(string $id): JsonResponse
    {
        $template = OcrTemplate::with('namingRule')->findOrFail($id);
        return response()->json($template);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $template = OcrTemplate::findOrFail($id);

        $validated = $request->validate([
            'kategori' => 'required|string|unique:ocr_templates,kategori,' . $id,
            'description' => 'nullable|string',
            'fields' => 'required|array',
            'fields.*.name' => 'required|string',
            'fields.*.type' => 'required|string|in:text,date,number,email,phone',
            'fields.*.description' => 'nullable|string',
        ]);

        $template->update($validated);

        return response()->json($template);
    }

    public function destroy(string $id): JsonResponse
    {
        $template = OcrTemplate::findOrFail($id);
        $template->delete();

        return response()->json(['message' => 'Template deleted successfully']);
    }
}
