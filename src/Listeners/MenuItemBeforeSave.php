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
        /**
         * @var $user User
         */
        $user = \Auth::getUser();

        /**
         * @var $data array
         */
        $data = $event->getData();

        if (is_string($data['data'])) {
            $data['data'] = json_decode($data['data']);
        }
        /**
         * @var $item Menu
         */
        $item = $event->getItem();

        /**
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

        /**
         * Парамтеры пакуемые в json объект data
         */
        $packParameters = [
            'type' => 'type.id',
            'template' => 'template',
            'category_id' => 'category_id.id',
            'url' => 'url',
            'route_instance' => 'route_instance',
            'item_id' => 'item_id',
            'form_id' => 'form_id.id',
            'image' => 'image',
            'alias_id' => 'alias_id',
            'alias_menu_id' => 'alias_menu_id',
//            'meta_title' => 'meta_title',
//            'meta_description' => 'meta_description',
//            'meta_keywords' => 'meta_keywords',
//            'meta_robots' => 'meta_robots',
        ];

        $allData = $this->request->all();

        $data['data'] = (array)$data['data'];

        foreach ($packParameters as $name => $id) {
            $data['data'][$name] = $this->request->input($id);
        }

        $data['data'] = json_encode($data['data']);

        $event->setData($data);
    }
}
