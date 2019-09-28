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
            'FastDog\Menu\Listeners\MenuItemBeforeSave',
        ],
        'FastDog\Menu\Events\MenuItemAfterSave' => [
            'FastDog\Menu\Listeners\MenuItemAfterSave',
        ],
        'FastDog\Menu\Events\MenuItemAdminPrepare' => [
            'FastDog\Core\Listeners\AdminItemPrepare',// <-- Поля даты обновления и т.д.
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
            //<-- добавление ресурсов для создания меню
        ],
        'FastDog\Menu\Events\PageAdminPrepare' => [
            'FastDog\Core\Listeners\AdminItemPrepare',// <-- Поля даты обновления и т.д.
            'FastDog\Core\Listeners\MetadataAdminPrepare',// <-- SEO
            'FastDog\Menu\Listeners\PageAdminPrepare',
            'FastDog\Menu\Listeners\PageSetEditorForm',
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
