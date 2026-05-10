<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class FilamentCompatibilityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // nothing here
    }

    public function boot(): void
    {
        // Force-autoload Filament Resource Page classes so their typed static
        // properties are initialized early (prevents "accessed before initialization").
        $resourcesPath = app_path('Filament/Resources');

        if (! is_dir($resourcesPath)) {
            return;
        }

        $resourceDirs = glob($resourcesPath.'/*', GLOB_ONLYDIR) ?: [];

        foreach ($resourceDirs as $dir) {
            $pagesPath = $dir.'/Pages';

            if (! is_dir($pagesPath)) {
                continue;
            }

            $files = glob($pagesPath.'/*.php') ?: [];

            foreach ($files as $file) {
                $base = pathinfo($file, PATHINFO_FILENAME);

                // Build expected FQCN for the page class
                $resourceName = basename($dir);
                $class = "App\\Filament\\Resources\\{$resourceName}\\Pages\\{$base}";

                try {
                    // class_exists will trigger autoload; we ignore result
                    // — we only need the side-effect of loading the class.
                    \class_exists($class, true);
                } catch (\Throwable $e) {
                    // Ignore any errors during autoloading — we don't want to
                    // break application boot for edge cases.
                    logger()->debug('Filament compatibility autoload failed for '.$class.': '.$e->getMessage());
                }
            }
        }
    }
}
