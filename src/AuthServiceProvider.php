<?php

namespace FastDog\Menu;


use FastDog\Menu\Policies\MenuPolicy;
use FastDog\User\Policies\UserPolicy;
use FastDog\User\Policies\UsersMailingPolicy;
use FastDog\User\Policies\UsersMailingTemplatesPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

/**
 * Class AuthServiceProvider
 *
 * @package FastDog\Menu
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * Сопоставление политик для приложения.
     *
     * @var array
     */
    protected $policies = [
        \FastDog\Menu\Menu::class => MenuPolicy::class
    ];

    /**
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
