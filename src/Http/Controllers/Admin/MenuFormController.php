<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 17.04.2018
 * Time: 15:30
 */

namespace FastDog\Menu\Controllers\Admin;

use App\Core\BaseModel;
use App\Core\Form\Interfaces\FormControllerInterface;
use App\Core\Form\Traits\FormControllerTrait;
use App\Core\Module\ModuleInterface;
use App\Core\Module\ModuleManager;
use App\Http\Controllers\Controller;
use FastDog\Menu\Config\Entity\DomainManager;
use FastDog\Menu\Events\CatalogCreateProperty;
use FastDog\Menu\Events\MenuItemAfterSave;
use FastDog\Menu\Events\MenuItemBeforeSave;
use FastDog\Menu\Menu;
use FastDog\Menu\Request\AddMenu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Меню навигации - Форма
 *
 * @package FastDog\Menu\Controllers\Admin
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuFormController extends Controller implements FormControllerInterface
{
    use FormControllerTrait;

    /**
     * MenuFormController constructor.
     * @param Menu $model
     */
    public function __construct(Menu $model)
    {
        $this->model = $model;
        $this->page_title = trans('app.Меню навигации');
        parent::__construct();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getEditItem(Request $request): JsonResponse
    {
        $this->breadcrumbs->push(['url' => '/menu/index', 'name' => trans('app.Управление меню навигации')]);
        $parent_id = \Route::input('parent_id', null);
        $parent = null;
        /**
         * Отключаем кэширование в методе Menu::getData(cached)
         */
        config([
            'cache.enabled' => false,
        ]);
        $result = $this->getItemData($request);

        $result['item'] = array_first($result['items']);

        if ($parent_id) {
            $parent = Menu::find($parent_id);
        } else {
            $parent = $this->item->parent;
        }

        if ($parent) {
            $this->breadcrumbs->push(['url' => '/menu/list/' . $parent->id, 'name' => $parent->{BaseModel::NAME}]);
        }
        if ($this->item) {
            $this->breadcrumbs->push(['url' => false, 'name' => $this->item->{BaseModel::NAME}]);
        }

        //$result['items'] = Menu::getAll();

        return $this->json($result, __METHOD__);
    }

    /**
     * Сохранение элемента
     *
     * @param AddMenu $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postMenu(AddMenu $request): JsonResponse
    {
        $result = ['success' => true];
        $data = $request->all();

        $data['parent_id'] = $request->input('parent_id.id');
        $data[Menu::STATE] = $request->input(Menu::STATE . '.id');


        if ($data[Menu::ALIAS] == '' && $data['id'] == 0) {
            $data[Menu::ALIAS] = \Slug::make($data[Menu::NAME]);
        }

        $root = Menu::where([
            'id' => $request->input('menu_id.id'),
        ])->first();

        if (null === $root) {
            $root = Menu::where(function (Builder $query) use ($request) {
                $query->where('lft', 1);
                $query->where(Menu::SITE_ID, $request->input(Menu::SITE_ID . '.id', DomainManager::getSiteId()));
            })->first();
        }

        $updateData = [
            Menu::NAME => $data[Menu::NAME],
            Menu::ALIAS => $data[Menu::ALIAS],
            Menu::ROUTE => \DB::raw('null'),
            Menu::SITE_ID => $request->input(Menu::SITE_ID . '.id', DomainManager::getSiteId()),
            Menu::DATA => json_encode($data[Menu::DATA]),
        ];

        /**
         * Попытка добавить пункт меню в чужой\общий сайт
         */
        if ($root->{Menu::SITE_ID} !== DomainManager::getSiteId()) {
            if (DomainManager::checkIsDefault() === false) {
                return $this->json([
                    'success' => false,
                    'message' => trans('app.Ошибка выполнения команды, Вам не разрешено добавление элементов в меню') .
                        ' "' . $root->{Menu::NAME} . '"',
                ]);
            }
            if (!$request->has(Menu::SITE_ID . '.id')) {
                $updateData[Menu::SITE_ID] = $root->{Menu::SITE_ID};
            }
        }

        if (!isset($data['id']) || $data['id'] == 0) {
            $item = Menu::create($updateData);
            $data['id'] = $item->id;
        }
        /**
         * @var $item Menu
         */
        $item = Menu::find($data['id']);
        $itemData = $item->getData(false);
        /**
         * Если изменен родительский элемент, перемещаем
         */

        if (isset($data['menu_id']['id']) && $data['menu_id']['id'] <> $itemData['menu_id']) {
            if ($root) {
                if (isset($data['parent_id']) && $data['parent_id'] > 0) {
                    if ($data['parent_id'] === $item->id) {
                        return $this->json([
                            'success' => false,
                            'message' => trans('app.Ошибка выполнения команды, не верно задан родитель.'),
                        ]);
                    }
                    $parent = Menu::find($data['parent_id']);
                    $item->makeLastChildOf($parent);
                } else {
                    if ($itemData[Menu::SITE_ID] == $root->{Menu::SITE_ID}) {
                        if ($data['menu_id'] <> $data['parent_id']) {
                            $item = $item->makeLastChildOf($root);
                        }
                    } else {
                        return $this->json([
                            'success' => false,
                            'message' => trans('app.Ошибка перемещения пункта меню, не разрешено перемещение пунктов с разными сайтами'),
                        ]);
                    }
                }
            }
        }

        if ((isset($data['parent_id']) && $data['parent_id']) > 0 &&
            ($data['parent_id'] <> $itemData['parent_id'])) {
            $parent = Menu::find($data['parent_id']);
            $item->makeLastChildOf($parent);
        }

        if ($item) {
            $item = Menu::find($item->id);
            $itemData = $item->getData(false);
            /**
             * Обновляем параметры пункта меню
             */
            $_data = [];

            /**
             * @var $moduleManager ModuleManager
             */
            $moduleManager = \App::make('ModuleManager');
            $modules = $moduleManager->getModules();

            /**
             * @var $module ModuleInterface
             */
            foreach ($modules as $module) {
                /**
                 * Получаем ссылку согласно настройкам модуля
                 */
                $types = $module->getMenuType();

                if ($types !== null) {
                    foreach ($types as $type) {
                        $type = (object)$type;
                        if (isset($type->id)) {
                            if ($type->id === $request->input('type.id')) {
                                $_data['module_data'] = $module->getConfig();

                                $_data['module_data']->menu = [$type];//<-- Для отображения типа меню в таблице администрирования

                                /**
                                 * Получаем коректный набор значений для инициализации маршрута,
                                 * массив должен иметь вид:
                                 * [
                                 *      'instance'=> Controller::class,
                                 *      'route'=> 'path/to/data/route[?.html]'
                                 * ]
                                 */

                                $routeData = $module->getMenuRoute($request, $item);

                                if ($routeData['route'] || (isset($routeData['alias']) && $routeData['alias'])) {
                                    $updateData[Menu::ROUTE] = $routeData['route'];
                                    $_data['route_data'] = $routeData;
                                }
                            }
                        } else {
                            dd($type);
                        }
                    }
                }
            }
            $_data['meta_title'] = $request->input('data.meta_title');
            $_data['meta_description'] = $request->input('data.meta_description');
            $_data['meta_keywords'] = $request->input('data.meta_keywords');
            $_data['meta_robots'] = $request->input('data.meta_robots');

            $updateData['data'] = json_encode($_data);
            /**
             * Изменен код доступа к пункту меню
             */
            if ($updateData[Menu::SITE_ID] <> $itemData[Menu::SITE_ID]) {
                if (DomainManager::checkIsDefault()) {
                    $ids = [];
                    foreach ($item->children as $child) {
                        array_push($ids, $child->id);
                    }
                    if (count($ids)) {
                        Menu::whereIn('id', $ids)->update([
                            Menu::SITE_ID => $updateData[Menu::SITE_ID],
                        ]);
                    }
                }
            }

            \Event::fire(new MenuItemBeforeSave($updateData, $item));

            Menu::where('id', $data['id'])->update($updateData);

            \Event::fire(new MenuItemAfterSave($result, $item));
            if ($request->has('set_properties')) {
                /**
                 * Обновление свойств фильтра
                 *
                 * @var $properties object
                 */
                $properties = $request->input('set_properties', []);

                /**
                 * Обновление своиств фильтра пункта меню
                 */
                \Event::fire(new CatalogCreateProperty($properties, $item));
            }
            /**
             * Обновление шаблона
             */
            if ($request->has('template_raw')) {
                $template = $request->input('template.id');
                if (view()->exists($template)) {
                    $path = view($template)->getPath();
                   // \File::put($path, $request->input('template_raw'));
                }
            }

        }

        return $this->json($result, __METHOD__);
    }

}
