<?php

namespace Eksprt\FileManager;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver;
use Eksprt\FileManager\Models\File;
use Eksprt\FileManager\Observers\FileObserver;
use Eksprt\FileManager\View\Components\Dropzone;
use Eksprt\FileManager\View\Components\FileUpload;

class FileManagerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        if (! app()->configurationIsCached()) {
            $this->mergeConfigFrom(
                __DIR__.'/../config/filemanager.php', 'filemanager'
            );
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filemanager');

        if (app()->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/filemanager.php' => config_path('filemanager.php'),
            ], 'filemanager-config');

            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'filemanager-migration');
        }

        if (config('filemanager.image_driver') === 'imagick') {
            config(['image.driver' => Driver::class]);
        } else {
            config(['image.driver' => GdDriver::class]);
        }

        $this->loadViewComponentsAs('filemanager', [
            FileUpload::class,
            Dropzone::class,
        ]);

        File::observe(FileObserver::class);

        Blade::directive('filemanagerScript', function () {
            return "<script src='".route('filemanager.uploader')."?ver=4.0.2'></script>";
        });
    }
}