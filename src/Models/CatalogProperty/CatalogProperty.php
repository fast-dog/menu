<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 025 25.09.18
 * Time: 10:50
 */

namespace FastDog\Menu\Models\CatalogProperty;

use \FastDog\Menu\Catalog\Entity\CatalogItemProperty as CCatalogItemProperty;



/**
 * Реализация хранения для предустановленного фильтра в пунктах меню
 *
 * @package FastDog\Menu\Models
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class CatalogProperty extends CCatalogItemProperty
{
    /**
     * Название таблицы
     * @var string $table
     */
    public $table = 'menus_filters_properties_values';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function propertyValue()
    {
        return $this->hasOne(CatalogPropertyListValues::class, 'id', self::VALUE);
    }
}
