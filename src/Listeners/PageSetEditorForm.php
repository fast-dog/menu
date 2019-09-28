<?php

namespace FastDog\Menu\Listeners;

use FastDog\Core\Models\DomainManager;
use FastDog\Core\Models\FormFieldTypes;
use FastDog\Menu\Events\PageAdminPrepare as PageAdminPrepareEvent;
use FastDog\Menu\Menu;
use FastDog\Menu\Models\Page;
use Illuminate\Http\Request;

/**
 * Обработка данных в разделе администрирования
 *
 * Событие добавляет поля инициализации формы редактирования
 *
 * @package FastDog\Menu\Listeners
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class PageSetEditorForm
{
    /**
     * @var Request $request
     */
    protected $request;

    /**
     * AfterSave constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param PageAdminPrepareEvent $event
     * @return void
     */
    public function handle(PageAdminPrepareEvent $event)
    {
        /** @var $data array */
        $data = $event->getData();

        /** @var Page $item */
        $item = $event->getItem();

        $result = $event->getResult();

        $result['form'] = [
            'create_url' => 'menu/page/create',
            'update_url' => 'menu/page/create',
            'tabs' => (array)[
                (object)[
                    'id' => 'menu-general-tab',
                    'name' => trans('menu::forms.general.title'),
                    'active' => true,
                    'fields' => [
                        [
                            'type' => FormFieldTypes::TYPE_TEXT,
                            'name' => Menu::NAME,
                            'label' => trans('menu::forms.general.fields.name'),
                            'css_class' => 'col-sm-6',
                            'form_group' => false,
                        ],
                        [
                            'type' => FormFieldTypes::TYPE_TEXT_ALIAS,
                            'name' => Menu::ALIAS,
                            'label' => trans('menu::forms.general.fields.alias'),
                        ],
                    ],
                    'side' => [
                        [
                            'id' => 'access',
                            'type' => FormFieldTypes::TYPE_ACCESS_LIST,
                            'name' => Menu::SITE_ID, 'label' => trans('menu::forms.general.fields.access'),
                            'items' => DomainManager::getAccessDomainList(),
                            'css_class' => 'col-sm-12',
                            'active' => DomainManager::checkIsDefault(),
                        ],
                    ],
                ],
                (object)[
                    'id' => 'menu-item-media-tab',
                    'name' => trans('menu::forms.media.title'),
                    'fields' => [
                        [
                            'type' => FormFieldTypes::TYPE_COMPONENT_MEDIA,
                        ],
                    ],
                ],
                (object)[
                    'id' => 'menu-item-seo-tab',
                    'name' => trans('menu::forms.seo.title'),
                    'fields' => [
                        [
                            'type' => FormFieldTypes::TYPE_COMPONENT_SEO,
                        ],
                    ],
                ],
                (object)[
                    'id' => 'menu-item-extend-tab',
                    'name' => trans('menu::forms.extend.title'),
                    'fields' => [
                        [
                            'type' => FormFieldTypes::TYPE_COMPONENT_SAMPLE_PROPERTIES,
                            'model_id' => $item->getModelId(),
                            'model' => Menu::class,
                        ],
                    ],
                ],
                (object)[
                    'id' => 'menu-item-templates-tab',
                    'name' => trans('menu::forms.template.title'),
                    'expression' => 'function(item){ return (item.template_raw != undefined) }',
                    'fields' => [
                        [
                            'id' => 'template_raw',
                            'type' => FormFieldTypes::TYPE_CODE_EDITOR,
                            'name' => 'template_raw',
                            'css_class' => 'col-sm-12 m-t-xs',
                            'label' => trans('menu::forms.template.fields.html'),
                            'default_mode' => 'lazy',
                        ],
                    ],
                ],
                (object)[
                    'id' => 'menu-item-translate-tab',
                    'name' => trans('menu::forms.localization.title'),
                    'expression' => 'function(item){ return (item.translate != undefined) }',
                    'fields' => [
                        [
                            'type' => FormFieldTypes::TYPE_COMPONENT_TRANSLATE_ITEMS,
                        ],
                    ],
                ],
            ],
        ];

        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }

        $event->setResult($result);
        $event->setData($data);
    }
}
