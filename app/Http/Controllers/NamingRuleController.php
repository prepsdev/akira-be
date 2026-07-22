<?php

namespace App\Http\Controllers;

use App\Models\NamingRule;
use App\Models\OcrTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NamingRuleController extends Controller
{
    public function index(): JsonResponse
    {
        $rules = NamingRule::with('ocrTemplate')->get();
        return response()->json($rules);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ocr_template_id' => 'required|exists:ocr_templates,id',
            'naming_pattern' => 'required|string',
        ]);

        $rule = NamingRule::create($validated);

        return response()->json($rule->load('ocrTemplate'), 201);
    }

    public function show(string $id): JsonResponse
    {
        $rule = NamingRule::with('ocrTemplate')->findOrFail($id);
        return response()->json($rule);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $rule = NamingRule::findOrFail($id);

        $validated = $request->validate([
            'ocr_template_id' => 'required|exists:ocr_templates,id',
            'naming_pattern' => 'required|string',
        ]);

        $rule->update($validated);

        return response()->json($rule->load('ocrTemplate'));
    }

    public function destroy(string $id): JsonResponse
    {
        $rule = NamingRule::findOrFail($id);
        $rule->delete();

        return response()->json(['message' => 'Naming rule deleted successfully']);
    }

    public function getByTemplate(string $templateId): JsonResponse
    {
        $rule = NamingRule::where('ocr_template_id', $templateId)->first();
        return response()->json($rule);
    }
}
