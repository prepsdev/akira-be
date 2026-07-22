<?php

namespace App\Jobs;

use App\Models\DataOcr;
use App\Models\OcrTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessOcrDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 0;
    public $tries = 1;

    protected $dataOcrId;
    protected $filePath;
    protected $templateId;

    public function __construct(int $dataOcrId, string $filePath, int $templateId)
    {
        $this->dataOcrId = $dataOcrId;
        $this->filePath = $filePath;
        $this->templateId = $templateId;
    }

    public function handle(): void
    {
        $dataOcr = DataOcr::find($this->dataOcrId);

        if (!$dataOcr) {
            Log::error("DataOcr not found: {$this->dataOcrId}");
            return;
        }

        try {
            Log::info("Starting OCR processing for DataOcr ID: {$this->dataOcrId}");

            $dataOcr->update([
                'status' => 'Processing',
                'progress' => 10
            ]);

            $template = OcrTemplate::with('namingRule')->find($this->templateId);

            if (!$template) {
                throw new \Exception('Template not found');
            }

            $fullPath = storage_path('app/public/' . $this->filePath);

            if (!file_exists($fullPath)) {
                throw new \Exception('File not found: ' . $fullPath);
            }

            Log::info("Calling DocAI API for file: {$fullPath}");

            $dataOcr->update(['progress' => 30]);

            $response = Http::timeout(0)->attach(
                'file',
                file_get_contents($fullPath),
                basename($fullPath)
            )->post('http://localhost:8001/process-ocr', [
                'fields' => json_encode($template->fields)
            ]);

            $dataOcr->update(['progress' => 80]);

            if (!$response->successful()) {
                throw new \Exception('OCR API request failed: ' . $response->body());
            }

            $result = $response->json();

            if (!$result || !isset($result['success'])) {
                throw new \Exception('Invalid response from OCR API');
            }

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'OCR processing failed');
            }

            Log::info("OCR processing successful for DataOcr ID: {$this->dataOcrId}");

            $fileName = $this->generateFileName($template, $result['extracted_fields']);

            $oldPath = $dataOcr->file_path;
            $extension = pathinfo($oldPath, PATHINFO_EXTENSION);
            $newPath = 'uploads/' . $fileName . '.' . $extension;

            $counter = 1;
            $basePath = $newPath;
            while (Storage::disk('public')->exists($newPath)) {
                $newPath = 'uploads/' . $fileName . '_' . $counter . '.' . $extension;
                $counter++;
            }

            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->move($oldPath, $newPath);
                Log::info("File renamed from {$oldPath} to {$newPath}");
            }

            $dataOcr->update([
                'extracted_fields' => $result['extracted_fields'],
                'nama_file' => $fileName,
                'content' => $result['full_text'] ?? '',
                'confidence_score' => $result['confidence_score'] ?? 0.0,
                'file_path' => $newPath,
                'status' => 'Uploaded',
                'progress' => 100
            ]);

            Log::info("DataOcr updated successfully: {$this->dataOcrId}");
        } catch (\Exception $e) {
            Log::error("OCR processing failed for DataOcr ID: {$this->dataOcrId}");
            Log::error("Error: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());

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

        return $pattern;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Job failed permanently for DataOcr ID: {$this->dataOcrId}");
        Log::error("Exception: " . $exception->getMessage());

        $dataOcr = DataOcr::find($this->dataOcrId);

        if ($dataOcr) {
            $dataOcr->update([
                'status' => 'Failed',
                'progress' => 0
            ]);
        }
    }
}
