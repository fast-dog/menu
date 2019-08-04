<?php

use FastDog\Core\Models\DomainManager;
use FastDog\Menu\Models\Menu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

Route::group([
    'prefix' => config('core.admin_path', 'admin'),
    'middleware' => ['web', FastDog\Admin\Http\Middleware\Admin::class],
],
    function() {

        // Таблица
        $ctrl = '\FastDog\Menu\Http\Controllers\Admin\MenuTableController';

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
//
//        //Изменение сортировки меню
//        \Route::post('/menu/reorder-tree',  [
//            'uses' => $ctrl . '@postMenuReorderTree',
//
//        ]);

        // Обновление параметров меню из общего списка
        \Route::post('/menu/list/update', [
            'uses' => $ctrl . '@postMenuUpdate',
        ]);

        // Обновление параметров меню из общего списка
        \Route::post('/menu/roots/update', [
            'uses' => $ctrl . '@postMenuUpdate',
        ]);

        //Форма
        $ctrl = '\FastDog\Menu\Http\Controllers\Admin\MenuFormController';

        \Route::get('/menu/{id}', [
            'uses' => $ctrl . '@getEditItem',
        ])->where('id', '[0-9]+');

        \Route::get('/menu-root/{parent_id}', [
            'uses' => $ctrl . '@getEditItem',

        ])->where('id', '[0-9]+');

        // Обновление парамтеров позиции
//        \Route::post('/menu',  [
//            'uses' => $ctrl . '@postMenu',
//        ]);

        // Добавление позиции
        \Route::post('/menu/create', [
            'uses' => $ctrl . '@postMenu',
        ]);

        // API
        $ctrl = '\FastDog\Menu\Menu\Http\Controllers\Admin\ApiController';

        // Страница информации\настроек параметров\доступа
        \Route::get('/menu/admin-info', [
            'uses' => $ctrl . '@getInfo',
        ]);

        // Очистка кэша
        \Route::post('/menu/clear-cache', [
            'uses' => $ctrl . '@postClearCache',
        ]);

        // Изменение доступа к модулю
        \Route::post('/menu/access', [
            'uses' => $ctrl . '@postAccess',
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

        /**
         * Карта сайта
         */
        $ctrl = '\FastDog\Menu\Menu\Http\Controllers\Admin\Sitemap\TableController';

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

if (!\App::runningInConsole()) {
    /**
     * Получаем активные пункты меню для определения параметров доступных маршрутов
     */
    \FastDog\Menu\Models\Menu::where(function(Builder $query) {
        $query->where(Menu::SITE_ID, DomainManager::getSiteId());
        $query->where(Menu::STATE, Menu::STATE_PUBLISHED);
        $query->where(function(Builder $query) {
            $query
                //->whereRaw(\DB::raw('data->"$.type" != \'catalog_index\''))
                ->whereRaw(\DB::raw('data->"$.type" != \'catalog_categories\''));
        });
    })->get()->each(function(\FastDog\Menu\Models\Menu $item) {
        $data = $item->getData();
        if (isset($item->route) && (!in_array($item->route, ['#', 'menu']))) {
            if (isset($data['data']->route_data)) {
                /**
                 * Зарезервированные в других модулях маршруты:
                 *
                 * catalog - главная каталога
                 */
                if (!in_array($item->route, ['catalog'])) {
                }
                \Route::get($item->route, function(Request $request) use ($item, $data) {
                    return FastDog\Menu\Menu::buildRoute($item, $data, $request);
                });

            }
        }
    });
}


/**
 * Карта сайта
 */
\Route::get('sitemap.xml', function(Request $request) {

    return self::buildSiteMap($request);
});
