<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

/**
 * Laravel Eloquent sluggable field.
 *
 * @license MIT
 * @author Evens Pierre <evenspierre@snippetify.com>
 */
trait SluggableTrait
{
    use HasRouteBindingTrait;
    
    /**
     * Sluggable any field.
     *
     * Use laravel static boot method to listen to model's saving event
     * And sluggify the given field
     *
     * @throws \InvalidArgumentException When getSlugField is empty
     * @throws \InvalidArgumentException When getContentToSlug is empty
     * @return void
     */
    public static function bootSluggableTrait()
    {
        static::saving(function (Model $model) {
            if (is_null($model->getSlugField())) {
                throw new \InvalidArgumentException('The getSlugField method must return a value');
            }
            if (is_null($model->getContentToSlug())) {
                throw new \InvalidArgumentException('The getContentToSlug method must return a value');
            }
            $slug = Str::slug($model->getContentToSlug());
            $count = self::where($model->getSlugField(), 'like', "{$slug}%") // Check other models
                ->where('id', '!=', (int) $model->id)
                ->withTrashed()
                ->withoutGlobalScopes()
                ->count();
            // Check current model
            $count = self::where($model->getSlugField(), $slug)->where('id', (int) $model->id)->exists() ? 0 : $count;
            $model->{$model->getSlugField()} = $count < 1 ? $slug : "{$slug}-{$count}";
        });
    }

    /**
     * Get the slug field.
     *
     * Return the field where the slug result will saved
     *
     * @return string
     */
    public function getSlugField()
    {
        return 'slug';
    }

    /**
     * Get the slug field.
     *
     * Get the content to slug
     * NB: You must override this method in your model to return the field you want to sluggify
     *
     * @return string
     */
    public function getContentToSlug()
    {
        return $this->title ?? $this->name;
    }
}