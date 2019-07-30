<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 15.06.2018
 * Time: 12:49
 */

namespace FastDog\Menu\Models\CatalogProperty;


use FastDog\Menu\Catalog\Entity\CatalogItemsPropertyHtmlValues as CCatalogItemsPropertyHtmlValues;

/**
 * Class CatalogItemsPropertyHtmlValues
 * @package FastDog\Menu\Models\CatalogProperty
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class CatalogPropertyHtmlValues extends CCatalogItemsPropertyHtmlValues
{
    /**
     * @var string
     */
    public $table = 'menus_filters_properties_html_values';

}
