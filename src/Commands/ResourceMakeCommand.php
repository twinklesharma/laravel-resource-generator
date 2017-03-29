<?php

namespace LaravelResource\ResourceMaker\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Foundation\Composer;
use Illuminate\Filesystem\Filesystem;
use File;

class ResourceMakeCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:resource {name : The model name} {attributes?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a model, repository, controller and services';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $files;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var array The data types that can be created in a migration.
     */
    private $dataTypes = [
        'string', 'integer', 'boolean', 'bigIncrements', 'bigInteger',
        'binary', 'boolean', 'char', 'date', 'dateTime', 'float', 'increments',
        'json', 'jsonb', 'longText', 'mediumInteger', 'mediumText', 'nullableTimestamps',
        'smallInteger', 'tinyInteger', 'softDeletes', 'text', 'time', 'timestamp',
        'timestamps', 'rememberToken',
    ];
    private $fakerMethods = [
        'string' => ['method' => 'words', 'parameters' => '2, true'],
        'integer' => ['method' => 'randomNumber', 'parameters' => ''],
    ];

    /**
     * @var array $columnProperties Properties that can be applied to a table column.
     */
    private $columnProperties = [
        'unsigned', 'index', 'nullable'
    ];

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     * @param Composer $composer
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct();

        $this->files = $files;

        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $name = trim($this->input->getArgument('name'));
        $path = app_path('Http/Domain/Models/' . $name);
        $this->files->makeDirectory($path, $mode = 0777, true, true);
        $path = app_path('Http/Domain/Repositories/' . $name);
        $this->files->makeDirectory($path, $mode = 0777, true, true);
        $path = app_path('Services');
        $this->files->makeDirectory($path, $mode = 0777, true, true);


