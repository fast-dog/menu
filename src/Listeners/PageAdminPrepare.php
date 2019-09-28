<?php

namespace FastDog\Menu\Listeners;

use FastDog\Core\Models\ModuleManager;
use FastDog\Media\Models\GalleryItem;
use FastDog\Menu\Events\PageAdminPrepare as PageAdminPrepareEvent;
use FastDog\Menu\Menu;
use Illuminate\Http\Request;

/**
 * Обработка данных в разделе администрирования
 *
 * Событие добавляет дополнительные поля параметров в модель в случае их отсутствия
 *
 * @package FastDog\Menu\Listeners
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class PageAdminPrepare
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
     * @param PageAdminPrepareEvent $event
     * @return void
     */
    public function handle(PageAdminPrepareEvent $event)
    {
        /**
         * @var $moduleManager ModuleManager
         */
        $moduleManager = \App::make(ModuleManager::class);

        /**
         * @var $item Menu
         */
        $item = $event->getItem();

        /**
         * @var $data array
         */
        $data = $event->getData();

        $data['item']['el_finder'] = [
            GalleryItem::PARENT_TYPE => GalleryItem::TYPE_MENU,
            GalleryItem::PARENT_ID => (isset($item->id)) ? $item->id : 0,
        ];
        $data['item']['files_module'] = ($moduleManager->hasModule('media')) ? 'Y' : 'N';

        $data['properties'] = $item->properties();
        $data['media'] = $item->getMedia();


        if (!isset($data['item']['id'])) {
            $data['item']['id'] = 0;
        }
        unset($data['data']->module_data);

        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }

        $event->setData($data);
    }
}
