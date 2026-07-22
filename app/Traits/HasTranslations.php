<?php

namespace App\Traits;

use Illuminate\Support\Facades\App;

trait HasTranslations
{
    /**
     * Define the relationship for translations.
     * The model using this trait must define a translatableFields() array,
     * or we can just infer it if the translation model is dynamically resolved.
     */
    public function translations()
    {
        return $this->hasMany($this->getTranslationModelName());
    }

    /**
     * Get the class name of the translation model.
     */
    protected function getTranslationModelName()
    {
        return static::class . 'Translation';
    }

    /**
     * Magic method to intercept attribute access and return translated values if available.
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);
        $locale = App::getLocale();
        $fallbackLocale = config('app.fallback_locale');

        // We only try to translate if the locale is not the fallback locale
        if ($locale === $fallbackLocale) {
            return $value;
        }

        // Check if this attribute is translatable
        if (!property_exists($this, 'translatable') || !in_array($key, $this->translatable)) {
            return $value;
        }

        // Try to find the translation
        // Assuming translations relationship is loaded or we load it
        $translation = $this->translations->where('locale', $locale)->first();

        if ($translation && $translation->getAttribute($key) !== null) {
            return $translation->getAttribute($key);
        }

        return $value;
    }
}
