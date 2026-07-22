<?php

namespace App\Http\Controllers;

use App\Models\DataOcr;
use App\Models\OcrTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ExportController extends Controller
{
    public function export(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'kategori' => 'required|integer|exists:ocr_templates,id',
            'format' => 'required|in:xlsx,csv',
        ]);

        $template = OcrTemplate::find($validated['kategori']);
        
        $startDate = $validated['start_date'] . ' 00:00:00';
        $endDate = $validated['end_date'] . ' 23:59:59';
        
        $documents = DataOcr::where('ocr_template_id', $validated['kategori'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        if ($validated['format'] === 'xlsx') {
            return $this->exportXlsx($documents, $template);
        } else {
            return $this->exportCsv($documents, $template);
        }
    }

    private function exportXlsx($documents, $template)
    {
        $fields = $template->fields;
        $headers = array_merge(['Nama File', 'Status', 'Confidence Score', 'Tanggal'], array_column($fields, 'name'));
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $colIndex = 1;
        foreach ($headers as $header) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue($colLetter . '1', $header);
            $sheet->getStyle($colLetter . '1')->getFont()->setBold(true);
            $colIndex++;
        }
        
        $row = 2;
        foreach ($documents as $doc) {
            $sheet->setCellValue('A' . $row, $doc->nama_file);
            $sheet->setCellValue('B' . $row, $doc->status);
            $sheet->setCellValue('C' . $row, $doc->confidence_score);
            $sheet->setCellValue('D' . $row, $doc->created_at->format('Y-m-d H:i:s'));
            
            $colIndex = 5;
            foreach ($fields as $field) {
                $extractedFields = is_string($doc->extracted_fields) 
                    ? json_decode($doc->extracted_fields, true) 
                    : $doc->extracted_fields;
                $value = $extractedFields[$field['name']] ?? '';
                $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue($colLetter . $row, $value);
                $colIndex++;
            }
            $row++;
        }
        
        for ($i = 1; $i < $colIndex; $i++) {
            $colLetter = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'export_' . str_replace(' ', '_', $template->kategori) . '_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);
        
        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    private function exportCsv($documents, $template)
    {
        $fields = $template->fields;
        $headers = array_merge(['Nama File', 'Status', 'Confidence Score', 'Tanggal'], array_column($fields, 'name'));
        
        $filename = 'export_' . str_replace(' ', '_', $template->kategori) . '_' . date('YmdHis') . '.csv';
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $file = fopen($tempFile, 'w');
        
        fputcsv($file, $headers);
        
        foreach ($documents as $doc) {
            $row = [
                $doc->nama_file,
                $doc->status,
                $doc->confidence_score,
                $doc->created_at->format('Y-m-d H:i:s'),
            ];
            
            $extractedFields = is_string($doc->extracted_fields) 
                ? json_decode($doc->extracted_fields, true) 
                : $doc->extracted_fields;
            
            foreach ($fields as $field) {
                $row[] = $extractedFields[$field['name']] ?? '';
            }
            
            fputcsv($file, $row);
        }
        
        fclose($file);
        
        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    public function getDocumentCount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'kategori' => 'nullable|integer|exists:ocr_templates,id',
        ]);

        $query = DataOcr::whereBetween('created_at', [$validated['start_date'], $validated['end_date']]);
        
        if (isset($validated['kategori'])) {
            $query->where('ocr_template_id', $validated['kategori']);
        }

        $count = $query->count();

        return response()->json(['count' => $count]);
    }
}
