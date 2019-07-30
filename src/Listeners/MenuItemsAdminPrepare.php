<?php

namespace FastDog\Menu\Listeners;


use FastDog\Core\Models\DomainManager;
use FastDog\Menu\Events\MenuItemsAdminPrepare as MenuItemsAdminPrepareEvent;
use FastDog\Menu\Menu;
use Illuminate\Http\Request;

/**
 * Обработка списка меню в разделе администрирования
 *
 * @package FastDog\Menu\Listeners
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuItemsAdminPrepare
{

    /**
     * @var Request
     */
    protected $request;

    /**
     * AfterSave constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param MenuItemsAdminPrepareEvent $event
     */
    public function handle(MenuItemsAdminPrepareEvent $event)
    {
        /**
         * @var $data array
         */
        $data = $event->getData();


        foreach ($data['items'] as &$item) {
            $item['suffix'] = DomainManager::getDomainSuffix($item[Menu::SITE_ID]);
        }

        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }

        $event->setData($data);
    }
}
