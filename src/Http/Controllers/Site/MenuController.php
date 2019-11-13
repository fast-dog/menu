<?php

namespace FastDog\Menu\Http\Controllers\Site;

use Carbon\Carbon;
use FastDog\Core\Http\Controllers\Controller;
use FastDog\Core\Interfaces\PrepareContent;
use FastDog\Core\Models\DomainManager;
use Illuminate\Http\Request;

/**
 * Class MenuController
 * @package FastDog\Menu\Http\Controllers\Site
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuController extends Controller implements PrepareContent
{
    
    /**
     * Обработка публичного запроса
     *
     * @param  Request  $request
     * @param  mixed  $item  объект активного пункта меню
     * @param $data
     * @return mixed|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function prepareContent(Request $request, $item, $data): \Illuminate\View\View
    {
        $viewData = [
            'menuItem' => $item,
            'data' => $data,
            'path' => $item->getPath(),
            'theme' => DomainManager::getAssetPath(),
        ];
        
        Carbon::setLocale('ru');
        
        switch ($data['data']->type) {
            default:
                break;
        }
        
        view()->share($viewData);
        
        if (isset($data['data']->template->id) && view()->exists($data['data']->template->id)) {
            $viewData['menuItem']->success();
            
            return view($data['data']->template->id, $viewData);
        }
        $viewData['menuItem']->error();
        
        return abort(424);
    }
}