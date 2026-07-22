<?php

namespace App\Jobs;

use App\Models\DataOcr;
use App\Models\OcrTemplate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessOcrDocumentWithCategorization implements ShouldQueue
{
    use Queueable;

    public $timeout = 0;
    public $tries = 1;

    protected $dataOcrId;
    protected $filePath;

    public function __construct($dataOcrId, $filePath)
    {
        $this->dataOcrId = $dataOcrId;
        $this->filePath = $filePath;
    }

    public function handle(): void
    {
        $dataOcr = DataOcr::find($this->dataOcrId);

        if (!$dataOcr) {
            Log::error("DataOcr not found: {$this->dataOcrId}");
            return;
        }

        try {
            Log::info("Starting categorization for DataOcr ID: {$this->dataOcrId}");

            $dataOcr->update([
                'status' => 'Processing',
                'progress' => 10
            ]);

            $templates = OcrTemplate::all();

            if ($templates->isEmpty()) {
                throw new \Exception('No OCR templates found');
            }

            $categoriesWithDescriptions = $templates->map(function ($template) {
                return [
                    'name' => $template->kategori,
                    'description' => $template->description ?? ''
                ];
            })->toArray();

            $fullPath = storage_path('app/public/' . $this->filePath);

            if (!file_exists($fullPath)) {
                throw new \Exception('File not found: ' . $fullPath);
            }

            Log::info("Calling categorization API for file: {$fullPath}");

            $dataOcr->update(['progress' => 20]);

            $response = Http::timeout(600)->attach(
                'file',
                file_get_contents($fullPath),
                basename($fullPath)
            )->post('http://localhost:8001/categorize-document', [
                'categories' => json_encode($categoriesWithDescriptions)
            ]);

            if (!$response->successful()) {
                throw new \Exception('Categorization API failed');
            }

            $result = $response->json();

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Categorization failed');
            }

            $detectedCategory = $result['category'];
            $fullText = $result['full_text'] ?? '';

            $template = $templates->firstWhere('kategori', $detectedCategory);

            if (!$template) {
                throw new \Exception("Template not found for category: {$detectedCategory}");
            }

            Log::info("Document categorized as: {$detectedCategory}");

            $dataOcr->update([
                'ocr_template_id' => $template->id,
                'content' => $fullText,
                'progress' => 50
            ]);

            Log::info("Extracting fields with AI using existing OCR text");

            $extractResponse = Http::timeout(0)->post('http://localhost:8001/extract-fields', [
                'text' => $fullText,
                'fields' => json_encode($template->fields)
            ]);

            $dataOcr->update(['progress' => 80]);

            if (!$extractResponse->successful()) {
                throw new \Exception('Field extraction failed');
            }

            $extractResult = $extractResponse->json();

            if (!$extractResult['success']) {
                throw new \Exception($extractResult['error'] ?? 'Field extraction failed');
            }

            Log::info("Field extraction completed successfully");

            $fileName = $this->generateFileName($template, $extractResult['extracted_fields'] ?? []);

            Log::info("Generated filename: {$fileName}");

            $oldPath = $dataOcr->file_path;
            $extension = pathinfo($oldPath, PATHINFO_EXTENSION);
            $newPath = 'uploads/' . $fileName . '.' . $extension;

            $counter = 1;
            while (Storage::disk('public')->exists($newPath)) {
                $newPath = 'uploads/' . $fileName . '_' . $counter . '.' . $extension;
                $counter++;
            }

            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->move($oldPath, $newPath);
                Log::info("File renamed from {$oldPath} to {$newPath}");
            }

            $dataOcr->update([
                'extracted_fields' => $extractResult['extracted_fields'] ?? [],
                'confidence_score' => $extractResult['confidence_score'] ?? 0.0,
                'nama_file' => $fileName,
                'file_path' => $newPath,
                'status' => 'Uploaded',
                'progress' => 100
            ]);

            Log::info("Processing completed for DataOcr ID: {$this->dataOcrId}");
        } catch (\Exception $e) {
            Log::error("Processing failed for DataOcr ID: {$this->dataOcrId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $dataOcr->update([
                'status' => 'Failed',
                'progress' => 0
            ]);

            throw $e;
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

        $baseFileName = $pattern;
        $fileName = $baseFileName;
        $counter = 1;

        while (DataOcr::where('nama_file', $fileName)
            ->where('id', '!=', $this->dataOcrId)
            ->exists()
        ) {
            $fileName = $baseFileName . '_' . $counter;
            $counter++;
        }

        return $fileName;
    }
}
