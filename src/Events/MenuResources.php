<?php

namespace FastDog\Menu\Events;


/**
 * Обработка данных в публичной части
 *
 * @package FastDog\Menu\Events
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuResources
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
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}
