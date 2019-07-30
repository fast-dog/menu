<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 05.02.2017
 * Time: 13:23
 */

namespace FastDog\Menu\Listeners\Site;


use FastDog\Menu\Config\Entity\DomainManager;
use FastDog\Menu\Events\Site\ExamplePageBeforeRending as ExamplePageBeforeRendingEvent;
use FastDog\Menu\Menu;
use Illuminate\Http\Request;

/**
 * Перед выводом шаблона
 *
 * Событие будет вызвано в публичной части сайта для шаблона views.public.001.modules.menu.example_page.blade.php
 *
 * @package FastDog\Menu\Listeners\Site
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ExamplePageBeforeRending
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
     * @param ExamplePageBeforeRendingEvent $event
     */
    public function handle(ExamplePageBeforeRendingEvent $event)
    {
        /**
         * @var $data array
         */
        $data = $event->getData();

        $key = __METHOD__ . '::' . DomainManager::getSiteId() . '::menu-module-' . $data['module']->id;
        $isRedis = config('cache.default') == 'redis';
        $result = ($isRedis) ? \Cache::tags(['events'])->get($key, null) : \Cache::get($key, null);
        if ($result === null) {
            /**
             * @var $item Menu
             */
            foreach ($data['items'] as &$item) {
                $item->_data = $item->getData();
                $item->_children = $item->children;
            }

            if ($isRedis) {
                \Cache::tags(['events'])->put($key, $data, config('cache.ttl_events', 5));
            } else {
                \Cache::put($key, $data, config('cache.ttl_events', 5));
            }
        } else {
            $data = $result;
        }

        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }
        $event->setData($data);
    }
}
