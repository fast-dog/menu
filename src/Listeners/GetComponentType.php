<?php

namespace FastDog\Menu\Listeners;


use FastDog\Core\Models\Components;
use FastDog\Menu\Models\Menu;
use Illuminate\Http\Request;
use FastDog\Core\Events\GetComponentType as GetComponentTypeEvent;
use Illuminate\Support\Arr;

/**
 * Class GetComponentType
 * @package FastDog\Menu\Listeners
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class GetComponentType
{
    /**
     * @var Request
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
     * @param GetComponentTypeEvent $event
     */
    public function handle(GetComponentTypeEvent $event)
    {
        $data = $event->getData();

        $paths = Arr::first(config('view.paths'));

        array_push($data, [
            'id' => 'menu',
            'instance' => Menu::class,
            'name' => trans('menu::interface.Меню навигации'),
            'items' => [
                [
                    'id' => 'menu',
                    'name' => trans('menu::interface.Меню') . ' :: ' . trans('menu::modules.menu'),
                    'templates' => Components::getTemplates($paths . '/vendor/fast_dog/menu/components/*.blade.php'),
                ],
            ],
        ]);


        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }
        $event->setData($data);
    }
}