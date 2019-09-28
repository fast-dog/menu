<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('pages')) {
            Schema::create('pages', function(Blueprint $table) {
                $table->increments('id');
                $table->string('name')->comment('Название');
                $table->string('alias')->nullable()->comment('Псевдоним');
                $table->char('site_id', 3)->default('001')->comment('Код сайта');
                $table->tinyInteger(\FastDog\Menu\Models\Page::STATE)
                    ->default(\FastDog\Menu\Models\Page::STATE_PUBLISHED)->comment('Состояние');

                $table->index('alias', 'IDX_pages_alias');
                $table->timestamps();
                $table->softDeletes();
            });

            DB::statement("ALTER TABLE `pages` comment 'Статичные страницы'");
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pages');
    }
}
