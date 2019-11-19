<?php
namespace FastDog\Menu\Listeners;

use FastDog\Core\Models\Notifications;
use FastDog\Menu\Models\Menu;
use FastDog\Menu\Events\MenuItemBeforeSave as MenuItemBeforeSaveEvent;
use FastDog\User\Models\User;
use Illuminate\Http\Request;

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

        $event->setData($data);
    }
}
