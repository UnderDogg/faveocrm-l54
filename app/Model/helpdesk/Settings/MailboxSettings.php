<?php
namespace App\Model\helpdesk\Settings;

use App\BaseModel;

class MailboxSettings extends BaseModel
{
    /* Using Mailboxes table  */
    protected $table = 'mailboxes__settings';
    /* Set fillable fields in table */
    protected $fillable = [
        'id', 'template', 'sys_email', 'alert_email', 'admin_email', 'mta', 'email_fetching', 'strip',
        'separator', 'all_emails', 'email_collaborator', 'attachment',
    ];
}
