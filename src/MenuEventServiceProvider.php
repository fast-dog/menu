<?php

namespace FastDog\Media;

use FastDog\Core\Models\DomainManager;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Arr;

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
            'App\Core\Listeners\AdminItemPrepare',// <-- Поля даты обновления и т.д.
            'App\Core\Listeners\MetadataAdminPrepare',// <-- SEO
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
    ];


    /**
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

}
