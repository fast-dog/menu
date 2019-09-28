<?php

namespace FastDog\Menu\Http\Controllers\Admin;

use FastDog\Core\Form\Interfaces\FormControllerInterface;
use FastDog\Core\Form\Traits\FormControllerTrait;
use FastDog\Core\Http\Controllers\Controller;
use FastDog\Menu\Models\Page;
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
        $this->breadcrumbs->push(['url' => '/menu/index', 'name' => trans('menu::interface.Управление')]);

        $result = $this->getItemData($request);
        $result['item'] = array_first($result['items']);

        $this->breadcrumbs->push([
            'url' => false,
            'name' => ($this->item->id > 0) ? $this->item->{Page::NAME} : trans('menu::forms.general.new_item')
        ]);

        return $this->json($result, __METHOD__);
    }
}
