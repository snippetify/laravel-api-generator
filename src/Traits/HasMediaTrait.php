<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;


trait HasMediaTrait
{
    use InteractsWithMedia {
        InteractsWithMedia::registerMediaCollections as parentRegisterMediaCollections;
    }

    public static function bootHasMediaTrait()
    {
        static::saved(function (Model $model) {
            if (request()->hasFile('file')) {
                $model->addMediaFromRequest('file')->toMediaCollection('file');
            }
            if (request()->hasFile('image')) {
                $model->addMediaFromRequest('image')->toMediaCollection('image');
            }
        });

        static::deleting(function (Model $model) {
            optional($model->getFirstMedia('file'))->delete();
            optional($model->getFirstMedia('image'))->delete();
        });
    }
    
    /**
     * Spatie register media collections.
     *
     * @return void
     */
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('image')
            ->singleFile();
        
        $this
            ->addMediaCollection('file')
            ->singleFile();
    }

    /**
     * Get image attribute
     */
    public function getImagePathAttribute()
    {
        return $this->getFirstMediaUrl('image');
    }

    /**
     * Get file attribute
     */
    public function getFilePathAttribute()
    {
        return $this->getFirstMediaUrl('file');
    }
}