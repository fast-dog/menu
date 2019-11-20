<?php

namespace FastDog\Menu\Http\Controllers\Admin;


use Baum\Extensions\Eloquent\Model;
use FastDog\Core\Http\Controllers\Controller;
use FastDog\Core\Models\BaseModel;
use FastDog\Core\Models\DomainManager;
use FastDog\Core\Table\Interfaces\TableControllerInterface;
use FastDog\Core\Table\Traits\TableTrait;
use FastDog\Menu\Events\MenuItemsAdminPrepare;
use FastDog\Menu\Events\MenuResources;
use FastDog\Menu\Models\Menu;
use FastDog\Menu\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Страницы - Таблица
 *
 * @package FastDog\Menu\Http\Controllers\Admin
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class PageTableController extends Controller implements TableControllerInterface
{
    use  TableTrait;

    /**
     * Модель по которой будет осуществляться выборка данных
     *
     * @var \FastDog\Menu\Models\Page|null $model
     */
    protected $model = null;

    /**
     * MenuTableController constructor.
     * @param Page $model
     */
    public function __construct(Page $model)
    {
        parent::__construct();

        $this->page_title = trans('menu::interface.Страницы');

        $this->model = $model;
        $this->initTable();

    }

    /**
     * Модель, контекст выборок
     *
     * @return  BaseModel
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
                'name' => trans('menu::forms.general.fields.name'),
                'key' => Menu::NAME,
                'domain' => true,
                'extra' => true,
                'link' => 'menu_items',
                'edit_link' => 'menu_item',
                'action' => [
                    'edit' => true,
                    'delete' => true,
                ],
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
     * Список страниц
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $result = self::paginate($request);

        $this->breadcrumbs->push(['url' => '/menu/index', 'name' => trans('menu::interface.Меню')]);
        $this->breadcrumbs->push(['url' => false, 'name' => trans('menu::interface.Страницы')]);

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
                'code' => $e->getCode(),
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
     * Поиск страницы по имени
     *
     * метод используется при выборе материалов в меню\публичных модулях
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPageSearch(Request $request)
    {
        $result = ['success' => true, 'items' => []];

        $items = Page::where(function(Builder $query) use ($request) {
            $query->where(Page::NAME, 'LIKE', '%' . $request->input('filter.query') . '%');
        })->paginate(self::PAGE_SIZE)->each(function(Page $item) use (&$result) {
            array_push($result['items'], [
                'id' => $item->id,
                Page::NAME => $item->{Page::NAME},
                'value' => $item->{Page::NAME},
            ]);
        });

        return $this->json($result, __METHOD__);
    }
}
