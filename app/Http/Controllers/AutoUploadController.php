<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class AutoUploadController extends Controller
{
    public function triggerScan(): JsonResponse
    {
        try {
            $pythonScript = base_path('../DocAI/auto_upload.py');

            if (!file_exists($pythonScript)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Auto-upload script not found'
                ], 404);
            }

            $process = new Process(['python', $pythonScript]);
            $process->setTimeout(600);
            $process->start();

            $process->wait();

            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();

            if ($process->isSuccessful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Directory scan completed successfully',
                    'output' => $output,
                    'errors' => $errorOutput
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Scan failed',
                    'output' => $output,
                    'errors' => $errorOutput
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error executing scan: ' . $e->getMessage()
            ], 500);
        }
    }
}
