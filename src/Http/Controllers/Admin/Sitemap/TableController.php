<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 21.05.2018
 * Time: 10:44
 */

namespace FastDog\Menu\Controllers\Admin\Sitemap;

use App\Core\Table\Interfaces\TableControllerInterface;
use App\Core\Table\Traits\TableTrait;
use App\Http\Controllers\Controller;
use FastDog\Menu\Config\Entity\DomainManager;
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

        $this->page_title = trans('app.Меню навигации');

        $this->model = $model;
        $this->initTable();

        $this->accessKey = strtolower(\FastDog\Menu\Menu::class) . '::'
            . DomainManager::getSiteId() . '::guest';
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

        $this->breadcrumbs->push(['url' => '/menu/index', 'name' => trans('app.Меню навигации')]);
        $this->breadcrumbs->push(['url' => false, 'name' => trans('app.Карта сайта')]);

        return $this->json($result, __METHOD__);
    }

    public function reorder(Request $request)
    {
        Artisan::call('sitemap', ['domain' => $request->root()]);
    }

}
