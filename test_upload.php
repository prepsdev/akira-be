<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // Test if Job class exists
    echo "Testing Job class...\n";
    $jobClass = \App\Jobs\ProcessOcrDocument::class;
    echo "Job class exists: " . $jobClass . "\n";
    
    // Test if we can create a job instance
    echo "\nTesting job instantiation...\n";
    $job = new \App\Jobs\ProcessOcrDocument(1, 'test.pdf', 1);
    echo "Job created successfully!\n";
    
    // Test DataOcr model
    echo "\nTesting DataOcr model...\n";
    $dataOcr = new \App\Models\DataOcr();
    echo "DataOcr fillable: " . implode(', ', $dataOcr->getFillable()) . "\n";
    
    echo "\n✅ All tests passed!\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
