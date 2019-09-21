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
        $items = $event->getItem();
        $items = $items->toHierarchy();

        if ($this->request->input('new', 1) == 1) {
            $data['items'] = [];
            $items->each(function($item) use (&$data) {
                array_push($data['items'], [
                    'id' => $item->id,
                    'name' => $item->{Menu::NAME},
                    'text' => $item->{Menu::NAME},
                    'depth' => ($item->{Menu::DEPTH} - 1),
                    'alias' => $item->{Menu::ALIAS},
                    'site_id' => $item->{Menu::SITE_ID},
                    'route' => $item->{Menu::ROUTE},
                    'parent_id' => $item->{'parent_id'},
                    Menu::STATE => $item->{Menu::STATE},
                    'type' => $item->getType(),
                    'checked' => false,
                    'extra' => trans('app.Тип') . ': ' . $item->getExtendType(),
                    'children' => $this->getShildren($item)
                ]);
            });
        }

        foreach ($data['items'] as &$item) {
            $item['suffix'] = DomainManager::getDomainSuffix($item[Menu::SITE_ID]);
        }

        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }

        $event->setData($data);
    }

    /**
     * @param $item
     * @return array
     */
    protected function getShildren($item): array
    {
        $result = [];
        if ($item->children) {
            $item->children->each(function(\FastDog\Menu\Models\Menu $child) {
                array_push($result, [
                    'id' => $child->id,
                    'name' => $child->{Menu::NAME},
                    'text' => $child->{Menu::NAME},
                    'depth' => ($child->{Menu::DEPTH} - 1),
                    'alias' => $child->{Menu::ALIAS},
                    'site_id' => $child->{Menu::SITE_ID},
                    'route' => $child->{Menu::ROUTE},
                    'parent_id' => $child->{'parent_id'},
                    Menu::STATE => $child->{Menu::STATE},
                    'type' => $child->getType(),
                    'checked' => false,
                    'extra' => trans('app.Тип') . ': ' . $child->getExtendType(),
                    'children' => $this->getShildren($child)
                ]);
            });
        }
        return $result;
    }
}
