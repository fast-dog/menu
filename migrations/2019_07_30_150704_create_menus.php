<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMenus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('menus')) {
            Schema::create('menus', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('parent_id')->default(0);
                $table->integer('lft');
                $table->integer('rgt');
                $table->char('site_id', 3)->default('001')->comment('Код сайта');
                $table->integer('depth')->default(0);
                $table->string('name')->comment('Название');
                $table->string('route')->nullable()->comment('Маршрут');
                $table->string('alias')->nullable()->comment('Псевдоним');
                $table->tinyInteger(\FastDog\Menu\Models\Menu::STATE)
                    ->default(\FastDog\Menu\Models\Menu::STATE_NOT_PUBLISHED)->comment('Состояние');
                $table->json('data')->nullable()->comment('Дополнительные параметры');
                $table->integer('stat_success')->default(0)->comment('Успешных переходов');
                $table->integer('stat_redirect')->default(0)->comment('Перенаправлений');
                $table->integer('stat_error')->default(0)->comment('Переходов с ошибками');
                $table->index('alias', 'IDX_menus_alias');
                $table->timestamps();
                $table->softDeletes();

                $table->index('lft');
                $table->index('rgt');
                $table->index('parent_id');
            });
            DB::statement("ALTER TABLE `menus` comment 'Навигация по сайту'");
            DB::unprepared("DROP TRIGGER IF EXISTS menus_before_update");

            $user = config('database.connections.mysql.username');
            $host = config('database.connections.mysql.host');

            DB::unprepared("
CREATE  
        DEFINER = '{$user}'@'{$host}'
TRIGGER menus_before_update
	BEFORE UPDATE
	ON menus
	FOR EACH ROW
BEGIN
  IF (NEW.stat_error <> OLD.stat_error) THEN
    SET @statisticId = (SELECT
        ID
      FROM menus_statistic AS ms
      WHERE DATE_FORMAT(ms.created_at, '%Y-%m-%d') = CURDATE() LIMIT 1);

    IF (@statisticId > 0) THEN
      UPDATE menus_statistic SET errors = errors + 1 WHERE ID = @statisticId;
      ELSE  
      INSERT LOW_PRIORITY INTO menus_statistic(created_at,errors,site_id) VALUES (NOW(),1,NEW.site_id);
    END IF; 
  END IF;

    IF (NEW.stat_success <> OLD.stat_success) THEN
    SET @statisticId = (SELECT
        ID
      FROM menus_statistic AS ms
      WHERE DATE_FORMAT(ms.created_at, '%Y-%m-%d') = CURDATE() LIMIT 1);

    IF (@statisticId > 0) THEN
      UPDATE menus_statistic SET success = success + 1 WHERE ID = @statisticId;
      ELSE  
      INSERT LOW_PRIORITY INTO menus_statistic(created_at,success,site_id) VALUES (NOW(),1,NEW.site_id);
    END IF; 
  END IF;
    
  IF (NEW.stat_redirect <> OLD.stat_redirect) THEN
    SET @statisticId = (SELECT
        ID
      FROM menus_statistic AS ms
      WHERE DATE_FORMAT(ms.created_at, '%Y-%m-%d') = CURDATE() LIMIT 1);

    IF (@statisticId > 0) THEN
      UPDATE menus_statistic SET redirect = redirect + 1 WHERE ID = @statisticId;
      ELSE  
      INSERT LOW_PRIORITY INTO menus_statistic(created_at,redirect,site_id) VALUES (NOW(),1,NEW.site_id);
    END IF; 
  END IF;
END
 ");

            // default all
            FastDog\Menu\Models\Menu::create([
                'name' => 'root',
                'alias' => 'root',
                'lft' => 1,
                'rgt' => 2,
                'site_id' => '000',
            ]);

            /** @var FastDog\Menu\Models\Menu $root default site 1 */
            $root = FastDog\Menu\Models\Menu::create([
                'name' => 'root',
                'alias' => 'root',
                'lft' => 1,
                'rgt' => 2,
                'site_id' => '001',
            ]);
            /** @var FastDog\Menu\Models\Menu $topMenu */
            $topMenu = FastDog\Menu\Models\Menu::create([
                'name' => 'Top menu',
                'alias' => 'top-menu',
                'site_id' => '001',
                'route' => \DB::raw('null'),
            ]);

            $topMenu->makeLastChildOf($root);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menus');
    }
}
