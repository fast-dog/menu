<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 05.02.2017
 * Time: 13:23
 */

namespace FastDog\Menu\Events\Site;

/**
 * Перед выводом в публичной части
 *
 * Событие будет вызвано в публичной части сайта, если для шаблона определен слушатель данного события.
 *
 * Пример реализации:
 *
 * Для шаблона views.public.001.modules.menu.example_page.blade.php определен слушатель с именем ExamplePageBeforeRending
 * где [ExamplePage]BeforeRending преобразованное имя шаблона, перед выводом меню использующее указанный шаблон будет вызвано
 * сопоставленный слушатель.
 *
 * Так же поддерживается событие [ExamplePage]AfterRending
 *
 * @package FastDog\Menu\Events\Site
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class ExamplePageBeforeRending
{
    /**
     * @var array $data
     */
    protected $data = [];

    /**
     * MenuPrepare constructor.
     * @param array $data
     */
    public function __construct(array &$data)
    {
        $this->data = &$data;
    }


    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}
