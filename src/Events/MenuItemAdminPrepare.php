<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 18.12.2016
 * Time: 23:50
 */

namespace FastDog\Menu\Events;


use App\Core\Interfaces\AdminPrepareEventInterface;
use FastDog\Menu\Models\Menu;

/**
 * Обработка данных в разделе администрирования
 *
 * @package FastDog\Menu\Events
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuItemAdminPrepare implements AdminPrepareEventInterface
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
     * @var $result array
     */
    protected $result;

    /**
     * MenuItemBeforeSave constructor.
     * @param array $data
     * @param $item
     * @param $result
     */
    public function __construct(array &$data, &$item, &$result)
    {
        $this->data = &$data;
        $this->item = &$item;
        $this->result = &$result;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param array $result
     */
    public function setResult($result)
    {
        $this->result = $result;
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
