<?php

namespace FastDog\Menu\Events;


use FastDog\Menu\Menu;

/**
 * Class CatalogCreateProperty
 * @package FastDog\Menu\Events
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class CatalogCreateProperty
{
    /**
     * @var array $properties
     */
    protected $properties;
    /**
     * @var Menu $item
     */
    protected $item;

    public function __construct($properties, $item)
    {
        $this->setProperties($properties);
        $this->setItem($item);
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
    }

    /**
     * @return Menu
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @param Menu $item
     */
    public function setItem($item)
    {
        $this->item = $item;
    }


}
