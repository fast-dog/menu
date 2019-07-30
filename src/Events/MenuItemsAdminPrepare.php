<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 18.12.2016
 * Time: 23:50
 */

namespace FastDog\Menu\Events;


use FastDog\Menu\Models\Menu;

/**
 * Обработка списка меню в разделе администрирования
 *
 * @package FastDog\Menu\Events
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuItemsAdminPrepare
{

    /**
     * @var array $data
     */
    protected $data = [];

    /**
     * @var Menu $items
     */
    protected $items;

    /**
     * MenuItemsAdminPrepare constructor.
     * @param array $data
     * @param $items
     */
    public function __construct(array &$data, &$items)
    {
        $this->data = &$data;
        $this->items = &$items;
    }

    /**
     * @return Menu
     */
    public function getItem()
    {
        return $this->items;
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
}
