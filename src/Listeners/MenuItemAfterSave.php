<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 18.12.2016
 * Time: 23:33
 */

namespace FastDog\Menu\Listeners;


use FastDog\Menu\Models\Menu;
use FastDog\Menu\Events\MenuItemAfterSave as MenuItemAfterSaveEvent;
use Illuminate\Http\Request;

/**
 * После сохранения
 *
 * @package FastDog\Menu\Listeners
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuItemAfterSave
{

    /**
     * @var Request $request
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
     * @param MenuItemAfterSaveEvent $event
     * @return void
     */
    public function handle(MenuItemAfterSaveEvent $event)
    {
        /**
         * @var $item Menu
         */
        $item = $event->getItem();

        /**
         * @var $data array
         */
        $data = $event->getData();

        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }
        $requestData = $this->request->all();
        /**
         * Сохранение дополнительных параметров
         */
        if ((isset($requestData['properties']) && count($requestData['properties']) > 0) && method_exists($item, 'storeProperties')) {
            $item->storeProperties(collect($requestData['properties']));
        }
        /*
         * Сохранение медиа материалов
         */
        if ((isset($requestData['media']) && count($requestData['media']) > 0) && method_exists($item, 'storeMedia')) {
            $item->storeMedia(collect($requestData['media']));
        }

        $event->setData($data);
    }
}
