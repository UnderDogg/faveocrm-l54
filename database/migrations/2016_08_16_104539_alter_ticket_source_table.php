<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AlterTicketSourceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('tickets_sources', 'css_class')) {
            Schema::table('tickets_sources', function (Blueprint $table) {
                $table->string('css_class');
            });
        }
        DB::table('tickets_sources')->delete();
        $values = $this->values();
        foreach ($values as $value) {
            DB::table('tickets_sources')->insert($value);
        }
    }

    public function values()
    {
        return [
            ['name' => 'web', 'value' => 'Web', 'css_class' => 'fa fa-internet-explorer'],
            ['name' => 'email', 'value' => 'E-mail', 'css_class' => 'fa fa-envelope'],
            ['name' => 'staff', 'value' => 'Staff Panel', 'css_class' => 'fa fa-envelope'],
            ['name' => 'facebook', 'value' => 'Facebook', 'css_class' => 'fa fa-facebook'],
            ['name' => 'twitter', 'value' => 'Twitter', 'css_class' => 'fa fa-twitter'],
            ['name' => 'call', 'value' => 'Call', 'css_class' => 'fa fa-phone'],
            ['name' => 'chat', 'value' => 'Chat', 'css_class' => 'fa fa-comment'],
        ];
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tickets_sources', function (Blueprint $table) {
            //
        });
    }
}
