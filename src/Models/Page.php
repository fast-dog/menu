<?php

namespace FastDog\Menu\Models;

use FastDog\Core\Media\Interfaces\MediaInterface;
use FastDog\Core\Media\Traits\MediaTraits;
use FastDog\Core\Models\BaseModel;
use FastDog\Core\Properties\Interfases\PropertiesInterface;
use FastDog\Core\Properties\Traits\PropertiesTrait;
use FastDog\Core\Table\Filters\BaseFilter;
use FastDog\Core\Table\Filters\Operator\BaseOperator;
use FastDog\Core\Table\Interfaces\TableModelInterface;
use FastDog\Core\Traits\StateTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Статичные страницы
 *
 * @package FastDog\Menu\Models
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class Page extends BaseModel implements TableModelInterface, PropertiesInterface, MediaInterface
{
    use SoftDeletes, StateTrait, PropertiesTrait, MediaTraits;

    /**
     * Краткое описание
     * @const string
     */
    const INTROTEXT = 'introtext';

    /**
     * Полное описание
     * @const string
     */
    const FULLTEXT = 'fulltext';

    /**
     * Кол-во просмотров
     * @const string
     */
    const VIEW_COUNTER = 'view_counter';

    /**
     * @var string $table
     */
    public $table = 'pages';

    /**
     * Массив полей автозаполнения
     * @var array $fillable
     */
    protected $fillable = [self::NAME, self::ALIAS, self::DATA, self::SITE_ID, self::TYPE, self::INTROTEXT, self::FULLTEXT, self::VIEW_COUNTER];

    /**
     * Возвращает общую информацию о текущей модели
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'id' => $this->id,
            self::NAME => $this->{self::NAME},
            self::ALIAS => $this->{self::ALIAS},
            self::DATA => $this->{self::DATA},
            self::SITE_ID => $this->{self::SITE_ID},
            self::TYPE => $this->{self::TYPE},
            self::INTROTEXT => $this->{self::INTROTEXT},
            self::FULLTEXT => $this->{self::FULLTEXT},
        ];
    }

    /**
     * Возвращает описание доступных полей для вывода в колонки...
     *
     * ... метод используется для первоначального конфигурирования таблицы,
     * дальнейшие типы, порядок колонок и т.д. будут храниться в объекте BaseTable
     *
     * @return array
     */
    public function getTableCols(): array
    {
        return [
            [
                'name' => trans('menu::forms.general.fields.name'),
                'key' => self::NAME,
                'domain' => true,
                'link' => 'page_item',
                'extra' => true,
                'action' => [
                    'edit' => true,
                    'delete' => true,
                ],
            ],
            [
                'name' => trans('menu::forms.general.fields.created_at'),
                'key' => 'created_at',
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
     * Определение фильтров таблицы в виде массива
     *
     * @return array
     */
    public function getAdminFilters(): array
    {
        $default = [
            [
                [
                    BaseFilter::NAME => self::NAME,
                    BaseFilter::PLACEHOLDER => trans('menu::forms.general.fields.name'),
                    BaseFilter::TYPE => BaseFilter::TYPE_TEXT,
                    BaseFilter::DISPLAY => true,
                    BaseFilter::OPERATOR => (new BaseOperator('LIKE', 'LIKE'))->getOperator(),
                ],
                BaseFilter::getLogicAnd(),
                [
                    BaseFilter::TYPE => BaseFilter::TYPE_TEXT,
                    BaseFilter::NAME => self::ALIAS,
                    BaseFilter::DISPLAY => true,
                    BaseFilter::PLACEHOLDER => trans('menu::forms.general.fields.alias'),
                    BaseFilter::OPERATOR => (new BaseOperator())->getOperator(),
                    BaseFilter::VALIDATE => 'required|min:5',
                ],
                BaseFilter::getLogicAnd(),
                [
                    BaseFilter::TYPE => BaseFilter::TYPE_DATETIME,
                    BaseFilter::NAME => self::CREATED_AT,
                    BaseFilter::DISPLAY => true,
                    BaseFilter::PLACEHOLDER => trans('menu::forms.general.fields.created_at'),
                    BaseFilter::OPERATOR => (new BaseOperator('BETWEEN', 'BETWEEN'))->getOperator(
                        [['id' => 'BETWEEN', 'name' => 'BETWEEN']]
                    ),
                ],
            ],
        ];

        return $default;
    }
}
