<?php

namespace FastDog\Menu\Console\Commands;


use FastDog\Core\Models\DomainManager;
use FastDog\Menu\Models\Menu;
use FastDog\Menu\Models\MenuRouterCheckResult;
use Carbon\Carbon;
use Curl\Curl;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Карта сайта
 *
 * php artisan sitemap {domain=http://xxx-xxx-xxx.xx}
 *
 * @package App\Console\Commands
 * @version 0.2.1
 * @author Андрей Мартынов <d.g.dev482@gmail.com>
 *
 */
class SiteMap extends Command
{
    /**
     * @var array
     */
    protected $priority = [
        0 => 1,
        1 => 0.9,
        2 => 0.8,
        3 => 0.7,
        4 => 0.6,
        5 => 0.5,
        6 => 0.4,
        7 => 0.3,
        8 => 0.2,
        9 => 0.1,
    ];
    /**
     * The name and signature of the console command.
     *
     * @var string $signature
     */
    protected $signature = 'sitemap';

    /**
     * The console command description.
     *
     * @var string $description
     */
    protected $description = 'Sitemap generator';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     *
     */
    public function handle()
    {
        $routeList = [];
        request()->merge(['_site_id' => '000']);
        // \Config::set('app.url', $this->argument('domain'));
        $site_id = '001';//DomainManager::getSiteId($this->argument('domain'));


        /** @var $root Menu */
        $root = Menu::where([
            'lft' => 1,
            Menu::SITE_ID => $site_id,
        ])->first();
        Carbon::setLocale(app()->getLocale());

        $checks = $root->getDescendantsAndSelf();
        try {

             MenuRouterCheckResult::where(function (Builder $query) {
                $query->where(MenuRouterCheckResult::CODE, '=', 200);
            })->get()
                ->each(function (MenuRouterCheckResult $item) use (&$routeList, $site_id) {
                    if ($item->menu) {
                        $item->menu->getRoutes($site_id);
                    }
                });

            $checker = new Curl();

            /** @var $check Menu */
            foreach ($checks as $check) {
                if ($check->getLevel() > 1) {
                    $route = $check->sanitizeRoute($check->{Menu::ROUTE});


                    if (!in_array($route, ['#'])) {
                        $url = (strpos($route, 'http') === false) ?
                            url(config('app.url') . $route) : $route;

                        if (is_string($url)) {

                            if(app()->runningInConsole()){
                                echo 'check url: ' . $url . PHP_EOL;
                            }

                            $checker->get($url);

                            if(app()->runningInConsole()){
                                echo $checker->httpStatusCode . PHP_EOL;
                            }

                            $checkItem = MenuRouterCheckResult::where([
                                MenuRouterCheckResult::ITEM_ID => $check->id,
                                MenuRouterCheckResult::SITE_ID =>  DomainManager::getSiteId(),
                            ])->first();

                            if (!$checkItem) {
                                MenuRouterCheckResult::create([
                                    MenuRouterCheckResult::ITEM_ID => $check->id,
                                    MenuRouterCheckResult::SITE_ID => DomainManager::getSiteId(),
                                    MenuRouterCheckResult::CODE => $checker->httpStatusCode,
                                ]);
                                if ($checker->httpStatusCode == 200) {
                                     SiteMap::firstOrCreate([
                                        'route' => $url,
                                        'priority'=> $this->priority[2],
                                        'site_id'=>$site_id
                                    ]);
                                }
                            } else {
                                MenuRouterCheckResult::where('id', $checkItem->id)->update([
                                    MenuRouterCheckResult::CODE => $checker->httpStatusCode,
                                ]);
                            }
                        }
                    }
                }
            }
        } catch (\ErrorException $e) {
            var_dump($e);
        }


        MenuRouterCheckResult::where(function (Builder $query) {
            $query->where(MenuRouterCheckResult::CODE, '=', 200);
        })->get()
            ->each(function (MenuRouterCheckResult $item) use (&$routeList, $site_id) {

                if ($item->menu) {
                    $item->menu->getRoutes($site_id);
                }
            });
    }
}
