<?php

namespace FastDog\Menu\Listeners;

use FastDog\Menu\Http\Controllers\Site\MenuController;
use FastDog\Menu\Models\Page;
use Illuminate\Http\Request;

/**
 *
 * @package FastDog\Menu\Listeners
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuResources
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
     * @param \FastDog\Menu\Events\MenuResources $event
     */
    public function handle(\FastDog\Menu\Events\MenuResources $event)
    {

        $data = $event->getData();

        if (!$data['resource']) {
            $data['resource'] = [];
        }
        $data['resource']['pages'] = [
            'id' => 'pages',
            'name' => trans('menu::interface.Страницы'),
            'items' => collect([]),
        ];

        Page::all()->each(function(Page $item) use (&$data) {
            $data['resource']['pages']['items']->push([
                'id' => 'menu::page',
                'name' => $item->{Page::NAME},
                'sort' => (int)$item->{Page::SORT},
                'data' => [
                    'route_instance' => [
                        'id' => $item->id,
                        'value' => $item->{Page::NAME},
                        'instance' => MenuController::class
                    ],
                    'template' => [
                        'id' => 'theme#000::menu.page.default',
                        'name' => trans('menu::templates.default')
                    ]
                ]
            ]);
        });

        if (config('app.debug')) {
            $data['_events_'][] = __METHOD__;
        }

        $event->setData($data);
    }
}
