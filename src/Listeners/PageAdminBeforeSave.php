<?php

namespace FastDog\Menu\Listeners;

use FastDog\Menu\Events\PageAdminBeforeSave as EventPageAdminBeforeSave;
use FastDog\Core\Models\DomainManager;
use FastDog\Core\Models\Notifications;
use FastDog\Menu\Models\Menu;
use FastDog\Menu\Models\Page;
use FastDog\User\Models\User;
use Illuminate\Http\Request;

/**
 * Перед сохранением страницы
 *
 * Проверка и исправление маршрутов, генерация метаданных страницы, преобразование абсолютных адресов в относительные в
 * тексте публикации
 *
 * @package FastDog\Menu\Listeners
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class PageAdminBeforeSave
{
    /**
     * @var Request $request
     */
    protected $request;

    /**
     * ContentAdminBeforeSave constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param EventPageAdminBeforeSave $event
     * @return void
     */
    public function handle(EventPageAdminBeforeSave $event)
    {
        //$moduleManager = \App::make(ModuleManager::class);
        $data = $event->getData();
        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }
        $config = null;
        $data = \FastDog\Content\Content::prepareTextBeforeSave($data);

        /**
         * @var $user User
         */
        $user = \Auth::getUser();

        $id = $this->request->input('id', null);
        if ($id > 0) {
            /**
             * @var  $item Page
             */
            $item = Page::find($id);
            if ($item) {
                /**
                 * Проверка существования пункта меню с старым псевдонимом в маршруте компонента и
                 * исправление маршрута
                 */
                if ($item->{Page::ALIAS} <> $data[Page::ALIAS]) {
                    $menuItems = Menu::findMenuItem($item->{Page::ALIAS});
                    /**
                     * @var $menuItem Menu
                     */
                    foreach ($menuItems as $menuItem) {
                        $_data = $menuItem->getData();
                        if (isset($_data['data']->type)) {
                            if ($_data['data']->type == 'page_item') {
                                $routeExp = explode('/', $_data['route']);
                                $menuItem->fixRoute($data[Page::ALIAS], $item->{Page::ALIAS}, count($routeExp) - 1);
                                if ($_data['route'] !== $menuItem->getRoute()) {
                                    $this->request->merge([
                                        'update_canonical' => 'Y',
                                    ]);
                                    Menu::where('id', $menuItem->id)->update([
                                        Menu::ROUTE => $menuItem->getRoute(),
                                    ]);
                                    $menuItem = Menu::find($menuItem->id);
                                    Notifications::add([
                                        Notifications::TYPE => Notifications::TYPE_CHANGE_ROUTE,
                                        'message' => 'При изменение псевдонима страницы <a href="/{ADMIN}/#!/page/item/' . $item->id .
                                            '" target="_blank">#' . $item->id . '</a> обновлен маршрут меню ' .
                                            '<a href="/{ADMIN}/#!/menu/' . $menuItem->id . '" target="_blank">#' .
                                            $menuItem->id . '</a>',
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $item = new Page();
        }

        if (is_string($data['data'])) {
            $data['data'] = json_decode($data['data']);
        }


        if (isset($data['data']->{'meta_title'}) && $data['data']->{'meta_title'} == '') {
            // $data['data']->{'meta_title'} = $data['data']->{'meta_title'};
            $data['data']->{'meta_title'} = $data[Page::NAME];
        }

        /**
         * Генерировать MetaKeyword из текста материалов
         */
        if (!isset($data['data']->{'meta_keywords'})) {
            $data['data']->{'meta_keywords'} = '';
        }
        if (class_exists('FastDog\Content\Models\Content')) {
            $countWord = 20;
            $text = strip_tags($data[Page::INTROTEXT]) . strip_tags($data[Page::FULLTEXT]);
            if (strlen($text)) {
                $topWord = \FastDog\Content\Models\Content::top_words($text, $countWord);
                $data['data']->{'meta_keywords'} = implode(', ', $topWord);
            }
        }


        /**
         * Генерировать Meta Description из текста материалов
         */
        if (!isset($data['data']->{'meta_description'})) {
            $data['data']->{'meta_description'} = '';
        }

        $text = strip_tags($data[Page::INTROTEXT]);
        if (strlen($text)) {
            $data['data']->{'meta_description'} = trim(str_limit($text, 200));
        }


        /**
         * Генерировать теги из текста материалов
         */
        if (!isset($data['data']->{'meta_search_keywords'})) {
            $data['data']->{'meta_search_keywords'} = '';
        }
        if (class_exists('FastDog\Content\Models\Content')) {
            $countWord = 5;
            $text = strip_tags($data[Page::INTROTEXT]) . strip_tags($data[Page::FULLTEXT]);
            if (strlen($text)) {
                $topWord = \FastDog\Content\Models\Content::top_words($text, $countWord);
                $data['data']->{'meta_search_keywords'} = implode(', ', $topWord);
            }
        }


        /**
         * Преобразование абсолютных адресов в относительные в тексте публикации
         */
        $domainList = DomainManager::getAccessDomainList();
        foreach ($domainList as $item) {
            if ($item['id'] == DomainManager::getSiteId()) {
                $baseDomain = $item[DomainManager::URL];
                $data[Page::INTROTEXT] = str_replace($baseDomain, '', $data[Page::INTROTEXT]);
                $data[Page::FULLTEXT] = str_replace($baseDomain, '', $data[Page::FULLTEXT]);
            }
        }

        if (!is_string($data['data'])) {
            $data['data'] = json_encode($data['data']);
        }
        $event->setData($data);
    }
}
