<?php

namespace App\Http\Controllers;

use App\Models\DataOcr;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DataOcrController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DataOcr::with('ocrTemplate');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('ocr_template_id')) {
            $query->where('ocr_template_id', $request->ocr_template_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $request->start_date . ' 00:00:00';
            $endDate = $request->end_date . ' 23:59:59';
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $data = $query->orderBy('created_at', 'desc')->get();
        return response()->json($data);
    }

    public function show(string $id): JsonResponse
    {
        $data = DataOcr::with('ocrTemplate')->findOrFail($id);
        return response()->json($data);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = DataOcr::findOrFail($id);

        $validated = $request->validate([
            'extracted_fields' => 'required|array',
        ]);

        $data->update($validated);

        return response()->json($data->load('ocrTemplate'));
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $data = DataOcr::findOrFail($id);

        $validated = $request->validate([
            'extracted_fields' => 'required|array',
            'corrected_content' => 'nullable|string',
        ]);

        $data->update([
            'extracted_fields' => $validated['extracted_fields'],
            'corrected_content' => $validated['corrected_content'] ?? $data->content,
            'status' => 'Approved',
        ]);

        activity()
            ->performedOn($data)
            ->causedBy(Auth::user())
            ->withProperties([
                'document_id' => $data->id,
                'document_name' => $data->nama_file,
                'category' => $data->ocrTemplate->kategori ?? 'Unknown',
                'action' => 'approved'
            ])
            ->log('Document approved');

        return response()->json([
            'message' => 'Data approved successfully',
            'data' => $data->load('ocrTemplate')
        ]);
    }

    public function reject(string $id): JsonResponse
    {
        $data = DataOcr::findOrFail($id);

        $data->update([
            'status' => 'Rejected',
        ]);

        activity()
            ->performedOn($data)
            ->causedBy(Auth::user())
            ->withProperties([
                'document_id' => $data->id,
                'document_name' => $data->nama_file,
                'category' => $data->ocrTemplate->kategori ?? 'Unknown',
                'action' => 'rejected'
            ])
            ->log('Document rejected');

        return response()->json([
            'message' => 'Data rejected successfully',
            'data' => $data->load('ocrTemplate')
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $data = DataOcr::findOrFail($id);
        $data->delete();

        return response()->json(['message' => 'Data deleted successfully']);
    }
}
