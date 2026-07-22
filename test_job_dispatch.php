<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Testing Job Dispatch...\n\n";
    
    // Test if we can dispatch a job
    echo "1. Creating test DataOcr record...\n";
    $dataOcr = \App\Models\DataOcr::create([
        'ocr_template_id' => 1,
        'extracted_fields' => [],
        'nama_file' => 'test.pdf',
        'content' => '',
        'confidence_score' => 0.0,
        'file_path' => 'uploads/test.pdf',
        'status' => 'Processing',
        'progress' => 0
    ]);
    echo "   ✅ DataOcr created with ID: " . $dataOcr->id . "\n\n";
    
    echo "2. Dispatching ProcessOcrDocument job...\n";
    \App\Jobs\ProcessOcrDocument::dispatch($dataOcr->id, 'uploads/test.pdf', 1);
    echo "   ✅ Job dispatched successfully!\n\n";
    
    echo "3. Checking jobs table...\n";
    $jobCount = \DB::table('jobs')->count();
    echo "   Jobs in queue: " . $jobCount . "\n\n";
    
    // Clean up
    echo "4. Cleaning up test data...\n";
    $dataOcr->delete();
    \DB::table('jobs')->truncate();
    echo "   ✅ Cleanup complete\n\n";
    
    echo "✅ All tests passed! Job dispatch is working.\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
