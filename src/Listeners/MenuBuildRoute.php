<?php

namespace FastDog\Menu\Listeners;

use FastDog\Menu\Http\Controllers\Site\MenuController;
use FastDog\Menu\Menu;
use FastDog\Menu\Models\Page;
use Illuminate\Http\Request;

/**
 *
 * @package FastDog\Menu\Listeners
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuBuildRoute
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * UpdateProfile constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param \FastDog\Menu\Events\MenuBuildRoute $event
     * @return array
     */
    public function handle(\FastDog\Menu\Events\MenuBuildRoute $event): void
    {
        $type = $this->request->input('type.id');

        /** @var array $data */
        $data = $event->getData();

        /** @var Menu $item */
        $item = $event->getItem();

        $data[Menu::DATA] = (is_string($data[Menu::DATA])) ? json_decode($data[Menu::DATA]) : $data[Menu::DATA];

        switch ($type) {
            case Menu::TYPE_MENU:
                $parents = $item->ancestors()->get();

                /** @var $parent Menu */
                foreach ($parents as $parent) {
                    if (!in_array($parent->getRoute(), ['/', '#']) && $parent->{Menu::DEPTH} > 1) {
                        array_push($result, $parent->getRoute());
                    }
                }
                array_push($result, $this->request->input('alias'));
                break;
            case Menu::TYPE_STATIC:
                array_push($result, $this->request->input('data.url'));
                break;
            case Menu::TYPE_ALIAS:

                /** @var $alias Menu */
                $alias = Menu::where(['id' => $this->request->input('data.alias_id')])->first();

                $data[Menu::ROUTE] = null;
                $data[Menu::DATA]->route_data = [
                    'type' => $type,
                    'route' => null,
                    'instance' => null,
                    'alias' => ($alias) ? $alias->getRoute() : null,
                ];
                break;
            case Menu::TYPE_PAGE:
                /** @var $page Page */
                $page = Page::where(['id' => $this->request->input('route_instance.id')])->first();

                $this->request->merge([
                    'route_instance' => $this->request->input('data.route_instance')
                ]);

                $data[Menu::DATA]->route_data = [
                    'type' => $type,
                    'route' => ($page) ? $page->getRoute() : null,
                    'instance' => MenuController::class
                ];
                break;
        }

        // маршрут по умолчанию
        $data[Menu::DATA]->route_data = [
            'type' => $type,
            'instance' => $this->request->input('data.route_instance'),
            'route' => implode('/', $result),
        ];

        if (config('app.debug')) {
            $data['_events_'][] = __METHOD__;
        }

        $event->setData($data);
    }
}
