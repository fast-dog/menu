<?php
namespace FastDog\Menu\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Статистика переходов по маршрутам
 *
 * Таблица заполняется автоматически, по триггеру: menus_before_update
 * @package FastDog\Menu\Models
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuStatistic extends Model
{
    /**
     * Ошибка перехода
     * @const string
     */
    const ERROR = 'errors';

    /**
     * Успешный переход
     * @const string
     */
    const SUCCESS = 'success';

    /**
     * Перенаправление
     * @const string
     */
    const REDIRECT = 'redirect';

    /**
     * Название таблицы
     * @var string $table
     */
    public $table = 'menus_statistic';

}
