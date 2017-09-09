<?php
namespace App\Model\MailJob;

use Illuminate\Database\Eloquent\Model;

class Condition extends Model
{
    protected $table = 'conditions';
    protected $fillable = ['job', 'value'];

    public function getConditionValue($job)
    {
        $value = ['condition' => '', 'at' => ''];
        $condition = $this->where('job', $job)->first();
        if ($condition) {
            $condition_value = explode(',', $condition->value);
            $value = ['condition' => $condition_value, 'at' => ''];
            if (is_array($condition_value)) {
                $value = ['condition' => $this->checkArray(0, $condition_value), 'at' => $this->checkArray(1, $condition_value)];
            }
        }
        return $value;
    }

    public function checkArray($key, $array)
    {
        $value = '';
        if (is_array($array)) {
            if (array_key_exists($key, $array)) {
                $value = $array[$key];
            }
        }
        return $value;
    }

    public function checkActiveJob()
    {
        $result = ['fetching' => '', 'notification' => '', 'work' => '', 'message' => '', 'remind' => ''];
        $mailboxes = new \App\Model\helpdesk\Settings\MailboxSettings();
        $mailbox = $mailboxes->find(1);
        if ($mailbox) {
            if ($mailbox->email_fetching == 1) {
                $result['fetching'] = true;
            }
            if ($mailbox->notification_cron == 1) {
                $result['notification'] = true;
            }
        }
        $works = new \App\Model\helpdesk\Workflow\WorkflowClose();
        $work = $works->find(1);
        //dd($work);
        if ($work) {
            if ($work->condition == 1) {
                $result['work'] = true;
            }
        }
        return $result;
    }
}
