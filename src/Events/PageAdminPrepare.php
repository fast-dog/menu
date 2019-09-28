<?php

namespace FastDog\Menu\Events;


use FastDog\Core\Interfaces\AdminPrepareEventInterface;
use FastDog\Menu\Models\Menu;
use Illuminate\Database\Eloquent\Model;

/**
 * Обработка данных в разделе администрирования
 *
 * @package FastDog\Menu\Events
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class PageAdminPrepare implements AdminPrepareEventInterface
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
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * @param array $result
     */
    public function setResult(array $result): void
    {
        $this->result = $result;
    }

    /**
     * @return Menu
     */
    public function getItem(): Model
    {
        return $this->item;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param $data
     * @return void
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
