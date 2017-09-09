<?php
namespace App\Http\Controllers\Admin\helpdesk;

// controllers
use App\Http\Controllers\Admin\MailFetch as Fetch;
use App\Http\Controllers\Controller;
use App\Http\Requests\helpdesk\EmailsRequest;
// model
use App\Http\Requests\helpdesk\Mail\MailRequest;
use App\Model\helpdesk\Staff\Department;
use App\Model\helpdesk\Mailboxes\Mailboxes;
use App\Model\helpdesk\Manage\HelpTopic;
use App\Model\helpdesk\Settings\MailboxSettings;
use App\Model\helpdesk\Ticket\TicketPriority;
// classes
use App\Model\helpdesk\Utility\MailboxProtocol;
use Crypt;
use Exception;
use Lang;

/**
 * ======================================
 * MailBoxesController.
 * ======================================
 * This Controller is used to define below mentioned set of functions applied to the Mailboxes in the system.
 *
 * @author Ladybird <info@ladybirdweb.com>
 */
class MailBoxesController extends Controller
{
    /**
     * Defining constructor variables.
     *
     * @return type
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('roles');
    }

    /**
     * Display a listing of the Mailboxes.
     *
     * @param type Mailboxes $mailboxes
     *
     * @return type view
     */
    public function index(Mailboxes $mailbox)
    {
        try {
            // fetch all the mailboxes from mailboxes table
            $mailboxes = $mailbox->get();
            return view('themes.default1.admin.helpdesk.mailboxes.mailboxes.index', compact('mailboxes'));
        } catch (Exception $e) {
            return redirect()->back()->with('fails', $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param type Department      $department
     * @param type HelpTopic      $help
     * @param type Priority        $priority
     * @param type MailboxProtocol $mailbox_protocol
     *
     * @return type Response
     */
    public function create(Department $department, HelpTopic $help, TicketPriority $ticket_priority, MailboxProtocol $mailbox_protocol)
    {
        try {
            // fetch all the departments from the department table
            $departments = $department->get();
            // fetch all the helptopics from the helptopic table
            $helps = $help->where('status', '=', 1)->get();
            // fetch all the types of priority from the tickets_priorities table
            $priority = $ticket_priority->where('status', '=', 1)->get();
            // fetch all the types of mailbox protocols from the mailbox_protocols table
            $mailbox_protocols = $mailbox_protocol->get();
            $service = new \App\Model\MailJob\MailService();
            $services = $service->pluck('name', 'id')->toArray();
            // return with all the table data
            return view('themes.default1.admin.helpdesk.mailboxes.mailboxes.create', compact('mailbox_protocols', 'priority', 'departments', 'helps', 'services'));
        } catch (Exception $e) {
            // return error messages if any
            return redirect()->back()->with('fails', $e->getMessage());
        }
    }

    /**
     * Check for email input validation.
     *
     * @param EmailsRequest $request
     *
     * @return int
     */
    public function validatingEmailSettings(MailRequest $request, $id = '')
    {
        //dd($request->all());
        try {
            $service_request = $request->except('sending_status', '_token', 'email_address', 'mailbox_name', 'password', 'department', 'priority', 'help_topic', 'fetching_protocol', 'fetching_host', 'fetching_port', 'fetching_encryption', 'imap_authentication', 'sending_protocol', 'sending_host', 'sending_port', 'sending_encryption', 'smtp_authentication', 'internal_notes', '_wysihtml5_mode', 'code');
            $service = $request->input('sending_protocol');
            $validate = '/novalidate-cert';
            $fetch = 1;
            $send = 1;
            //dd($request->input('fetching_status'));
            if ($request->input('fetching_status')) {
                $fetch = $this->getImapStream($request, $validate);
            }
            if ($request->input('sending_status') === 'on') {
                $this->emailService($service, $service_request);
                $send = $this->checkMail($request);
            }
            if ($send == 1 && $fetch == 1) {
                $this->store($request, $service_request, $id);
                return $this->jsonResponse('success', Lang::get('lang.success'));
            }
            return $this->validateEmailError($send, $fetch);
        } catch (Exception $ex) {
            $message = $ex->getMessage();
            if ($request->input('fetching_status') && imap_last_error()) {
                $message = imap_last_error();
            }
            logging('mail-config', $message);
            return $this->jsonResponse('fails', $message);
        }
    }

    public function validateEmailError($out, $in)
    {
        if ($out !== 1) {
            return $this->jsonResponse('fails', Lang::get('lang.outgoing_email_connection_failed'));
        }
        if ($in !== 1) {
            return $this->jsonResponse('fails', Lang::get('lang.incoming_email_connection_failed_please_check_email_credentials_or_imap_settings'));
        }
    }

    public function jsonResponse($type, $message)
    {
        if ($type == 'fails') {
            $result = ['fails' => $message];
        }
        if ($type == 'success') {
            $result = ['success' => $message];
        }
        return response()->json(compact('result'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param type Mailboxes        $mailbox
     * @param type EmailsRequest $request
     *
     * @return type Redirect
     */
    public function store($request, $service_request = [], $id = '')
    {
        $mailbox = new Mailboxes();
        if ($id !== '') {
            $mailbox = $mailbox->find($id);
        }
        $mailbox->email_address = $request->email_address;
        $mailbox->mailbox_name = $request->mailbox_name;
        $mailbox->fetching_host = $request->fetching_host;
        $mailbox->fetching_port = $request->fetching_port;
        $mailbox->fetching_protocol = $request->fetching_protocol;
        $mailbox->sending_host = $request->sending_host;
        $mailbox->sending_port = $request->sending_port;
        $mailbox->sending_protocol = $this->getDriver($request->sending_protocol);
        $mailbox->sending_encryption = $request->sending_encryption;
        if ($request->smtp_validate == 'on') {
            $mailbox->smtp_validate = $request->smtp_validate;
        }
        if ($request->input('password')) {
            $mailbox->password = Crypt::encrypt($request->input('password'));
        }
        if ($request->input('fetching_status') == 'on') {
            $mailbox->fetching_status = 1;
        } else {
            $mailbox->fetching_status = 0;
        }
        if ($request->input('sending_status') == 'on') {
            $mailbox->sending_status = 1;
        } else {
            $mailbox->sending_status = 0;
        }
        if ($request->input('auto_response') == 'on') {
            $mailbox->auto_response = 1;
        } else {
            $mailbox->auto_response = 0;
        }
        $mailbox->fetching_encryption = $request->input('fetching_encryption');
        if (!$request->input('imap_validate')) {
            $mailbox->mailbox_protocol = 'novalidate-cert';
        }
        $mailbox->department = $this->departmentValue($request->input('department'));
        // fetching priority value
        $mailbox->priority = $this->priorityValue($request->input('priority'));
        // fetching helptopic value
        $mailbox->help_topic = $this->helpTopicValue($request->input('help_topic'));
        // inserting the encrypted value of password
        $mailbox->password = Crypt::encrypt($request->input('password'));
        $mailbox->save(); // run save
        if ($request->input('fetching_status')) {
            $this->fetch($mailbox);
        }
        if ($id === '') {
            // Creating a default system email as the first email is inserted to the system
            $email_settings = MailboxSettings::where('id', '=', '1')->first();
            $email_settings->sys_email = $mailbox->id;
            $email_settings->save();
        } else {
            $this->update($id, $request);
        }
        if (count($service_request) > 0) {
            $this->saveMailService($mailbox->id, $service_request, $this->getDriver($request->sending_protocol));
        }
        if ($request->input('fetching_status')) {
            $this->fetch($mailbox);
        }
        return 1;
    }

    public function checkMail($request)
    {
        $mailservice_id = $request->input('sending_protocol');
        $driver = $this->getDriver($mailservice_id);
        $username = $request->input('email_address');
        $password = $request->input('password');
        $name = $request->input('mailbox_name');
        $host = $request->input('sending_host');
        $port = $request->input('sending_port');
        $enc = $request->input('sending_encryption');
        $service_request = $request->except('sending_status', '_token', 'email_address', 'mailbox_name', 'password', 'department', 'priority', 'help_topic', 'fetching_protocol', 'fetching_host', 'fetching_port', 'fetching_encryption', 'imap_authentication', 'sending_protocol', 'sending_host', 'sending_port', 'sending_encryption', 'smtp_authentication', 'internal_notes', '_wysihtml5_mode');

        $this->emailService($driver, $service_request);
        $this->setMailConfig($driver, $username, $name, $password, $enc, $host, $port);
        $transport = \Swift_SmtpTransport::newInstance($host, $port, $enc);
        $transport->setUsername($username);
        $transport->setPassword($password);
        $mailer = \Swift_Mailer::newInstance($transport);
        $mailer->getTransport()->start();
        return 1;
    }

    public function sendDiagnoEmail($request)
    {
        $mailservice_id = $request->input('sending_protocol');
        $driver = $this->getDriver($mailservice_id);
        $username = $request->input('email_address');
        $password = $request->input('password');
        $name = $request->input('mailbox_name');
        $host = $request->input('sending_host');
        $port = $request->input('sending_port');
        $enc = $request->input('sending_encryption');
        $service_request = $request->except('sending_status', '_token', 'email_address', 'mailbox_name', 'password', 'department', 'priority', 'help_topic', 'fetching_protocol', 'fetching_host', 'fetching_port', 'fetching_encryption', 'imap_authentication', 'sending_protocol', 'sending_host', 'sending_port', 'sending_encryption', 'smtp_authentication', 'internal_notes', '_wysihtml5_mode');
        $this->emailService($driver, $service_request);
        $this->setMailConfig($driver, $username, $name, $password, $enc, $host, $port);
        $controller = new \App\Http\Controllers\Common\PhpMailController();
        $subject = 'test';
        $data = 'test';
        //dd(\Config::get('mail'),\Config::get('services'));
        $send = $controller->laravelMail($username, $name, $subject, $data, [], []);
        return $send;
    }

    public function setMailConfig($driver, $username, $name, $password, $enc, $host, $port)
    {
        $configs = [
            'username' => $username,
            'from' => ['address' => $username, 'name' => $name],
            'password' => $password,
            'encryption' => $enc,
            'host' => $host,
            'port' => $port,
            'driver' => $driver,
        ];
        foreach ($configs as $key => $config) {
            if (is_array($config)) {
                foreach ($config as $from) {
                    \Config::set('mail.' . $key, $config);
                }
            } else {
                \Config::set('mail.' . $key, $config);
            }
        }
    }

    public function getDriver($driver_id)
    {
        $short = '';
        $email_drivers = new \App\Model\MailJob\MailService();
        $email_driver = $email_drivers->find($driver_id);
        if ($email_driver) {
            $short = $email_driver->short_name;
        }
        return $short;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param type int             $id
     * @param type Department      $department
     * @param type HelpTopic      $help
     * @param type Mailboxes          $mailbox
     * @param type Priority        $priority
     * @param type MailboxProtocol $mailbox_protocol
     *
     * @return type Response
     */
    public function edit($id, Department $department, HelpTopic $help, Mailboxes $mailbox, TicketPriority $ticket_priority, MailboxProtocol $mailbox_protocol)
    {
        try {
            $sys_email = \DB::table('mailboxes__settings')->select('sys_email')->where('id', '=', 1)->first();
            // dd($sys_email);
            // fetch the selected mailboxes
            $mailboxes = $mailbox->whereId($id)->first();
            // get all the departments
            $departments = $department->get();
            //get count of mailboxes
            $count = $mailbox->count();
            // get all the helptopic
            $helps = $help->where('status', '=', 1)->get();
            // get all the priority
            $priority = $ticket_priority->where('status', '=', 1)->get();
            // get all the mailbox protocols
            $mailbox_protocols = $mailbox_protocol->get();
            $service = new \App\Model\MailJob\MailService();
            $services = $service->pluck('name', 'id')->toArray();
            // return if the execution is succeeded
            return view('themes.default1.admin.helpdesk.mailboxes.mailboxes.edit', compact('mailbox_protocols', 'priority', 'departments', 'helps', 'mailboxes', 'sys_email', 'services'))->with('count', $count);
        } catch (Exception $e) {
            // return if try fails
            return redirect()->back()->with('fails', $e->getMessage());
        }
    }

    /**
     * Check for email input validation.
     *
     * @param EmailsRequest $request
     *
     * @return int
     */
    public function validatingEmailSettingsUpdate($id, MailRequest $request)
    {
        try {
            return $this->validatingEmailSettings($request, $id);
        } catch (Exception $ex) {
            $message = $ex->getMessage();
            if (imap_last_error()) {
                $message = imap_last_error();
            }
            //dd($ex->getMessage());
            logging('mail-config', $message);
            //Log::error($ex->getMessage());
            return $this->jsonResponse('fails', $message);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param type $id
     * @param type Mailboxes            $mailbox
     * @param type EmailsEditRequest $request
     *
     * @return type Response
     */
    public function update($id, $request)
    {
        try {
            if ($request->sys_email == 'on') {
                $system = \DB::table('mailboxes__settings')
                    ->where('id', '=', 1)
                    ->update(['sys_email' => $id]);
            } elseif ($request->input('count') <= 1 && $request->sys_email == null) {
                $system = \DB::table('mailboxes__settings')
                    ->where('id', '=', 1)
                    ->update(['sys_email' => null]);
            }
            $return = 1;
        } catch (Exception $e) {
            $return = $e->getMessage();
        }
        return $return;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param type int    $id
     * @param type Mailboxes $mailbox
     *
     * @return type Redirect
     */
    public function destroy($id, Mailboxes $mailbox)
    {
        // fetching the details on the basis of the $id passed to the function
        $default_system_email = MailboxSettings::where('id', '=', '1')->first();
        if ($default_system_email->sys_email) {
            // checking if the default system email is the passed email
            if ($id == $default_system_email->sys_email) {
                return redirect('mailboxes')->with('fails', Lang::get('lang.you_cannot_delete_system_default_email'));
            }
        }
        try {
            // fetching the database instance of the current email
            $mailboxes = $mailbox->whereId($id)->first();
            // checking if deleting the email is success or if it's carrying any dependencies
            $mailboxes->delete();
            return redirect('mailboxes')->with('success', Lang::get('lang.email_deleted_sucessfully'));
        } catch (Exception $e) {
            // returns if the try fails
            return redirect()->back()->with('fails', $e->getMessage());
        }
    }

    /**
     * Create imap connection.
     *
     * @param type $request
     *
     * @return type int
     */
    public function getImapStream($request)
    {
        $host = $request->input('fetching_host');
        $port = $request->input('fetching_port');
        $service = $request->input('fetching_protocol');
        $encryption = $request->input('fetching_encryption');
        $validate = $request->input('imap_validate');
        $username = $request->input('email_address');
        $password = $request->input('password');
        $server = new Fetch($host, $port, $service);
        //$server->setFlag('novalidate-cert');
        if ($encryption != '') {
            $server->setFlag($encryption);
        }
        if (!$validate) {
            $server->setFlag('novalidate-cert');
        } else {
            $server->setFlag('validate-cert');
        }
        $server->setAuthentication($username, $password);
        //$imapStream = @imap_open($server, $username, $password, $this->imapOptions, $this->imapRetriesNum, $this->imapParams);
        //It should end up in : {imap.gmail.com:993/imap/ssl/novalidate-cert}
        // Note : if you let the string end in a slash (so if you forget the novalidate, it will give you an error
        $server->getImapStream();
        return 1;
    }

    /**
     * Check connection.
     *
     * @param type $imap_stream
     *
     * @return type int
     */
    public function checkImapStream($imap_stream)
    {
        $check_imap_stream = imap_check($imap_stream);
        if ($check_imap_stream) {
            $imap_stream = 1;
        } else {
            $imap_stream = 0;
        }
        return $imap_stream;
    }

    /**
     * Get smtp connection.
     *
     * @param type $request
     *
     * @return int
     */
    public function getSmtp($request)
    {
        $sending_status = $request->input('sending_status');
        // cheking for the sending protocol
        if ($request->input('sending_protocol') == 'smtp') {
            $mail = new \PHPMailer();
            $mail->isSMTP();
            $mail->Host = $request->input('sending_host');            // Specify main and backup SMTP servers
            //$mail->SMTPAuth = true;                                   // Enable SMTP authentication
            $mail->Username = $request->input('email_address');       // SMTP username
            $mail->Password = $request->input('password');            // SMTP password
            $mail->SMTPSecure = $request->input('sending_encryption'); // Enable TLS encryption, `ssl` also accepted
            $mail->Port = $request->input('sending_port');            // TCP port to connect to
            if (!$request->input('smtp_validate')) {
                $mail->SMTPAuth = true;                               // Enable SMTP authentication
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ];
                if ($mail->smtpConnect($mail->SMTPOptions) == true) {
                    $mail->smtpClose();
                    $return = 1;
                } else {
                    $return = 0;
                }
            } else {
                if ($mail->smtpConnect()) {
                    $mail->smtpClose();
                    $return = 1;
                } else {
                    $return = 0;
                }
            }
        } elseif ($request->input('sending_protocol') == 'mail') {
            $return = 1;
        }
        return $return;
    }

    /**
     * Checking if department value is null.
     *
     * @param type $dept
     *
     * @return type string or null
     */
    public function departmentValue($dept)
    {
        if ($dept) {
            $email_department = $dept;
        } else {
            $email_department = null;
        }
        return $email_department;
    }

    /**
     * Checking if priority value is null.
     *
     * @param type $priority
     *
     * @return type string or null
     */
    public function priorityValue($priority)
    {
        if ($priority) {
            $email_priority = $priority;
        } else {
            $email_priority = null;
        }
        return $email_priority;
    }

    /**
     * Checking if helptopic value is null.
     *
     * @param type $help_topic
     *
     * @return type string or null
     */
    public function helpTopicValue($help_topic)
    {
        if ($help_topic) {
            $email_help_topic = $help_topic;
        } else {
            $email_help_topic = null;
        }
        return $email_help_topic;
    }

    public function emailService($service, $value = [])
    {
        switch ($service) {
            case 'mailgun':
                $this->setServiceConfig($service, $value);
            case 'mandrill':
                $this->setServiceConfig($service, $value);
            case 'ses':
                $this->setServiceConfig($service, $value);
        }
    }

    public function setServiceConfig($service, $value)
    {
        //dd($service);
        if (count($value) > 0) {
            foreach ($value as $k => $v) {
                \Config::set("services.$service.$k", $v);
            }
        }
    }

    public function saveMailService($emailid, $request, $driver)
    {
        $mail_service = new \App\Model\MailJob\FaveoMail();
        $mails = $mail_service->where('mailbox_id', $emailid)->get();
        if (count($request) > 0) {
            foreach ($mails as $mail) {
                $mail->delete();
            }
            foreach ($request as $key => $value) {
                $mail_service->create([
                    'drive' => $driver,
                    'key' => $key,
                    'value' => $value,
                    'mailbox_id' => $emailid,
                ]);
            }
        }
    }

    public function readMails()
    {
        $PhpMailController = new \App\Http\Controllers\Common\PhpMailController();
        $NotificationController = new \App\Http\Controllers\Common\NotificationController();
        $TicketController = new \App\Http\Controllers\Staff\helpdesk\TicketController($PhpMailController, $NotificationController);
        $TicketWorkflowController = new \App\Http\Controllers\Staff\helpdesk\TicketWorkflowController($TicketController);
        $controller = new \App\Http\Controllers\Staff\helpdesk\MailController($TicketWorkflowController);
        $mailboxes = new Mailboxes();
        $mailsettings = new MailboxSettings();
        $system = new \App\Model\helpdesk\Settings\System();
        $ticket = new \App\Model\helpdesk\Settings\Ticket();
        $controller->readmails($mailboxes, $mailsettings, $system, $ticket);
    }

    public function fetch($mailbox)
    {
        $PhpMailController = new \App\Http\Controllers\Common\PhpMailController();
        $NotificationController = new \App\Http\Controllers\Common\NotificationController();
        $TicketController = new \App\Http\Controllers\Staff\helpdesk\TicketController($PhpMailController, $NotificationController);
        $TicketWorkflowController = new \App\Http\Controllers\Staff\helpdesk\TicketWorkflowController($TicketController);
        $controller = new \App\Http\Controllers\Staff\helpdesk\MailController($TicketWorkflowController);
        $controller->fetch($mailbox);
    }
}
