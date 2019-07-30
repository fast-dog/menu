<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 17.04.2018
 * Time: 11:11
 */

namespace FastDog\Menu\Controllers\Admin;

use App\Core\Module\ModuleInterface;
use App\Core\Module\ModuleManager;
use App\Core\Table\Interfaces\TableControllerInterface;
use App\Core\Table\Traits\TableTrait;
use App\Http\Controllers\Controller;
use FastDog\Menu\Config\Entity\DomainManager;
use FastDog\Menu\Models\Menu;
use FastDog\Menu\Events\MenuItemsAdminPrepare;
use Baum\Extensions\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Меню навигации - Таблица
 *
 * @package FastDog\Menu\Controllers\Admin
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuTableController extends Controller implements TableControllerInterface
{
    use  TableTrait;

    /**
     * Имя родительского списка доступа
     *
     * из за реализации ACL в пакете kodeine/laravel-acl
     * нужно использовать имя верхнего уровня: action.__CLASS__::SITE_ID::access_level
     *
     * @var string $accessKey
     */
    protected $accessKey = '';


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

        $this->page_title = trans('app.Меню навигации');

        $this->model = $model;
        $this->initTable('tree');

        $this->accessKey = strtolower(\FastDog\Menu\Menu::class) . '::' . DomainManager::getSiteId() . '::guest';
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
     */
    public function getColsRoot(): Collection
    {
        return collect([
            [
                'name' => trans('app.Название'),
                'key' => Menu::NAME,
                'domain' => true,
                'extra' => true,
                'link' => 'menu_item',
            ],
            [
                'name' => '',
                'key' => 'children_publish',
                'width' => 50,
                'link' => 'menu_items_child',
                'class' => 'text-center',
            ],
            [
                'name' => '',
                'key' => 'children_count',
                'width' => 50,
                'link' => null,
                'class' => 'text-center',
            ],
            [
                'name' => trans('app.Дата'),
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
        $this->breadcrumbs->push(['url' => false, 'name' => trans('app.Управление меню навигации')]);
        $scope = 'active';

        $root = Menu::where(function (Builder $query) {
            $query->where('lft', 1);

            $query->where(Menu::SITE_ID, DomainManager::getSiteId());

        })->first();

        if (!$root) {
            Menu::create([
                'parent_id' => 0,
                Menu::NAME => trans('app.Корневой элемент') . '#' . DomainManager::getSiteId(),
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
            ->each(function (Menu $root) use (&$result, $limitDepth, $request, $scope, $prepareName) {
                if ($root->{Menu::DEPTH} == 0 && $root->{Menu::SITE_ID} == DomainManager::getSiteId()) {
                    $result['set_root_id'] = $root->id;
                }
                $root->descendants()->limitDepth($limitDepth)
                    ->where(function ($query) use ($request, &$scope) {
                        $this->setFilters($query);
                    })
                    ->$scope()
                    ->get()
                    ->each(function (Menu $item) use ($prepareName, &$result) {
                        if ($item->{Menu::DEPTH} > 0) {
                            $countPublish = $item->getCountPublish();
                            $data = $item->getData(false);
                            $repeatLevel = ($data[Menu::DEPTH] - 1);
                            if ($prepareName && $repeatLevel > 0) {
                                $data[Menu::NAME] = str_repeat('┊ ', $repeatLevel) . $data[Menu::NAME];
                            }
                            $data['children_count'] = '<span class="badge badge-danger">';
                            if (($data['total_children'] - $countPublish) > 0) {
                                $data['children_count'] .= ($data['total_children'] - $countPublish);
                            } else {
                                $data['children_count'] .= 0;
                            }
                            $data['children_count'] .= '</span>';
                            $data['children_publish'] = '<span class="badge badge-primary">' . ($countPublish) . '</span>';

                            $data[Menu::DEPTH] = 0;
                            $data['created_at'] = $item->created_at->format('d.m.y H:i');
                            array_push($result['items'], $data);
                        }
                    });
            });

        \Event::fire(new MenuItemsAdminPrepare($result, $items));

        if ($request->input('only_items', 'N') === 'Y') {
            unset($result['breadcrumbs'], $result['page_title'], $result['access']);
        }
        /**
         * @var $moduleManager ModuleManager
         */
        $moduleManager = \App::make(ModuleManager::class);

        //включить в ответ возможные шаблоны модуля
        if ($request->input('include_templates', 'N') === 'Y') {
            $paths = array_first(\Config::get('view.paths'));
            /**
             * @var  $module ModuleInterface
             */
            $module = $moduleManager->getInstance(Menu::class);
            $result['templates'] = $module->getTemplates($paths . '/modules/menu/*.blade.php');
        }

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
        $result = ['success' => true, 'items' => [],
            'access' => $this->getAccess(),
            'filters' => $this->getFilters(),
            'cols' => [
                [
                    'name' => trans('app.Название'),
                    'key' => Menu::NAME,
                    'domain' => true,
                    'extra' => true,
                    'link' => 'menu_item',
                ],
                [
                    'name' => trans('app.Дата'),
                    'key' => 'created_at',
                    'width' => 150,
                    'link' => null,
                    'extra' => false,
                    'class' => 'text-center',
                ],
                [
                    'name' => '#',
                    'key' => 'id',
                    'link' => null,
                    'width' => 80,
                    'extra' => false,
                    'class' => 'text-center',
                ],
            ],
        ];
        $this->breadcrumbs->push(['url' => '/menu/index', 'name' => trans('app.Управление меню навигации')]);
        /**
         * @var $root Menu
         */
        $root = Menu::find($request->input('filter.root', 0));

        $scope = 'default';

        if ($root) {
            $this->breadcrumbs->push(['url' => false, 'name' => $root->{Menu::NAME}]);
            $result['item'] = [
                'id' => $root->id,
            ];

            switch ($request->input('view', 'table')) {
                case 'table':
            /**
             * @var $items Collection
             */
            $items = $root->descendantsAndSelf()->withoutSelf()
                ->where(function ($query) use ($request, &$scope) {
                    $this->setFilters($query);
                })
                //->$scope()
                ->orderBy($request->input('order_by', 'lft'), $request->input('direction', 'asc'))
                ->paginate($request->input('limit', self::PAGE_SIZE));

            $items->each(function (Menu $item) use (&$result) {

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

            \Event::fire(new MenuItemsAdminPrepare($result, $items));
                    break;
                case 'tree':
                    /**
                     * @var $root Menu
                     */
                    $root = Menu::find($request->input('filter.root'));
                    $scope = 'active';

                    if ($root) {
                        $result['root'] = $root->id;
                        $result['text'] = $root->{Menu::NAME};

                        $items = $root->descendantsAndSelf()->where(function ($query) use ($request, &$scope) {
                            $this->_getMenuFilter($query, $request, $scope, Menu::class);
                        });//->$scope();
                        /**
                         * @var $items Collection
                         */
                        $items = $items->get()->toHierarchy();

                        /**
                         * @var $item Menu
                         */
                        foreach ($items as $item) {
                            $_prefix = DomainManager::getDomainSuffix($item->{Menu::SITE_ID});
                            $prefix = '<i class="fa fa-globe" data-toggle="tooltip" data-placement="top" title="#' .
                                $_prefix['text']['id'] . ' ' . $_prefix['text']['name'] . '" style="color:#' . $_prefix['color'] . '"></i>';
                            if (DomainManager::checkIsDefault() === false && $item->{Menu::SITE_ID} !== '000') {
                                $prefix = '';
                            }

                            $data = [
                                'id' => $item->id,
                                'text' => $prefix . '&nbsp;' . $item->{Menu::NAME} .
                                    ' [' . $item->id . ']',
                                'depth' => ($item->{Menu::DEPTH} - 1),
                                'parent_id' => $item->parent_id,
                                'state' => ['opened' => true],
                                'type' => $item->getType(),
                                'children' => [],
                                Menu::SITE_ID => $item->{Menu::SITE_ID},
                            ];
                            $this->getChildren($item, $data);
                            array_push($result['items'], $data);
                        }
                    }
                    break;
            }
        }

        return $this->json($result, __METHOD__);
    }



    /**
     * Сортировка дерева
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postMenuReorderTree(Request $request)
    {
        return $this->modelTreeReorder($request, \FastDog\Menu\Models\Menu::class, __METHOD__);
    }
    /**
     * Обновление параметров материалов
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postMenuUpdate(Request $request)
    {
        $result = ['success' => true, 'items' => []];
        $this->updatedModel($request->all(), Menu::class);

        return $this->json($result, __METHOD__);
    }
}
