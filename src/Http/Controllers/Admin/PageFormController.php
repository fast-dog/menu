<?php

namespace FastDog\Menu\Http\Controllers\Admin;


use FastDog\Menu\Events\PageAdminBeforeSave;
use FastDog\Core\Form\Interfaces\FormControllerInterface;
use FastDog\Core\Form\Traits\FormControllerTrait;
use FastDog\Core\Http\Controllers\Controller;
use FastDog\Core\Models\DomainManager;
use FastDog\Menu\Events\PageAdminPrepare;
use FastDog\Menu\Http\Request\AddPage;
use FastDog\Menu\Models\Page;
use FastDog\Menu\Events\PageAdminAfterSave;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Страницы - Форма
 *
 * @package FastDog\Menu\Http\Controllers\Admin
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class PageFormController extends Controller implements FormControllerInterface
{
    use FormControllerTrait;

    /**
     * MenuFormController constructor.
     * @param Page $model
     */
    public function __construct(Page $model)
    {
        $this->model = $model;
        $this->page_title = trans('menu::interface.Страницы');
        parent::__construct();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getEditItem(Request $request): JsonResponse
    {
        $this->breadcrumbs->push(['url' => '/menu/index', 'name' => trans('menu::interface.Меню')]);

        $result = $this->getItemData($request);
        $result['item'] = array_first($result['items']);

        $this->breadcrumbs->push([
            'url' => false,
            'name' => ($this->item->id > 0) ? $this->item->{Page::NAME} : trans('menu::forms.page.new_item'),
        ]);

        return $this->json($result, __METHOD__);
    }

    /**
     * @param AddPage $request
     * @return JsonResponse
     */
    public function postSave(AddPage $request): JsonResponse
    {
        $result = ['success' => true, 'items' => []];

        $data = $request->all();
        $item = null;

        if (DomainManager::checkIsDefault() === false) {
            $data[Page::SITE_ID] = DomainManager::getSiteId();
        }
        
        if ($data[Page::ALIAS] == '#' || $data[Page::ALIAS] == '') {
            $data[Page::ALIAS] = str_slug($data[Page::NAME]);
        }

        $_data = [
            Page::NAME => $data[Page::NAME],
            Page::ALIAS => $data[Page::ALIAS],
            Page::STATE => (isset($data[Page::STATE]['id'])) ? $data[Page::STATE]['id'] : Page::STATE_PUBLISHED,
            Page::FULLTEXT => $data[Page::FULLTEXT],
            Page::INTROTEXT => $data[Page::INTROTEXT],
            Page::SITE_ID => (isset($data[Page::SITE_ID]['id'])) ? $data[Page::SITE_ID]['id'] : DomainManager::getSiteId(),
            Page::DATA => json_encode($data['data']),
        ];

        // Определение основных параметров, SEO, маршрута роутера и т.д.
        event(new PageAdminBeforeSave($_data));

        if ($request->input('id')) {
            $item = Page::find($request->input('id'));
            if ($item) {
                unset($_data['_events']);
                Page::where('id', $item->id)->update($_data);
                $item = Page::where('id', $item->id)->first();
            }
        } else {
            $item = Page::create($_data);
            // Передача нового объекта на клиент для корректного обновления формы
            array_push($result['items'], $item);
        }

        // Сохранение дополнительных параметров, тегов, медиа файлов и т.д.
        event(new PageAdminAfterSave($data, $item));

        return $this->json($result, __METHOD__);
    }

    /**
     * @return string
     */
    public function getPrepareEvent(): string
    {
        return PageAdminPrepare::class;
    }


    /**
     * Обновление параметров страницы
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function postUpdate(Request $request): JsonResponse
    {
        $result = ['success' => true, 'items' => []];

        $this->updatedModel($request->all(), Page::class);

        return $this->json($result, __METHOD__);
    }
}
