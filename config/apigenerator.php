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
    //   'name' => 'Blog', // The name of your module in StudlyCase(PascalCase), for namespacing
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
    //       'title' => [ 'type' => 'string', 'rules' => 'bail|required|string|max:255', 'fillable' => true, 'hidden' => false ],
    //       'content' => [ 'type' => 'string', 'rules' => 'bail|required|string', 'fillable' => true, 'hidden' => false ],
    //       'is_based' => [ 'type' => 'boolean', 'rules' => 'bail|nullable|boolean', 'fillable' => true, 'hidden' => false ],
    //     ],
    //     'relations' => [ // Relation types: oneToOne, oneToMany, manyToOne, manyToMany
    //       'tag' => [ 'type' => 'oneToOne', 'class' => 'App\Models\Blog\Tag', 'morph' => false, 'with' => true, 'inverse' => true, 'foreign_key' => 'tag_id' ],
    //       'image' => [ 'type' => 'oneToOne', 'class' => 'App\Models\Blog\Image', 'morph' => true, 'with' => true, 'inverse' => true, 'foreign_key' => 'imageable' ],
    //       'user' => [ 'type' => 'manyToOne', 'class' => 'App\Models\User\User', 'morph' => false, 'with' => true, 'inverse' => true, 'foreign_key' => 'user_id' ],
    //       'likes' => [ 'type' => 'manyToOne', 'class' => 'App\Models\Blog\Like', 'morph' => true, 'with' => true, 'inverse' => true, 'foreign_key' => 'likeable' ],
    //       'comments' => [ 'type' => 'oneToMany', 'class' => 'App\Models\Blog\Comment', 'morph' => false, 'with' => true ],
    //       'categories' => [ 'type' => 'manyToMany', 'class' => 'App\Models\Blog\Category', 'morph' => false, 'with' => true, 'table_name' => 'article_category', 'foreign_key_1' => 'article_id', 'foreign_key_2' => 'category_id'  ],
    //       'tags' => [ 'type' => 'manyToMany', 'class' => 'App\Models\Blog\Tag', 'morph' => true, 'with' => true, 'table_name' => 'taggables', 'foreign_key_1' => 'tag_id', 'foreign_key_2' => 'taggable'  ],
    //     ]
    //   ]
    // ]
  ],

    // App dependencies
    "dependencies" => [
      'algolia/algoliasearch-client-php' => '^2.8',
      'fruitcake/laravel-cors' => '^2.0',
      'guzzlehttp/guzzle' => '^7.0.1',
      'laravel/sanctum' => '^2.8',
      'laravel/scout' => '^8.6',
      'spatie/laravel-activitylog' => '^3.16',
      'spatie/laravel-medialibrary' => '^9.4',
      'spatie/laravel-query-builder' => '^3.3'
  ]
];