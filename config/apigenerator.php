<?php

/*
 * This file is part of the snippetify package.
 *
 * (c) Evens Pierre <evenspierre@snippetify.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

  /*
    |-----------------------------------------------------------------------
    | Snippetify ApiGenerator Configuration
    |-----------------------------------------------------------------------
    |
    | A collection of default options to apply to snippet ApiGenerator objects
    |
    | @see {@link https =>//github.com/snippetify/laravel-api-generator}
    */

  // Define the skeleton of your project here in the module array
  // Or create a 'snippetify-ams.json' file in the root of your project
  'modules' => [
    // [
    //   'name' => 'Article', // The name of your module in StudlyCase(PascalCase), for namespacing
    //   'model' => [
    //     'name' => 'Article', // The name of your model in StudlyCase(PascalCase)
    //     'hasUser' => true, // Is owned by a user,
    //     'hasLog' => true, // Allow model to be loggable,
    //     'softDelete' => true, // Add laravel soft delete feature to model
    //     'isSearchable' => true, // Allow this model to be fulltext searchable using laravel scout library
    //     'hasMedia' => true, // Attach a media to this library using the Spatie Media library
    //     'hasSlug' => true, // Add a slug to this model
    //     'issers' => [ 'published', 'activated' ], // Issers like isPublished, isActivated, etc...
    //     'attributes' => [
    //       'name' => [ 'type' => 'string', 'rules' => '', 'fillable' => true, 'hidden' => false ],
    //     ],
    //     'relations' => [ // Relation types: oneToOne, oneToMany, manyToOne, manyToMany
    //       'comments' => [ 'type' => 'oneToOne', 'class' => 'App\Models\Blog\Comment', 'morph' => false, 'with' => true, 'inverse' => true ],
    //       'categories' => [ 'type' => 'oneToMany', 'class' => 'App\Models\Blog\Category', 'morph' => false, 'with' => true ],
    //     ]
    //   ]
    // ]
  ]
];