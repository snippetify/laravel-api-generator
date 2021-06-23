<?php

namespace App\Traits;

trait CreateInstanceTrait
{
    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
    	parent::__construct($attributes);

    	$this->dispatchesEvents = [
	        'created' => \App\Events\Model\ModelCreated::class,
	        'updated' => \App\Events\Model\ModelUpdated::class,
	        'deleted' => \App\Events\Model\ModelDeleted::class,
	        'restored' => \App\Events\Model\ModelRestored::class,
	        'retrieved' => \App\Events\Model\ModelRetrieved::class,
	    ];
    }
}