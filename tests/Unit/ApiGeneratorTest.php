<?php

/*
 * This file is part of the snippetify package.
 *
 * (c) Evens Pierre <evenspierre@snippetify.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snippetify\ApiGenerator\Tests\Unit;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Snippetify\ApiGenerator\Tests\TestCase;

class ApiGeneratorTest extends TestCase
{
  /** setup */
  protected function setUp(): void
  {
    parent::setUp();
    
    // Import config
    if (Storage::missing(config_path('apigenerator.php'))) {
      copy(__DIR__.'/../../config/apigenerator_test.php', config_path('apigenerator.php'));
      // Execute command
      Artisan::call('snippetify:make:api');
    }

  }

  /** @afterClass */
  public static function tearDownAfterAll(): void
  {}

  /** @test */
  function the_model_generated()
  {
    $this->assertTrue(File::exists(app_path('Models/Blog/Article.php')));
  }

  /** @test */
  function the_policy_generated()
  {
    $this->assertTrue(File::exists(app_path('Policies/Blog/ArticlePolicy.php')));
  }

  /** @test */
  function the_form_request_generated()
  {
    $this->assertTrue(File::exists(app_path('Http/Requests/Blog/ArticleRequest.php')));
  }

  /** @test */
  function the_controller_generated()
  {
    $this->assertTrue(File::exists(app_path('Http/Controllers/Blog/ArticleController.php')));
  }

  /** @test */
  function the_resource_generated()
  {
    $this->assertTrue(File::exists(app_path('Http/Resources/Blog/Article.php')));
  }

  /** @test */
  function the_factory_generated()
  {
    $this->assertTrue(File::exists(database_path('factories/Blog/ArticleFactory.php')));
  }

  /** @test */
  function the_seeder_generated()
  {
    $this->assertTrue(File::exists(database_path('seeders/Blog/ArticleSeeder.php')));
  }

  /** @test */
  function the_test_generated()
  {
    $this->assertTrue(File::exists(base_path('tests/Feature/Blog/ArticleTest.php')));
  }

  /** @test */
  function the_test_data_generated()
  {
    $this->assertTrue(File::exists(base_path('tests/Feature/Data/Blog/ArticleTestDataTrait.php')));
  }
}