<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMenusStatistic extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('menus_statistic')) {
            Schema::create('menus_statistic', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->mediumInteger('success')->default(0)->comment('Обрбаотано успешно');
                $table->mediumInteger('errors')->default(0)->comment('Обработано с ошибкой');
                $table->mediumInteger('redirect')->default(0)->comment('Перенаправлений');
                $table->char('site_id', 3)->default('001')->comment('Код сайта');
                $table->timestamps();

                $table->index('site_id', 'IDX_menus_statistic_site_id');

            });
            DB::statement("ALTER TABLE `menus_statistic` comment 'Статистика обработки маршрутов'");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menus_statistic');
    }
}
