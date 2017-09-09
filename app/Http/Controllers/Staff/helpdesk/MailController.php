<?php
namespace App\Http\Controllers\Staff\helpdesk;

// models
use App\Http\Controllers\Admin\MailFetch as Fetch;
use App\Http\Controllers\Controller;
use App\Model\helpdesk\Mailboxes\Mailboxes;
use App\Model\helpdesk\Manage\HelpTopic;
use App\Model\helpdesk\Settings\MailboxSettings;
use App\Model\helpdesk\Settings\System;
use App\Model\helpdesk\Settings\Ticket;
use App\Model\helpdesk\Ticket\TicketAttachments;
use App\Model\helpdesk\Ticket\TicketSource;
use App\Model\helpdesk\Ticket\TicketThread;
// classes
use App\Model\helpdesk\Ticket\Tickets;

/**
 * MailController.
 *
 * @author      Ladybird <info@ladybirdweb.com>
 */
class MailController extends Controller
{
    /**
     * constructor
     * Create a new controller instance.
     *
     * @param type TicketController $TicketController
     */
    public function __construct(TicketWorkflowController $TicketWorkflowController)
    {
        $this->middleware('board');
        $this->TicketWorkflowController = $TicketWorkflowController;
    }

    /**
     * Reademails.
     *
     * @return type
     */
    public function readmails(Mailboxes $mailboxes, MailboxSettings $mailsettings, System $system, Ticket $ticket)
    {
        if ($mailsettings->first()->email_fetching == 1) {
            if ($mailsettings->first()->all_emails == 1) {
                $mailbox = $mailboxes->get();
                if ($mailbox->count() > 0) {
                    foreach ($mailbox as $e_mail) {
                        try {
                            $this->fetch($e_mail);
                        } catch (\Exception $ex) {
                            $add = '';
                            if ($mailbox) {
                                $add = $e_mail->email_address;
                            }
                            logging($add, $ex->getMessage());
                        }
                    }
                }
            }
        }
    }

    /**
     * separate reply.
     *
     * @param type $body
     *
     * @return type string
     */
    public function separate_reply($body)
    {
        $body2 = explode('---Reply above this line---', $body);
        $body3 = $body2[0];
        return $body3;
    }

    /**
     * @param object $mailbox
     *
     * @return int
     */
    public function priority($mailbox)
    {
        $priority = $mailbox->priority;
        if (!$priority) {
            $priority = $this->ticketController()->getSystemDefaultPriority();
        }
        return $priority;
    }

    /**
     * get department.
     *
     * @param object $mailbox
     *
     * @return int
     */
    public function department($mailbox)
    {
        $department = $mailbox->department;
        if (!$department) {
            $department = $this->ticketController()->getSystemDefaultDepartment();
        }
        return $department;
    }

    /**
     * get help topic.
     *
     * @param object $mailbox
     *
     * @return int
     */
    public function helptopic($mailbox)
    {
        //dd($mailbox);
        $helptopic = $mailbox->help_topic;
        if (!$helptopic) {
            $helptopic = $this->ticketController()->getSystemDefaultHelpTopic();
        }
        return $helptopic;
    }

    /**
     * get sla.
     *
     * @param object $mailbox
     *
     * @return int
     */
    public function sla($mailbox)
    {
        $helptopic = $this->helptopic($mailbox);
        $help = HelpTopic::where('id', '=', $helptopic)->first();
        if ($help) {
            $sla = $help->sla_plan;
        }
        if (!$sla) {
            $sla = $this->ticketController()->getSystemDefaultSla();
        }
        return $sla;
    }

    /**
     * get ticket controller.
     *
     * @return \App\Http\Controllers\Staff\helpdesk\TicketController
     */
    public function ticketController()
    {
        $PhpMailController = new \App\Http\Controllers\Common\PhpMailController();
        $NotificationController = new \App\Http\Controllers\Common\NotificationController();
        $controller = new TicketController($PhpMailController, $NotificationController);
        return $controller;
    }

