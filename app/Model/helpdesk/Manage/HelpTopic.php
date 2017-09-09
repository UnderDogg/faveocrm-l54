<?php
namespace App\Model\helpdesk\Manage;

use App\BaseModel;

class HelpTopic extends BaseModel
{
    protected $table = 'help_topic';
    protected $fillable = [
        'id', 'topic', 'parent_topic', 'custom_form', 'department', 'tickets__statuses', 'priority',
        'sla_plan', 'thank_page', 'ticket_num_format', 'internal_notes', 'status', 'type', 'auto_assign',
        'auto_response',
    ];

    public function department()
    {
        $related = 'App\Model\helpdesk\Staff\Department';
        $foreignKey = 'department';
        return $this->belongsTo($related, $foreignKey);
    }

    public function delete()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        parent::delete();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
