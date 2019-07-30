<?php
namespace FastDog\Menu\Models;




use FastDog\Core\Models\BaseModel;
use FastDog\Core\Table\Interfaces\TableModelInterface;

/**
 * Карта сайта
 *
 * @package FastDog\Menu\Models
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class SiteMap extends BaseModel implements TableModelInterface
{
    /**
     * @const string
     */
    const ROUTE = 'route';

    /**
     * @const string
     */
    const PRIORITY = 'priority';

    /**
     * @const string
     */
    const CHANGEFREQ = 'changefreq';

    /**
     * @var string $table
     */
    public $table = 'menus_sitemap';

    /**
     * @var array $fillable
     */
    public $fillable = [self::ROUTE, self::PRIORITY, self::CHANGEFREQ, self::SITE_ID];

    /**
     * Возвращает описание доступных полей для вывода в колонки...
     *
     * ... метод используется для первоначального конфигурирования таблицы,
     * дальнейшие типы, порядок колонок и т.д. будут храниться в обхекте BaseTable
     *
     * @return array
     */
    public function getTableCols(): array
    {
        return [
            [
                'name' => 'URL',
                'key' => SiteMap::ROUTE,

            ],
            [
                'name' => 'Priority',
                'key' => SiteMap::PRIORITY,
                'width' => 150,
                'link' => null,
                'class' => 'text-center',
            ],
            [
                'name' => 'Changefreq',
                'key' => SiteMap::CHANGEFREQ,
                'width' => 150,
                'link' => null,
                'class' => 'text-center',
            ],
            [
                'name' => 'Last update',
                'key' => SiteMap::UPDATED_AT,
                'width' => 150,
                'link' => null,
                'class' => 'text-center',
            ],
            [
                'name' => '#',
                'key' => 'id',
                'link' => null,
                'width' => 80,
                'class' => 'text-center',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getAdminFilters(): array
    {
        return [];
    }

}
