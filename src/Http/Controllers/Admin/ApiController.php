<?php

namespace FastDog\Menu\Http\Controllers\Admin;


use FastDog\Config\Models\Translate;
use FastDog\Core\Http\Controllers\Controller;
use FastDog\Core\Models\BaseModel;
use FastDog\Core\Models\DomainManager;
use FastDog\Core\Models\ModuleManager;
use FastDog\Core\Table\Traits\TableTrait;
use FastDog\Menu\Models\MenuRouterCheckResult;
use FastDog\Menu\Models\MenuStatistic;
use FastDog\Menu\Menu;
use Carbon\Carbon;
use Curl\Curl;
use FastDog\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Дополнительный функционал
 *
 * @package FastDog\Menu\Http\Controllers\Admin
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ApiController extends Controller
{
    use TableTrait;

    /**
     * @var BaseModel $model
     */
    protected $model;

    /**
     * ApiController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->page_title = trans('menu::interface.Меню навигации');
    }

    /**
     * Информация о модуле
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInfo(Request $request):JsonResponse
    {
        $result = [
            'success' => true,
            'items' => [],
        ];
        $this->breadcrumbs->push(['url' => false, 'name' => trans('menu::interface.Информация')]);
        /**
         * @var $moduleManager ModuleManager
         */
        $moduleManager = \App::make('ModuleManager');
        /**
         * @var $module array
         */
        $module = $moduleManager->getInstance('menu');

        /**
         * Параметры модуля
         */
        array_push($result['items'], $module->getConfig());

        /**
         * Статистика по состояниям
         */
        array_push($result['items'], Menu::getStatistic());


        return $this->json($result, __METHOD__);
    }

    /**
     * Очистка кэша
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postClearCache(Request $request):JsonResponse
    {
        $result = ['success' => true, 'message' => ''];
        $tag = $request->input('tag');
        switch ($tag) {
            case 'all':
                \Cache::flush();
                $result['message'] = trans('app.Кэш успешно очищен.');
                break;
            case 'menu':
                if (env('CACHE_DRIVER') == 'redis') {
                    \Cache::tags([$tag])->flush();
                    $result['message'] = trans('app.Кэш') . ' "' . $tag . '" ' . trans('app.успешно очищен.');
                } else {
                    \Cache::flush();
                    $result['message'] = trans('app.Кэш успешно очищен.');
                }
                break;
        }

        return $this->json($result);
    }


    /**
     * Страница диагностики
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDiagnostic(Request $request):JsonResponse
    {
        $result = [
            'success' => true,
            'access' => $this->getAccess(),
            'items' => [],
            'Graph' => [
                'errors' => [],
                'success' => [],
            ],
        ];
        $this->breadcrumbs->push(['url' => false, 'name' => trans('app.Диагностика')]);

        $items = MenuStatistic::where(function(Builder $query) use ($request) {

            $period = $request->input('period', 'total');
            switch ($period) {
                case 'today':
                    $start = Carbon::now()->startOfDay();
                    $end = Carbon::now();
                    $query->where('created_at', '>=', $start->format(Carbon::DEFAULT_TO_STRING_FORMAT));
                    $query->where('created_at', '<=', $end->format(Carbon::DEFAULT_TO_STRING_FORMAT));
                    break;
                case 'current_month':
                    $start = Carbon::now()->startOfMonth();
                    $end = Carbon::now();
                    $query->where('created_at', '>=', $start->format(Carbon::DEFAULT_TO_STRING_FORMAT));
                    $query->where('created_at', '<=', $end->format(Carbon::DEFAULT_TO_STRING_FORMAT));
                    break;
                default:
                    break;
            }

            $query->where('site_id', DomainManager::getSiteId());
        });

        $_items = $items->orderBy('created_at', 'desc')->get();

        foreach ($_items as $item) {
            $time = ($item->created_at->getTimestamp() * 1000);
            array_push($result['Graph']['errors'], [
                $time, (int)$item->errors,
            ]);
            array_push($result['Graph']['success'], [
                $time, ((int)$item->success + (int)$item->redirect),
            ]);
        }

        /**
         * Статистика переходов
         */
        $report = $items->select(\DB::raw(<<<SQL
sum(errors) as errors, sum(success) as success, sum(redirect) as redirect
SQL
        ))->first();


        $total = ((int)$report->errors + (int)$report->success + (int)$report->redirect);

        array_push($result['items'], [
            'errors' => number_format((int)$report->errors, 0, '.', ' '),
            'errors_percent' => ($report->errors > 0) ? round((((int)$report->errors * 100) / $total), 2) : 0,
            'success' => number_format((int)$report->success, 0, '.', ' '),
            'success_percent' => ($report->success > 0) ? round((((int)$report->success * 100) / $total), 2) : 0,
            'redirect' => number_format((int)$report->redirect, 0, '.', ' '),
            'redirect_percent' => ($report->redirect > 0) ? round((((int)$report->redirect * 100) / $total), 2) : 0,
        ]);

        return $this->json($result, __METHOD__);
    }

    /**
     * Проверка маршрутов
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postCheckRoute(Request $request):JsonResponse
    {
        /** @var $user User */
        $user = auth()->getUser();

        $result = [
            'success' => true,
            'items' => [],
            'checked' => false,
            'access' => $this->getAccess(),
            'cols' => [
                [
                    'name' => trans('menu::forms.general.fields.name'),
                    'key' => Menu::NAME,
                    'domain' => true,
                    'extra' => true,
                    'link' => 'menu_item',
                ],
                [
                    'name' => trans('menu::interface.checked_at'),
                    'key' => 'checked_at',
                    'width' => 150,
                    'link' => null,
                    'class' => 'text-center',
                ],
                [
                    'name' => trans('menu::interface.result'),
                    'key' => 'result',
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
            ],
        ];

        $this->breadcrumbs->push([
            'url' => '/menu/index',
            'name' => trans('menu::interface.Меню')
        ]);
        $this->breadcrumbs->push(['url' => false, 'name' => trans('menu::interface.Диагностика')]);

        /** @var $root Menu */
        $root = Menu::where([
            'lft' => 1,
            Menu::SITE_ID => DomainManager::getSiteId(),
        ])->first();

        Carbon::setLocale('ru');

        $root->getDescendantsAndSelf()->each(function(Menu $item) use (&$result) {
            $data = $item->getData(false);
            $data['result'] = '';
            $check = $item->check;
            $data[Menu::NAME] = str_repeat('┊  ', $data[Menu::DEPTH]) . ' ' . $data[Menu::NAME];
            if ($check) {
                $data['checked_at'] =
                    '<i class="fa fa-clock-o" data-toggle="tooltip" title="' . $check->updated_at->format('d.m.y H:i') . '"></i> ' .
                    $check->updated_at->diffForHumans();

                switch ($check->code) {
                    case 200:
                        $data['result'] = '<a href="' . url($item->getRoute()) . '" target="_blank" data-toggle="tooltip"
                         title="' . trans('menu::interface.http_code') . ':' . $check->{'code'} . '">';
                        $data['result'] .= '<span class="label label-primary">' . trans('menu::interface.state_check.200') . '</span></a>';
                        break;
                    case 303:
                    case 302:
                        $data['result'] = '<a href="' . url($item->getRoute()) . '" target="_blank" data-toggle="tooltip"
                         title="' . trans('menu::interface.http_code') . ':' . $check->{'code'} . '">';
                        $data['result'] .= '<span class="label label-warning">' . trans('menu::interface.state_check.302') . '</span></a>';
                        break;
                    case 403:
                        $data['result'] = '<a href="' . url($item->getRoute()) . '" target="_blank" data-toggle="tooltip"
                         title="' . trans('menu::interface.http_code') . ':' . $check->{'code'} . '">';
                        $data['result'] .= '<span class="label label-danger">' . trans('menu::interface.state_check.403') . '</span></a>';
                        break;
                    case 404:
                        $data['result'] = '<a href="' . url($item->getRoute()) . '" target="_blank" data-toggle="tooltip"
                         title="' . trans('menu::interface.http_code') . ':' . $check->{'code'} . '">';
                        $data['result'] .= '<span class="label label-danger">' . trans('menu::interface.state_check.404') . '</span></a>';
                        break;
                    case 424:
                        $data['result'] = '<a href="' . url($item->getRoute()) . '" target="_blank" data-toggle="tooltip"
                         title="' . trans('menu::interface.http_code') . ':' . $check->{'code'} . '">';
                        $data['result'] .= '<span class="label label-danger">' . trans('menu::interface.state_check.424') . '</span></a>';
                        break;
                    default:
                        $data['result'] = '<span class="label">' . trans('menu::interface.state_check.default') . '</span>';
                        break;
                }
            } else {
                $data['result'] = '<span class="label">' . trans('menu::interface.state_check.default') . '</span>';
            }
            array_push($result['items'], $data);
        });


        return $this->json($result, __METHOD__);
    }

    /**
     * Проверка маршрутов
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \ErrorException
     */
    public function getCheckRoute(Request $request):JsonResponse
    {
        $result = [
            'success' => true,
            'total' => 0,
            'pages' => 0,
            'current' => 0,
            'progress' => 0,
            '_check' => [],
        ];
        /**
         * @var $root Menu
         */
        $root = Menu::where([
            'lft' => 1,
            Menu::SITE_ID => DomainManager::getSiteId(),
        ])->first();

        $page = 50;
        $checks = [];
        $items = $root->getDescendantsAndSelf();
        /**
         * @var $item Menu
         */
        foreach ($items as $item) {
            array_push($checks, $item);
        }


        $result['total'] = $root->rgt - $root->lft;
        $result['current'] = (int)$request->input('step', 1);
        $result['pages'] = ceil($result['total'] / $page);
        $result['progress'] = ceil(($result['current'] * 100) / $result['pages']);


        $checks = array_slice($checks, $page * ($result['current'] - 1), $page);
        $checker = new Curl();

        /**
         * @var $check Menu
         */
        foreach ($checks as $check) {
            if ($check->getLevel() > 1) {
                $route = $check->getRoute();
                if (!in_array($route, ['#'])) {
                    $url = url($route);

                    if (is_string($url)) {
                        $checker->get($url);

                        $checkItem = MenuRouterCheckResult::where([
                            MenuRouterCheckResult::ITEM_ID => $check->id,
                            MenuRouterCheckResult::SITE_ID => DomainManager::getSiteId(),
                        ])->first();

                        if (!$checkItem) {
                            MenuRouterCheckResult::create([
                                MenuRouterCheckResult::ITEM_ID => $check->id,
                                MenuRouterCheckResult::SITE_ID => DomainManager::getSiteId(),
                                MenuRouterCheckResult::CODE => $checker->httpStatusCode,
                            ]);
                        } else {
                            MenuRouterCheckResult::where('id', $checkItem->id)->update([
                                MenuRouterCheckResult::CODE => $checker->httpStatusCode,
                            ]);
                        }
                        array_push($result['_check'], $url);
                    }
                }
            }
        }

        return $this->json($result, __METHOD__);
    }

    /**
     * Сохранение локализации шаблона
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postMenuTranslate(Request $request): JsonResponse
    {
        $result = [
            'success' => true,
        ];
        $key = str_replace(['.', '::'], '/', $request->input('template.id'));
        /**
         * @var $items Collection
         */
        $items = Translate::where([
            Translate::CODE => $key,
        ])->get();
        $tmp = [];
        $updated = false;
        foreach ($request->input('translate', []) as $item) {
            $tmp[$item['id']] = $item['value'];
        }
        $items->each(function($item) use ($tmp, &$updated) {
            if (isset($tmp[$item->id]) && $tmp[$item->id] !== $item->{Translate::VALUE}) {
                Translate::where('id', $item->id)->update([
                    Translate::VALUE => $tmp[$item->id],
                ]);
                $updated = true;
            }
        });

        if ($updated) {
            Translate::getSegment($key, [], true);
        }

        return $this->json($result, __METHOD__);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @deprecated
     */
    public function postMenuReloadTranslate(Request $request): JsonResponse
    {
        $result = [
            'success' => true,
            'items' => [],
        ];
        $trans_key = str_replace(['.', '::'], '/', $request->input('template'));
        $langFile = resource_path() . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . config('app.locale')
            . DIRECTORY_SEPARATOR . $trans_key . '.php';
        if (file_exists($langFile)) {
            $request->merge([
                'reload_language_segment' => 'Y',
            ]);
            require_once($langFile);

            $result['items'] = Translate::getSegmentAdmin($trans_key);
        }

        return $this->json($result, __METHOD__);
    }

    /**
     * Перезапись HTML шаблона
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postMenuTemplate(Request $request): JsonResponse
    {
        $result = [
            'success' => true,
        ];
        $template = $request->input('template');
        if (view()->exists($template)) {
            $path = view($template)->getPath();
            \File::put($path, $request->input('raw'));
        }

        return $this->json($result, __METHOD__);
    }

}
