<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedTranslations;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasLocalizedTranslations;

    protected $fillable = [
        'user_id', 'title', 'slug', 'preview_img', 'preview_text', 'status', 'body',
    ];

    protected $casts = [
        'body' => 'array',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PostTranslation::class);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if (($field ?? $this->getRouteKeyName()) !== 'slug') {
            return parent::resolveRouteBinding($value, $field);
        }

        return $this->where('slug', $value)
            ->orWhereHas('translations', fn ($query) => $query
                ->where('locale', app()->getLocale())
                ->where('slug', $value))
            ->first();
    }
}
