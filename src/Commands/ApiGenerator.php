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
     * The total of items created.
     *
     * @var integer
     */
    protected $itemsGenerated = 0;

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
        // Get modules from configuration
        $config = collect(config('apigenerator.modules', []));

        // Test if file exists
        // SAMS (Snippetify Api Model Skeleton)
        if ($config->isEmpty() && Storage::disk('base')->exists('snippetify-ams.json')) {
            $config = collect(json_decode(Storage::disk('base')->get('snippetify-ams.json'), true));
        }
        
        // If config is empty
        if ($config->isEmpty()) {
            $this->error("You must set a configuration in 'apigenerator.php' config file or create a 'snippetify-ams.json' file in your project root.");
            return;
        }

        $this->itemsGenerated = 0;

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

        $this->info($this->itemsGenerated > 0 ? "Api items({$this->itemsGenerated}) generated" : "No items generated");

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
                $with      = '';
                $casts     = '';
                $hidden    = '';
                $import    = '';
                $fillable  = '';
                $appends   = '';
                $implement = '';
                $relations = '';

                // Foreach and set attributes
                foreach (Arr::get($item, 'model.attributes') as $key => $items) {
                    // Casts items
                    if ('boolean' === $items['type']) {
                        $casts .= "'".Str::snake($key)."' => '".$items['type']."', \n";
                    }
                    if ($items['fillable']) {
                        $fillable .= "'".Str::snake($key)."', ";
                    }
                    if ($items['hidden']) {
                        $hidden .= "'".Str::snake($key)."', ";
                    }
                }

                // Foreach and set relations
                foreach (Arr::get($item, 'model.relations') as $key => $items) {
                    $name = $key;
                    $relation = '';
                    $morphable = '';
                    if ($items['morph']) { // Polymorphic relationship
                        if (collect(['oneToOne', 'manyToOne'])->contains($items['type'])) $relation = 'morphOne';
                        else if ('oneToMany' === $items['type']) $relation = 'morphMany';
                        else if ('manyToMany' === $items['type']) $relation = 'morphToMany';
                        $morphable = ", ".Str::singular($key)."able";
                        if ($items['inverse']) {
                            if (collect(['oneToOne', 'manyToOne'])->contains($items['type'])) {
                                $morphable = "";
                                $relation = 'morphTo';
                                $name = Str::singular($key)."able";
                            }
                            else if ('manyToMany' === $items['type']) $relation = 'morphedByMany';
                        }
                    } else {
                        if (collect(['oneToOne', 'manyToOne'])->contains($items['type'])) $relation = 'belongsTo';
                        else if ('oneToMany' === $items['type']) $relation = 'hasMany';
                        else if ('manyToMany' === $items['type']) $relation = 'belongsToMany';
                    }

                    $relations .= "
                    /**
                     * Get the item's {$name}.
                     */
                    public function {$name}()
                    {
                        return \$this->{$relation}({$items['type']}::class{$morphable});
                    }

                    ";
                    if ($items['with']) {
                        $with .= "'".Str::snake($key)."', ";
                    }
                }

                // Enable soft delete
                if (Arr::get($item, 'model.softDelete')) {
                    $use .= 'use SoftDeletes;';
                    $hidden .= "'deleted_at', ";
                }

                // Create issers
                foreach (Arr::get($item, 'model.issers') as $isser) {
                    if (!empty($isser)) {
                        $isserName = (string)Str::of($isser)->studly();
                        $this->makeFile(
                            'Traits',
                            "{$isserName}Trait",
                            'stubs/DummyIsserTrait.stub',
                            "app/Traits/{$isserName}Trait.php",
                            function ($isserValue) use ($isserName) {
                                return Str::of($isserValue)
                                    ->replace("StudlyName", $isserName)
                                    ->replace("snake_name", Str::snake($isserName))
                                ;
                            }
                        );
                        $appends  .= "'".Str::snake($isserName)."', ";
                        $fillable .= "'".Str::snake($isserName)."', ";
                        $use      .= "use \App\Traits\{$isserName}Trait;";
                    }
                }

                // Attach model to a user
                if (Arr::get($item, 'model.hasUser')) {
                    $use .= 'use \Snippetify\ApiGenerator\Traits\HasUserTrait;';
                }

                // Allow model to be loggable
                if (Arr::get($item, 'model.hasLog')) {
                    $use .= 'use \Snippetify\ApiGenerator\Traits\HasLogsTrait;';
                }

                // Make model searchable
                if (Arr::get($item, 'model.isSearchable')) {
                    $use .= 'use \Snippetify\ApiGenerator\Traits\SearchableTrait;';
                }

                // Make model sluggable
                if (Arr::get($item, 'model.hasSlug')) {
                    $use .= 'use \Snippetify\ApiGenerator\Traits\SluggableTrait;';
                } else {
                    $use .= 'use \Snippetify\ApiGenerator\Traits\HasRouteBindingTrait;';
                }

                // Add media to model
                if (Arr::get($item, 'model.hasMedia')) {
                    $implement = 'implements HasMedia';
                    $use      .= 'use \Snippetify\ApiGenerator\Traits\HasMediaTrait;';
                }
                
                return Str::of($value)
                    ->replace("%%with%%", $with)
                    ->replace("%%casts%%", $casts)
                    ->replace("%%hidden%%", $hidden)
                    ->replace("%%usetraits%%", $use)
                    ->replace("%%appends%%", $appends)
                    ->replace("%%fillable%%", $fillable)
                    ->replace("%%relations%%", $relations)
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
            "database/factories/{$module}/{$model}Factory.php",
            function ($value) use ($item) {
                $definitions = '';

                // Foreach attributes and set definitions
                foreach (Arr::get($item, 'model.attributes') as $key => $items) {
                    // Casts items
                    switch ($items['type']) {
                        case 'boolean':
                            $definitions .= "\$this->faker->randomElement([true, false]),\n";
                            break;
                        case 'int':
                        case 'float':
                        case 'double':
                        case 'integer':
                            $definitions .= "\$this->faker->randomElement([true, false]),\n";
                            break;
                        default:
                            $definitions .= "\$this->faker->word,\n";
                    }
                }

                return Str::of($value)
                    ->replace("%%definitions%%", $definitions)
                ;
            }
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
    Route::name('$plural.restore')->patch('$modLower/$plural/{model}/restore', [\App\Http\Controllers\\".$module."\\".$model."Controller::class, 'restore']);
    Route::name('$plural.toggle-many')->put('$modLower/$plural/toggle-many', [\App\Http\Controllers\\".$module."\\".$model."Controller::class, 'massToggle']);
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
        
        if (empty($module) || empty($model)) { // Test item missing
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
        
        $this->itemsGenerated++;
    }
}
