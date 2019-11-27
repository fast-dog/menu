<?php

namespace FastDog\Menu\Events;


use FastDog\Menu\Models\Menu;

/**
 * Сборка маршрута страницы
 *
 * @package FastDog\Menu\Events
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuBuildRoute
{

    /**
     * @var array $data
     */
    protected $data = [];

    /**
     * @var Menu $item
     */
    protected $item;


    /**
     * MenuPrepare constructor.
     * @param array $data
     */
    public function __construct(array &$data, Menu $item)
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
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return Menu
     */
    public function getItem(): Menu
    {
        return $this->item;
    }

    /**
     * @param Menu $item
     */
    public function setItem(Menu $item): void
    {
        $this->item = $item;
    }
}
