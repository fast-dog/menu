<?php

namespace FastDog\Menu\Listeners;

use FastDog\Core\Models\Notifications;
use FastDog\Menu\Events\MenuBuildRoute;
use FastDog\Menu\Models\Menu;
use FastDog\Menu\Events\MenuItemBeforeSave as MenuItemBeforeSaveEvent;
use FastDog\User\Models\User;
use Illuminate\Http\Request;
use FastDog\Core\Models\ModuleManager;

/**
 * Перед сохранением
 *
 * Проверка дочерних пунктов меню и исправление их маршрутов
 *
 * @package FastDog\Menu\Listeners
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuItemBeforeSave
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
     * @param MenuItemBeforeSaveEvent $event
     * @return void
     */
    public function handle(MenuItemBeforeSaveEvent $event)
    {
        /** @var $user User */
        $user = auth()->getUser();

        /**  @var $data array */
        $data = $event->getData();

        /** @var $item Menu */
        $item = $event->getItem();

        /*
         * Передаем флаг обновления канонических ссылок
         */
        if ($data[Menu::ALIAS] !== $item->{Menu::ALIAS}) {
            $this->request->merge([
                'update_canonical' => 'Y',
            ]);
            /**
             * Проверка дочерних пунктов меню и исправление их маршрутов
             */
            $children = $item->getDescendants();
            /**
             * @var $menuItem Menu
             */
            foreach ($children as $menuItem) {
                $menuItem->fixRoute($data[Menu::ALIAS], $item->{Menu::ALIAS});
                Menu::where('id', $menuItem->id)->update([
                    Menu::ROUTE => $menuItem->getRoute(),
                ]);
                $menuItem = Menu::find($menuItem->id);
                Notifications::add([
                    'type' => Notifications::TYPE_CHANGE_ROUTE,
                    'message' => 'При изменение псевдонима пункта меню <a href="/{ADMIN}/#!/menu/' . $item->id .
                        '" target="_blank">#' . $item->id . '</a> обновлен маршрут меню ' .
                        '<a href="/{ADMIN}/#!/menu/' . $menuItem->id . '" target="_blank">#' .
                        $menuItem->id . '</a>',
                ]);
            }
        }

         event(new MenuBuildRoute($data, $item));

//        app()->make(ModuleManager::class)->getModules()
//            ->each(function($__data) use (&$data, $item) {
//                collect($__data['module_type'])->each(function($type) use ($__data, &$data, $item) {
//                    if ($__data['id'] . '::' . $type['id'] === $this->request->input('type.id')) {
//                        if ($__data['route'] instanceof \Closure) {
//                            $routeData = $__data['route']($this->request, $item);
//                            if ($routeData['route'] || (isset($routeData['alias']) && $routeData['alias'])) {
//                                $data[Menu::ROUTE] = $routeData['route'];
//                                $data['data'] = (is_string($data['data'])) ? json_decode($data['data']) : $data['data'];
//                                $data['data']->route_data = $routeData;
//                            }
//                        }
//                    }
//                });
//            });

        $data['data'] = (!is_string($data['data'])) ? json_encode($data['data']) : $data['data'];

        $event->setData($data);
    }
}
