<?php

namespace App\Http\Controllers;

use App\Models\EmailSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailScheduleController extends Controller
{
    public function index(): JsonResponse
    {
        $schedules = EmailSchedule::all()->map(function ($schedule) {
            $schedule->ocr_templates = $schedule->ocrTemplates();
            return $schedule;
        });

        return response()->json($schedules);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'frequency' => 'required|in:daily,weekly,monthly',
            'ocr_template_ids' => 'nullable|array',
            'ocr_template_ids.*' => 'integer|exists:ocr_templates,id',
            'export_format' => 'required|in:xlsx,csv',
        ]);

        $schedule = EmailSchedule::create($validated);
        $schedule->ocr_templates = $schedule->ocrTemplates();

        return response()->json([
            'success' => true,
            'message' => 'Email schedule created successfully',
            'schedule' => $schedule
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $schedule = EmailSchedule::findOrFail($id);

        $validated = $request->validate([
            'email' => 'sometimes|email',
            'frequency' => 'sometimes|in:daily,weekly,monthly',
            'ocr_template_ids' => 'nullable|array',
            'ocr_template_ids.*' => 'integer|exists:ocr_templates,id',
            'export_format' => 'sometimes|in:xlsx,csv',
            'is_active' => 'sometimes|boolean',
        ]);

        $schedule->update($validated);
        $schedule->ocr_templates = $schedule->ocrTemplates();

        return response()->json([
            'success' => true,
            'message' => 'Email schedule updated successfully',
            'schedule' => $schedule
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $schedule = EmailSchedule::findOrFail($id);
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Email schedule deleted successfully'
        ]);
    }

    public function toggle($id): JsonResponse
    {
        $schedule = EmailSchedule::findOrFail($id);
        $schedule->update(['is_active' => !$schedule->is_active]);
        $schedule->load('ocrTemplate');

        return response()->json([
            'success' => true,
            'message' => 'Email schedule toggled successfully',
            'schedule' => $schedule
        ]);
    }
}
