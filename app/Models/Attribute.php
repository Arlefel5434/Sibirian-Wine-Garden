<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use HasLocalizedTranslations;

    protected $fillable = ['name', 'type', 'unit'];

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(AttributeTranslation::class);
    }
}
