<?php

namespace FastDog\Menu\Models;

use FastDog\Core\Media\Interfaces\MediaInterface;
use FastDog\Core\Media\Traits\MediaTraits;
use FastDog\Core\Models\BaseModel;
use FastDog\Core\Properties\Interfases\PropertiesInterface;
use FastDog\Core\Properties\Traits\PropertiesTrait;
use FastDog\Core\Traits\StateTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Статичные страницы
 *
 * @package FastDog\Menu\Models
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class Page extends BaseModel implements PropertiesInterface, MediaInterface
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
     * @var string $table
     */
    public $table = 'pages';

    /**
     * Массив полей автозаполнения
     * @var array $fillable
     */
    protected $fillable = [self::NAME, self::ALIAS, self::DATA, self::SITE_ID, self::TYPE, self::INTROTEXT, self::FULLTEXT];

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
}
