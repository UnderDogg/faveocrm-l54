<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTicketAttachmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets__attachments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('thread_id')->unsigned()->nullable()->index('thread_id');
            $table->string('size');
            $table->string('type');
            $table->string('poster');
            $table->timestamps();
        });
        \DB::statement('ALTER TABLE `tickets__attachments` ADD `file` MEDIUMBLOB');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('tickets__attachments');
    }
}
