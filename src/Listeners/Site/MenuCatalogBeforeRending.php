<?php
/**
 * Created by PhpStorm.
 * User: dg
 * Date: 05.02.2017
 * Time: 13:23
 */

namespace FastDog\Menu\Listeners\Site;


use FastDog\Menu\Config\Entity\DomainManager;
use FastDog\Menu\Events\Site\MenuMenuTopBeforeRending as MenuMenuTopBeforeRendingEvent;
use FastDog\Menu\Menu;
use Illuminate\Http\Request;

/**
 * Перед выводом шаблона
 *
 * Событие будет вызвано в публичной части сайта для шаблона views.public.001.modules.menu.example_page.blade.php
 *
 * @package FastDog\Menu\Listeners\Site
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class MenuCatalogBeforeRending extends MenuMenuTopBeforeRending
{
}
