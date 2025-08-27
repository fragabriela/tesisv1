<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;

class TemporaryStorageProvider extends ServiceProvider
{
    public function register()
    {
        // Desactivar Storage temporalmente
        $this->app->bind('filesystem', function() {
            return new class {
                public function disk($name = null) {
                    return $this;
                }
                
                public function makeDirectory($path) {
                    // Crear directorio manualmente
                    $fullPath = storage_path('app/' . $path);
                    if (!file_exists($fullPath)) {
                        mkdir($fullPath, 0755, true);
                    }
                    return true;
                }
                
                public function __call($method, $args) {
                    // No hacer nada para otros métodos
                    return true;
                }
            };
        });
    }
}