<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class LoggingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Fix log file permissions issue when multiple processes write to it
        $logger = $this->app->make(LoggerInterface::class);

        if ($logger instanceof Logger) {
            foreach ($logger->getHandlers() as $handler) {
                if ($handler instanceof StreamHandler) {
                    // Get the stream URL/path
                    $reflection = new \ReflectionClass($handler);
                    $property = $reflection->getProperty('stream');
                    $property->setAccessible(true);
                    $stream = $property->getValue($handler);

                    // If it's a string path, ensure permissions
                    if (is_string($stream) && file_exists($stream)) {
                        try {
                            chmod($stream, 0666);
                            $dir = dirname($stream);
                            if (is_dir($dir)) {
                                chmod($dir, 0777);
                            }
                        } catch (\Exception $e) {
                            // Silent fail - permissions might be restricted
                        }
                    }
                }
            }
        }
    }
}
