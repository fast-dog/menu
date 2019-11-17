<?php

namespace FastDog\Menu\Events;


use FastDog\Menu\Menu;
use Illuminate\Http\Request;

/**
 * Перед передачей данных в метод генерации контента
 *
 * @package FastDog\Menu\Events
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class BeforePrepareContent
{
    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var \FastDog\Menu\Menu
     */
    protected $item;

    /**
     * @var array $data
     */
    protected $data = [];

    /**
     * BeforePrepareContent constructor.
     * @param array $data
     */
    public function __construct(Request $request, Menu &$item, array &$data)
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
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
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
