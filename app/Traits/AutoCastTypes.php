<?php

namespace App\Traits;

trait AutoCastTypes
{
    /**
     * Override getCasts to automatically ensure common numeric fields are casted correctly.
     * This helps when the DB driver returns everything as strings on some production environments.
     */
    public function getCasts(): array
    {
        $casts = parent::getCasts();

        // Always cast primary 'id' to integer
        if (!isset($casts['id'])) {
            $casts['id'] = 'integer';
        }

        // Automatically cast any field ending in '_id' to integer if not explicitly casted
        foreach ($this->attributes as $key => $value) {
            if (str_ends_with($key, '_id') && !isset($casts[$key])) {
                $casts[$key] = 'integer';
            }
        }

        return $casts;
    }
}