        $this->createModel($name);

//        $this->createRepository($name);
//
//        $this->createController($name);
//
//        $this->createRepoInterface($name);
//        
//        $this->createModelInterface($name);
//
//        $this->createServices($name);
    }

    private function createServices($name)
    {
        $model = $this->modelName($name);

        $stub = $this->files->get(__DIR__ . '/../Stubs/service.stub');

        $stub = str_replace('NAME_PLACEHOLDER', $model, $stub);

        $filename = $model . 'Service.php';



        // $stub = str_replace('REPOSITORY_NAME', ucfirst($name), $stub);

        $this->files->put(app_path('Services/' . $filename), $stub);

        $this->info($model . ' Services created');

        return true;
    }

    public function buildFakerAttributes($attributes)
    {
        $faker = '';

        foreach ($attributes as $attribute) {

            $formatter = $this->fakerMethods[$this->getFieldTypeFromProperties($attribute['properties'])];

            $method = $formatter['method'];
            $parameters = $formatter['parameters'];

            $faker .= "'" . $attribute['name'] . "' => \$faker->" . $method . "(" . $parameters . ")," . PHP_EOL . '        ';
        }

        return rtrim($faker);
    }

    /**
     * Create and store a new Model to the filesystem.
     *
     * @param string $name
     * @return bool
     */
    private function createModel($name)
    {
        $modelName = $this->modelName($name);

        $filename = $modelName . '.php';

        if ($this->files->exists(app_path('Http/Domain/Models/' . $name . '/' . $filename))) {
            $this->error('Model already exists!');
            return false;
        }

        $model = $this->buildModel($name);

        //$this->files->put(app_path('Http/Domain/Models/' . $name.'/'.$filename), $model);

        $this->info($modelName . ' Model created');

        return true;
    }

    private function createRepository($name)
    {
        // $filename = $this->buildRepositoryFilename($name);
        $modelName = $this->modelName($name);
        $filename = $modelName . 'Repository.php';

        if ($this->files->exists(app_path('Http/Domain/Repositories/' . $name . '/' . $filename))) {
            $this->error('Repository already exists!');
            return false;
        }

        $model = $this->buildRepository($name);

        $this->files->put(app_path('Http/Domain/Repositories/' . $name . '/' . $filename), $model);

        $this->info($modelName . ' Repository created');

        return true;
    }

    private function createController($modelName)
    {
        $filename = ucfirst($modelName) . 'Controller.php';

        if ($this->files->exists(app_path('Http/' . $filename))) {
            $this->error('Controller already exists!');
            return false;
        }

        $stub = $this->files->get(__DIR__ . '/../Stubs/controller.stub');

        $stub = str_replace('MyModelClass', ucfirst($modelName), $stub);
        $stub = str_replace('myModelInstance', Str::camel($modelName), $stub);
        $stub = str_replace('template', strtolower($modelName), $stub);

        $this->files->put(app_path('Http/Controllers/' . $filename), $stub);

        $this->info('Created controller ' . $filename);

        return true;
    }

    private function createRepoInterface($name)
    {
        $modelName = $this->modelName($name);
        $filename = $modelName . 'RepositoryInterface.php';

        if ($this->files->exists(app_path('Http/Domain/Repositories/' . $name . '/' . $filename))) {
            $this->error('Repository already exists!');
            return false;
        }

        $model = $this->buildRepoInterface($name);

        $this->files->put(app_path('Http/Domain/Repositories/' . $name . '/' . $filename), $model);

        $this->info($modelName . ' Repository Interface created');

        return true;
    }

    private function createModelInterface($name)
    {
        $modelName = $this->modelName($name);
        $filename = $modelName . 'Interface.php';

        if ($this->files->exists(app_path('Http/Domain/Models/' . $name . '/' . $filename))) {
            $this->error('Repository already exists!');
            return false;
        }

        $model = $this->buildModelInterface($name);

        $this->files->put(app_path('Http/Domain/Models/' . $name . '/' . $filename), $model);

        $this->info($modelName . ' Models Interface created');

        return true;
    }

    protected function buildRepository($name)
    {
        $stub = $this->files->get(__DIR__ . '/../Stubs/repository.stub');

        $stub = str_replace('REPOSITORY_NAME', ucfirst($name), $stub);

        return $stub;
    }

    protected function buildRepoInterface($name)
    {
        $stub = $this->files->get(__DIR__ . '/../Stubs/repo_interface.stub');

        $stub = str_replace('NAME_PLACEHOLDER', ucfirst($name), $stub);

        return $stub;
    }

    protected function buildModelInterface($name)
    {
        $stub = $this->files->get(__DIR__ . '/../Stubs/model_interface.stub');

        $stub = str_replace('NAME_PLACEHOLDER', ucfirst($name), $stub);

        return $stub;
    }

    protected function buildModel($name)
    {
        $modelName = $this->modelName($name);

        $filename = $modelName . '.php';

        $attr_array = explode(',', ($this->argument('attributes')));
        foreach ($attr_array as $array) {
            if ($this->files->exists(app_path('Http/Domain/Models/' . $name . '/' . $filename))) {
       
                // Get stub file
                $funStub = $this->files->get(__DIR__ . '/../Stubs/function.stub');
                $funStub = str_replace('FUNCTION_NAME', ucfirst($array), $funStub);
                $funStub = str_replace('COLUMN_NAME', ($array), $funStub);
                $funStub = str_replace('CAPS_COL_NAME', ucwords($array), $funStub);

                $this->files->append(app_path('Http/Domain/Models/' . $name . '/' . $filename), "\n" . $funStub);
            } else {
                
                $stub = $this->files->get(__DIR__ . '/../Stubs/model.stub');
                $stub = str_replace('FUNCTION_NAME', ucfirst($array), $stub);
                $stub = str_replace('COLUMN_NAME', ($array), $stub);
                $stub = str_replace('CAPS_COL_NAME', ucwords($array), $stub);
                $stub = $this->replaceClassName(ucfirst($name), $stub);
                // Create controller file
                $this->files->put(app_path('Http/Domain/Models/' . $name . '/' . $filename), $stub);
            }
        }
        
        $this->files->append(app_path('Http/Domain/Models/' . $name . '/' . $filename), "\n" . '}');
        return $stub;
    }

    public function convertModelToTableName($model)
    {
        return Str::plural(Str::snake($model));
    }

    public function buildRepositoryFilename($model)
    {
        $table = $this->convertModelToTableName($model);

        return date('Y_m_d_his') . '_create_' . $table . '_table.php';
    }

    private function replaceClassName($name, $stub)
    {
        return str_replace('NAME_PLACEHOLDER', $name, $stub);
    }

    private function addMigrationAttributes($text, $stub)
    {
        $attributesAsArray = $this->parseAttributesFromInputString($text);
        $attributesAsText = $this->convertArrayToString($attributesAsArray);

        return str_replace('MIGRATION_ATTRIBUTES_PLACEHOLDER', $attributesAsText, $stub);
    }

    /**
     * Convert a pipe-separated list of attributes to an array.
     *
     * @param string $text The Pipe separated attributes
     * @return array
     */
    public function parseAttributesFromInputString($text)
    {
        $parts = explode('|', $text);

        $attributes = [];

        foreach ($parts as $part) {
            $components = explode(':', $part);
            $attributes[$components[0]] = isset($components[1]) ? explode(',', $components[1]) : [];
        }

        return $attributes;
    }

    /**
     * Convert a PHP array into a string version.
     *
     * @param $array
     *
     * @return string
     */
    public function convertArrayToString($array)
    {
        $string = '[';

        foreach ($array as $name => $properties) {
            $string .= '[';
            $string .= "'name' => '" . $name . "',";

            $string .= "'properties' => [";
            foreach ($properties as $property) {
                $string .= "'" . $property . "', ";
            }
            $string = rtrim($string, ', ');
            $string .= ']';

            $string .= '],';
        }

        $string = rtrim($string, ',');

        $string .= ']';


        return $string;
    }

    public function addModelAttributes($name, $attributes, $stub)
    {
        $attributes = '[' . collect($this->parseAttributesFromInputString($attributes))
                        ->filter(function($attribute) use ($name) {
                            return in_array($name, $attribute);
                        })->map(function ($attributes, $name) {
                    return "'" . $name . "'";
                })->values()->implode(', ') . ']';


        return str_replace(strtoupper($name) . '_PLACEHOLDER', $attributes, $stub);
    }

    public function buildTableColumns($attributes)
    {

        return rtrim(collect($attributes)->reduce(function($column, $attribute) {

                    $fieldType = $this->getFieldTypeFromProperties($attribute['properties']);

                    if ($length = $this->typeCanDefineSize($fieldType)) {
                        $length = $this->extractFieldLengthValue($attribute['properties']);
                    }

                    $properties = $this->extractAttributePropertiesToApply($attribute['properties']);

                    return $column . $this->buildSchemaColumn($fieldType, $attribute['name'], $length, $properties);
                }));
    }

    /**
     * Get the column field type based from the properties of the field being created.
     *
     * @param array $properties
     * @return string
     */
    private function getFieldTypeFromProperties($properties)
    {
        $type = array_intersect($properties, $this->dataTypes);

        // If the properties that were given in the command
        // do not explicitly define a data type, or there
        // is no matching data type found, the column
        // should be cast to a string.

        if (!$type) {
            return 'string';
        }

        return $type[0];
    }

    /**
     * Can the data type have it's size controlled within the migration?
     *
     * @param string $type
     * @return bool
     */
    private function typeCanDefineSize($type)
    {
        return $type == 'string' || $type == 'char';
    }

    /**
     * Extract a numeric length value from all properties specified for the attribute.
     *
     * @param array $properties
     * @return int $length
     */
    private function extractFieldLengthValue($properties)
    {
        foreach ($properties as $property) {
            if (is_numeric($property)) {
                return $property;
            }
        }

        return 0;
    }

    /**
     * Get the column properties that should be applied to the column.
     *
     * @param $properties
     * @return array
     */
    private function extractAttributePropertiesToApply($properties)
    {
        return array_intersect($properties, $this->columnProperties);
    }

    /**
     * Create a Schema Builder column.
     *
     * @param string $fieldType The type of column to create
     * @param string $name Name of the column to create
     * @param int $length Field length
     * @param array $traits Additional properties to apply to the column
     * @return string
     */
    private function buildSchemaColumn($fieldType, $name, $length = 0, $traits = [])
    {
        return sprintf("\$table->%s('%s'%s)%s;" . PHP_EOL . '            ', $fieldType, $name, $length > 0 ? ", $length" : '', implode('', array_map(function ($trait) {
                            return '->' . $trait . '()';
                        }, $traits))
        );
    }

    /**
     * Build a Model name from a word.
     *
     * @param string $name
     * @return string
     */
    private function modelName($name)
    {
        return ucfirst($name);
    }

}
