<?php

namespace FastDog\Menu\Listeners;


use FastDog\Core\Store;
use FastDog\Menu\Events\MenuPrepare as MenuPrepareEvent;
use FastDog\Menu\Menu;
use Illuminate\Http\Request;

/**
 * Обработка данных в публичной части
 *
 * Определяет активный пункт меню
 *
 * @package FastDog\Menu\Listeners
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuPrepare
{

    /**
     * @var Request $request
     */
    protected $request;

    /**
     * @var Store
     */
    protected $storeManager;

    /**
     * AfterSave constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->storeManager = \App::make(Store::class);
    }

    /**
     * @param MenuPrepareEvent $event
     * @return void
     */
    public function handle(MenuPrepareEvent $event)
    {
        /**
         * @var $data array
         */
        $data = $event->getData();


        if (isset($data['items'])) {
            $currentUrl = \Request::url();
            /**
             * @var $item Menu
             */
            foreach ($data['items'] as &$item) {
                if ($item->_hidden == 'N') {
                    $children = $item->children;
                    $active = ($item->getUrl() == $currentUrl || (in_array($item->id, (array)\Request::input('active_ids', []))));
                    if (false == $active) {
                        $active = $this->searchActive($children, $currentUrl);
                    }
                    $item->active = $active;
                }
            }
        }

        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }
        $event->setData($data);
    }

    /**
     * Поиск активного пункта меню в дочерних элементах,
     * метод рекурсивный
     *
     * @param $children
     * @param $currentUrl
     * @return bool
     */
    protected function searchActive($children, $currentUrl)
    {
        foreach ($children as &$item) {
            $children = $item->children;
            $item->active = ($item->getUrl() == $currentUrl || (in_array($item->id, (array)\Request::input('active_ids', []))));
            if (false == $item->active) {
                $item->active = $this->searchActive($children, $currentUrl);
            }
            if ($item->active) {
                return $item->active;
            }
        }

        return false;
    }


}
