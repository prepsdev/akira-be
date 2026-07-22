<?php

namespace App\Http\Controllers;

use App\Models\DataOcr;
use App\Models\GlobalSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FieldSuggestionController extends Controller
{
    public function getSuggestions(Request $request): JsonResponse
    {
        if (!GlobalSetting::isEnabled('field_suggestions')) {
            return response()->json([
                'success' => false,
                'message' => 'Field suggestions feature is disabled',
                'suggestions' => [],
                'total_approved' => 0
            ]);
        }
        
        $validated = $request->validate([
            'field_name' => 'required|string',
            'current_value' => 'required|string',
            'template_id' => 'required|integer',
        ]);

        $fieldName = $validated['field_name'];
        $currentValue = strtolower($validated['current_value']);
        $templateId = $validated['template_id'];

        $approvedDocuments = DataOcr::where('ocr_template_id', $templateId)
            ->where('status', 'Approved')
            ->get();

        $suggestions = [];
        $seenValues = [];

        foreach ($approvedDocuments as $doc) {
            $extractedFields = $doc->extracted_fields;
            
            if (isset($extractedFields[$fieldName])) {
                $value = $extractedFields[$fieldName];
                $valueLower = strtolower($value);
                
                if (empty($value) || isset($seenValues[$valueLower])) {
                    continue;
                }
                
                $similarity = $this->calculateSimilarity($currentValue, $valueLower);
                
                if ($similarity > 0.3) {
                    $suggestions[] = [
                        'value' => $value,
                        'similarity' => round($similarity * 100, 2),
                        'source_id' => $doc->id,
                        'source_file' => $doc->nama_file
                    ];
                    $seenValues[$valueLower] = true;
                }
            }
        }

        usort($suggestions, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        $suggestions = array_slice($suggestions, 0, 5);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
            'total_approved' => count($approvedDocuments)
        ]);
    }

    private function calculateSimilarity(string $str1, string $str2): float
    {
        if ($str1 === $str2) {
            return 1.0;
        }

        $str1 = preg_replace('/[^a-z0-9\s]/', '', $str1);
        $str2 = preg_replace('/[^a-z0-9\s]/', '', $str2);

        similar_text($str1, $str2, $percent);
        $similarityScore = $percent / 100;

        $levenshtein = levenshtein(substr($str1, 0, 255), substr($str2, 0, 255));
        $maxLen = max(strlen($str1), strlen($str2));
        $levenshteinScore = $maxLen > 0 ? 1 - ($levenshtein / $maxLen) : 0;

        return ($similarityScore + $levenshteinScore) / 2;
    }
}
