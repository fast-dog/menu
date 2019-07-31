<?php

namespace FastDog\Menu;


use FastDog\Core\Models\ModuleManager;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

/**
 * Class MenuServiceProvider
 * @package FastDog\Media
 */
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
        $this->handleConfigs();
        $this->handleRoutes();
        $this->handleMigrations();
        $this->handleLang();

//        $this->publishes([
//            __DIR__ . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR =>
//                base_path('resources/views/'),
//        ]);


        /**
         * @var $moduleManager ModuleManager
         */
        $moduleManager = \App::make(ModuleManager::class);
        $moduleManager->pushModule(Menu::MODULE_ID, (new Menu())->getModuleInfo());
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->register(MenuEventServiceProvider::class);
        $this->app->register(AuthServiceProvider::class);
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
        $configPath = __DIR__ . '/../config/menu.php';
        $this->publishes([$configPath => config_path('menu.php')]);

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
