<?php
namespace App\Model\helpdesk\Staff;

use App\BaseModel;

class Assign_team_Staff extends BaseModel
{
    protected $table = 'team_assign_agent';
    protected $fillable = ['id', 'team_id', 'staff_id', 'updated_at', 'created_at'];
}
