<?php

namespace FastDog\Page\Events;

use FastDog\Menu\Models\Page;

/**
 * После сохранения
 *
 * @package FastDog\Page\Events
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class PageAdminAfterSave
{
    /**
     * @var array $data
     */
    protected $data = [];

    /**
     * @var Page $item
     */
    protected $item;

    /**
     * PageAdminPrepare constructor.
     * @param $data
     * @param Page $item
     */
    public function __construct(array &$data, Page &$item)
    {
        $this->data = &$data;
        $this->item = &$item;
    }

    /**
     * @return Page
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
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}
