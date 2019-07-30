<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 06.01.2017
 * Time: 22:40
 */

namespace FastDog\Menu\Events;


/**
 * Обработка данных в публичной части
 *
 * @package FastDog\Menu\Events
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuPrepare
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
