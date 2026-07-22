<?php

namespace App\Http\Controllers;

use App\Models\OcrTemplate;
use App\Models\DataOcr;
use App\Models\NamingRule;
use App\Jobs\ProcessOcrDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class OcrProcessController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'ocr_template_id' => 'required|exists:ocr_templates,id',
        ]);

        $template = OcrTemplate::with('namingRule')->findOrFail($validated['ocr_template_id']);
        $uploadedFiles = [];
        $errors = [];

        foreach ($request->file('files') as $file) {
            try {
                $filePath = $file->store('uploads', 'public');

                $dataOcr = DataOcr::create([
                    'ocr_template_id' => $template->id,
                    'extracted_fields' => [],
                    'nama_file' => $file->getClientOriginalName(),
                    'content' => '',
                    'confidence_score' => 0.0,
                    'file_path' => $filePath,
                    'status' => 'Queued',
                    'progress' => 0
                ]);

                ProcessOcrDocument::dispatch($dataOcr->id, $filePath, $template->id);

                $uploadedFiles[] = $dataOcr->load('ocrTemplate');
            } catch (\Exception $e) {
                if (isset($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }

                Log::error('OCR Upload failed for file: ' . $file->getClientOriginalName(), [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                $errors[] = [
                    'filename' => $file->getClientOriginalName(),
                    'error' => $e->getMessage()
                ];
            }
        }

        if (count($uploadedFiles) > 0) {
            return response()->json([
                'success' => true,
                'message' => count($uploadedFiles) . ' file(s) uploaded successfully. Processing in background.',
                'data' => $uploadedFiles,
                'errors' => $errors
            ], 201);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'All uploads failed',
                'errors' => $errors
            ], 500);
        }
    }

    public function uploadWithAutoCategorization(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $templatesCount = OcrTemplate::count();
        if ($templatesCount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No OCR templates found. Please create templates first.',
            ], 400);
        }

        $uploadedFiles = [];
        $errors = [];

        foreach ($request->file('files') as $file) {
            try {
                $filePath = $file->store('uploads', 'public');

                $dataOcr = DataOcr::create([
                    'ocr_template_id' => null,
                    'extracted_fields' => [],
                    'nama_file' => $file->getClientOriginalName(),
                    'content' => '',
                    'confidence_score' => 0.0,
                    'file_path' => $filePath,
                    'status' => 'Queued',
                    'progress' => 0
                ]);

                \App\Jobs\ProcessOcrDocumentWithCategorization::dispatch($dataOcr->id, $filePath);

                $uploadedFiles[] = [
                    'file' => $file->getClientOriginalName(),
                    'data' => $dataOcr
                ];

                Log::info("File queued for auto-categorization: {$file->getClientOriginalName()}", [
                    'data_ocr_id' => $dataOcr->id
                ]);
            } catch (\Exception $e) {
                if (isset($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }

                Log::error('Upload failed for file: ' . $file->getClientOriginalName(), [
                    'error' => $e->getMessage(),
                ]);

                $errors[] = [
                    'filename' => $file->getClientOriginalName(),
                    'error' => $e->getMessage()
                ];
            }
        }

        if (count($uploadedFiles) > 0) {
            return response()->json([
                'success' => true,
                'message' => count($uploadedFiles) . ' file(s) uploaded and queued for auto-categorization.',
                'data' => $uploadedFiles,
                'errors' => $errors
            ], 201);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'All uploads failed',
                'errors' => $errors
            ], 500);
        }
    }

    private function generateFileName(OcrTemplate $template, array $extractedFields): string
    {
        $namingRule = $template->namingRule;

        if (!$namingRule) {
            return $template->kategori . '_' . time();
        }

        $pattern = $namingRule->naming_pattern;

        foreach ($extractedFields as $fieldName => $value) {
            $pattern = str_replace('{' . $fieldName . '}', $value, $pattern);
        }

        $pattern = str_replace('{kategori}', $template->kategori, $pattern);

        return $pattern;
    }
}
