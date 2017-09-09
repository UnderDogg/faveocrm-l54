<?php
namespace App\Http\Controllers\Admin\helpdesk;

// controller
use App\Http\Controllers\Controller;
// request
use App\Http\Requests\helpdesk\DepartmentRequest;
use App\Http\Requests\helpdesk\DepartmentUpdate;
// model
use App\Model\helpdesk\Staff\Department;
use App\Model\helpdesk\Staff\Group_assign_department;
use App\Model\helpdesk\Staff\Groups;
use App\Model\helpdesk\Staff\Teams;
use App\Model\helpdesk\Mailboxes\Mailboxes;
use App\Model\helpdesk\Mailboxes\Template;
use App\Model\helpdesk\Manage\HelpTopic;
use App\Model\helpdesk\Manage\Sla_plan;
use App\Model\helpdesk\Settings\System;
use App\Model\helpdesk\Ticket\Tickets;
use App\Staff;
// classes
use DB;
use Exception;
use Lang;

/**
 * DepartmentController.
 *
 * @author      Ladybird <info@ladybirdweb.com>
 */
class DepartmentController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('roles');
    }

    /**
     * Get index page.
     *
     * @param type Department $department
     *
     * @return type Response
     */
    public function index(Department $department)
    {
        try {
            $departments = $department->get();
            return view('themes.default1.admin.helpdesk.staff.departments.index', compact('departments'));
        } catch (Exception $e) {
            return view('404');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param type Staff                    $user
     * @param type Group_assign_department $group_assign_department
     * @param type Department              $department
     * @param type Sla_plan                $sla
     * @param type Template                $template
     * @param type Mailboxes                  $mailbox
     * @param type Groups                  $group
     *
     * @return type Response
     */
    public function create(Staff $user, Group_assign_department $group_assign_department, Department $department, Sla_plan $sla, Template $template, Mailboxes $mailbox, Groups $group)
    {
        try {
            $slas = $sla->where('status', '=', 1)
                ->select('grace_period', 'id')->get();
            $user = $user->where('role', '<>', 'user')
                ->where('active', '=', 1)
                ->get();
            $mailboxes = $mailbox->select('mailbox_name', 'id')->get();
            $templates = $template->get();
            $department = $department->get();
            $groups = $group->pluck('id', 'name');
            return view('themes.default1.admin.helpdesk.staff.departments.create', compact('department', 'templates', 'slas', 'user', 'mailboxes', 'groups'));
        } catch (Exception $e) {
            return redirect()->back()->with('fails', $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param type Department        $department
     * @param type DepartmentRequest $request
     *
     * @return type Response
     */
    public function store(Department $department, DepartmentRequest $request)
    {
        try {
            $department->fill($request->except('group_id', 'manager', 'sla'))->save();
            if ($request->sla) {
                $department->sla = $request->input('sla');
            } else {
                $department->sla = null;
            }
            $requests = $request->input('group_id');
            $id = $department->id;
            if ($request->manager) {
                $department->manager = $request->input('manager');
            } else {
                $department->manager = null;
            }
            /* Succes And Failure condition */
            /*  Check Whether the function Success or Fail */
            if ($department->save() == true) {
                if ($request->input('sys_department') == 'on') {
                    DB::table('settings_system')
                        ->where('id', 1)
                        ->update(['department' => $department->id]);
                }
                return redirect('departments')->with('success', Lang::get('lang.department_created_sucessfully'));
            } else {
                return redirect('departments')->with('fails', Lang::get('lang.failed_to_create_department'));
            }
        } catch (Exception $e) {
            return redirect('departments')->with('fails', Lang::get('lang.failed_to_create_department'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param type int                     $id
     * @param type Staff                    $user
     * @param type Group_assign_department $group_assign_department
     * @param type Template                $template
     * @param type Teams                   $team
     * @param type Department              $department
     * @param type Sla_plan                $sla
     * @param type Mailboxes                  $mailbox
     * @param type Groups                  $group
     *
     * @return type Response
     */
    public function edit($id, Staff $user, Group_assign_department $group_assign_department, Template $template, Teams $team, Department $department, Sla_plan $sla, Mailboxes $mailbox, Groups $group)
    {
        try {
            $sys_department = \DB::table('settings_system')
                ->select('department')
                ->where('id', '=', 1)
                ->first();
            $slas = $sla->where('status', '=', 1)
                ->select('grace_period', 'id')->get();
            $user = $user->where('primary_dpt', $id)
                ->where('active', '=', 1)
                ->get();
            $mailboxes = $mailbox->select('mailbox_name', 'id')->get();
            $templates = $template->get();
            $departments = $department->whereId($id)->first();
            //$groups = $group->pluck('id', 'name');
            $assign = $group_assign_department->where('department_id', $id)->pluck('group_id');
            return view('themes.default1.admin.helpdesk.staff.departments.edit', compact('assign', 'team', 'templates', 'departments', 'slas', 'user', 'mailboxes', 'sys_department'));
        } catch (Exception $e) {
            return redirect('departments')->with('fails', $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param type int                     $id
     * @param type Group_assign_department $group_assign_department
     * @param type Department              $department
     * @param type DepartmentUpdate        $request
     *
     * @return type Response
     */
    public function update($id, Group_assign_department $group_assign_department, Department $department, DepartmentUpdate $request)
    {
        // dd($id);
        try {
            $table = $group_assign_department->where('department_id', $id);
            $table->delete();
            $requests = $request->input('group_id');
            // foreach ($requests as $req) {
            // DB::insert('insert into group_assign_department (group_id, department_id) values (?,?)', [$req, $id]);
            // }
            $departments = $department->whereId($id)->first();
            if ($request->manager) {
                $departments->manager = $request->input('manager');
            } else {
                $departments->manager = null;
            }
            $departments->save();
            if ($request->sla) {
                $departments->sla = $request->input('sla');
                $departments->save();
            } else {
                $departments->sla = null;
                $departments->save();
            }
            if ($request->input('sys_department') == 'on') {
                DB::table('settings_system')
                    ->where('id', 1)
                    ->update(['department' => $id]);
            }
            if ($departments->fill($request->except('group_access', 'manager', 'sla'))->save()) {
                return redirect('departments')->with('success', Lang::get('lang.department_updated_sucessfully'));
            } else {
                return redirect('departments')->with('fails', Lang::get('lang.department_not_updated'));
            }
        } catch (Exception $e) {
            return redirect('departments')->with('fails', Lang::get('lang.department_not_updated'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param type int                     $id
     * @param type Department              $department
     * @param type Group_assign_department $group_assign_department
     *
     * @return type Response
     */
    public function destroy($id, Department $department, Group_assign_department $group_assign_department, System $system, Tickets $tickets)
    {
        // try {
        $system = $system->where('id', '=', '1')->first();
        if ($system->department == $id) {
            return redirect('departments')->with('fails', Lang::get('lang.you_cannot_delete_default_department'));
        } else {
            $tickets = DB::table('tickets')->where('dept_id', '=', $id)->update(['dept_id' => $system->department]);
            if ($tickets > 0) {
                if ($tickets > 1) {
                    $text_tickets = 'Tickets';
                } else {
                    $text_tickets = 'Ticket';
                }
                $ticket = '<li>' . $tickets . ' ' . $text_tickets . Lang::get('lang.have_been_moved_to_default_department') . '</li>';
            } else {
                $ticket = '';
            }
            $users = DB::table('users')->where('primary_dpt', '=', $id)->update(['primary_dpt' => $system->department]);
            if ($users > 0) {
                if ($users > 1) {
                    $text_user = 'Users';
                } else {
                    $text_user = 'Staff';
                }
                $user = '<li>' . $users . ' ' . $text_user . Lang::get('lang.have_been_moved_to_default_department') . '</li>';
            } else {
                $user = '';
            }
            $mailboxes = DB::table('mailboxes')->where('department', '=', $id)->update(['department' => $system->department]);
            if ($mailboxes > 0) {
                if ($mailboxes > 1) {
                    $text_emails = 'Mailboxes';
                } else {
                    $text_emails = 'Mailboxes';
                }
                $mailbox = '<li>' . $mailboxes . ' System ' . $text_emails . Lang::get('lang.have_been_moved_to_default_department') . ' </li>';
            } else {
                $mailbox = '';
            }
            $helptopic = DB::table('help_topic')->where('department', '=', $id)->update(['department' => null], ['status' => '1']);
            if ($helptopic > 0) {
                $helptopic = '<li>' . Lang::get('lang.the_associated_helptopic_has_been_deactivated') . '</li>';
            } else {
                $helptopic = '';
            }
            $message = $ticket . $user . $mailbox . $helptopic;
            /* Becouse of foreign key we delete group_assign_department first */
            $group_assign_department = $group_assign_department->where('department_id', $id);
            $group_assign_department->delete();
            $departments = $department->whereId($id)->first();
            /* Check the function is Success or Fail */
            if ($departments->delete() == true) {
                return redirect('departments')->with('success', Lang::get('lang.department_deleted_sucessfully') . $message);
            } else {
                return redirect('departments')->with('fails', Lang::get('lang.department_can_not_delete'));
            }
        }
    }
}
