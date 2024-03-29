<?php

use FastDog\Core\Models\DomainManager;
use FastDog\Menu\Models\Menu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

Route::group([
    'prefix' => config('core.admin_path', 'admin'),
    'middleware' => ['web', FastDog\Admin\Http\Middleware\Admin::class],
],
    function () {

        // Таблица
        $ctrl = '\FastDog\Menu\Http\Controllers\Admin\MenuTableController';

        // Список корневых элементов меню
        \Route::post('/menu/load', [
            'uses' => $ctrl . '@postMenuLoad',
        ]);

        // Список корневых элементов меню
        \Route::post('/menu/roots', [
            'uses' => $ctrl . '@postMenuRoots',
        ]);

        // Просмотр списка дочерних пунктов меню в виде списка
        \Route::post('/menu/list/{root_id}', [
            'uses' => $ctrl . '@postMenuList',
        ])->where('root_id', '[0-9]+');

        // Сортировка меню
        \Route::post('/menu/reorder', [
            'uses' => $ctrl . '@reorder',
        ]);

        // Обновление параметров меню
        \Route::post('/menu/update', [
            'uses' => $ctrl . '@postMenuUpdate',
        ]);
        
        //Форма - Страницы
        $ctrl = '\FastDog\Menu\Http\Controllers\Admin\PageFormController';

        \Route::get('/page/{id}', [
            'uses' => $ctrl . '@getEditItem',
        ])->where('id', '[0-9]+');

        \Route::post('/page/create', [
            'uses' => $ctrl . '@postSave',
        ]);

        // Обновление объекта из списка (публикаций, перемещение в корзинку)
        \Route::post('/pages/update', [
            'uses' => $ctrl . '@postUpdate',
        ]);

        //Таблица - Страницы
        $ctrl = '\FastDog\Menu\Http\Controllers\Admin\PageTableController';

        \Route::post('/pages', [
            'uses' => $ctrl . '@list',
        ])->where('id', '[0-9]+');

        // Поиск по страницам
        \Route::post('/page/search-list', [
            'uses' => $ctrl . '@getPageSearch',
        ]);

        //Форма - Меню
        $ctrl = '\FastDog\Menu\Http\Controllers\Admin\MenuFormController';

        \Route::get('/menu/{id}', [
            'uses' => $ctrl . '@getEditItem',
        ])->where('id', '[0-9]+');
        
        
        // Добавление позиции
        \Route::post('/menu/create', [
            'uses' => $ctrl . '@postMenu',
        ]);

        // Добавление позиции
        \Route::post('/menu/append', [
            'uses' => $ctrl . '@postAppendMenu',
        ]);

        // API
        $ctrl = '\FastDog\Menu\Http\Controllers\Admin\ApiController';

        // Страница информации\настроек параметров\доступа
        \Route::get('/menu/admin-info', [
            'uses' => $ctrl . '@getInfo',
        ]);

        // Очистка кэша
        \Route::post('/menu/clear-cache', [
            'uses' => $ctrl . '@postClearCache',
        ]);

     
        // Статистика переходов
        \Route::get('/menu/diagnostic', [
            'uses' => $ctrl . '@getDiagnostic',
        ]);

        // Проверка роутера - таблица результатов
        \Route::post('/menu/check-route', [
            'uses' => $ctrl . '@postCheckRoute',
        ]);

        // Проверка роутера - выполнение пошаговой проверки
        \Route::get('/menu/check-route', [
            'uses' => $ctrl . '@getCheckRoute',
        ]);

        // Сохранение локализации шаблона
        \Route::post('/menu/api/translate', [
            'uses' => $ctrl . '@postMenuTranslate',
        ]);

//        // Сохранение локализации шаблона
//        \Route::post('/menu/api/translate',  [
//            'uses' => $ctrl . '@postMenuReloadTranslate',
//
//        ]);

        // Сохранение HTML шаблона
        \Route::post('/menu/api/template', [
            'uses' => $ctrl . '@postMenuTemplate',
        ]);

        /*
         * Карта сайта
         */
        $ctrl = '\FastDog\Menu\Http\Controllers\Admin\Sitemap\TableController';

        // Страница информации\настроек параметров\доступа
        \Route::post('/sitemap', [
            'uses' => $ctrl . '@list',
        ]);

        // Страница информации\настроек параметров\доступа
        \Route::post('/sitemap/reorder', [
            'uses' => $ctrl . '@reorder',
        ]);
    }
);

if (!app()->runningInConsole()) {
    /**
     * Получаем активные пункты меню для определения параметров доступных маршрутов
     */
    \FastDog\Menu\Menu::where(function (Builder $query) {
        $query->where(Menu::SITE_ID, DomainManager::getSiteId());
        $query->where(Menu::STATE, Menu::STATE_PUBLISHED);
        $query->where(function (Builder $query) {
            $query
                //->whereRaw(\DB::raw('data->"$.type" != \'catalog_index\''))
                ->whereRaw(\DB::raw('data->"$.type" != \'catalog_categories\''));
        });
    })->get()->each(function (\FastDog\Menu\Menu $item) {
        $data = $item->getData();
        if (isset($item->route) && (!in_array($item->route, ['#', 'menu']))) {
            if (isset($data['data']->route_data)) {
                \Route::get($item->route, function (Request $request) use ($item, $data) {
                    return FastDog\Menu\Menu::buildRoute($item, $data, $request);
                });

            }
        }
    });
}


/**
 * Карта сайта
 */
\Route::get('sitemap.xml', function (Request $request) {

    return Menu::buildSiteMap($request);
});
