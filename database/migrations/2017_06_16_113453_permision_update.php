<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class permissionUpdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        //Schema::dropIfExists('groups');
        //\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        Schema::create('permission', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->text('permission');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('permission');
    }
}
