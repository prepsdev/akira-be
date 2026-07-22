<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataOcr extends Model
{
    protected $table = 'data_ocr';

    protected $fillable = [
        'ocr_template_id',
        'extracted_fields',
        'nama_file',
        'content',
        'corrected_content',
        'confidence_score',
        'file_path',
        'progress',
        'status',
    ];

    protected $casts = [
        'extracted_fields' => 'array',
        'confidence_score' => 'decimal:2',
    ];

    public function ocrTemplate(): BelongsTo
    {
        return $this->belongsTo(OcrTemplate::class);
    }
}
