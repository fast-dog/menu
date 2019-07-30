<?php

namespace FastDog\Menu;


use FastDog\Menu\Models\Menu as BaseMenu;
use FastDog\Menu\Models\MenuRouterCheckResult;
use FastDog\Menu\Models\MenuStatistic;
use FastDog\Menu\Models\SiteMap;
use FastDog\Menu\Events\MenuPrepare;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use SimpleXMLElement;
use Illuminate\Database\Eloquent\Collection as BaseCollection;

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
        $showGuest = $item->getParameterByFilterData(['name' => 'SHOW_GUEST'], 'N') === 'Y';

        if ($showGuest === false && \Auth::guest() === true) {
            $redirect = $item->getParameterByFilterData(['name' => 'REDIRECT_TO'], null);
            $redirectCode = $item->getParameterByFilterData(['name' => 'REDIRECT_CODE'], 302);

            if ($redirect) {
                $item->redirect();

                return redirect(url($redirect), $redirectCode);
            }
        }
        /**
         * Обработка маршрута, если установлены параметры инициализации
         * модуля, запрашиваем контент, во всех остальных случаях,
         * если не предусмотрен маршрут перенаправления, выдаем ошибку 400
         */

        if (isset($data['data']->route_data->instance) || (isset($data['data']->route_instance))) {
            /**
             * @var $instance PrepareContent
             */
            if (isset($data['data']->route_data->instance)) {
                $instance = new $data['data']->route_data->instance();
            } else if (isset($data['data']->route_instance)) {
                $instance = new $data['data']->route_instance();
            }
            /**
             * Определение фильтра меню для каталога (модулей каталога)
             */
            $setFilters = [
                'QUERY' => null,
                'IN' => [], //<-- Суррогатные идентификаторы свойства+парамтера (facet_id) для выборки в условие IN (MySql)
                'RANGE' => [], //<-- Диапазоны значении для inner join
                'ITEMS' => [],//<-- Выборка из бд
                'PARAMETERS' => [],//<-- Параметры поиска
                'CATEGORY_IDS' => [],//<-- Категории
                'TOTAL' => 0,
                'ERRORS' => false,
                'PRICE_MATRIX' => [],//<-- Диапазон ценовых предложений
            ];
            $category_id = null;
            $item->catalogProperties->each(function($item) use (&$setFilters, &$category_id) {
                $facet_id = $item->property_id + $item->category_id + (int)$item->value + $item->property->created_at->timestamp;
                $category_id = $item->category_id;
                switch ($item->property->type) {
                    case 'list':
                        $setFilters['PARAMETERS'][$item->property_id][] = $facet_id;
                        break;
                    default:
                        break;
                }
            });

            if ($category_id != null) {
                $setFilters['CATEGORY_IDS'] = Category::getChildrenIds($category_id);
                $request->merge(['setFilters' => $setFilters]);
            }

            return $instance->prepareContent($request, $item, $data);
        }

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
     * События обрабатываемые модулем
     *
     * @return void
     */
    public function initEvents()
    {
        $paths = array_first(\Config::get('view.paths'));

        $isRedis = config('cache.default') == 'redis';
        $key = __METHOD__ . '::' . DomainManager::getSiteId() . '::events';
        $result = ($isRedis) ? \Cache::tags(['menu'])->get($key, null) : \Cache::get($key, null);

        if ($result === null) {
            $result = [
                'FastDog\Menu\Events\MenuItemBeforeSave' => [
                    'FastDog\Menu\Listeners\MenuItemBeforeSave',
                ],
                'FastDog\Menu\Events\MenuItemAfterSave' => [
                    'FastDog\Menu\Listeners\MenuItemAfterSave',
                ],
                'FastDog\Menu\Events\MenuItemAdminPrepare' => [
                    'App\Core\Listeners\AdminItemPrepare',// <-- Поля даты обновления и т.д.
                    'App\Core\Listeners\MetadataAdminPrepare',// <-- SEO
                    'FastDog\Menu\Listeners\MenuItemAdminPrepare',
                    'FastDog\Menu\Listeners\MenuItemSetEditorForm',
                ],
                'FastDog\Menu\Events\MenuItemsAdminPrepare' => [
                    'FastDog\Menu\Listeners\MenuItemsAdminPrepare',
                ],
                'FastDog\Menu\Events\MenuPrepare' => [
                    'FastDog\Menu\Listeners\MenuPrepare',
                ],
                'FastDog\Menu\Events\CatalogCreateProperty' => [
                    'FastDog\Menu\Listeners\CatalogCreateProperty',
                ],
            ];

            $paths = array_first(\Config::get('view.paths'));
            /**
             * Регистрация событий для публичного раздела сайта
             */
            foreach (["static" => "/modules/menu/static/*.blade.php",
                         "index" => "/modules/menu/*.blade.php",
                     ] as $templatePath) {
                $templatePath = array_first((array)$templatePath);
                $templates = $this->getTemplates($paths . $templatePath, true);

                foreach ($templates as $template) {
                    foreach ($template as $items) {
                        if (is_array($items)) {
                            foreach ($items as $item) {
                                //$dir = dirname(view('theme::modules.' . $item['id'])->getPath());
//                                $baseName = camel_case(str_replace('.', '_', $item['id']));
                                $baseName = substr(camel_case(str_replace('.', '_', $item['id'])), -11);
                                $beforeRendingEvent = 'FastDog\Menu\Events\Site\\' . ucfirst($baseName . 'BeforeRending');

                                if (class_exists($beforeRendingEvent)) {
                                    $beforeRendingListener = 'FastDog\Menu\Listeners\Site\\' . ucfirst($baseName . 'BeforeRending');
                                    if (class_exists($beforeRendingListener)) {
                                        $result[$beforeRendingEvent][$beforeRendingListener] = $beforeRendingListener;
                                    }
                                }

                                $afterRendingEvent = 'FastDog\Menu\Events\Site\\' . ucfirst($baseName . 'AfterRending');
                                if (class_exists($afterRendingEvent)) {
                                    $afterRendingListener = 'FastDog\Menu\Listeners\Site\\' . ucfirst($baseName . 'AfterRending');
                                    if (class_exists($afterRendingListener)) {
                                        $result[$afterRendingEvent][$afterRendingListener] = $afterRendingListener;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ($isRedis) {
                \Cache::tags(['menu'])->put($key, $result, config('cache.ttl_core', 5));
            } else {
                \Cache::put($key, $result, config('cache.ttl_core', 5));
            }
        }

        return $result;
    }

    /**
     * Возвращает доступные шаблоны
     *
     * @param string $paths
     * @param bool $skip_load_raw
     * @return array
     */
    public function getTemplates($paths = "/modules/menu/*.blade.php", $skip_load_raw = false)
    {
        $result = [];

        //получаем доступные пользователю site_id
        $domainsCode = DomainManager::getScopeIds();

        $list = DomainManager::getAccessDomainList();
        foreach ($domainsCode as $code) {
            $_code = $code;
            $currentPath = str_replace('modules', 'public/' . $code . '/modules', $paths);
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
                        $name = array_last(explode('.', $templateName));

                        if (isset($description[$name])) {
                            $name = $description[$name];
                        }
                        $id = 'theme#' . $_code . '::' . $templateName;
                        $trans_key = str_replace(['.', '::'], '/', $id);

                        array_push($result[$code]['templates'], [
                            'id' => $id,
                            'name' => $name,
                            'translate' => ($skip_load_raw === false) ? Translate::getSegmentAdmin($trans_key) : [],
                            'raw' => ($skip_load_raw === false) ? File::get(view($id)->getPath()) : [],
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
     * Типы меню должны быть определены в файле module.json в секции 'menu', пример:
     * <pre>
     *   "menu": [
     *              {
     *                  "id": "menu",
     *                  "name": "Родительский тип меню",
     *                  "sort": 1
     *              },
     *              {
     *                  "id": "static",
     *                  "name": "Внешняя ссылка",
     *                  "sort": 20
     *              }
     * ],
     * </pre>
     *
     * @return null|object|array
     */
    public function getMenuType()
    {
        return [
            (object)[
                'id' => 'menu',
                'name' => 'Меню :: Родительский тип меню',
                'sort' => 1,
            ],
            (object)[
                'id' => 'static',
                'name' => 'Меню :: Внешняя ссылка',
                'sort' => 20,
            ],
            (object)[
                'id' => 'alias',
                'name' => 'Меню :: Псевдоним',
                'sort' => 30,
            ],
        ];
    }

    /**
     * Возвращает информацию о модуле
     *
     * @param bool $includeTemplates
     * @return array|null
     */
    public function getModuleInfo($includeTemplates = true)
    {
        $result = [];
        $paths = array_first(\Config::get('view.paths'));
        $templates_paths = array_first($this->config->{'templates_paths'});
        if (isset($this->config->menu)) {
            foreach ($this->config->menu as $item) {
                if (isset($item->id)) {
                    $templates = [];
                    if ($includeTemplates && isset($templates_paths->{$item->id})) {
                        $templates = $this->getTemplates($paths . $templates_paths->{$item->id});
                    }
                    array_push($result, [
                        'id' => $item->id,
                        'name' => $item->name,
                        'templates' => $templates,
                        'class' => __CLASS__,
                    ]);
                }
            }
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
     */
    public function getModuleType()
    {
        $paths = array_first(\Config::get('view.paths'));

        $result = [
            'id' => 'menu',
            'instance' => __CLASS__,
            'name' => trans('app.Меню'),
            'items' => [
                [
                    'id' => 'item',
                    'name' => trans('app.Меню') . ' :: ' . trans('app.Меню навигации'),
                    'templates' => $this->getTemplates($paths . '/modules/menu/*.blade.php'),
                ],
            ],
        ];

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
                /**
                 * @var $parent Menu
                 */
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
                /**
                 * @var $alias Menu
                 */
                $alias = Menu::where(['id' => $request->input('data.alias_id')])->first();

                return [
                    'route' => null,
                    'instance' => null,
                    'alias' => ($alias) ? $alias->getRoute() : null,
                ];
                break;
        }


        return [
            'instance' => $request->input('data.route_instance'),
            'route' => implode('/', $result),
        ];
    }

    /**
     * Инициализация уровней доступа ACL
     *
     * @return null
     */
    public function initAcl()
    {
        $domainList = DomainManager::getAccessDomainList();
        foreach ($domainList as $domain) {
            if ($domain['id'] !== '000') {
                /**
                 * Имя раздела разрешений должно быть в нижнем регистре из за
                 * особенностей реализации методов в пакете kodeine/laravel-acl
                 */
                $name = strtolower(__CLASS__ . '::' . $domain['id']);

                $roleGuest = DomainManager::getRoleGuest($domain['id']);
                $data = [
                    'name' => $name,
                    'slug' => [
                        'create' => false,
                        'view' => true,
                        'update' => false,
                        'delete' => false,
                        'reorder' => false,
                        'info' => false,
                        'api' => false,
                    ],
                    'description' => \GuzzleHttp\json_encode([
                        'module_name' => 'Меню',
                        'description' => 'ACL для домена #' . $domain['id'],
                    ]),
                ];
                $permGuest = Permission::where([
                    'name' => $data['name'] . '::guest',
                ])->first();

                if (!$permGuest) {
                    $data['name'] = $name . '::guest';
                    $permGuest = Permission::create($data);
                    $roleGuest->assignPermission($permGuest);
                } else {
                    Permission::where('id', $permGuest->id)->update([
                        'slug' => json_encode($data['slug']),
                    ]);
                }
                $permUser = Permission::where([
                    'name' => $data['name'] . '::user',
                ])->first();
                if (!$permUser) {
                    $data['inherit_id'] = $permGuest->id;
                    $data['name'] = $name . '::user';
                    $permUser = Permission::create($data);
                } else {
                    Permission::where('id', $permUser->id)->update([
                        'slug' => json_encode($data['slug']),
                    ]);
                }
                if ($permUser) {
                    $roleUser = DomainManager::getRoleUser($domain['id']);
                    if ($roleUser) {
                        $roleUser->assignPermission($permUser);
                    }

                    $roleAdmin = DomainManager::getRoleAdmin($domain['id']);
                    $data['slug'] = [
                        'create' => true,
                        'update' => true,
                        'delete' => true,
                        'reorder' => true,
                        'info' => true,
                        'api' => true,
                    ];
                    $permAdmin = Permission::where([
                        'name' => $data['name'] . '::admin',
                    ])->first();
                    if (!$permAdmin) {
                        $data['name'] = $name . '::admin';
                        $data['inherit_id'] = $permUser->id;
                        $permAdmin = Permission::create($data);
                        $roleAdmin->assignPermission($permAdmin);
                    } else {
                        Permission::where('id', $permAdmin->id)->update([
                            'slug' => json_encode($data['slug']),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Метод возвращает отображаемый в публичной части контнет
     *
     * @param Components $module
     * @return string
     * @throws \Throwable
     */
    public function getContent(Components $module)
    {
        \Auth::check();
        /** @var $storeManager Store */
        $storeManager = \App::make(Store::class);
        /** @var Collection $menuItemsCollection */
        $menuItemsCollection = $storeManager->getCollection(self::class);
        if (null === $menuItemsCollection) {
            $storeManager->pushCollection(self::class, self::where(function(Builder $query) {

            })->get());
            $menuItemsCollection = $storeManager->getCollection(self::class);
        }
        $data = $module->getData();
        $result = '';
        $scope = 'defaultSite';
        $menuId = (isset($data['data']->item_id->id)) ? $data['data']->item_id->id : null;

        $isGuest = \Auth::guest();

        $key = __METHOD__ . '::' . DomainManager::getSiteId() . '::menu_id-' . $menuId;
        $key .= ($isGuest) ? '-guest' : '-user';

        $isRedis = config('cache.default') == 'redis';

        $items = ($isRedis) ? \Cache::tags(['menu'])->get($key, null) : \Cache::get($key, null);
        /** @var \FastDog\Menu\Models\Menu $root */
        $root = $menuItemsCollection->where('id', $menuId)->first();


        if (null === $items) {
            if ($menuId && $root) {
                $items = $root->descendants()->where(function(Builder $query) use (&$scope) {
                    $query->where(Menu::STATE, Menu::STATE_PUBLISHED);
                })->$scope();

                $items = $items->get();

                /**
                 * Скрыть пункт меню по условиям авторизации
                 *
                 * @var $item self
                 */
                foreach ($items as &$item) {
                    $item->_hidden = 'N';
                    $showGuest = $item->getParameterByFilterData(['name' => 'SHOW_GUEST'], 'Y') == 'Y';
                    $showUser = $item->getParameterByFilterData(['name' => 'SHOW_USER'], 'Y') == 'Y';

                    if ((false === $showGuest) && ($isGuest === true) ||
                        (false === $showUser) && ($isGuest === false)
                    ) {
                        $item->_hidden = 'Y';
                    }
                }
                $items = $items->toHierarchy();

                if ($isRedis) {
                    \Cache::tags(['menu'])->put($key, $items, config('cache.ttl_view', 5));
                } else {
                    \Cache::put($key, $items, config('cache.ttl_view', 5));
                }
            }
        }

        $render_data = [
            'items' => $items,
            'module' => $module,
        ];

        \Event::fire(new MenuPrepare($render_data));

        /**
         * События обработки шаблонов
         */
        $prefixEvents = 'FastDog\Menu\Events\Site\\';

        switch ($data['data']->type->id) {
            case 'menu::item':
                if (isset($data['data']->template->id)) {
                    $data['data']->template->id;
                    /**
                     * Вычисляем имена событий и если они зарегистрированны вызываем
                     */
                    $baseName = substr(camel_case(str_replace('.', '_', $data['data']->template->id)), -11);

                    $beforeRendingEvent = $prefixEvents . ucfirst($baseName . 'BeforeRending');
                    $afterRendingEvent = $prefixEvents . ucfirst($baseName . 'AfterRending');

                    if (class_exists($beforeRendingEvent)) {
                        event(new $beforeRendingEvent($render_data));
                    }

                    if (view()->exists($data['data']->template->id))
                        $result = view($data['data']->template->id, $render_data)->render();

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
     * Метод возвращает директорию модуля
     *
     * @return string
     */
    public function getModuleDir()
    {
        return dirname(__FILE__);
    }

    /**
     * Возвращает параметры блоков добавляемых на рабочий стол администратора
     *
     * @return array
     */
    public function getDesktopWidget()
    {
        return [];
    }

    /**
     * Схема установки модуля
     *
     * @param $allSteps
     * @return mixed
     */
    public function getInstallStep(&$allSteps)
    {
        $last = array_last(array_keys($allSteps));

        $allSteps[$last]['step'] = 'menu_init';
        $allSteps['menu_init'] = [
            'title_step' => trans('app.Модуль Меню: подготовка, создание таблиц'),
            'step' => 'menu_install',
            'stop' => false,
            'install' => function($request) {
                sleep(1);
            },
        ];
        $allSteps['menu_install'] = [
            'title_step' => trans('app.Модуль Меню: таблицы созданы'),
            'step' => '',
            'stop' => false,
            'install' => function($request) {
                Menu::createDbSchema();
                MenuRouterCheckResult::createDbSchema();
                MenuStatistic::createDbSchema();
                sleep(1);
            },
        ];

        return $allSteps;
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

        array_push($result, [
            'icon' => 'fa-table',
            'name' => trans('app.Управление'),
            'route' => '/menu/index',
        ]);
        array_push($result, [
            'name' => '<i class="fa fa-power-off"></i> ' . trans('app.Диагностика'),
            'route' => '/menu/diagnostic',
        ]);
        array_push($result, [
            'name' => '<i class="fa fa-sitemap"></i> ' . trans('app.Карта сайта'),
            'route' => '/menu/sitemap',
        ]);
        array_push($result, [
            'name' => '<i class="fa fa-gears"></i> ' . trans('app.Настройки'),
            'route' => '/menu/configuration',
        ]);

        return $result;
    }

    /**
     * Возвращает массив таблиц для резервного копирования
     *
     * @return array
     */
    public function getTables()
    {
        // TODO: Implement getTables() method.
    }
}
