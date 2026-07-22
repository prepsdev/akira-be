<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OcrTemplate extends Model
{
    protected $fillable = [
        'kategori',
        'description',
        'fields',
    ];

    protected $casts = [
        'fields' => 'array',
    ];

    public function namingRule(): HasOne
    {
        return $this->hasOne(NamingRule::class);
    }

    public function dataOcr(): HasMany
    {
        return $this->hasMany(DataOcr::class);
    }
}
