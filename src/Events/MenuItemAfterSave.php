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
 * После сохранения
 *
 * @package FastDog\Menu\Events
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuItemAfterSave
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
     * MenuItemBeforeSave constructor.
     * @param array $data
     * @param $item
     */
    public function __construct(array &$data, &$item)
    {
        $this->data = &$data;
        $this->item = &$item;
    }

    /**
     * @return Menu
     */
    public function getItem()
    {
        return $this->item;
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
