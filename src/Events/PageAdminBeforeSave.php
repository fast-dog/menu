<?php

namespace FastDog\Menu\Events;

/**
 * Перед сохранением материала
 *
 * @package FastDog\Content\Events
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class PageAdminBeforeSave
{
    /**
     * @var array $data
     */
    protected $data = [];


    /**
     * ContentAdminBeforeSave constructor.
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
