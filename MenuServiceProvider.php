<?php

namespace FastDog\Media;


use FastDog\Core\Models\ModuleManager;
use FastDog\Menu\Menu\Menu;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class MenuServiceProvider extends LaravelServiceProvider
{
    const NAME = 'menu';

    /**
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(): void
    {
//        $this->handleConfigs();
        $this->handleRoutes();
        $this->handleMigrations();
        $this->handleLang();

        $this->loadViewsFrom(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR, 'elfinder');

        $this->publishes([
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR =>
                base_path('resources/views/'),
        ]);


        /**
         * @var $moduleManager ModuleManager
         */
        $moduleManager = \App::make(ModuleManager::class);
        $moduleManager->pushModule(Menu::MODULE_ID, (new Menu())->getModuleInfo(true));
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {

        $this->app->register(MediaEventServiceProvider::class);
        $this->app->register(ElfinderServiceProviderFD::class);

        $this->app->alias('Image', \Intervention\Image\Facades\Image::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [];
    }


    /**
     * Определение конфигурации по умолчанию
     */
    private function handleConfigs(): void
    {
        $configPath = __DIR__ . '/../config/media.php';
        $this->publishes([$configPath => config_path('media.php')]);

        $this->mergeConfigFrom($configPath, self::NAME);
    }

    /**
     * Миграции базы данных
     */
    private function handleMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../migrations/');
    }


    /**
     * Определение маршрутов пакета
     */
    private function handleRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/routes.php');
    }

    /**
     * Определение локализации
     */
    private function handleLang(): void
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR;
        $this->loadTranslationsFrom($path, self::NAME);
        $this->publishes([
            $path => resource_path('lang/vendor/fast_dog/' . self::NAME),
        ]);
    }
}
