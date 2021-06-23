<?php

namespace App\Traits;

use App\Enums\Feature;
use App\Enums\Privilege;
use Illuminate\Database\Eloquent\Model;

trait HasPrivilegeTrait
{

	/**
	 * Queued privileges
	 *
	 * @var array
	 */
	protected $queuedPrivileges = [];

	/**
	 * The "booting" method of the model.
	 *
	 * @return void
	 */
	protected static function bootHasPrivilegeTrait()
	{
			static::created(function (Model $model) {
					if (count($model->queuedPrivileges) > 0) {
							$model->privileges()->attach($model->queuedPrivileges);
							$model->queuedPrivileges = [];
					}
			});
	}

	/**
	 * Set privileges attribute
	 */
	public function setPrivilegesAttribute(array $values)
	{
			if (!$this->exists) {
					$this->queuedPrivileges = $values;
					return;
			}

			$this->privileges()->sync($values);
	}

	public function hasPrivilege($feature, string $privilege): bool
	{
		if ($this->privileges->isEmpty()) return false;

		return $this->privileges->filter(function ($priv) use ($feature, $privilege) {
			$feat = collect($priv->features);
			$privs = collect($priv->privileges);
			return ($feat->contains($feature) && $privs->contains($privilege)) || 
				($feat->contains($feature) && $privs->contains(Privilege::ANY)) || 
				($feat->contains(Feature::ANY) && $privs->contains($privilege)) || 
				($feat->contains(Feature::ANY) && $privs->contains(Privilege::ANY)) ||
				$this->isSuperAdmin;
			;
		})->isNotEmpty();
	}

	public function hasPrivileges(array $values): bool
	{
		if ($this->privileges->isEmpty()) return false;

		return collect($values)->filter(function ($value, $key) {
			if (is_array($value)) {
				return collect($value)->filter(function ($val) use ($key) {
					return $this->hasPrivilege($key, $val);
				})->isNotEmpty();
			}
			return $this->hasPrivilege($key, $value);
		})->isNotEmpty();
	}
}