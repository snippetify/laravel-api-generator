<?php

namespace Snippetify\ApiGenerator\Commands;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ApiGeneratorGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "snippetify:make:api {--f|force : For overriding}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate API';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Test if file exists
        // SAMS (Snippetify Api Model Skeleton)
        if (Storage::disk('base')->missing('snippetify-ams.json')) {
            $this->error("The 'snippetify-ams.json' is missing");
            return;
        }

        $config = collect(json_decode(Storage::disk('base')->get('snippetify-ams.json'), true));

        collect($config->get('modules', []))
            ->each(function ($item) {

                $module = Arr::get($item, 'name');
                $model  = Arr::get($item, 'model.name');
                
                // Make controller
                $this->makeController($module, $model, $item);
                
                // Make policy
                $this->makePolicy($module, $model, $item);
                
                // Make form request
                $this->makeFormRequest($module, $model, $item);
                
                // Make model
                $this->makeModel($module, $model, $item);

                // Make Migration
                $this->makeMigration($module, $model, $item);
                
                // Make resource
                $this->makeResource($module, $model, $item);
                
                // Make factory
                $this->makeFactory($module, $model, $item);

                // Make seeder
                $this->makeSeeder($module, $model, $item);
                
                // Make test
                $this->makeTest($module, $model, $item);
                
                // Make data test
                $this->makeTestData($module, $model, $item);
                
                // Make route
                $this->makeRoute($module, $model);
            });

        $this->info("Api items generated");

        return 0;
    }

    /**
     * Make model
    */
    private function makeModel($module, $model, $item)
    {
        $this->makeFile(
            $module, 
            $model,
            'stubs/DummyModel.stub', 
            "app/Models/{$module}/{$model}.php",
            function ($value) use ($item) {
                $use       = '';
                $import    = '';
                $implement = '';
                if (Arr::get($item, 'model.hasPublished')) {
                    $use    .= 'use IsPublishedTrait;';
                    $import .= 'use App\Traits\IsPublishedTrait;';
                }
                if (Arr::get($item, 'model.isSearchable')) {
                    $use    .= 'use SearchableTrait;';
                    $import .= 'use App\Traits\SearchableTrait;';
                }
                if (Arr::get($item, 'model.hasSlug')) {
                    $use    .= 'use SluggableTrait;';
                    $import .= 'use App\Traits\SluggableTrait;';
                } else {
                    $use    .= 'use HasRouteBindingTrait;';
                    $import .= 'use App\Traits\HasRouteBindingTrait;';
                }
                if (Arr::get($item, 'model.hasMedia')) {
                    $use    .= 'use HasMediaTrait;';
                    $implement = 'implements HasMedia';
                    $import .= 'use App\Traits\HasMediaTrait;';
                }
                return Str::of($value)
                    ->replace("%%usetraits%%", $use)
                    ->replace("%%importtraits%%", $import)
                    ->replace("%%implementsHasMedia%%", $implement);
            }
        );
    }

    /**
     * Make policy
    */
    private function makePolicy($module, $model, $item)
    {
        $this->makeFile(
            $module, 
            $model,
            'stubs/DummyPolicy.stub', 
            "app/Policies/{$module}/{$model}Policy.php"
        );
    }

    /**
     * Make form request
    */
    private function makeFormRequest($module, $model, $item)
    {
        $this->makeFile(
            $module, 
            $model,
            'stubs/DummyRequest.stub', 
            "app/Http/Requests/{$module}/{$model}Request.php"
        );
    }

    /**
     * Make controller
    */
    private function makeController($module, $model, $item)
    {
        $this->makeFile(
            $module, 
            $model,
            'stubs/DummyController.stub', 
            "app/Http/Controllers/{$module}/{$model}Controller.php",
            function ($value) use ($item) {
                if (!Arr::get($item, 'model.hasSlug')) {
                    return Str::of($value)->replace(", 'slug'", '');
                }
                return $value;
            }
        );
    }

    /**
     * Make migration
    */
    private function makeMigration($module, $model, $item)
    {
        $table = Str::snake(Str::plural($model));
        if (!collect(Storage::disk('base')->allFiles('database/migrations'))
            ->contains(function ($value) use ($table) {
                return Str::of($value)->contains($table);
        })) {
            $this->call('make:migration', ['name' => "create_{$table}_table"]);
        }
    }

    /**
     * Make resource
    */
    private function makeResource($module, $model, $item)
    {
        $this->makeFile(
            $module, 
            $model,
            'stubs/DummyResource.stub', 
            "app/Http/Resources/{$module}/{$model}.php"
        );
    }

    /**
     * Make factory
    */
    private function makeFactory($module, $model, $item)
    {
        $this->makeFile(
            $module, 
            $model,
            'stubs/DummyFactory.stub', 
            "database/factories/{$module}/{$model}Factory.php"
        );
    }

    /**
     * Make seeder
    */
    private function makeSeeder($module, $model, $item)
    {
        $this->makeFile(
            $module, 
            $model,
            'stubs/DummySeeder.stub', 
            "database/seeders/{$module}/{$model}Seeder.php"
        );
    }

    /**
     * Make test
    */
    private function makeTest($module, $model, $item)
    {
        $this->makeFile(
            $module, 
            $model,
            'stubs/DummyTest.stub', 
            "tests/Feature/{$module}/{$model}Test.php",
            function ($value) use ($item, $module, $model) {
                $modLower = Str::lower($module);
                $plural   = Str::lower(Str::plural($model));
                $media    = '$this->assertTrue($output->get(\'image\', true)); // Test spatie image upload';
                if (!Arr::get($item, 'model.hasMedia')) {
                    return Str::of($value)
                        ->replace($media, '')
                        ->replace('module_name', $modLower)
                        ->replace('models_name', $plural);
                }
                return $value;
            }
        );
    }

    /**
     * Make test data
    */
    private function makeTestData($module, $model, $item)
    {
        $this->makeFile(
            $module, 
            $model,
            'stubs/DummyTestDataTrait.stub', 
            "tests/Feature/Data/{$module}/{$model}TestDataTrait.php"
        );
    }

    private function makeRoute($module, $model)
    {
        $path     = 'routes/api.php';
        $modLower = Str::lower($module);
        $plural   = Str::lower(Str::plural($model));
        $content  = Storage::disk('base')->get($path);

        if (!Str::of($content)->contains("'$modLower/$plural'")) {
            $content .= "
/** $module - $model **/
Route::name('$modLower.')->group(function () {
    Route::name('$plural.filter')->get('$modLower/$plural/filter', [\App\Http\Controllers\\".$module."\\".$model."Controller::class, 'filter']);
    Route::name('$plural.restore')->patch('$modLower/$plural/{model}/restore', [\App\Http\Controllers\\".$module."\\".$model."Controller::class, 'restore']);
    Route::name('$plural.toggle-activated-many')->put('$modLower/$plural/toggle-activated-many', [\App\Http\Controllers\\".$module."\\".$model."Controller::class, 'massToggleActivated']);
    Route::name('$plural.delete-many')->delete('$modLower/$plural/delete-many', [\App\Http\Controllers\\".$module."\\".$model."Controller::class, 'massDestroy']);
    Route::apiResource('$modLower/$plural', \App\Http\Controllers\\".$module."\\".$model."Controller::class)->parameters(['$plural' => 'model']);
});            
";
            
            Storage::disk('base')->put($path, $content);
        }
    }

    private function makeFile($module, $model, $stub, $path, ?\Closure $transform = null)
    {
        $force = $this->option('force');
        
        if (empty($module) || empty($model)) { // Test file missing
            $this->error("Module or model name cannot be empty.");
            return;
            
        }

        if (Storage::disk('base')->missing($stub)) { // Test file missing
            $this->error("This file: $stub is missing");
            return;
            
        }

        if (Storage::disk('base')->exists($path) && !$force) { // Test file exists
            $this->error("This file: $path existed. Add --force to override it");
            return;
        }

        $content = Str::of(Storage::disk('base')->get($stub))->replace('ModuleName', $module)->replace('ModelName', $model);

        if ($transform instanceof \Closure) { $content = $transform($content); }

        Storage::disk('base')->put($path, $content);
    }
}
