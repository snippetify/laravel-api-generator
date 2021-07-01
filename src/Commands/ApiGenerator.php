<?php

namespace Snippetify\ApiGenerator\Commands;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;

class ApiGenerator extends Command
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;
    
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
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
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

        // Add disks to filesystem
        $this->info("Adding disks to filsystem.php file ");
        $this->addDisksToFilesystem();

        // Test if file exists
        // SAMS (Snippetify Api Model Skeleton)
        if ($config->isEmpty() && $this->files->exists(base_path('snippetify-ams.json'))) {
            $config = collect(json_decode($this->files->get(base_path('snippetify-ams.json')), true));
        }
        
        // If config is empty
        if ($config->isEmpty()) {
            $this->error("You must set a configuration in 'apigenerator.php' config file or create a 'snippetify-ams.json' file in your project root.");
            return;
        }

        $this->itemsGenerated = 0;

        $config->each(function ($item) {

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
        
            $this->info("Importing traits helpers");
            $this->moveUtilities();

            // $this->info("Adding dependencies to composer.json file ");
            // $this->importComposerDepencies();
        
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
                    $classname = (string) Str::start($items['class'], '\\');
                    $relations .= <<<RELATIONS
    /**
     * Get the item's $name.
     */
    public function $name()
    {
        return \$this->$relation({$classname}::class{$morphable});
    }

RELATIONS;
                    if ($items['with']) {
                        $with .= "'".Str::snake($key)."', ";
                    }
                }

                // Enable soft delete
                if (Arr::get($item, 'model.softDelete')) {
                    $use .= "\tuse SoftDeletes;\n";
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
                        $use      .= "\tuse \App\Traits\\{$isserName}Trait;\n";
                    }
                }

                // Attach model to a user
                if (Arr::get($item, 'model.hasUser')) {
                    $use .= "\tuse \App\Traits\HasUserTrait;\n";
                }

                // Allow model to be loggable
                if (Arr::get($item, 'model.hasLog')) {
                    $use .= "\tuse \App\Traits\HasLogsTrait;\n";
                }

                // Make model searchable
                if (Arr::get($item, 'model.isSearchable')) {
                    $use .= "\tuse \App\Traits\SearchableTrait;\n";
                }

                // Add media to model
                if (Arr::get($item, 'model.hasMedia')) {
                    $implement = 'implements HasMedia';
                    $use      .= "\tuse \App\Traits\HasMediaTrait;\n";
                }

                // Make model sluggable
                if (Arr::get($item, 'model.hasSlug')) {
                    $use .= "\tuse \App\Traits\SluggableTrait;";
                } else {
                    $use .= "\tuse \App\Traits\HasRouteBindingTrait;";
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
            "app/Http/Requests/{$module}/{$model}Request.php",
            function ($value) use ($item) {
                $definitions = '';

                // Foreach attributes and set definitions
                foreach (Arr::get($item, 'model.attributes') as $key => $items) {
                    $definitions .= "'{$key}' => '{$items['rules']}',";
                }

                return Str::of($value)
                    ->replace("%%definitions%%", $definitions)
                ;
            }
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
        if (!collect($this->files->allFiles(database_path('migrations')))
            ->contains(function ($value) use ($table) {
                return Str::of($value)->contains($table);
        })) {
            // $this->call('make:migration', ['name' => "create_{$table}_table"]);
            $filename = date('Y_m_d_his')."_create_{$table}_table";
            $this->makeFile(
                $module,
                $model,
                'stubs/DummyDatabaseMigration.stub',
                "database/migrations/{$filename}.php",
                function ($value) use ($table, $item) {
                    $definitions = '';
    
                    // Foreach attributes and set definitions
                    foreach (Arr::get($item, 'model.attributes') as $key => $items) {
                        switch ($items['type']) {
                            case 'boolean':
                                $definitions .= "\$table->boolean('{$key}')";
                                break;
                            case 'float':
                                $definitions .= "\$table->float('{$key}')";
                                break;
                            case 'double':
                                $definitions .= "\$table->double('{$key}')";
                                break;
                            case 'int':
                            case 'integer':
                            case 'numeric':
                                $definitions .= "\$table->integer('{$key}')";
                                break;
                            default:
                                $definitions .= Str::contains($items['rules'], 'max:') ? 
                                    "\$table->string('{$key}')" : "\$table->text('{$key}')";
                        }
                        if (!Str::contains($items['rules'], 'required')) {
                            $definitions .= '->nullable()';
                        }
                        $definitions .= ";\n";
                    }
    
                    return Str::of($value)
                        ->replace("%%definitions%%", $definitions)
                        ->replace("%%snake_name%%", Str::snake($table))
                        ->replace("%%StudlyName%%", Str::studly($table))
                    ;
                }
            );
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
                    switch ($items['type']) {
                        case 'boolean':
                            $definitions .= "'{$key}' => \$this->faker->randomElement([true, false]),\n";
                            break;
                        case 'int':
                        case 'float':
                        case 'double':
                        case 'integer':
                            $definitions .= "'{$key}' => \$this->faker->randomElement([true, false]),\n";
                            break;
                        default:
                            $definitions .= "'{$key}' => \$this->faker->word,\n";
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
        $content  = $this->files->exists(base_path($path)) ? $this->files->get(base_path($path)) : "";

        if (!Str::of($content)->contains("'$modLower/$plural'")) {
            $content .= <<<ROUTE

/** $module - $model **/
Route::name('$modLower.')->group(function () {
    Route::name('$plural.restore')->patch('$modLower/$plural/{model}/restore', [\App\Http\Controllers\\{$module}\\{$model}Controller::class, 'restore']);
    Route::name('$plural.toggle-many')->put('$modLower/$plural/toggle-many', [\App\Http\Controllers\\{$module}\\{$model}Controller::class, 'massToggle']);
    Route::name('$plural.delete-many')->delete('$modLower/$plural/delete-many', [\App\Http\Controllers\\{$module}\\{$model}Controller::class, 'massDestroy']);
    Route::apiResource('$modLower/$plural', \App\Http\Controllers\\{$module}\\{$model}Controller::class)->parameters(['$plural' => 'model']);
});

ROUTE;
            
            $this->files->put(base_path($path), $content);
        }
    }

    private function makeFile($module, $model, $stub, $path, ?\Closure $transform = null)
    {
        $force    = $this->option('force');
        $stubPath = __DIR__."/../../{$stub}";
        
        if (empty($module) || empty($model)) { // Test item missing
            $this->error("Module or model name cannot be empty.");
            return;
            
        }

        if ($this->files->missing($stubPath)) { // Test file missing
            $this->error("This file: {$stubPath} is missing");
            return;
            
        }

        if ($this->files->exists(base_path($path)) && !$force) { // Test file exists
            $this->warn("This file: $path existed. Add --force to override it");
            return;
        }

        $content = Str::of($this->files->get($stubPath))->replace('ModuleName', $module)->replace('ModelName', $model);

        if ($transform instanceof \Closure) { $content = $transform($content); }

        // Ensure a directory exists
        $this->files->ensureDirectoryExists(Str::of($path)->dirname());

        // Save content to file
        $this->files->put(base_path($path), $content);
        
        $this->itemsGenerated++;
    }

    private function importComposerDepencies()
    {
        $dependencies = collect(config('apigenerator.dependencies', []));

        if ($this->files->exists(base_path('composer.json'))) {
            $content = json_decode($this->files->get(base_path('composer.json')), true);
            foreach ($dependencies as $key => $value) {
                if (!Arr::has($content, "require.{$key}")) {
                    Arr::set($content, "require.{$key}", $value);
                }
            }
            $this->files->put(base_path('composer.json'), json_encode($content, JSON_PRETTY_PRINT));
        }
    }

    private function addDisksToFilesystem()
    {
        if ($this->files->exists(config_path('filesystems.php'))) {
            $content = collect($this->files->get(config_path('filesystems.php')));
            if (!Arr::has($content, 'disks.base')) {
                $this->files->copy(__DIR__.'/../../config/filesystems.php', config_path('filesystems.php'));
            }
        }
    }

    private function moveUtilities()
    {
        // Move app traits
        foreach ($this->files->files(__DIR__.'/../Traits') as $key => $file) {
            $name = (string) Str::of($file)->basename();
            $filename = app_path("Traits/{$name}");
            if ($this->files->missing($filename)) {
                $this->files->ensureDirectoryExists(Str::of($filename)->dirname()); // Ensure a directory exists
                $this->files->copy($file, $filename);
            }
        }

        // Move app test traits
        foreach ($this->files->files(__DIR__.'/../../tests/Feature/Traits') as $key => $file) {
            $name = (string) Str::of($file)->basename();
            $filename = base_path("tests/Feature/Traits/{$name}");
            if ($this->files->missing($filename)) {
                $this->files->ensureDirectoryExists(Str::of($filename)->dirname()); // Ensure a directory exists
                $this->files->copy($file, $filename);
            }
        }
    }
}
