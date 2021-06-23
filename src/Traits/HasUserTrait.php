<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

use App\Models\User\User;

trait HasUserTrait
{
    public static function bootHasUserTrait()
    {
        static::creating(function (Model $model) {
            $model->user_id = $model->user_id ?? optional(auth()->user())->id;
        });
    }


    /**
     * Set user attribute
     */
    public function setUserAttribute($value)
    {
        $this->user()->associate($value);
    }

    /**
     * Get isOwner
     */
    public function getIsOwnerAttribute(): bool
    {
        return $this->isOwner();
    }


    /**
     * Get User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * User scope
     */
    public function scopeWhereUser(Builder $builder, $id)
    {
        return $builder->where('user_id', $id);
    }

    /**
     * Is owner
     */
    public function isOwner(?User $user = null): bool
    {
        return optional($this->user)->id === optional($user ?? auth('api')->user())->id;
    }

    /**
     * Get deletedBy
     */
    public function getDeletedByAttribute(): ?User
    {
        $revision = $this->revisions()->where('action', 'deleted')->first();
        return optional($revision)->causer;
    }
}