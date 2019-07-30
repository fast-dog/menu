<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMenusRouterCheckResult extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('menus_router_check_result')) {
            Schema::create('menus_router_check_result', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('item_id');
                $table->char('code', 3)->comment('Код ответа HTTP');
                $table->char('site_id', 3)->default('001')->comment('Код сайта');
                $table->index(['item_id', 'site_id'], 'IDX_menus_router_check_result');
                $table->index('site_id', 'IDX_menus_router_check_result_site_id');
                $table->timestamps();
                $table->softDeletes();
            });
            DB::statement("ALTER TABLE `menus_router_check_result` comment 'Проверка доступности маршрутов меню'");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menus_router_check_resultå');
    }
}