    public function fetch($mailbox)
    {
        //  dd($mailbox);
        if ($mailbox) {
            $username = $mailbox->email_address;
            $password = $mailbox->password;
            $service = $mailbox->fetching_protocol;
            $host = $mailbox->fetching_host;
            $port = $mailbox->fetching_port;
            $encryption = $mailbox->fetching_encryption;
            $cert = $mailbox->mailbox_protocol;
            $server = new Fetch($host, $port, $service);
            if ($encryption != null || $encryption != '') {
                $server->setFlag($encryption);
            }
            $server->setFlag($cert);
            $server->setAuthentication($username, $password);
            $date = date('d M Y', strtotime('-1 days'));
            $messages = $server->search("SINCE \"$date\" UNSEEN");
            $this->message($messages, $mailbox);
        }
    }

    public function message($messages, $mailbox)
    {
        if (count($messages) > 0) {
            foreach ($messages as $message) {
                $deamon = starts_with($message->getAddresses('from')['address'], 'mailer-daemon');
                $auto_response = $message->auto_respond;
                if (!$deamon && !$auto_response) {
                    $this->getMessageContent($message, $mailbox);
                }
            }
        }
    }

    public function getMessageContent($message, $mailbox)
    {
        $body = $message->getMessageBody(true);
        if (!$body) {
            $body = $message->getMessageBody();
        }
        $body = $this->separateReply($body);
        $subject = $message->getSubject();
        $address = $message->getAddresses('reply-to');
        if (!$address) {
            $address = $message->getAddresses('from');
        }
        $collaborators = $this->collaburators($message, $mailbox);
        $attachments = $message->getAttachments();
        //dd(['body' => $body, 'subject' => $subject, 'address' => $address, 'cc' => $collaborator, 'attachments' => $attachments]);
        $message_id = '';
        $reference_id = '';
        $uid = '';
        if ($message->getOverview()) {
            if (property_exists($message->getOverview(), 'message_id')) {
                $message_id = $message->getOverview()->message_id;
            }
            if (property_exists($message->getOverview(), 'references')) {
                $reference_id = $message->getOverview()->references;
            }
            if (property_exists($message->getOverview(), 'uid')) {
                $uid = $message->getOverview()->uid;
            }
        }
        $email_content = ['message_id' => $message_id, 'uid' => $uid, 'reference_id' => $reference_id];
        $this->workflow($address, $subject, $body, $collaborators, $attachments, $mailbox, $email_content);
    }

    public function workflow($address, $subject, $body, $collaborator, $attachments, $mailbox, $email_content = [])
    {
        $fromaddress = checkArray('address', $address[0]);
        $fromname = checkArray('name', $address[0]);
        $helptopic = $this->helptopic($mailbox);
        $sla = $this->sla($mailbox);
        $priority = $this->priority($mailbox);
        $ticket_source = TicketSource::where('name', '=', 'email')->first();
        $source = $ticket_source->id;
        $dept = $this->department($mailbox);
        $get_helptopic = HelpTopic::where('id', '=', $helptopic)->first();
        $assign = $get_helptopic->auto_assign;
        $form_data = null;
        $team_assign = null;
        $ticket_status = null;
        $auto_response = $mailbox->auto_response;
        $result = $this->TicketWorkflowController->workflow($fromaddress, $fromname, $subject, $body, $phone = '', $phonecode = '', $mobile_number = '', $helptopic, $sla, $priority, $source, $collaborator, $dept, $assign, $team_assign, $ticket_status, $form_data = [], $auto_response, $attachments, [], $email_content);
    }

    public function updateThread($ticket_number, $body, $attachments)
    {
        $ticket_table = Tickets::where('ticket_number', '=', $ticket_number)->first();
        $thread_id = TicketThread::where('ticket_id', '=', $ticket_table->id)->max('id');
        $thread = TicketThread::where('id', '=', $thread_id)->first();
        $thread->body = $this->separate_reply($body);
        $thread->save();
        if (file_exists(app_path('/FaveoStorage/Controllers/StorageController.php'))) {
            try {
                $storage = new \App\FaveoStorage\Controllers\StorageController();
                $storage->saveAttachments($thread->id, $attachments);
            } catch (\Exception $ex) {
                logging('attachment', $ex->getMessage());
            }
        } else {
            logging('attachment', 'FaveoStorage not installed');
        }
        \Log::info('Ticket has created : ', ['id' => $thread->ticket_id]);
    }

