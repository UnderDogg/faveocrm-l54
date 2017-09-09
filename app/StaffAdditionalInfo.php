<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class StaffAdditionalInfo extends Model
{
    protected $table = 'staff_additional_infos';
    protected $fillable = ['owner', 'service', 'key', 'value'];
}
