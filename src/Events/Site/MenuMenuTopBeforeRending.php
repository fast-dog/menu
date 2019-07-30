<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 29.08.2018
 * Time: 10:59
 */

namespace FastDog\Menu\Events\Site;

/**
 * Class MenuTopBeforeRending
 *
 * @package FastDog\Menu\Events\Site
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuMenuTopBeforeRending
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
