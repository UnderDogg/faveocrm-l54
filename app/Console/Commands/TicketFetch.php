<?php
namespace App\Console\Commands;

use App\Http\Controllers\Staff\helpdesk\MailController;
use App\Http\Controllers\Staff\helpdesk\TicketWorkflowController;
use Event;
use Illuminate\Console\Command;

class TicketFetch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ticket:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetching the tickets from service provider';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (env('DB_INSTALL') == 1) {
            $controller = $this->mailController();
            $mailboxes = new \App\Model\helpdesk\Mailboxes\Mailboxes();
            $mailsettings = new \App\Model\helpdesk\Settings\MailboxSettings();
            $system = new \App\Model\helpdesk\Settings\System();
            $ticket = new \App\Model\helpdesk\Settings\Ticket();
            $controller->readmails($mailboxes, $mailsettings, $system, $ticket);
            Event::fire('ticket.fetch', ['event' => '']);
            logging('fetching-ticket', 'Ticket has read', 'info');
            //\Log::info('Ticket has read');
            $this->info('Ticket has read');
        }
    }

    public function mailController()
    {
        $PhpMailController = new \App\Http\Controllers\Common\PhpMailController();
        $NotificationController = new \App\Http\Controllers\Common\NotificationController();
        $ticket = new \App\Http\Controllers\Staff\helpdesk\TicketController($PhpMailController, $NotificationController);
        $work = new TicketWorkflowController($ticket);
        $controller = new MailController($work);
        return $controller;
    }
}
