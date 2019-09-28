<?php

namespace FastDog\Menu\Http\Controllers\Admin;


use FastDog\Core\Form\Interfaces\FormControllerInterface;
use FastDog\Core\Form\Traits\FormControllerTrait;
use FastDog\Core\Http\Controllers\Controller;
use FastDog\Core\Models\DomainManager;
use FastDog\Core\Models\ModuleManager;
use FastDog\Menu\Events\MenuItemAfterSave;
use FastDog\Menu\Events\MenuItemBeforeSave;
use FastDog\Menu\Models\Menu;
use FastDog\Menu\Request\AddMenu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Меню навигации - Форма
 *
 * @package FastDog\Menu\Http\Controllers\Admin
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
        $this->page_title = trans('menu::interface.Меню навигации');
        parent::__construct();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getEditItem(Request $request): JsonResponse
    {
        $this->breadcrumbs->push(['url' => '/menu/index', 'name' => trans('menu::interface.Управление')]);
        $parent_id = \Route::input('parent_id', null);
        $parent = null;

        //Отключаем кэширование в методе Menu::getData(cached)
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
            $this->breadcrumbs->push(['url' => '/menu/list/' . $parent->id, 'name' => $parent->{Menu::NAME}]);
        }

        $this->breadcrumbs->push([
            'url' => false,
            'name' => ($this->item->id > 0) ? $this->item->{Menu::NAME} : trans('menu::forms.general.new_item')
        ]);


        //$result['items'] = Menu::getAll();

        return $this->json($result, __METHOD__);
    }

    /**
     * @param AddMenu $request
     * @return JsonResponse
     */
    public function postAppendMenu(AddMenu $request): JsonResponse
    {
        foreach ($request->input('items', []) as $item) {
            $request->merge([
                'id' => $item['id'],
                'menu_id' => $item['menu_id'],
                'type' => $item['type'],
                'name' => $item['name'],
            ]);
            $this->postMenu($request);
        }

        return $this->json(['success' => true], __METHOD__);
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
            $data[Menu::ALIAS] = Str::slug($data[Menu::NAME]);
        }

        $root = Menu::where([
            'id' => $request->input('menu_id.id'),
        ])->first();

        if (null === $root) {
            $root = Menu::where(function(Builder $query) use ($request) {
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

        // Попытка добавить пункт меню в чужой\общий сайт
        if ($root->{Menu::SITE_ID} !== DomainManager::getSiteId()) {
            if (DomainManager::checkIsDefault() === false) {
                return $this->json([
                    'success' => false,
                    'message' => trans('menu::interface.error.Ошибка выполнения команды, Вам не разрешено добавление элементов в меню') .
                        ' "' . $root->{Menu::NAME} . '"',
                ]);
            }
            if (!$request->has(Menu::SITE_ID . '.id')) {
                $updateData[Menu::SITE_ID] = $root->{Menu::SITE_ID};
            }
        }

        if (!isset($data['id']) || $data['id'] == 0) {
            /** @var Menu $item */
            $item = Menu::create($updateData);

            if ($data['parent_id']) {
                $parent = Menu::find($data['parent_id']);
                if ($parent) {
                    $item->makeLastChildOf($parent);
                }
            }

            $data['id'] = $item->id;
        }

        /** @var $item Menu */
        $item = Menu::find($data['id']);
        $itemData = $item->getData(false);

        // Если изменен родительский элемент, перемещаем
        if (isset($data['menu_id']['id']) && $data['menu_id']['id'] <> $itemData['menu_id']) {
            if ($root) {
                if (isset($data['parent_id']) && $data['parent_id'] > 0) {
                    if ($data['parent_id'] === $item->id) {
                        return $this->json([
                            'success' => false,
                            'message' => trans('menu::interface.error.Ошибка выполнения команды, не верно задан родительский элемент.'),
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
                            'message' => trans('menu::interface.error.Ошибка перемещения пункта меню, не разрешено перемещение пунктов с разными сайтами.'),
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

            // Обновляем параметры пункта меню
            $_data = [];

            \App::make(ModuleManager::class)->getModules()
                ->each(function($data) use (&$_data, $request, $item) {
                    collect($data['module_type'])->each(function($type) use ($data, &$_data, $request, $item) {
                        if ($data['id'] . '::' . $type['id'] === $request->input('type.id')) {
                            if ($data['route'] instanceof \Closure) {
                                $routeData = $data['route']($request, $item);
                                if ($routeData['route'] || (isset($routeData['alias']) && $routeData['alias'])) {
                                    $updateData[Menu::ROUTE] = $routeData['route'];
                                    $_data['route_data'] = $routeData;
                                }
                            }
                        }
                    });
                });


            $_data['meta_title'] = $request->input('data.meta_title', $data[Menu::NAME]);
            $_data['meta_description'] = $request->input('data.meta_description');
            $_data['meta_keywords'] = $request->input('data.meta_keywords');
            $_data['meta_robots'] = $request->input('data.meta_robots');

            $updateData['data'] = json_encode($_data);

            // Изменен код доступа к пункту меню
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

            event(new MenuItemBeforeSave($updateData, $item));

            Menu::where('id', $data['id'])->update($updateData);

            event(new MenuItemAfterSave($result, $item));

            $result['items'][] = $item->getData(false);
//            if ($request->has('set_properties')) { fix me: move to MenuItemAfterSaveListeners Catalog cmp
//                /**
//                 * Обновление свойств фильтра
//                 *
//                 * @var $properties object
//                 */
//                $properties = $request->input('set_properties', []);
//
//                /**
//                 * Обновление своиств фильтра пункта меню
//                 */
//                event(new CatalogCreateProperty($properties, $item));
//            }
        }

        return $this->json($result, __METHOD__);
    }

}
