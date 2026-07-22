<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Checking OCR Template Fields...\n\n";
    
    $template = \App\Models\OcrTemplate::first();
    
    if (!$template) {
        echo "No templates found in database.\n";
        exit(0);
    }
    
    echo "Template ID: " . $template->id . "\n";
    echo "Kategori: " . $template->kategori . "\n";
    echo "Fields:\n";
    echo json_encode($template->fields, JSON_PRETTY_PRINT) . "\n\n";
    
    // Check if description exists in fields
    $hasDescription = false;
    foreach ($template->fields as $field) {
        if (isset($field['description'])) {
            $hasDescription = true;
            break;
        }
    }
    
    if ($hasDescription) {
        echo "✅ Description field exists in template!\n";
    } else {
        echo "❌ Description field NOT found in template!\n";
        echo "   Fields only have: " . implode(', ', array_keys($template->fields[0] ?? [])) . "\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
