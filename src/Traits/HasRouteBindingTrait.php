<?php

namespace App\Traits;

/**
 * Laravel Eloquent sluggable field.
 *
 * @license MIT
 * @author Evens Pierre <evenspierre@snippetify.com>
 */
trait HasRouteBindingTrait
{
    /**
     * Resolve route binding
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this
            ->where(is_numeric($value) ? 'id' : 'slug', $value)
            ->withTrashed()
            ->firstOrFail();
    }
}