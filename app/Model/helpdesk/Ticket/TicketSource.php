<?php
namespace App\Model\helpdesk\Ticket;

use App\BaseModel;

class TicketSource extends BaseModel
{
    public $timestamps = false;
    protected $table = 'tickets_sources';
    protected $fillable = [
        'name', 'value', 'css_class',
    ];
}
