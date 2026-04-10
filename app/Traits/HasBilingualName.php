<?php

namespace App\Traits;

trait HasBilingualName
{
    /**
     * Get the name attribute based on current locale
     */
    public function getNameAttribute($value)
    {
        $locale = app()->getLocale();

        if ($locale === 'ar' && !empty($this->attributes['name_ar'])) {
            return $this->attributes['name_ar'];
        }

        if ($locale === 'en' && !empty($this->attributes['name_en'])) {
            return $this->attributes['name_en'];
        }

        // Fallback: return whichever is available
        return $this->attributes['name_ar'] ?? $this->attributes['name_en'] ?? $value;
    }

    /**
     * Get display name (fallback logic)
     */
    public function getDisplayNameAttribute()
    {
        $locale = app()->getLocale();

        if ($locale === 'ar') {
            return $this->name_ar ?? $this->name_en;
        }

        return $this->name_en ?? $this->name_ar;
    }
}
