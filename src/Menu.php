<?php

namespace FastDog\Menu;

use FastDog\Config\Models\Translate;
use FastDog\Core\Interfaces\PrepareContent;
use FastDog\Core\Models\Cache;
use FastDog\Core\Models\Components;
use FastDog\Core\Models\DomainManager;
use FastDog\Core\Models\ModuleManager;
use FastDog\Core\Store;
use FastDog\Menu\Events\BeforePrepareContent;
use FastDog\Menu\Http\Controllers\Site\MenuController;
use FastDog\Menu\Models\Menu as BaseMenu;
use FastDog\Menu\Models\Page;
use FastDog\Menu\Models\SiteMap;
use FastDog\Menu\Events\MenuPrepare;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use SimpleXMLElement;


/**
 * Меню навигации
 *
 * Управление контентом публичного раздела с помощью меню навигации. Подержка SEO, диагностика работоспособности.
 * Поддрежка событий шаблонов [TemplateName]BeforeRending и [TemplateName]AfterRending.
 *
 * @package FastDog\Menu
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class Menu extends BaseMenu
{
    /**
     * Идентификатор модуля
     * @const string
     */
    const MODULE_ID = 'menu';

    /**
     * Параметры модуля загруженные из файла modules.json
     * @var array|object $config
     */
    protected $config;

    /**
     * Имя кешируемого сегмента при использование redis
     *
     * @var string $cache_key
     */
    protected $cache_key = 'menu_data';

    /**
     * Включение HTML фрагмента в пункт меню
     *
     * @var $inject_html string
     */
    public $inject_html;


    /**
     * @param Request $request
     * @return mixed
     */
    public static function buildSiteMap(Request $request)
    {
        $xmlStr = <<<XML
<?xml version='1.0' standalone='yes'?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
</urlset>
XML;

        $xml = new SimpleXMLElement($xmlStr);

        SiteMap::where(function(Builder $query) use ($request) {
            $query->where(SiteMap::SITE_ID, DomainManager::getSiteId());
            $query->where(SiteMap::ROUTE, 'LIKE', '%' . $request->root() . '%');
        })->orderBy(SiteMap::PRIORITY, 'desc')->get()->each(function(SiteMap $item) use (&$xml) {
            $url = $xml->addChild('url');
            $url->addChild('loc', $item->{SiteMap::ROUTE});
            $url->addChild('lastmod', $item->{SiteMap::UPDATED_AT}->format('Y-m-d'));
            $url->addChild('changefreq', $item->{SiteMap::CHANGEFREQ});
            $url->addChild('priority', (float)$item->{SiteMap::PRIORITY} / 10);
        });

        return response($xml->asXML(), 200)->header('Content-Type', 'text/xml');
    }

    /**
     * @param $item \FastDog\Menu\Models\Menu
     * @param $data array
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|mixed|void
     */
    public static function buildRoute(Menu $item, $data, Request $request)
    {
        // показывать не авторизованным пользователям?
        $showGuest = $item->getParameterByFilterData(['name' => 'SHOW_GUEST'], 'N') === 'Y';

        if ($showGuest === false && auth()->guest() === true) {
            $redirect = $item->getParameterByFilterData(['name' => 'REDIRECT_TO'], null);
            $redirectCode = $item->getParameterByFilterData(['name' => 'REDIRECT_CODE'], 302);

            if ($redirect) {
                $item->redirect();

                return redirect(url($redirect), $redirectCode);
            }
        }
        /*
         * Обработка маршрута, если установлены параметры инициализации
         * модуля, запрашиваем контент, во всех остальных случаях,
         * если не предусмотрен маршрут перенаправления, выдаем ошибку 400
         */

        if (isset($data['data']->route_data->instance) || (isset($data['data']->route_instance))) {

            if (isset($data['data']->route_data->instance)) {
                /** @var $instance PrepareContent */
                $instance = new $data['data']->route_data->instance();
            } else {
                if (isset($data['data']->route_instance)) {
                    /** @var $instance PrepareContent */
                    $instance = new $data['data']->route_instance();
                }
            }
            /**
             * Определение фильтра меню для каталога (модулей каталога)
             *
             * TODO: перенести в событие BeforePrepareContent модуля Каталог
             */
//            $setFilters = [
//                'QUERY' => null,
//                'IN' => [], //<-- Суррогатные идентификаторы свойства+парамтера (facet_id) для выборки в условие IN (MySql)
//                'RANGE' => [], //<-- Диапазоны значении для inner join
//                'ITEMS' => [],//<-- Выборка из бд
//                'PARAMETERS' => [],//<-- Параметры поиска
//                'CATEGORY_IDS' => [],//<-- Категории
//                'TOTAL' => 0,
//                'ERRORS' => false,
//                'PRICE_MATRIX' => [],//<-- Диапазон ценовых предложений
//            ];
//            $category_id = null;
//            $item->catalogProperties->each(function($item) use (&$setFilters, &$category_id) {
//                $facet_id = $item->property_id + $item->category_id + (int)$item->value + $item->property->created_at->timestamp;
//                $category_id = $item->category_id;
//                switch ($item->property->type) {
//                    case 'list':
//                        $setFilters['PARAMETERS'][$item->property_id][] = $facet_id;
//                        break;
//                    default:
//                        break;
//                }
//            });
//
//            if ($category_id != null) {
//                $setFilters['CATEGORY_IDS'] = Category::getChildrenIds($category_id);
//                $request->merge(['setFilters' => $setFilters]);
//            }

            event(new BeforePrepareContent($request, $item, $data));

            return $instance->prepareContent($request, $item, $data);
        }

        /*
         * Параметры редиректа
         */
        $redirect = $item->getParameterByFilterData(['name' => 'REDIRECT_TO'], null);
        $redirectCode = $item->getParameterByFilterData(['name' => 'REDIRECT_CODE'], 302);

        if ($redirect !== 'N') {
            $item->redirect();

            return redirect(url($redirect), $redirectCode);
        }

        $item->error();

        return abort(400);
    }


    /**
     * Доступные шаблоны
     *
     * @param  $paths
     * @return null|array
     */
    public function getTemplates($paths = ''): array
    {
        $result = [];

        //получаем доступные пользователю site_id
        $domainsCode = DomainManager::getScopeIds();

        $list = DomainManager::getAccessDomainList();

        foreach ($domainsCode as $code) {
            $_code = $code;
            $currentPath = str_replace('{SITE_ID}', $code, $paths);

            if (isset($list[$code])) {
                $code = $list[$code]['name'];
            }

            if ($currentPath !== '') {
                $description = [];
                if (file_exists(dirname($currentPath) . '/.description.php') && $description == []) {
                    $description = include dirname($currentPath) . '/.description.php';
                }
                foreach (glob($currentPath) as $filename) {
                    if (!isset($result[$code])) {
                        $result[$code]['templates'] = [];
                    }
                    $tmp = explode('/', $filename);

                    $count = count($tmp);
                    if ($count >= 2) {
                        $search = array_search($_code, $tmp);
                        if ($search) {
                            $tmp = array_slice($tmp, $search + 1, $count);
                        }
                        $templateName = implode('.', $tmp);

                        $templateName = str_replace(['.blade.php'], [''], $templateName);
                        $name = Arr::last(explode('.', $templateName));

                        if (isset($description[$name])) {
                            $name = $description[$name];
                        }
                        $id = 'theme#' . $_code . '::' . $templateName;
                        $trans_key = str_replace(['.', '::'], '/', $id);

                        array_push($result[$code]['templates'], [
                            'id' => $id,
                            'name' => $name,
                            'translate' => Translate::getSegmentAdmin($trans_key),
                            'raw' => File::get(view($id)->getPath()),
                        ]);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Возвращает доступные типы меню
     *
     * @return null|object|array
     */
    public function getMenuType()
    {
        return [
            ['id' => 'menu', 'name' => trans('menu::menu.parent'), 'sort' => 1,],
            ['id' => 'static', 'name' => trans('menu::menu.link'), 'sort' => 20,],
            ['id' => 'alias', 'name' => trans('menu::menu.alias'), 'sort' => 30,],
            ['id' => 'page', 'name' => trans('menu::menu.page'), 'sort' => 40,],
        ];
    }

    /**
     * Пути к шаблонам пунктов меню
     *
     * @return array
     */
    public function getTemplatesPaths(): array
    {
        return [
            'page' => '/vendor/fast_dog/{SITE_ID}/menu/page/*.blade.php',
        ];
    }

    /**
     * Возвращает информацию о модуле
     *
     * @param bool $includeTemplates
     * @return array|null
     */
    public function getModuleInfo(): array
    {
        $paths = Arr::first(config('view.paths'));
        $templates_paths = $this->getTemplatesPaths();

        return [
            'id' => self::MODULE_ID,
            'menu' => function() use ($paths, $templates_paths) {
                $result = collect();
                foreach ($this->getMenuType() as $id => $item) {
                    $result->push([
                        'id' => self::MODULE_ID . '::' . $item['id'],
                        'name' => $item['name'],
                        'sort' => $item['sort'],
                        'templates' => (isset($templates_paths[$item['id']])) ? $this->getTemplates($paths . $templates_paths[$item['id']]) : [],
                        'class' => __CLASS__,
                    ]);
                }
                $result = $result->sortBy('sort');

                return $result;
            },
            'templates_paths' => $templates_paths,
            'module_type' => $this->getMenuType(),
            'admin_menu' => function() {
                return $this->getAdminMenuItems();
            },
            'access' => function() {
                return [
                    '000',
                ];
            },
            'route' => function(Request $request, $item) {
                return $this->getMenuRoute($request, $item);
            },
        ];
    }


    /**
     * Типы меню в проекте
     *
     * @return array
     */
    public static function getTypes()
    {
        $result = [];

        foreach (app()->make(ModuleManager::class)->getModules() as $module) {
            $module['menu']()->each(function($data) use (&$result) {
                array_push($result, $data);
            });
        }

        return $result;
    }

    /**
     * Устанавливает параметры в контексте объекта
     *
     * @param $data
     * @return mixed
     */
    public function setConfig($data)
    {
        $this->config = $data;
    }

    /**
     *  Возвращает параметры объекта
     *
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }


    /**
     * Возвращает возможные типы модулей
     *
     * Данный тип используется в модулях выводящих контент на сайт
     *
     * @return mixed
     * @deprecated
     */
    public function getModuleType()
    {
        $result = [];

        return $result;
    }


    /**
     * Возвращает маршрут компонента
     *
     * @param Request $request
     * @param Menu $item
     * @return mixed
     */
    public function getMenuRoute($request, $item)
    {
        $result = [];
        switch ($request->input('type.id')) {
            case self::TYPE_MENU:
                $parents = $item->ancestors()->get();

                /** @var $parent Menu */
                foreach ($parents as $parent) {
                    if (!in_array($parent->getRoute(), ['/', '#']) && $parent->{Menu::DEPTH} > 1) {
                        array_push($result, $parent->getRoute());
                    }
                }
                array_push($result, $request->input('alias'));
                break;
            case self::TYPE_STATIC:
                array_push($result, $request->input('data.url'));
                break;
            case self::TYPE_ALIAS:

                /** @var $alias Menu */
                $alias = Menu::where(['id' => $request->input('data.alias_id')])->first();

                return [
                    'route' => null,
                    'instance' => null,
                    'alias' => ($alias) ? $alias->getRoute() : null,
                ];
                break;
            case self::TYPE_PAGE:
                /** @var $page Page */
                $page = Page::where(['id' => $request->input('data.item_id')])->first();

                return [
                    'route' => $page->getRoute(),
                    'instance' => MenuController::class
                ];
                break;
        }

        return [
            'instance' => $request->input('data.route_instance'),
            'route' => implode('/', $result),
        ];
    }

    /**
     * Метод возвращает отображаемый в публичной части контнет
     *
     * @param Components $module
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     * @throws \Throwable
     */
    public function getContent(Components $module)
    {
        auth()->check();

        /** @var $storeManager Store */
        $storeManager = app()->make(Store::class);

        /** @var Cache $cache */
        $cache = app()->make(Cache::class);

        /** @var Collection $menuItemsCollection */
        $menuItemsCollection = $storeManager->getCollection(self::class);

        if (null === $menuItemsCollection) {
            $storeManager->pushCollection(self::class, self::where(function(Builder $query) {

            })->get());
            $menuItemsCollection = $storeManager->getCollection(self::class);
        }
        $data = $module->getData();
        $result = '';

        $menuId = (isset($data['data']->item_id->id)) ? $data['data']->item_id->id : null;

        $key = __METHOD__ . '::' . DomainManager::getSiteId() . '::menu_id-' . $menuId;
        $key .= (auth()->guest()) ? '-guest' : '-user';

        /** @var \FastDog\Menu\Models\Menu $root */
        $root = $menuItemsCollection->where('id', $menuId)->first();

        if ($menuId && $root) {
            $scope = 'defaultSite';
            $items = $cache->get($key, function() use ($root, $scope) {
                $isGuest = auth()->guest();

                /** @var Collection $items */
                $items = $root->descendants()->where(function(Builder $query) use (&$scope) {
                    $query->where(Menu::STATE, Menu::STATE_PUBLISHED);
                })
                    ->$scope()
                    ->get();

                $items->each(function(Menu &$item) use ($isGuest) {
                    $item->_hidden = 'N';
                    $showGuest = $item->getParameterByFilterData(['name' => 'SHOW_GUEST'], 'Y') == 'Y';
                    $showUser = $item->getParameterByFilterData(['name' => 'SHOW_USER'], 'Y') == 'Y';

                    // Скрыть пункт меню по условиям авторизации
                    if ((false === $showGuest) && ($isGuest === true) ||
                        (false === $showUser) && ($isGuest === false)) {
                        $item->_hidden = 'Y';
                    }
                });

                $items = $items->toHierarchy();

                return $items;
            }, ['menu']);

        }

        $render_data = [
            'items' => $items,
            'module' => $module,
        ];

        event(new MenuPrepare($render_data));

        // События обработки шаблонов
        $prefixEvents = 'FastDog\Menu\Events\Site\\';

        switch ($data['data']->type->id) {
            case 'menu::item':
                if (isset($data['data']->template->id)) {
                    $data['data']->template->id;
                    // Вычисляем имена событий и если они зарегистрированны вызываем
                    $baseName = substr(camel_case(str_replace('.', '_', $data['data']->template->id)), -11);

                    $beforeRendingEvent = $prefixEvents . ucfirst($baseName . 'BeforeRending');
                    $afterRendingEvent = $prefixEvents . ucfirst($baseName . 'AfterRending');

                    if (class_exists($beforeRendingEvent)) {
                        event(new $beforeRendingEvent($render_data));
                    }

                    if (view()->exists($data['data']->template->id)) {
                        $result = view($data['data']->template->id, $render_data)->render();
                    }

                    if (class_exists($afterRendingEvent)) {
                        event(new $afterRendingEvent($result));
                    }
                }
                break;
            default:
                $result = view('theme::modules.menu.default', $render_data);
                break;
        }

        return $result;
    }

    /**
     * Меню администратора
     *
     * Возвращает пунты меню для раздела администратора
     *
     * @return array
     */
    public function getAdminMenuItems()
    {
        $result = [];

        $result = [
            'icon' => 'fa-table',
            'name' => trans('menu::interface.Сайт'),
            'route' => '/menu',
            'children' => [],
        ];

        array_push($result['children'], [
            'icon' => 'fa-table',
            'name' => trans('menu::interface.Меню'),
            'route' => '/menu/index',
            'new' => '/menu/item/0',
        ]);

        array_push($result['children'], [
            'icon' => 'fa-file',
            'name' => trans('menu::interface.Страницы'),
            'route' => '/menu/pages',
            'new' => '/menu/page/0',
        ]);

        array_push($result['children'], [
            'icon' => 'fa-file-o',
            'name' => trans('menu::interface.Материалы'),
            'route' => '/content/items',
            'new' => '/content/item/0',
        ]);

        array_push($result['children'], [
            'icon' => 'fa-power-off',
            'name' => trans('menu::interface.Диагностика'),
            'route' => '/menu/diagnostic',
        ]);
        array_push($result['children'], [
            'icon' => 'fa-sitemap',
            'name' => trans('menu::interface.Карта сайта'),
            'route' => '/menu/sitemap',
        ]);
        array_push($result['children'], [
            'icon' => 'fa-gears',
            'name' => trans('menu::interface.Настройки'),
            'route' => '/menu/configuration',
        ]);

        return $result;
    }
}
