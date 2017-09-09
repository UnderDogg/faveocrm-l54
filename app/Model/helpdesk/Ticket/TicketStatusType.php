<?php
namespace App\Model\helpdesk\Ticket;

use App\BaseModel;

class TicketStatusType extends BaseModel
{
    protected $table = 'tickets__statustypes';
    protected $fillable = [
        'id', 'name', 'created_at', 'updated_at',
    ];

    public function status()
    {
        return $this->hasMany('App\Model\helpdesk\Ticket\Ticket_Status', 'purpose_of_status');
    }
}
