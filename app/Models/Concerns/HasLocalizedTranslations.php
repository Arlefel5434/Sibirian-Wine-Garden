<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

trait HasLocalizedTranslations
{
    public function translation(?string $locale = null): ?Model
    {
        $locale ??= App::currentLocale();
        $fallback = config('localization.fallback');
        $translations = $this->relationLoaded('translations')
            ? $this->translations
            : $this->translations()->whereIn('locale', array_unique([$locale, $fallback]))->get();

        return $translations->firstWhere('locale', $locale)
            ?? $translations->firstWhere('locale', $fallback);
    }

    public function localized(string $field, mixed $fallback = null, ?string $locale = null): mixed
    {
        return $this->translation($locale)?->getAttribute($field)
            ?? $fallback
            ?? $this->getAttribute($field);
    }
}
