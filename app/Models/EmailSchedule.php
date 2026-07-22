<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSchedule extends Model
{
    protected $fillable = [
        'email',
        'frequency',
        'ocr_template_ids',
        'export_format',
        'is_active',
        'last_sent_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_sent_at' => 'datetime',
        'ocr_template_ids' => 'array',
    ];

    public function ocrTemplates()
    {
        if (empty($this->ocr_template_ids)) {
            return collect([]);
        }

        return OcrTemplate::whereIn('id', $this->ocr_template_ids)->get();
    }
}
