<?php

namespace App\Console\Commands;

use App\Models\EmailSchedule;
use App\Models\DataOcr;
use App\Models\OcrTemplate;
use App\Mail\ScheduledExportMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SendScheduledExports extends Command
{
    protected $signature = 'exports:send-scheduled {frequency?}';
    protected $description = 'Send scheduled email exports (daily, weekly, monthly)';

    public function handle()
    {
        $frequency = $this->argument('frequency');

        $query = EmailSchedule::where('is_active', true);

        if ($frequency) {
            $query->where('frequency', $frequency);
        }

        $schedules = $query->get();

        if ($schedules->isEmpty()) {
            $this->info('No active schedules found.');
            return 0;
        }

        foreach ($schedules as $schedule) {
            try {
                $this->info("Processing schedule ID {$schedule->id} for {$schedule->email}");

                $dateRange = $this->getDateRange($schedule->frequency);
                $templateIds = $schedule->ocr_template_ids ?? [];

                $query = DataOcr::whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);

                if (!empty($templateIds)) {
                    $query->whereIn('ocr_template_id', $templateIds);
                }

                $documents = $query->with('ocrTemplate')->get();

                $categories = 'All Categories';
                if (!empty($templateIds)) {
                    $templates = OcrTemplate::whereIn('id', $templateIds)->pluck('kategori')->toArray();
                    $categories = implode(', ', $templates);
                }

                $filePath = $this->generateExport($documents, $schedule->export_format);

                Mail::to($schedule->email)->send(
                    new ScheduledExportMail(
                        $schedule->frequency,
                        $categories,
                        $documents->count(),
                        $dateRange['label'],
                        $filePath,
                        $schedule->export_format
                    )
                );

                $schedule->update(['last_sent_at' => now()]);

                if ($filePath) {
                    Storage::delete($filePath);
                }

                $this->info("✓ Email sent to {$schedule->email} ({$documents->count()} documents)");
            } catch (\Exception $e) {
                $this->error("✗ Failed to send email to {$schedule->email}: {$e->getMessage()}");
            }
        }

        $this->info('Scheduled exports completed.');
        return 0;
    }

    private function getDateRange($frequency)
    {
        $now = Carbon::now();

        switch ($frequency) {
            case 'daily':
                return [
                    'start' => $now->copy()->subDay()->startOfDay(),
                    'end' => $now->copy()->subDay()->endOfDay(),
                    'label' => $now->copy()->subDay()->format('Y-m-d')
                ];
            case 'weekly':
                return [
                    'start' => $now->copy()->subWeek()->startOfWeek(),
                    'end' => $now->copy()->subWeek()->endOfWeek(),
                    'label' => $now->copy()->subWeek()->startOfWeek()->format('Y-m-d') . ' to ' .
                        $now->copy()->subWeek()->endOfWeek()->format('Y-m-d')
                ];
            case 'monthly':
                return [
                    'start' => $now->copy()->subMonth()->startOfMonth(),
                    'end' => $now->copy()->subMonth()->endOfMonth(),
                    'label' => $now->copy()->subMonth()->format('F Y')
                ];
            default:
                return [
                    'start' => $now->copy()->subDay()->startOfDay(),
                    'end' => $now->copy()->subDay()->endOfDay(),
                    'label' => $now->copy()->subDay()->format('Y-m-d')
                ];
        }
    }

    private function generateExport($documents, $format)
    {
        if ($documents->isEmpty()) {
            return null;
        }

        $data = $documents->map(function ($doc) {
            $row = [
                'File Name' => $doc->nama_file,
                'Category' => $doc->ocrTemplate->kategori ?? 'N/A',
                'Status' => $doc->status,
                'Confidence Score' => $doc->confidence_score,
                'Created At' => $doc->created_at->format('Y-m-d H:i:s'),
            ];

            if (!empty($doc->extracted_fields)) {
                foreach ($doc->extracted_fields as $key => $value) {
                    $row[$key] = $value;
                }
            }

            return $row;
        });

        if ($format === 'xlsx') {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headers = array_keys($data->first());
            $sheet->fromArray($headers, null, 'A1');

            $rowIndex = 2;
            foreach ($data as $row) {
                $sheet->fromArray(array_values($row), null, 'A' . $rowIndex);
                $rowIndex++;
            }

            foreach (range('A', $sheet->getHighestColumn()) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $fileName = 'exports/scheduled_export_' . time() . '.xlsx';
            $filePath = storage_path('app/' . $fileName);

            if (!file_exists(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }

            $writer->save($filePath);

            return $fileName;
        } elseif ($format === 'csv') {
            $fileName = 'exports/scheduled_export_' . time() . '.csv';
            $filePath = storage_path('app/' . $fileName);

            if (!file_exists(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }

            $file = fopen($filePath, 'w');

            fputcsv($file, array_keys($data->first()));

            foreach ($data as $row) {
                fputcsv($file, array_values($row));
            }

            fclose($file);

            return $fileName;
        }

        return null;
    }
}
