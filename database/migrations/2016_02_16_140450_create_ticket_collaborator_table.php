<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTicketCollaboratorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets__collaborators', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('isactive');
            $table->integer('ticket_id')->unsigned()->nullable()->index('ticket_id');
            $table->integer('user_id')->unsigned()->nullable()->index('user_id');
            $table->string('role');
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
        Schema::drop('tickets__collaborators');
    }
}
