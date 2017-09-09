<?php
namespace App\Model\helpdesk\Mailboxes;

use App\BaseModel;

class Banlist extends BaseModel
{
    protected $table = 'banlist';
    protected $fillable = [
        'id', 'ban_status', 'email_address', 'internal_notes',
    ];
}
