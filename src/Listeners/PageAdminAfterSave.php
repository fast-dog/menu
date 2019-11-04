<?php

namespace FastDog\Menu\Listeners;

use FastDog\Menu\Events\PageAdminAfterSave as EventPageAdminAfterSave;

use FastDog\Menu\Models\Page;
use FastDog\User\Models\User;
use Illuminate\Http\Request;

/**
 * После сохранения
 *
 * Исправление маршрутов меню, создание\обновление поискового индекса
 *
 * @package FastDog\Menu\Listeners
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class PageAdminAfterSave
{
    /**
     * @var Request $request
     */
    protected $request;

    /**
     * ContentAdminPrepare constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param EventPageAdminAfterSave $event
     * @return void
     */
    public function handle(EventPageAdminAfterSave $event)
    {
        /**
         * @var $user User
         */
        // $user = \Auth::getUser();

        /**
         * @var $item Page
         */
        $item = $event->getItem();

        /**
         * @var $data array
         */
        $data = $event->getData();

        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }
        if (is_string($data['data'])) {
            $data['data'] = json_decode($data['data']);
        }

        /*
         * Сохранение дополнительных параметров
         */
        if ((isset($data['properties']) && count($data['properties']) > 0) && method_exists($item, 'storeProperties')) {
            $item->storeProperties(collect($data['properties']));
        }

        /*
         * Сохранение медиа материалов
         */
        if ((isset($data['media']) && count($data['media']) > 0) && method_exists($item, 'storeMedia')) {
            $item->storeMedia(collect($data['media']));
        }

        if (is_array($data['data'])) {
            $data['data'] = (object)$data['data'];
        }

        if (!isset($data['data']->{'meta_search_keywords'})) {
            $data['data']->{'meta_search_keywords'} = '';
        }

        $event->setData($data);
    }
}
