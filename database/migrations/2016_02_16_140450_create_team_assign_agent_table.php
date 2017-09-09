<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTeamAssignAgentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_assign_agent', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('team_id')->unsigned()->nullable()->index('team_id');
            $table->integer('staff_id')->unsigned()->nullable()->index('staff_id');
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
        Schema::drop('team_assign_agent');
    }
}
