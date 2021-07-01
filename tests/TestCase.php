<?php

/*
 * This file is part of the snippetify package.
 *
 * (c) Evens Pierre <evenspierre@snippetify.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snippetify\ApiGenerator\Tests;

use Snippetify\ApiGenerator\ApiGeneratorServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
  protected function getPackageProviders($app)
  {
    return [
      ApiGeneratorServiceProvider::class,
    ];
  }
}
