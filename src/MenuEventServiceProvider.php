<?php

namespace FastDog\Menu;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class MenuEventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'FastDog\Menu\Events\MenuItemBeforeSave' => [
            'FastDog\Core\Listeners\ModelBeforeSave',//<-- упаковка данных с формы в json поле data
            'FastDog\Menu\Listeners\MenuItemBeforeSave',//<-- Обновление канонических ссылок и т.д.
            // 'FastDog\Menu\Listeners\MenuBuildRoute',//<-- Определение маршрута
        ],
        'FastDog\Menu\Events\MenuItemAfterSave' => [
            'FastDog\Menu\Listeners\MenuItemAfterSave',
        ],
        'FastDog\Menu\Events\MenuItemAdminPrepare' => [
            'FastDog\Core\Listeners\AdminItemPrepare',// <-- Поля даты обновления и т.д., распаковка из json поля data
            'FastDog\Core\Listeners\MetadataAdminPrepare',// <-- SEO
            'FastDog\Menu\Listeners\MenuItemAdminPrepare',
            'FastDog\Menu\Listeners\MenuItemSetEditorForm',
        ],
        'FastDog\Menu\Events\MenuItemsAdminPrepare' => [
            'FastDog\Menu\Listeners\MenuItemsAdminPrepare',
        ],
        'FastDog\Menu\Events\MenuPrepare' => [
            'FastDog\Menu\Listeners\MenuPrepare',
        ],
        'FastDog\Menu\Events\CatalogCreateProperty' => [
            'FastDog\Menu\Listeners\CatalogCreateProperty',
        ],
        'FastDog\Menu\Events\MenuSetFormGeneralFields' => [
            //<-- добавление полей к основной форме редактирования
        ],
        'FastDog\Menu\Events\MenuSetFormTabs' => [
            //<-- добавление разделов к основной форме редактирования
        ],
        'FastDog\Menu\Events\MenuResources' => [
            'FastDog\Menu\Listeners\MenuResources',  //<-- добавление ресурсов для создания меню
        ],
        'FastDog\Menu\Events\PageAdminPrepare' => [
            'FastDog\Core\Listeners\AdminItemPrepare',// <-- Поля даты обновления и т.д.
            'FastDog\Core\Listeners\MetadataAdminPrepare',// <-- SEO
            'FastDog\Menu\Listeners\PageAdminPrepare',
            'FastDog\Menu\Listeners\PageSetEditorForm',
            'FastDog\Core\Listeners\FormBuilder',// <-- Настройка формы
        ],
        'FastDog\Menu\Events\PageAdminAfterSave' => [
            'FastDog\Menu\Listeners\PageAdminAfterSave',
        ],
        'FastDog\Menu\Events\PageAdminBeforeSave' => [
            'FastDog\Menu\Listeners\PageAdminBeforeSave',
        ],
        'FastDog\Core\Events\GetComponentType' => [
            'FastDog\Menu\Listeners\GetComponentType',// <-- Добавляем доступные в пакете типы компонентов
        ],
        'FastDog\Core\Events\GetComponentTypeFields' => [
            'FastDog\Menu\Listeners\GetComponentTypeFields',// <-- Добавляем зависимые (от типов) поля в форму редактирования компонентов
        ],
        'FastDog\Menu\Events\BeforePrepareContent' => [// <--  Перед получением контента
        ],
        'FastDog\Menu\Events\MenuBuildRoute' => [// <--  Определение маршрута пункта меню
            'FastDog\Menu\Listeners\MenuBuildRoute',
        ]
    ];


    /**
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

}
