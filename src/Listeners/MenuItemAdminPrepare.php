<?php

namespace FastDog\Menu\Listeners;

use FastDog\Core\Models\ModuleManager;
use FastDog\Media\Models\GalleryItem;
use FastDog\Menu\Events\MenuItemAdminPrepare as MenuItemAdminPrepareEvent;
use FastDog\Menu\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Обработка данных в разделе администрирования
 *
 * Событие добавляет дополнительные поля параметров в модель в случае их отсутствия
 *
 * @package FastDog\Menu\Listeners
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuItemAdminPrepare
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
     * @param MenuItemAdminPrepareEvent $event
     * @return void
     */
    public function handle(MenuItemAdminPrepareEvent $event)
    {
        /**
         * @var $moduleManager ModuleManager
         */
        $moduleManager = \App::make(ModuleManager::class);

        /**
         * @var $item Menu
         */
        $item = $event->getItem();

        /**
         * @var $data array
         */
        $data = $event->getData();

        $data['item']['el_finder'] = [
            GalleryItem::PARENT_TYPE => GalleryItem::TYPE_MENU,
            GalleryItem::PARENT_ID => (isset($item->id)) ? $item->id : 0,
        ];
        $data['item']['files_module'] = ($moduleManager->hasModule('FastDog\Menu\Media\Media')) ? 'Y' : 'N';

        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }

        $data['properties'] = $item->properties();
        $data['media'] = $item->getMedia();


        if (!isset($data['item']['id'])) {
            $data['item']['id'] = 0;
        }
        unset($data['data']->module_data);


        /**
         * Исправление значений под выпадающие списки, предполагается наличие корректной структуры вида:
         *
         *  {
         *      id:'id',
         *      name:'name'
         *  }
         */
        // Тип
        $data['data']->type = Arr::first(array_filter(Menu::getTypes(), function ($element) use ($data) {
            return $element->id == $data['type'];
        }));
        $menuRoots = Menu::getRoots();

        // Меню
        $data['menu_id'] = Arr::first(array_filter($menuRoots, function ($element) use ($data) {

            return $element['id'] == $data['menu_id'];
        }));


        $allMenu = Menu::getAll();
        if (isset($allMenu[$data['menu_id']['id']])) {
            //Родительский элемент
            $data['parent_id'] = Arr::first(array_filter($allMenu[$data['menu_id']['id']], function ($element) use ($data) {
                return $element['id'] == $data['parent_id'];
            }));
        }
        //Категория материалов
        if (isset($data['data']->category_id) && $data['data']->type->id == 'content_blog') {
            $data['data']->category_id = $data['category_id'] = array_first(array_filter(ContentCategory::getCategoryList(true), function ($element) use ($data) {

                return $element['id'] == $data['data']->category_id;
            }));
        }
        //Категория каталога

        if (isset($data['data']->category_id) && in_array($data['data']->type->id, ['catalog_categories', 'static'])) {
            $data['data']->category_id = $data['category_id'] = array_first(array_filter(Category::getCategoryList(true), function ($element) use ($data) {

                return $element['id'] == $data['data']->category_id;
            }));
        }
        //Псевдоним меню
        if (isset($data['data']->alias_menu_id->id)) {
            $data['data']->alias_menu_id = $data['alias_menu_id'] =
                array_first(array_filter($menuRoots, function ($element) use ($data) {
                    return (int)$element['id'] == (int)$data['data']->alias_menu_id->id;
            }));
        }

        // Форма
        if (isset($data['data']->form_id)) {
            /**
             * @var $form \FastDog\Menu\Form\Form
             */
            $form = $moduleManager->getInstance(\FastDog\Menu\Form\Form::class);

            /**
             * @var $entityForm FormInterface
             */
            $entityForm = Arr::first(array_filter($form->getList(), function (FormInterface $element) use ($data) {

                return $element->getAction() == $data['data']->form_id;
            }));
            if ($entityForm) {
                $data['data']->form_id = $data['form_id'] = [
                    'id' => $entityForm->getAction(),
                    'name' => $entityForm->getName(),
                ];
            }
        };

        $event->setData($data);
    }
}
