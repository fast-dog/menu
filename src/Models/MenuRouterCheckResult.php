<?php
namespace FastDog\Menu\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Проверка доступности маршрутов
 *
 * @package FastDog\Menu\Models
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuRouterCheckResult extends Model
{
    /**
     * Идентификатор пункта меню
     * @const string
     */
    const ITEM_ID = 'item_id';

    /**
     * Код сайта
     * @const string
     */
    const SITE_ID = 'site_id';

    /**
     * Код ответа HTTP
     * @const string
     */
    const CODE = 'code';

    /**
     * Название таблицы
     * @var string $table
     */
    public $table = 'menus_router_check_result';

    /**
     * Массив полей автозаполнения
     * @var array $fillable
     */
    public $fillable = [self::ITEM_ID, self::SITE_ID, self::CODE];


    /**
     * @return mixed
     */
    public function menu()
    {
        return $this->hasOne(Menu::class, 'id', self::ITEM_ID);
    }
}
