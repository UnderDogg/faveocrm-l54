<?php
namespace App\Model\helpdesk\Ticket;

use App\BaseModel;

class TicketCollaborator extends BaseModel
{
    protected $table = 'tickets__collaborators';
    protected $fillable = [
        'id', 'isactive', 'ticket_id', 'user_id', 'role', 'updated_at', 'created_at',
    ];
}
