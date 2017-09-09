<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToTicketCollaboratorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tickets__collaborators', function (Blueprint $table) {
            $table->foreign('ticket_id', 'tickets__collaborators_ibfk_1')->references('id')->on('tickets')->onUpdate('NO ACTION')->onDelete('RESTRICT');
            $table->foreign('user_id', 'tickets__collaborators_ibfk_2')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tickets__collaborators', function (Blueprint $table) {
            $table->dropForeign('tickets__collaborators_ibfk_1');
            $table->dropForeign('tickets__collaborators_ibfk_2');
        });
    }
}
