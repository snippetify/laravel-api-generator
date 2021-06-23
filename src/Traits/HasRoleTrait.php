<?php

namespace App\Traits;

use App\Enums\Role;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

trait HasRoleTrait
{
	public function getIsSuperAdminAttribute()
	{
		return $this->hasRole(Role::ROLE_SUPER_ADMIN);
	}

	public function getIsAdminAttribute()
	{
		return $this->hasRole(Role::ROLE_ADMIN) || $this->isSuperAdmin;
	}

	public function getIsAgentAttribute()
	{
		return $this->hasRole(Role::ROLE_AGENT) || $this->isAdmin;
	}

	public function getIsClientAttribute()
	{
		return $this->hasRole(Role::ROLE_CLIENT) || $this->isAdmin;
	}

	public function getIsWarrantorAttribute()
	{
		return $this->hasRole(Role::ROLE_WARRANTOR) || $this->isAdmin;
	}

	public function getRoleNameAttribute()
	{
		return (string) Str::of(collect($this->roles ?? [])->first())->afterLast('_')->lower();
	}

	public function hasRole(string $value): bool
	{
		if (empty($this->roles)) return false;
		
		$collect = collect($this->roles);

		return $collect->contains($value) || $collect->contains(Role::ROLE_SUPER_ADMIN);
	}

	public function hasRoles(array $values): bool
	{
		if (empty($this->roles)) return false;

		return collect($values)->filter(function ($value) {
			return $this->hasRole($value);
		})->isNotEmpty();
	}

	public function addRole(string $value)
	{
		if ($this->hasRole($value)) return $this;

		$this->roles = collect($this->roles ?? [])->push($value)->all();

		return $this;
	}

	public function addRoles(array $values)
	{
		collect($values)->each(function ($value) {
			$this->addRole($value);
		});

		return $this;
	}


	/**
	 * Get Roles.
	 */
	public function roles()
	{
			return $this->belongsToMany('App\Models\Setting\Role')->withTimestamps();
	}


	/**
	 * WhereSuperAdmin scope
	 */
	public function scopeWhereSuperAdmin(Builder $builder)
	{
		return $builder->where('roles', 'like', "%".Role::ROLE_SUPER_ADMIN."%");
	}

	/**
	 * WhereAdmin scope
	 */
	public function scopeWhereAdmin(Builder $builder)
	{
		return $builder->where('roles', 'like', "%".Role::ROLE_ADMIN."%");
	}

	/**
	 * WhereSimpleUser scope
	 */
	public function scopeWhereSimpleUser(Builder $builder)
	{
		return $builder->where('roles', 'not like', "%".Role::ROLE_ADMIN."%")->where('roles', 'not like', "%".Role::ROLE_SUPER_ADMIN."%");
	}

	/**
	 * WhereAgent scope
	 */
	public function scopeWhereAgent(Builder $builder)
	{
		return $builder->where('roles', 'like', "%".Role::ROLE_AGENT."%");
	}

	/**
	 * WhereWarrantor scope
	 */
	public function scopeWhereWarrantor(Builder $builder)
	{
		return $builder->where('roles', 'like', "%".Role::ROLE_WARRANTOR."%");
	}

	/**
	 * WhereClient scope
	 */
	public function scopeWhereClient(Builder $builder)
	{
		return $builder->where('roles', 'like', "%".Role::ROLE_CLIENT."%");
	}
}