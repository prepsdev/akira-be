<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NamingRule extends Model
{
    protected $fillable = [
        'ocr_template_id',
        'naming_pattern',
    ];

    public function ocrTemplate(): BelongsTo
    {
        return $this->belongsTo(OcrTemplate::class);
    }
}
