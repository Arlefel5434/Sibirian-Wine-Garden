<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkuTranslation extends Model
{
    protected $fillable = ['locale', 'name'];

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }
}
