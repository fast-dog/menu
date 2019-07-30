<?php
namespace FastDog\Menu\Models;



use Baum\Node;
use FastDog\Core\Media\Interfaces\MediaInterface;
use FastDog\Core\Media\Traits\MediaTraits;
use FastDog\Core\Models\DomainManager;
use FastDog\Core\Properties\Interfases\PropertiesInterface;
use FastDog\Core\Properties\Traits\PropertiesTrait;
use FastDog\Core\Store;
use FastDog\Core\Table\Interfaces\TableModelInterface;
use FastDog\Core\Traits\StateTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Меню навигации
 *
 * @package FastDog\Menu\Models
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class Menu extends Node implements TableModelInterface, PropertiesInterface, MediaInterface
{
    use SoftDeletes, StateTrait, PropertiesTrait, MediaTraits;

    /**
     * Имя
     * @const string
     */
    const NAME = 'name';

    /**
     * Псевдоним
     * @const string
     */
    const ALIAS = 'alias';

    /**
     * Маршрут
     * @const string
     */
    const ROUTE = 'route';

    /**
     * Дополнительные данные
     * @const string
     */
    const DATA = 'data';

    /**
     * Состояние
     * @const string
     */
    const STATE = 'state';

    /**
     * Глубина в дереве
     * @const string
     */
    const DEPTH = 'depth';

    /**
     * Код сайта
     * @const string
     */
    const SITE_ID = 'site_id';

    /**
     * Отчет о преходе: успешно
     * @const string
     */
    const STAT_SUCCESS = 'stat_success';

    /**
     * Отчет о преходе: ошибка
     * @const string
     */
    const STAT_ERROR = 'stat_error';

    /**
     * Отчет о преходе: перенаправление
     * @const string
     */
    const STAT_REDIRECT = 'stat_redirect';

    /**
     * Тип: меню
     * @const string
     */
    const TYPE_MENU = 'menu';

    /**
     * Тип: статичная ссылка
     * @const string
     */
    const TYPE_STATIC = 'static';

    /**
     * Тип: Псевдоним
     * @const string
     */
    const TYPE_ALIAS = 'alias';

    /**
     * Название таблицы
     * @var string $table
     */
    protected $table = 'menus';

    /**
     * Массив полей автозаполнения
     * @var array $fillable
     */
    protected $fillable = [self::NAME, self::ALIAS, self::ROUTE, self::DATA, self::STATE, self::SITE_ID, 'parent_id'];

    /**
     * Скрыть пункт меню
     *
     * Скрыть пункт меню при выводе в публичном разделе
     * @var string $_hidden
     */
    public $_hidden = 'N';

    /**
     * Дополнительные пунты меню свормированные динамически (в событиях и т.д.)
     * @var null
     */
    public $_children = null;

    /**
     * Текущий пункт меню активен
     * @var bool $active
     */
    public $active = false;

    /**
     * Состояние: Опубликовано
     * @const int
     */
    const STATE_PUBLISHED = 1;

    /**
     * Состояние: Не опубликовано
     * @const int
     */
    const STATE_NOT_PUBLISHED = 0;

    /**
     * Состояние: В корзине
     * @const int
     */
    const STATE_IN_TRASH = 2;

    /**
     * Приоритет для карты сайта
     * перенести в типаж
     * @var array $priority
     */
    protected $priority = [
        0 => 1.0, 1 => 0.9, 2 => 0.8, 3 => 0.7, 4 => 0.6,
        5 => 0.5, 6 => 0.4, 7 => 0.3, 8 => 0.2, 9 => 0.1,
        ];

    /**
     * Поле объединения дерева в режиме мультисайта
     * @var array $scoped
     */
    protected $scoped = [self::SITE_ID];

    /**
     * Массив полей преобразования даты-времени
     * @var array $dates
     */
    public $dates = ['deleted_at'];

    /**
     * Отношение к результату проверки
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function check()
    {
        return $this->hasOne('FastDog\Menu\Models\MenuRouterCheckResult', 'item_id', 'id');
    }

    /**
     * Отношение к свойствам категорий каталога для реализации предустановленного фильтра
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function catalogProperties()
    {
        return $this->hasMany('FastDog\Menu\Models\CatalogProperty\CatalogProperty', 'item_id', 'id')
            ->orderBy('sort');
    }

    /**
     * Подробные данные по модели
     *
     * @param bool $cached
     * @return array
     */
    public function getData($cached = true)
    {
        /** @var $storeManager Store */
        $storeManager = \App::make(Store::class);

        $key = 'menu::' . $this->id;
        $isRedis = config('cache.default') == 'redis';
        $data = ($isRedis) ? \Cache::tags(['menu'])->get($key, null) : \Cache::get($key, null);

        if ($cached === false) {
            $data = null;
        }
        if (config('cache.enabled') === false) {
            $data = null;
        }
        if (null === $data) {
            $menuItemsCollection = $storeManager->getCollection(\FastDog\Menu\Menu::class);
            if (null === $menuItemsCollection) {
                $storeManager->pushCollection(\FastDog\Menu\Menu::class, self::where(function (Builder $query) {

                })->get());
                $menuItemsCollection = $storeManager->getCollection(\FastDog\Menu\Menu::class);
            }
            //ancestors
            $parents = $this->id > 0 ?
                collect($menuItemsCollection->where($this->getLeftColumnName(), '<=', $this->getLeft())
                    ->where($this->getRightColumnName(), '>=', $this->getRight())
                    ->where('id', '!=', $this->id)->all()) : [];

            $parentsIds = [];
            foreach ($parents as $parent) {
                if ($parent->{Menu::DEPTH} >= 1) {
                    array_push($parentsIds, $parent->id);
                }
            }
            if (is_string($this->{self::DATA})) {
                $this->{self::DATA} = json_decode($this->{self::DATA});
            }
            $_data = $this->{self::DATA};
            $parent = $menuItemsCollection->where('id', $this->parent_id)->first();


            $data = [
                'id' => $this->id,
                Menu::NAME => $this->{Menu::NAME},
                Menu::DEPTH => $this->{Menu::DEPTH},
                Menu::ALIAS => $this->{Menu::ALIAS},
                Menu::STATE => $this->{Menu::STATE},
                'published' => $this->{Menu::STATE},
                Menu::ROUTE => $this->{Menu::ROUTE},
                Menu::SITE_ID => $this->{Menu::SITE_ID},
                'parent_id' => ($parent) ? $parent->id : $this->parent_id,
                'type' => (isset($_data->type)) ? $_data->type : '',
                'checked' => false,
                'total_children' => (($this->rgt - $this->lft) - 1) / 2,
                'menu_id' => array_first($parentsIds),
                'data' => $_data,
                'allow_modified' => true,
            ];


            if ($data[Menu::SITE_ID] !== DomainManager::getSiteId()) {
                $data['allow_modified'] = DomainManager::checkIsDefault();
            }
            if ($isRedis) {
                \Cache::tags(['menu'])->put($key, $data, config('cache.ttl_menu', 5));
            } else {
                \Cache::put($key, $data, config('cache.ttl_menu', 5));
            }
        }
        if (is_string($data['data'])) {
            $data->data = json_decode($data->data);
        }

        return $data;
    }

    /**
     * Метаданные
     *
     * Возвращает массив метаданных включая open Graph
     *
     * @return array
     */
    public function getMetadata()
    {
        $result = [];
        $data = $this->getData();

        if (isset($data['data'])) {
            $result['title'] = (isset($data['data']->meta_title)) ? $data['data']->meta_title : $data['name'];
            if (isset($data['data']->meta_description)) {
                $result['description'] = $data['data']->meta_description;
            }

            if (isset($data['data']->meta_keywords)) {
                $result['keywords'] = $data['data']->meta_keywords;
            }

            if (isset($data['data']->meta_robots)) {
                $result['robots'] = implode(',', $data['data']->meta_robots);
            } else {
                $result['robots'] = 'index,follow';
            }


            $result['og'] = [
                'title' => $result['title'],
                'type' => 'article',
                'url' => \Request::url(),
            ];

            $media = $this->getMedia();
            if ($media) {
                $image = $media->where('type', 'file')->first();
                if ($image) {
                    $result['og']['image'] = $image['value'];
                    $result['image_src'] = $image['value'];
                }
            }
        }

        if (isset($data['data']->hreflang)) {
            $result['hreflang'] = [];
            foreach ($data['data']->hreflang as $item) {
                if ($item->value !== '' && $item->code !== DomainManager::getSiteId()) {
                    array_push($result['hreflang'], $item);
                }
            }
        }

        return $result;
    }

    /**
     * Определение типа меню
     *
     * @return string
     */
    public function getType()
    {
        if ($this->{self::DEPTH} == 1) {
            return self::TYPE_MENU;
        }
        if (is_string($this->{self::DATA})) {
            $this->{self::DATA} = json_decode($this->{self::DATA});
        }
        if (isset($this->{self::DATA}->type)) {
            return $this->{self::DATA}->type;
        }

        return 'static';
    }

    /**
     * Получение типа меню
     *
     * @return null
     */
    public function getExtendType()
    {
        $result = null;
        if (is_string($this->{self::DATA})) {
            $this->{self::DATA} = json_decode($this->{self::DATA});
        }
        if (isset($this->{self::DATA}->type)) {
            if (isset($this->{self::DATA}->{'module_data'}->{'menu'})) {
                $result = array_first(array_filter($this->{self::DATA}->{'module_data'}->{'menu'}, function ($item) {
                    return $item->id == $this->{self::DATA}->type;
                }));
            }
        }
        if ($result && isset($result->name)) {
            return $result->name;
        }

        return null;
    }

    /**
     * Получение списка доступных пунктов меню первого уровня вложенности
     *
     * @return array
     */
    public static function getRoots()
    {
        $result = [];
        $roots = Menu::where(function (Builder $query) {
            $query->where('lft', 1);
            if (DomainManager::checkIsDefault() === false) {
                $query->where(Menu::SITE_ID, DomainManager::getSiteId());
            }

        })->get()
            ->each(function (Menu $root) use (&$result) {

                array_push($result, [
                    'id' => $root->id,
                    self::NAME => $root->{self::NAME} . ' (#' . $root->{self::SITE_ID} . ')',
                ]);

                $root->descendants()->limitDepth(1)->get()
                    ->each(function (Menu $item) use (&$result) {
                        $data = $item->getData();
                        $allow = true;
                        if ($data[Menu::SITE_ID] !== DomainManager::getSiteId()) {
                            $allow = DomainManager::checkIsDefault();
                        }
                        if ($allow) {
                            $data[self::NAME] = str_repeat('┊  ', $data[self::DEPTH]) . ' ' . $data[self::NAME];
                            array_push($result, [
                                'id' => $data['id'],
                                self::NAME => $data[self::NAME],
                            ]);
                        }
                    });
            });

        return $result;
    }

    /**
     * Список меню
     *
     * Используется в разделе администрирования, в выпадающих списках
     *
     * @return array
     */
    public static function getAll()
    {
        $result = [];
        $roots = Menu::where('lft', 1)->get();

        foreach ($roots as $root) {
            $allow = true;
            //проверяем принадлежность меню к сайту, разрешено ли добавлять текущему пользователю?
            if ($root->{Menu::SITE_ID} !== DomainManager::getSiteId()) {
                $allow = DomainManager::checkIsDefault();
            }
            if ($allow) {
                $_roots = $root->descendants()->limitDepth(1)->get();
                foreach ($_roots as $root) {
                    $items = self::find($root->id)->descendants()->get();
                    foreach ($items as $item) {
                        $data = $item->getData();
                        if ($data[self::DEPTH] - 2 > 0) {
                            $data[self::NAME] = str_repeat('┊ ', $data[self::DEPTH] - 2) . ' ' . $data[self::NAME];
                        }
                        $result[$root->id][] = [
                            'id' => $data['id'],
                            self::NAME => $data[self::NAME],
                        ];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Возвращает кол-во опубликованных пунктов
     *
     * @return mixed
     */
    public function getCountPublish()
    {
        /**
         * Из за global scope привязки по сайтам идет не корректный запрос в Eloquent
         */
        $count = \DB::select(
            \DB::raw(<<<SQL
  select count(*) as count FROM menus WHERE 
(site_id='{$this->{self::SITE_ID}}' AND ((lft > '{$this->lft}') AND (lft < '{$this->rgt}')) AND state=1) LIMIT 1
SQL
            ));

        return (isset($count[0]->count)) ? $count[0]->count : 0;
    }

    /**
     * Пустой элемент
     *
     * @return array
     */
    public static function getClearData()
    {
        return [
            'url' => '',
            'page_id' => 0,
            'page_title' => '',
            'image' => '',
            'image_second' => '',
            'category_id' => 0,
            'data' => [
                'type' => '',
            ],
        ];
    }

    /**
     * Информация по модулю
     *
     * для отображения в списке модулей
     * @param $data
     * @return string
     */
    public function getInstanceInfo($data)
    {
        $result = ['Меню навигации'];
        if (isset($data->menu_id)) {
            $menu = Menu::find($data->menu_id);
            array_push($result, $menu->name);
        }

        return implode(' :: ', $result);
    }

//    /**
//     * Типы меню в проекте
//     *
//     * @return array
//     */
//    public static function getTypes()
//    {
//        $result = [];
//        /**
//         * @var  $moduleManager ModuleManager
//         */
//        $moduleManager = \App::make('ModuleManager');
//        $modules = $moduleManager->getModules();
//        /**
//         * @var $module ModuleInterface
//         */
//        foreach ($modules as $module) {
//            $moduleType = $module->getMenuType();
//            if (is_array($moduleType) && count($moduleType) > 0) {
//                foreach ($moduleType as $item) {
//                    array_push($result, (object)$item);
//                }
//            }
//        }
//
//        usort($result, function ($a, $b) {
//            return $a->sort - $b->sort;
//        });
//
//        return $result;
//    }

    /**
     * Ссылка
     *
     * @param bool $url
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public function getUrl($url = true)
    {
        $route = $this->getRoute();
        if ($route) {
            return ($url) ? url($route) : $route;
        }
    }

    /**
     * Маршрут
     *
     * @param bool $return_route
     * @return mixed
     */
    public function getRoute($return_route = false)
    {
        $data = $this->getData();

        if (isset($data['data']->route_data->alias) && $return_route === false) {
            return $data['data']->route_data->alias;
        }

        if (null === $this->{self::ROUTE}) {
            $this->{self::ROUTE} = '';
        }

        return $this->{self::ROUTE};
    }

    /**
     * Имя
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->{self::NAME};
    }

    /**
     * Цепочка навигации
     *
     * @return array
     */
    public function getPath()
    {
        $result = [];
        $parents = $this->ancestors()->get();
        array_push($result, [
            'id' => 0,
            'name' => trans('public.Главная'),
            'url' => '/',
        ]);
        /**
         * @var $parent Menu
         */
        foreach ($parents as $parent) {
            $data = $parent->getData();
            $exclude = ($parent->getParameterByFilterData(['name' => 'EXCLUDE_BREADCRUMBS'], 'N') == 'Y');

            if ($exclude === false && $parent->lft > 1) {
                $url = $parent->getUrl();
                array_push($result, [
                    'id' => $parent->id,
                    'name' => $parent->getName(),
                    'url' => \Request::url() == $url ? false : $url,
                ]);
            }
        }
        $ex = $this->getParameterByFilterData(['name' => 'EXCLUDE_BREADCRUMBS'], 'N') == 'N';
        if ($ex) {
            $url = $this->getUrl();

            array_push($result, [
                'id' => $this->id,
                'name' => $this->getName(),
                'url' => \Request::url() == $url ? false : $url,
            ]);
        }


        return $result;
    }

    /**
     * Поиск по части маршрута
     *
     * @param $segment
     * @return mixed
     */
    public static function findMenuItem($segment)
    {
        return self::where(function (Builder $query) use ($segment) {
            $query->where(self::ROUTE, 'LIKE', '%' . $segment . '%');
        })->withTrashed()->get();

    }

    /**
     * Исправление маршрута
     *
     * Метод исправляет маршрут компонента согласно заданным параметрам
     *
     * @param $new
     * @param $old
     * @param bool $idx
     */
    public function fixRoute($new, $old, $idx = false)
    {
        if ($idx > 0) {
            $routeExp = explode('/', $this->{self::ROUTE});
            if (isset($routeExp[$idx])) {
                $routeExp[$idx] = $new;
            }
            $this->{self::ROUTE} = implode('/', $routeExp);
        } else {
            $this->{self::ROUTE} = str_replace($old, $new, $this->{self::ROUTE});
        }
    }

    /**
     * Фильтр по элементам
     *
     * Получение пунктов меню согласно заданному фильтру
     *
     * @param array $filter
     * @return array
     */
    public static function getAllItemByFilterData(array $filter = [])
    {
        $result = [];
        $searchExpCount = count($filter);
        $items = self::all();
        /**
         * @var $item self
         */
        foreach ($items as $key => &$item) {
            $data = $item->getData();
            if (isset($data['data'])) {
                if (count(array_intersect_assoc($filter, (array)$data['data'])) == $searchExpCount) {
                    array_push($result, $item);
                }
            }
        }

        return $result;
    }

    /**
     * Фильтр по дочерним элементам
     *
     * Получение пунктов меню согласно заданному фильтру
     * @param array $filter
     * @return array
     */
    public function getAllChildrenByFilterData(array $filter = [])
    {
        $result = [];
        $searchExpCount = count($filter);
        $items = self::getDescendants();
        /**
         * @var $item self
         */
        foreach ($items as $key => &$item) {
            $data = $item->getData(false);
            if (isset($data['data'])) {
                if (count(array_intersect_assoc($filter, (array)$data['data'])) == $searchExpCount) {
                    array_push($result, $item);
                }
            }
        }

        return $result;
    }

    /**
     * Фильтр параметров
     *
     * Получение параметров согласно заданному фильтру
     *
     * @param array $filter
     * @param $default
     * @return array
     */
    public function __getParameterByFilterData(array $filter = [], $default = null)
    {
        if (is_string($this->{self::DATA})) {
            $this->{self::DATA} = json_decode($this->{self::DATA});
        }
        /**
         * @var $item self
         */
        if (isset($this->{self::DATA}->{'properties'})) {
            foreach ($this->{self::DATA}->{'properties'} as $items) {
                foreach ($items as $name => $value) {
                    if (isset($filter[$name]) && $filter[$name] == $value) {
                        return (isset($items->{'value'})) ? $items->{'value'} : $items;
                    }
                }
            }
        }

        return $default;
    }

    /**
     * Ошибка перехода
     *
     * Обновляет количество ошибок при переходе по маршруту текущего пункта меню
     * @return int
     */
    public function error()
    {
        return $this->increment(self::STAT_ERROR);
    }

    /**
     * Перенаправление перехода
     *
     * Обновляет количество перенаправлени при переходе по маршруту текущего пункта меню
     * @return int
     */
    public function redirect()
    {
        return $this->increment(self::STAT_REDIRECT);
    }

    /**
     * Успешный переход
     *
     * Обновляет количество успешных переходов по маршруту текущего пункта меню
     * @return int
     */
    public function success()
    {
        $this->increment(self::STAT_SUCCESS);
    }



    /**
     * Возвращает описание доступных полей для вывода в колонки...
     *
     * ... метод используется для первоначального конфигурирования таблицы,
     * дальнейшие типы, порядок колонок и т.д. будут храниться в обхекте BaseTable
     *
     * @return array
     */
    public function getTableCols(): array
    {
        return [
            [
                'name' => trans('app.Название'),
                'key' => self::NAME,
                'domain' => true,
                'link' => 'category_item',
                'extra' => true,
            ],
            [
                'name' => trans('app.Дата'),
                'key' => 'created_at',
                'width' => 150,
                'link' => null,
                'class' => 'text-center',
            ],
            [
                'name' => '#',
                'key' => 'id',
                'link' => null,
                'width' => 80,
                'class' => 'text-center',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getAdminFilters(): array
    {
        $default = [
            [
                [
                    BaseFilter::NAME => self::NAME,
                    BaseFilter::PLACEHOLDER => trans('app.Название'),
                    BaseFilter::TYPE => BaseFilter::TYPE_TEXT,
                    BaseFilter::DISPLAY => false,
                    BaseFilter::OPERATOR => (new BaseOperator('LIKE', 'LIKE'))->getOperator(),

                ],
                BaseFilter::getLogicAnd(),
                [
                    BaseFilter::TYPE => BaseFilter::TYPE_TEXT,
                    BaseFilter::NAME => self::ALIAS,
                    BaseFilter::DISPLAY => true,
                    BaseFilter::PLACEHOLDER => trans('app.Псевдоним'),
                    BaseFilter::OPERATOR => (new BaseOperator())->getOperator(),
                    BaseFilter::VALIDATE => 'required|min:5',
                ],
                BaseFilter::getLogicAnd(),
                [
                    BaseFilter::TYPE => BaseFilter::TYPE_DATETIME,
                    BaseFilter::NAME => self::CREATED_AT,
                    BaseFilter::DISPLAY => true,
                    BaseFilter::PLACEHOLDER => trans('app.Дата создания'),
                    BaseFilter::OPERATOR => (new BaseOperator('BETWEEN', 'BETWEEN'))->getOperator(
                        [['id' => 'BETWEEN', 'name' => 'BETWEEN']]
                    ),
                ],
            ],
        ];

        return $default;
    }

    /**
     * Возвращает имя события вызываемого при обработке данных при передаче на клиент в разделе администрирования
     * @return string
     */
    public function getEventAdminPrepareName(): string
    {
        return MenuItemAdminPrepare::class;
    }

    /**
     * @return Collection
     */
    public function getDefaultProperties(): Collection
    {
        $result = [
            [
                BaseProperties::NAME => 'Адрес перенаправления',
                BaseProperties::ALIAS => 'REDIRECT_TO',
                BaseProperties::VALUE => '',
                BaseProperties::SORT => 100,
                BaseProperties::TYPE => BaseProperties::TYPE_STRING,
                BaseProperties::DATA => json_encode([
                    'description' => 'В случае если пункт меню не доступен по совокупности условий на этот адрес будет выполнен редирект.',
                ]),
            ],
            [
                BaseProperties::NAME => 'Код перенаправления',
                BaseProperties::ALIAS => 'REDIRECT_CODE',
                BaseProperties::VALUE => '303',
                BaseProperties::SORT => 200,
                BaseProperties::TYPE => BaseProperties::TYPE_STRING,
                BaseProperties::DATA => json_encode([
                    'description' => 'HTTP код при выполнение редиректа',
                ]),
            ],
            [
                BaseProperties::NAME => 'Исключить из навигации',
                BaseProperties::ALIAS => 'EXCLUDE_BREADCRUMBS',
                BaseProperties::VALUE => 'N',
                BaseProperties::SORT => 300,
                BaseProperties::TYPE => BaseProperties::TYPE_SELECT,
                BaseProperties::DATA => json_encode([
                    'description' => 'Исключить пункт меню из цепочки навигации - "хлебные крошки"',
                    'values' => [
                        ['id' => null, 'alias' => 'Y', 'name' => 'Разрешить',],
                        ['id' => null, 'alias' => 'N', 'name' => 'Запретить',],
                    ],
                ]),
            ],
            [
                BaseProperties::NAME => 'Обработка изображений',
                BaseProperties::ALIAS => 'IMAGES_RESIZE',
                BaseProperties::VALUE => 'Y',
                BaseProperties::SORT => 400,
                BaseProperties::TYPE => BaseProperties::TYPE_SELECT,
                BaseProperties::DATA => json_encode([
                    'description' => 'Параметр определяющий возможность автоматического изменения размеров изображений в списке Материалов, может быть использован в других модулях.',
                    'values' => [
                        ['id' => null, 'alias' => 'Y', 'name' => 'Разрешить',],
                        ['id' => null, 'alias' => 'N', 'name' => 'Запретить',],
                    ],
                ]),
            ],
            [
                BaseProperties::NAME => 'Ширина изображеня',
                BaseProperties::ALIAS => 'IMAGES_WIDTH',
                BaseProperties::VALUE => '250',
                BaseProperties::SORT => 500,
                BaseProperties::TYPE => BaseProperties::TYPE_STRING,
                BaseProperties::DATA => json_encode([
                    'description' => 'Размер по <strong>горизонтали</strong> к которму будут приведены изображения в случае автоматической обработки',

                ]),
            ],
            [
                BaseProperties::NAME => 'Высота изображеня',
                BaseProperties::ALIAS => 'IMAGES_HEIGHT',
                BaseProperties::VALUE => '180',
                BaseProperties::SORT => 600,
                BaseProperties::TYPE => BaseProperties::TYPE_STRING,
                BaseProperties::DATA => json_encode([
                    'description' => 'Размер по <strong>вертикали</strong> к которму будут приведены изображения в случае автоматической обработки',
                ]),
            ],
            [
                BaseProperties::NAME => 'Иконка',
                BaseProperties::ALIAS => 'ICON_CSS',
                BaseProperties::VALUE => '',
                BaseProperties::SORT => 700,
                BaseProperties::TYPE => BaseProperties::TYPE_FILE,
                BaseProperties::DATA => json_encode([
                    'description' => 'Изображение используемое для оформления пункта меню',
                ]),
            ],
            [
                BaseProperties::NAME => 'Альтернативный шаблон',
                BaseProperties::ALIAS => 'TEMPLATE',
                BaseProperties::VALUE => '',
                BaseProperties::SORT => 800,
                BaseProperties::TYPE => BaseProperties::TYPE_STRING,
                BaseProperties::DATA => json_encode([
                    'description' => 'Альтернативный шаблон для отображения содержимого пункта меню',
                ]),
            ],
            [
                BaseProperties::NAME => 'Доступ без авторизации',
                BaseProperties::ALIAS => 'SHOW_GUEST',
                BaseProperties::VALUE => 'Y',
                BaseProperties::SORT => 900,
                BaseProperties::TYPE => BaseProperties::TYPE_SELECT,
                BaseProperties::DATA => json_encode([
                    'description' => 'Доступ <strong>для всех, <span style="color:red">не авторизованных</span></strong> пользователей.',
                    'values' => [
                        ['id' => null, 'alias' => 'Y', 'name' => 'Разрешить',],
                        ['id' => null, 'alias' => 'N', 'name' => 'Запретить',],
                    ],
                ]),
            ],
            [
                BaseProperties::NAME => 'Доступ с авторизацией',
                BaseProperties::ALIAS => 'SHOW_USER',
                BaseProperties::VALUE => 'Y',
                BaseProperties::SORT => 900,
                BaseProperties::TYPE => BaseProperties::TYPE_SELECT,
                BaseProperties::DATA => json_encode([
                    'description' => 'Доступ <strong>только для авторизованных</strong> пользователей.',
                    'values' => [
                        ['id' => null, 'alias' => 'Y', 'name' => 'Разрешить',],
                        ['id' => null, 'alias' => 'N', 'name' => 'Запретить',],
                    ],
                ]),
            ],
        ];

        return collect($result);
    }

    /**
     * Children relation (self-referential) 1-N.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(get_class($this), $this->getParentColumnName())
            ->where(self::STATE, self::STATE_PUBLISHED)
            ->orderBy($this->getOrderColumnName());
    }

    /**
     * Приведение маршрута к нормальному формату
     *
     * @param $route
     * @return mixed|string
     */
    public function sanitizeRoute($route)
    {
        if (strpos($route, 'http') === false) {
            $route = '/' . array_first(explode('#', $route));
            $route = str_replace(['//'], '/', $route);
        }

        return $route;
    }

    /**
     * Уровень вложенности маршрута от корня
     *
     * @param $route
     * @return int
     */
    protected function getDepthRoute($route)
    {
        $count = 0;
        $route = explode('/', $route);
        array_filter($route, function ($item) use (&$count) {
            if (!empty($item)) {
                $count++;
            };
        });

        return $count;
    }

    /**
     * Получение всех маршрутов пунктов меню для нужд карты сайта
     *
     * @param string $site_id
     * @return array
     */
    public function getRoutes($site_id = '000')
    {
        $routeList = [];
        $route = $this->sanitizeRoute($this->{self::ROUTE});
        $depth = $this->getDepthRoute($route);


        if (is_string($this->{self::DATA})) {
            $this->{self::DATA} = json_decode($this->{self::DATA});
        }
        if (isset($this->{self::DATA}->type)) {
            switch ($this->{self::DATA}->type) {
                case 'static':
                    $routeList[$this->{self::ROUTE}] = [
                        'url' => (strpos($route, 'http') === false) ?
                            url(config('app.url') . $route) : $route,
                        'depth' => $depth,
                        'priority' => (isset($this->priority[$depth])) ? $this->priority[$depth] : 0.1 * 10,
                    ];
                    break;
                case 'catalog_index':
                case 'catalog_categories':
                    if (isset($this->data->route_data) && (!isset($routeList[$this->{self::ROUTE}]))) {
                        $catalogCategoryRoutes = Catalog::getCategoryRoutes($this->data->route_data->route, $site_id);

                        $routeList[$this->{self::ROUTE}] = [
                            'url' => url(config('app.url') . $route),
                            'depth' => $depth,
                            'priority' => (isset($this->priority[$depth])) ? $this->priority[$depth] : 0.1,
                        ];

                        foreach ($catalogCategoryRoutes as $route) {
                            $route = $this->sanitizeRoute($route);
                            $depth = $this->getDepthRoute($route);
                            $routeList[$route] = [
                                'url' => url(config('app.url') . $route),
                                'depth' => $depth,
                                'priority' => (isset($this->priority[$depth])) ? $this->priority[$depth] : 0.1,
                            ];
                        }
                    }
                    break;
                case 'content_blog':
                    if (isset($this->data->route_data) && (!isset($routeList[$this->{self::ROUTE}]))) {
                        $routeList[$this->{self::ROUTE}] = [
                            'url' => url(config('app.url') . $route),
                            'depth' => $depth,
                            'priority' => (isset($this->priority[$depth])) ? $this->priority[$depth] : 0.1,
                        ];

                        if (isset($this->data->category_id)) {
                            $contentCategoryRoutes = Content::getCategoryRoutes($this->data->route_data->route, $site_id, $this->data->category_id);
                            foreach ($contentCategoryRoutes as $route) {
                                $route = $this->sanitizeRoute($route);
                                $depth = $this->getDepthRoute($route);
                                $routeList[$route] = [
                                    'url' => url(config('app.url') . $route),
                                    'depth' => $depth,
                                    'priority' => (isset($this->priority[$depth])) ? $this->priority[$depth] : 0.1,
                                ];
                            }
                        }
                    }
                    break;
                default:
                    $routeList[$this->{self::ROUTE}] = [
                        'url' => url(config('app.url') . $route),
                        'depth' => $depth,
                        'priority' => (isset($this->priority[$depth])) ? $this->priority[$depth] : 0.1 * 10,
                    ];
                    break;
            }
        }

        foreach ($routeList as $routeMap) {
            SiteMap::firstOrCreate([
                SiteMap::ROUTE => $routeMap['url'],
                SiteMap::PRIORITY => $routeMap['priority'] * 10,
                SiteMap::CHANGEFREQ => ($routeMap['depth'] > 2) ? 'weekly' : 'daily',
                SiteMap::SITE_ID => $site_id,
            ]);
        }

        return $routeList;
    }

    /**
     * Возвращает ключ доступа к ACL
     * @param string $type
     * @return string
     */
    public function getAccessKey($type = 'guest'): string
    {
        return strtolower(\FastDog\Menu\Menu::class) . '::' . DomainManager::getSiteId() . '::' . $type;
    }

    /**
     * @return array
     */
    public function getExtractParameterNames()
    {
        return ['type', 'template', 'html', 'category_id', 'url', 'route_instance',
            'item_id', 'image', 'alias_id', 'alias_menu_id'];
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function descendants()
    {
        return parent::descendants()->where(self::STATE, self::STATE_PUBLISHED); // TODO: Change the autogenerated stub
    }
}