    public function saveAttachments($thread_id, $attachments = [])
    {
        if (is_array($attachments) && count($attachments) > 0) {
            foreach ($attachments as $attachment) {
                $structure = $attachment->getStructure();
                $disposition = 'ATTACHMENT';
                if (isset($structure->disposition)) {
                    $disposition = $structure->disposition;
                }
                $filename = str_random(16) . '-' . $attachment->getFileName();
                $type = $attachment->getMimeType();
                $size = $attachment->getSize();
                $data = $attachment->getData();
                //$path = storage_path('/');
                //$attachment->saveToDirectory($path);
                $this->manageAttachment($data, $filename, $type, $size, $disposition, $thread_id);
                $this->updateBody($attachment, $thread_id, $filename);
            }
        }
    }

    public function manageAttachment($data, $filename, $type, $size, $disposition, $thread_id)
    {
        $upload = new TicketAttachments();
        $upload->file = $data;
        $upload->thread_id = $thread_id;
        $upload->name = $filename;
        $upload->type = $type;
        $upload->size = $size;
        $upload->poster = $disposition;
        if ($data && $size && $disposition) {
            $upload->save();
        }
    }

    public function updateBody($attachment, $thread_id, $filename)
    {
        $structure = $attachment->getStructure();
        $disposition = 'ATTACHMENT';
        if (isset($structure->disposition)) {
            $disposition = $structure->disposition;
        }
        if ($disposition == 'INLINE' || $disposition == 'inline') {
            $id = str_replace('>', '', str_replace('<', '', $structure->id));
            //$filename = $attachment->getFileName();
            $threads = new TicketThread();
            $thread = $threads->find($thread_id);
            $body = $thread->body;
            $body = str_replace('cid:' . $id, $filename, $body);
            $thread->body = $body;
            $thread->save();
        }
    }

    public function collaburators($message, $mailbox)
    {
        $this_address = $mailbox->email_address;
        $collaborator_cc = $message->getAddresses('cc');
        //dd($collaborator_cc);
        $collaborator_bcc = $message->getAddresses('bcc');
        $collaborator_to = $message->getAddresses('to');
        $cc_array = [];
        $bcc_array = [];
        $to_array = [];
        if ($collaborator_cc) {
            foreach ($collaborator_cc as $cc) {
                $name = checkArray('name', $cc);
                $address = checkArray('address', $cc);
                $cc_array[$address] = $name;
            }
        }
        if ($collaborator_bcc) {
            foreach ($collaborator_bcc as $bcc) {
                $name = checkArray('name', $bcc);
                $address = checkArray('address', $bcc);
                $bcc_array[$address] = $name;
            }
        }
        if ($collaborator_to) {
            foreach ($collaborator_to as $to) {
                $name = checkArray('name', $to);
                $address = checkArray('address', $to);
                $to_array[$address] = $name;
            }
        }
        $array = array_merge($bcc_array, $cc_array);
        $array = array_merge($array, $to_array);
        if (array_key_exists($this_address, $array)) {
            unset($array[$this_address]);
        }
        return $array;
    }

    /**
     * function to load data.
     *
     * @param type $id
     *
     * @return type file
     */
    public function get_data($id)
    {
        $attachment = \App\Model\helpdesk\Ticket\TicketAttachments::where('id', '=', $id)->first();
        if (mime($attachment->type) == true) {
            echo "<img src=data:$attachment->type;base64," . $attachment->file . '>';
        } else {
            $file = base64_decode($attachment->file);
            return response($file)
                ->header('Cache-Control', 'no-cache private')
                ->header('Content-Description', 'File Transfer')
                ->header('Content-Type', $attachment->type)
                ->header('Content-length', strlen($file))
                ->header('Content-Disposition', 'attachment; filename=' . $attachment->name)
                ->header('Content-Transfer-Encoding', 'binary');
        }
    }

    /**
     * separate reply.
     *
     * @param type $body
     *
     * @return type string
     */
    public function separateReply($body)
    {
        $body2 = explode('---Reply above this line---', $body);
        if (is_array($body2) && array_key_exists(0, $body2)) {
            $body = $body2[0];
        }
        return $body;
    }
}
