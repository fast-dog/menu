<?php

namespace FastDog\Menu\Listeners;


use FastDog\Core\Models\DomainManager;
use FastDog\Core\Models\FormFieldTypes;
use FastDog\Menu\Models\Menu;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use FastDog\Core\Events\GetComponentTypeFields as GetComponentTypeEvent;


/**
 * Class GetComponentTypeFields
 * @package FastDog\Menu\Listeners
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class GetComponentTypeFields
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

        array_push($data, [
            'id' => 'item_id',
            'type' => FormFieldTypes::TYPE_SELECT,
            'name' => 'item_id',
            'form_group' => false,
            'label' => trans('menu::modules.menu_items'),
            'items' => $this->getMenuTree(),
            'option_group' => false,
            'expression' => 'function(item){ return (item.type.id == "menu::item") }',
        ]);


        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }
        $event->setData($data);
    }

    /**
     * Получение списка созданных меню с учетом доступа по домену
     *
     * @return array
     */
    protected function getMenuTree(): array
    {
        $menuTree = [];
        Menu::where(function(Builder $query) {
            $query->where('lft', 1);
            if (DomainManager::checkIsDefault() == false) {
                $query->whereIn(Menu::SITE_ID, DomainManager::getScopeIds());
            }
        })->get()->each(function(Menu $root) use (&$menuTree) {
            $name = $root->getName();
            $name = str_repeat('┊ ', $root->{Menu::DEPTH}) . $name;
            array_push($menuTree, [
                'id' => $root->id,
                'name' => $name . ' (#' . $root->{Menu::SITE_ID} . ')',
            ]);
            $root->descendantsAndSelf()->withoutSelf()->where(function($query) {

            })->get()->each(function(Menu $menuItem) use (&$menuTree) {
                $name = $menuItem->getName();
                $name = str_repeat('┊ ', $menuItem->{Menu::DEPTH}) . $name;
                array_push($menuTree, [
                    'id' => $menuItem->id,
                    'name' => $name,
                ]);
            });
        });

        return $menuTree;
    }
}