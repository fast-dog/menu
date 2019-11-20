<?php

namespace FastDog\Menu\Http\Controllers\Admin;


use Baum\Extensions\Eloquent\Model;
use FastDog\Core\Http\Controllers\Controller;
use FastDog\Core\Models\DomainManager;
use FastDog\Core\Table\Interfaces\TableControllerInterface;
use FastDog\Core\Table\Traits\TableTrait;
use FastDog\Menu\Events\MenuItemsAdminPrepare;
use FastDog\Menu\Events\MenuResources;
use FastDog\Menu\Models\Menu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Меню навигации - Таблица
 *
 * @package FastDog\Menu\Http\Controllers\Admin
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuTableController extends Controller implements TableControllerInterface
{
    use  TableTrait;

    /**
     * Модель по которой будет осуществляться выборка данных
     *
     * @var \FastDog\Menu\Models\Menu|null $model
     */
    protected $model = null;

    /**
     * MenuTableController constructor.
     * @param Menu $model
     */
    public function __construct(Menu $model)
    {
        parent::__construct();

        $this->page_title = trans('menu::interface.Меню навигации');

        $this->model = $model;
        $this->initTable('tree');

    }

    /**
     * Модель, контекст выборок
     *
     * @return  Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Описание структуры колонок таблицы
     *
     * @return Collection
     */
    public function getCols(): Collection
    {
        return $this->table->getCols();
    }

    /**
     * Описание структуры колонок таблицы корневых элементов меню
     * @return Collection
     * @deprecated
     */
    public function getColsRoot(): Collection
    {
        return collect([
            [
                'name' => trans('menu::forms.general.fields.name'),
                'key' => Menu::NAME,
                'domain' => true,
                'extra' => true,
                'link' => 'menu_items',
                'edit_link' => 'menu_item',
                'action' => [
                    'edit' => true,
                    'delete' => true,
                ]
            ],
            [
                'name' => trans('menu::forms.general.fields.created_at'),
                'key' => 'created_at',
                'width' => 150,
                'link' => null,
                'class' => 'text-center',
            ],
            [
                'name' => '#',
                'key' => 'id',
                'link' => null,
                'width' => 80,
                'class' => 'text-center',
            ],
        ]);
    }


    /**
     * Список элементов первого уровня
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postMenuRoots(Request $request)
    {
        $result = [
            'success' => true,
            'items' => [],
            'access' => $this->getAccess(),
            'filters' => $this->getFilters(),
            'cols' => $this->getColsRoot(),
        ];
        $this->breadcrumbs->push([
            'url' => false,
            'name' => trans('menu::interface.Список доступных меню')
        ]);
        $scope = 'active';

        $root = Menu::where(function(Builder $query) {
            $query->where('lft', 1);
            $query->where(Menu::SITE_ID, DomainManager::getSiteId());
        })->first();

        if (!$root) {
            Menu::create([
                'parent_id' => 0,
                Menu::NAME => trans('menu::menu.Корневой элемент'),
                Menu::ROUTE => '#',
                Menu::SITE_ID => DomainManager::getSiteId(),
                'lft' => 1,
                'rgt' => 2,
            ]);
        }
        $limitDepth = ($request->input('include_children', 'N') === 'Y') ? 100 : 1;
        $prepareName = ($request->input('only_items', 'N') === 'Y');

        Menu::where('lft', 1)
            ->get()
            ->each(function(Menu $root) use (&$result, $limitDepth, $request, $scope, $prepareName) {
                if ($root->{Menu::DEPTH} == 0 && $root->{Menu::SITE_ID} == DomainManager::getSiteId()) {
                    $result['set_root_id'] = $root->id;
                }
                $root->descendantsAndSelf()->withoutSelf()->limitDepth($limitDepth)
                    ->where(function($query) use ($request, &$scope) {
                        $this->setFilters($query);
                    })
                    ->$scope()
                    ->get()
                    ->each(function(Menu $item) use ($prepareName, &$result) {
                        if ($item->{Menu::DEPTH} > 0) {
                            $countPublish = $item->getCountPublish();
                            $data = $item->getData(false);
                            $repeatLevel = ($data[Menu::DEPTH] - 1);
                            if ($prepareName && $repeatLevel > 0) {
                                $data[Menu::NAME] = str_repeat('┊ ', $repeatLevel) . $data[Menu::NAME];
                            }

                            $data[Menu::DEPTH] = 0;
                            $data['created_at'] = $item->created_at->format('d.m.y H:i');
                            array_push($result['items'], $data);
                        }
                    });
            });

        event(new MenuItemsAdminPrepare($result, $items));

        return $this->json($result, __METHOD__);
    }

    /**
     * Список дочерних элементов меню первого уровня
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postMenuList(Request $request)
    {
        $result = [
            'success' => true,
            'items' => [],
            'access' => $this->getAccess(),
            'filters' => $this->getFilters(),
            'cols' => $this->getCols(),
        ];

        $this->breadcrumbs->push([
            'url' => '/menu/index',
            'name' => trans('menu::interface.Меню')
        ]);


        /** @var $root Menu */
        $root = Menu::find($request->input('filter.root', \Route::input('root_id', 0)));

        if (\Route::input('root_id', 0) == 0 && !$root) {
            $root = Menu::where([
                Menu::SITE_ID => DomainManager::getSiteId(),
                'lft' => 1
            ])->first();
        }
        $scope = 'default';

        if ($root) {
            $this->breadcrumbs->push([
                'url' => false,
                'name' => trans('menu::interface.Меню') . ': ' . $root->{Menu::NAME}
            ]);
            $result['item'] = ['id' => $root->id,];


            /** @var $items Collection */
            $items = $root->descendantsAndSelf()->withoutSelf()
                ->where(function(Builder $query) use ($request, &$scope) {
                    $this->setFilters($query);
                })
                //->$scope()
                ->orderBy($request->input('order_by', 'lft'), $request->input('direction', 'asc'))
                ->paginate($request->input('limit', self::PAGE_SIZE));


            $items->each(function(Menu $item) use (&$result) {
                array_push($result['items'], [
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
                ]);
            });

            $this->_getCurrentPaginationInfo($request, $items, $result);

            event(new MenuItemsAdminPrepare($result, $items));

        }

        return $this->json($result, __METHOD__);
    }


    /**
     * Обновление параметров материалов
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function postMenuUpdate(Request $request)
    {
        $result = ['success' => true, 'items' => []];

        try {
            $this->updatedModel($request->all(), Menu::class);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ], __METHOD__);
        }
        return $this->json($result, __METHOD__);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function items(Request $request): JsonResponse
    {
        // TODO: Implement items() method.
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function postMenuLoad(Request $request): JsonResponse
    {
        $result = [
            'success' => true,
            'roots' => [],
            'resource' => []
        ];

        $roots = $this->postMenuRoots($request)->getOriginalContent();
        $result['roots'] = ($roots['success']) ? $roots['items'] : [];

        event(new MenuResources($result));

        return $this->json($result, __METHOD__);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function reorder(Request $request): JsonResponse
    {
        return $this->reorderTree($request);
    }

}
