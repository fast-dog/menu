<?php

namespace FastDog\Menu\Controllers\Admin\Sitemap;


use FastDog\Core\Http\Controllers\Controller;
use FastDog\Core\Table\Interfaces\TableControllerInterface;
use FastDog\Core\Table\Traits\TableTrait;
use FastDog\Menu\Models\SiteMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;

/**
 * Карта сайта, табличное представление данных
 *
 * @package FastDog\Menu\Controllers\Admin\Sitemap
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class TableController extends Controller implements TableControllerInterface
{
    use  TableTrait;

    public function __construct(SiteMap $model)
    {
        parent::__construct();

        $this->page_title = trans('menu::interface.Меню навигации');

        $this->model = $model;
        $this->initTable();
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
     * Модель, контекст выборок
     *
     * @return  Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Таблица - Материалы
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $result = self::paginate($request);

        $this->breadcrumbs->push(['url' => '/menu/index', 'name' => trans('menu::interface.Меню навигации')]);
        $this->breadcrumbs->push(['url' => false, 'name' => trans('menu::interface.Карта сайта')]);

        return $this->json($result, __METHOD__);
    }

    /**
     * @param Request $request
     */
    public function reorder(Request $request)
    {
        Artisan::call('sitemap', ['domain' => $request->root()]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function items(Request $request): JsonResponse
    {
        // TODO: Implement items() method.
    }
}
