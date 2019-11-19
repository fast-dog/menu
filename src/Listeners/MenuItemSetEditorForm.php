<?php

namespace FastDog\Menu\Listeners;

use FastDog\Core\Models\DomainManager;
use FastDog\Core\Models\FormFieldTypes;
use FastDog\Core\Models\ModuleManager;
use FastDog\Menu\Events\MenuSetFormGeneralFields;
use FastDog\Menu\Events\MenuSetFormTabs;
use FastDog\Menu\Menu;
use FastDog\Menu\Events\MenuItemAdminPrepare as MenuItemAdminPrepareEvent;
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
class MenuItemSetEditorForm
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
     * @param MenuItemAdminPrepareEvent $event
     * @return void
     */
    public function handle(MenuItemAdminPrepareEvent $event)
    {

        /**
         * @var $data array
         */
        $data = $event->getData();
        $item = $event->getItem();

        foreach ($data['data'] as $name => $value) {
            if (!isset($data[$name])) {
                $data[$name] = '';
            }
        }

        if (\Route::input('parent_id', null)) {
            $data['menu_id'] = \Route::input('parent_id', null);
        }
        if (!isset($data['route_instance'])) {
            $data['route_instance'] = [];
        }
        $result = $event->getResult();

        $menuItems = Menu::getAll();
        $result['menu_items'] = $menuItems;

        $fields = (array)[
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
            [
                'type' => FormFieldTypes::TYPE_TEXT,
                'name' => Menu::ROUTE,
                'readonly' => true,
                'css_class' => 'col-sm-12',
                'label' => trans('menu::forms.general.fields.route.name'),
                'help' => trans('menu::forms.general.fields.route.help'),
            ],
            [
                'id' => 'type',
                'type' => FormFieldTypes::TYPE_SELECT,
                'name' => 'type',
                'form_group' => false,
                'label' => trans('menu::forms.general.fields.type'),
                'css_class' => 'col-sm-6',
                'items' => Menu::getTypes(),
            ],
            [
                'id' => 'template',
                'type' => FormFieldTypes::TYPE_SELECT,
                'name' => 'template',
                'form_group' => false,
                'label' => trans('menu::forms.general.fields.template'),
                'items' => [],
                'option_group' => true,
                'expression' => 'function(item){ return (item.type.id != "alias") }',
            ],
//                        [
//                            'id' => 'category_id',
//                            'type' => FormFieldTypes::TYPE_SELECT,
//                            'name' => 'category_id',
//                            'form_group' => false,
//                            'label' => trans('app.Категория'),
//                            'items' => ContentCategory::getCategoryList(true),
//                            'expression' => 'function(item){ return (item.type.id == "content_blog"); }',
//                        ],
//                        [
//                            'id' => 'category_id',
//                            'type' => FormFieldTypes::TYPE_SELECT,
//                            'name' => 'category_id',
//                            'form_group' => false,
//                            'label' => trans('app.Категория каталога'),
//                            'items' => Category::getCategoryList(true),
//                            'expression' => 'function(item){
//                                    return (["catalog_categories","static"].indexOf(item.type.id) !== -1)
//                                     }',
//                        ],
            [
                'id' => 'alias_menu_id',
                'type' => FormFieldTypes::TYPE_SELECT,
                'name' => 'alias_menu_id',
                'label' => trans('menu::forms.general.fields.alias_menu_id'),
                'items' => Menu::getRoots(),
                'form_group' => false,
                'css_class' => 'col-sm-5',
                'expression' => 'function(item){ return (item.type.id == "alias") }',
            ],
            [
                'id' => 'alias_id',
                'type' => FormFieldTypes::TYPE_SELECT,
                'name' => 'alias_id',
                'form_group' => false,
                'css_class' => 'col-sm-7',
                'label' => trans('menu::forms.general.fields.alias'),
                'items' => (isset($data['alias_menu_id']['id']) &&
                    isset($menuItems[$data['alias_menu_id']['id']])) ? $menuItems[$data['alias_menu_id']['id']] : [],
                'expression' => 'function(item){ return (item.type.id == "alias") }',
            ],
            [
                'type' => FormFieldTypes::TYPE_TEXT,
                'name' => 'url',
                'label' => trans('menu::forms.general.fields.url'),
                'expression' => 'function(item){ return (item.type.id == "menu::static"); }',
            ],
            [
                'type' => FormFieldTypes::TYPE_TEXT,
                'name' => 'route_instance',
                'label' => trans('menu::forms.general.fields.route_instance'),
                'expression' => 'function(item){ return (item.type.id == "menu::static"); }',
            ],
            [
                'type' => FormFieldTypes::TYPE_SEARCH,
                'id' => 'route_instance',
                'name' => 'route_instance',
                'label' => trans('menu::interface.Материал'),
                'expression' => 'function(item){ return (item.type.id == "menu::page"); }',
                'data_url' => "page/search-list",
                'css_class' => 'col-sm-12',
                'form_group' => false,
                'readonly' => true,
                'filter' => (object)[
                    'item_id' => $item->id,
                ],
            ],
//                        [
//                            'id' => 'form_id',
//                            'type' => FormFieldTypes::TYPE_SELECT,
//                            'name' => 'form_id',
//                            'form_group' => false,
//                            'label' => trans('app.Форма'),
//                            'items' => [],
//                            'expression' => 'function(item){ return (item.type.id == "form"); }',
//                        ],
//                        [
//                            'id' => 'item_id',
//                            'type' => FormFieldTypes::TYPE_SELECT,
//                            'name' => 'item_id',
//                            'form_group' => false,
//                            'label' => trans('app.Справочник'),
//                            'items' => \FastDog\Menu\DataSource\Entity\DataSource::getAdminList(),
//                            'expression' => 'function(item){ return (item.type.id == "data_source_item"); }',
//                        ],
//                        [
//                            'type' => FormFieldTypes::TYPE_CODE_EDITOR,
//                            'id' => 'data_html',
//                            'name' => 'html',
//                            'readonly' => true,
//                            'css_class' => 'col-sm-12',
//                            'label' => trans('app.'),
//                        ],
        ];

        event(new MenuSetFormGeneralFields($fields));

        $tabs = (array)[
            (object)[
                'id' => 'menu-general-tab',
                'name' => trans('menu::forms.general.title'),
                'active' => true,
                'fields' => $fields,
                'side' => [
                    [
                        'id' => 'access',
                        'type' => FormFieldTypes::TYPE_ACCESS_LIST,
                        'name' => Menu::SITE_ID,
                        'label' => trans('menu::forms.general.fields.access'),
                        'items' => DomainManager::getAccessDomainList(),
                        'css_class' => 'col-sm-12',
                        'active' => DomainManager::checkIsDefault(),
                    ],
                    [
                        'id' => Menu::STATE,
                        'type' => FormFieldTypes::TYPE_SELECT,
                        'name' => Menu::STATE,
                        'label' => trans('content::forms.fields.state'),
                        'css_class' => 'col-sm-12',
                        'items' => Menu::getStatusList(),
                    ],
                    [
                        'id' => 'menu_id',
                        'type' => FormFieldTypes::TYPE_SELECT,
                        'name' => 'menu_id',
                        'label' => trans('menu::forms.general.fields.menu'),
                        'items' => Menu::getRoots(),
                        'css_class' => 'col-sm-12',
                        'expression' => 'function(item){ return ((item.id !== null ) && (!item.depth || item.depth >= 1)); }',
                    ],
                    [
                        'id' => 'parent_id',
                        'type' => FormFieldTypes::TYPE_SELECT,
                        'name' => 'parent_id',
                        'css_class' => 'col-sm-12',
                        'label' => trans('menu::forms.general.fields.parent'),
                        'items' => (isset($data['menu_id']['id']) &&
                            isset($menuItems[$data['menu_id']['id']])) ? $menuItems[$data['menu_id']['id']] : [],
                        'expression' => 'function(item){ return (!item.depth || item.depth >= 1); }',
                    ],
                    [
                        'id' => 'image',
                        'type' => FormFieldTypes::TYPE_MEDIA,
                        'name' => 'image',
                        'label' => trans('menu::forms.general.fields.image.name'),
                        'css_class' => 'col-sm-12',
                        'placeholder' => trans('menu::forms.general.fields.image.placeholder'),
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
//                (object)[
//                    'id' => 'tab-properties',
//                    'name' => trans('menu::forms.catalog.title'),
//                    'fields' => [
//                        [
//                            'type' => FormFieldTypes::TYPE_COMPONENT_CATALOG_ITEM_PROPERTIES,
//                            'model_id' => $item->getModelId(),
//                            'is_calculate' => true,
//                            'storage' => 'menu',
//                        ],
//                    ],
//                ],
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
        ];


        event(new MenuSetFormTabs($tabs));


        $result['form'] = [
            'create_url' => 'menu/create',
            'update_url' => 'menu/create',
            'tabs' => $tabs,
        ];

        $result['modules'] = ModuleManager::moduleList(true);
        $result['types'] = Menu::getTypes();
        $result['roots'] = Menu::getRoots();


        if (isset($data['type']->id) && isset($result['modules'][$data['type']->id]) && isset($data['template']->id)) {
            foreach ($result['modules'][$data['type']->id]['templates'] as $templates) {
                foreach ($templates as $_templates) {
                    foreach ($_templates as $template) {
                        if ($template['id'] == $data['template']->id) {
                            $data['template_raw'] = $template['raw'];
                            $data['translate'] = $template['translate'];
                        }
                    }
                }
            }
        }


        if (config('app.debug')) {
            $data['_events'][] = __METHOD__;
        }
        $event->setResult($result);
        $event->setData($data);
    }
}
