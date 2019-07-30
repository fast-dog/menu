<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 15.06.2018
 * Time: 12:49
 */

namespace FastDog\Menu\Models\CatalogProperty;


use FastDog\Menu\Catalog\Entity\CatalogItemsPropertyStringValues;

/**
 * Class CatalogPropertyStringValues
 * @package FastDog\Menu\Models\CatalogProperty
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class CatalogPropertyStringValues extends CatalogItemsPropertyStringValues
{
    /**
     * @var string
     */
    public $table = 'menus_filters_properties_string_values';


}
