<?php

namespace FastDog\Menu\Listeners;

use FastDog\Menu\Events\CatalogCreateProperty as CatalogItemCreatePropertyEvent;

use FastDog\Menu\Models\CatalogProperty\CatalogProperty;
use FastDog\Menu\Models\CatalogProperty\CatalogPropertyHtmlValues;
use FastDog\Menu\Models\CatalogProperty\CatalogPropertyStringValues;
use Illuminate\Http\Request;

/**
 * При создание свойств в меню
 *
 * @package FastDog\Menu\Catalog\Listeners\Item
 * @version 0.2.0
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 *
 */
class CatalogCreateProperty extends \FastDog\Menu\Catalog\Listeners\Item\CatalogItemCreateProperty
{
    /**
     * @var Request $request
     */
    protected $request;

    /**
     * @return string
     */
    public function getCatalogItemProperty()
    {
        return CatalogProperty::class;
    }

    /**
     * @return string
     */
    protected function getCatalogItemsPropertyStringValues()
    {
        return CatalogPropertyStringValues::class;
    }

    /**
     * @param $child
     * @return bool
     */
    protected function isSearch($child): bool
    {
        return false;
    }

    /**
     * @return string
     */
    protected function getCatalogItemsPropertyHtmlValues()
    {
        return CatalogPropertyHtmlValues::class;
    }

    /**
     * CatalogItemCreateSearchPropertyIndex constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        parent::__construct($request);
    }

    /**
     * @param CatalogItemCreatePropertyEvent $event
     * @return void
     */
    public function handle($event)
    {
        parent::handle($event);
    }
}
