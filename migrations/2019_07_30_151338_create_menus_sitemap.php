<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMenusSitemap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menus_sitemap', function(Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('route', 255)->comment('Маршрут');
            $table->mediumInteger('priority')->default(0)->comment('Приоритет');
            $table->char('site_id', 3)->default('001')->comment('Код сайта');
            $table->string('changefreq', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menus_sitemap');
    }
}
