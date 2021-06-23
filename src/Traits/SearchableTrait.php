<?php

namespace App\Traits;

use Laravel\Scout\Searchable;

trait SearchableTrait
{
    use Searchable {
        Searchable::shouldBeSearchable as parentShouldBeSearchable;
    }

    public function shouldBeSearchable(): bool
    {
        return isset($this->attributes['published_at']) ? ($this->isPublished || optional($this->user)->isAdmin) : true;
    }
}