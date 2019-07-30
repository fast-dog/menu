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
        $baseParameters = [
            'middleware' => ['acl'],
            'is' => DomainManager::getSiteId() . '::admin',
        ];
        /*
         * Таблица
         */
        $ctrl = '\FastDog\Menu\Menu\Controllers\Admin\MenuTableController';

        // Список корневых элементов меню
        \Route::post('/public/menu/roots', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postMenuRoots',

        ]));

        // Просмотр списка дочерних пунктов меню в виде списка
        \Route::post('/public/menu/list', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postMenuList',

        ]));

        // Сортировка меню
        \Route::post('/public/menu/reorder', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@reorder',

        ]));
//
        //Изменение сортировки меню
        \Route::post('/public/menu/reorder-tree', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postMenuReorderTree',

        ]));

        // Обновление параметров меню из общего списка
        \Route::post('/public/menu/list/self-update', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postMenuUpdate',

        ]));

        // Обновление параметров меню из общего списка
        \Route::post('/public/menu/roots/self-update', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postMenuUpdate',

        ]));

        /*
         * Форма
         */
        $ctrl = '\FastDog\Menu\Menu\Controllers\Admin\MenuFormController';

        \Route::get('/public/menu/{id}', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@getEditItem',

        ]))->where('id', '[0-9]+');

        \Route::get('/public/menu-root/{parent_id}', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@getEditItem',

        ]))->where('id', '[0-9]+');

        // Обновление парамтеров позиции
        \Route::post('/public/menu', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postMenu',

        ]));

        // Добавление позиции
        \Route::post('/public/menu-create', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postMenu',

        ]));

        /**
         * API
         */
        $ctrl = '\FastDog\Menu\Menu\Controllers\Admin\ApiController';

        // Страница информации\настроек параметров\доступа
        \Route::get('/public/menu/admin-info', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@getInfo',
        ]));

        // Очистка кэша
        \Route::post('/public/menu/clear-cache', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postClearCache',

        ]));

        // Изменение доступа к модулю
        \Route::post('/menu/access', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postAccess',

        ]));

        // Статистика переходов
        \Route::get('/menu/diagnostic', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@getDiagnostic',

        ]));

        // Проверка роутера - таблица результатов
        \Route::post('/menu/check-route', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postCheckRoute',

        ]));

        // Проверка роутера - выполнение пошаговой проверки
        \Route::get('/menu/check-route', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@getCheckRoute',

        ]));

        // Сохранение локализации шаблона
        \Route::post('/menu/api/translate', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postMenuTranslate',

        ]));

//        // Сохранение локализации шаблона
//        \Route::post('/menu/api/translate', array_replace_recursive($baseParameters, [
//            'uses' => $ctrl . '@postMenuReloadTranslate',
//
//        ]));

        // Сохранение HTML шаблона
        \Route::post('/menu/api/template', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@postMenuTemplate',

        ]));

        /**
         * Карта сайта
         */
        $ctrl = '\FastDog\Menu\Menu\Controllers\Admin\Sitemap\TableController';

        // Страница информации\настроек параметров\доступа
        \Route::post('/sitemap', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@list',

        ]));
        // Страница информации\настроек параметров\доступа
        \Route::post('/sitemap/reorder', array_replace_recursive($baseParameters, [
            'uses' => $ctrl . '@reorder',

        ]));
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
