<?php


namespace Niogu\Lardgets;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeLardgetCommand extends Command
{
    protected $signature = 'make:lardget {ClassName}';

    protected $description = 'Makes a lardget';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $name = $this->argument('ClassName');
        $text = $this->classDefinition($name);

        if($name !== ucfirst(Str::camel($name))) {
            $this->error('ClassName must be CamelCase: ' . ucfirst(Str::camel($name)));
            return;
        }

        $this->makeDirectoryIfNotExists();

        $filename = app_path("Http/Lardgets/$name.php");
        if(!file_exists($filename)) {
            file_put_contents($filename, $text);
            $this->info("Created app/Http/Lardgets/$name.php");
        } else {
            $this->warn("Already exists app/Http/Lardgets/$name.php");
        }
    }

    public function makeDirectoryIfNotExists(): void
    {
        $dir = app_path('Http/Lardgets');
        if (!file_exists($dir)) {
            if (!mkdir($concurrentDirectory = $dir) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }
    }

    /**
     * @param $name
     * @return string
     */
    public function classDefinition($name): string
    {
        return "<?php

namespace App\Http\Lardgets;

use Niogu\Lardgets\Lardget;

class $name extends Lardget {

    public function __construct()
    {
    }

    public function render()
    {
        return \$this->blade(<<<'blade'
            <div>
            </div>
        blade, get_defined_vars());
    }
}
";
    }

}
