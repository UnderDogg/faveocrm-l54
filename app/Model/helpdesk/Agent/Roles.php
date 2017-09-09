<?php
namespace App\Model\helpdesk\Agent;

use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    protected $table = 'roles';
    protected $fillable = [
        'user_id', 'permission',
    ];

    public function getpermissionAttribute($value)
    {
        if ($value) {
            $value = json_decode($value, true);
        }
        return $value;
    }

    public function setpermissionAttribute($value)
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $this->attributes['permission'] = $value;
    }
}
