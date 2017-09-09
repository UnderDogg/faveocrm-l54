<?php
namespace App\Model\helpdesk\Mailboxes;

use App\BaseModel;

class Mailboxes extends BaseModel
{
    protected $table = 'mailboxes';
    protected $fillable = [
        'email_address', 'mailbox_name', 'department', 'priority', 'help_topic',
        'user_name', 'password', 'fetching_host', 'fetching_port', 'fetching_protocol', 'fetching_encryption', 'mailbox_protocol',
        'folder', 'sending_host', 'sending_port', 'sending_protocol', 'sending_encryption', 'internal_notes', 'auto_response',
        'fetching_status', 'move_to_folder', 'delete_email', 'do_nothing',
        'sending_status', 'authentication', 'header_spoofing', 'imap_config',
    ];

    public function getCurrentDrive()
    {
        $drive = $this->attributes['sending_protocol'];
        $mailServices = new \App\Model\MailJob\MailService();
        $id = '';
        $mailService = $mailServices->where('short_name', $drive)->first();
        if ($mailService) {
            $id = $mailService->id;
        }
        return $id;
    }

    public function getExtraField($key)
    {
        $value = '';
        $id = $this->attributes['id'];
        $services = new \App\Model\MailJob\FaveoMail();
        $service = $services->where('mailbox_id', $id)->where('key', $key)->first();
        if ($service) {
            $value = $service->value;
        }
        return $value;
    }

    public function extraFieldRelation()
    {
        $related = "App\Model\MailJob\FaveoMail";
        return $this->hasMany($related, 'mailbox_id');
    }

    public function deleteExtraFields()
    {
        $fields = $this->extraFieldRelation()->get();
        if ($fields->count() > 0) {
            foreach ($fields as $field) {
                $field->delete();
            }
        }
    }

    public function getPasswordAttribute($value)
    {
        if ($value) {
            return \Crypt::decrypt($value);
        }
        return $value;
    }

    public function delete()
    {
        $this->deleteExtraFields();
        parent::delete();
    }
}
