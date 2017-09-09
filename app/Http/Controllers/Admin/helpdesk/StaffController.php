<?php
namespace App\Http\Controllers\Admin\helpdesk;

// controller
use App\Http\Controllers\Common\PhpMailController;
use App\Http\Controllers\Controller;
// request
use App\Http\Requests\helpdesk\StaffRequest;
use App\Http\Requests\helpdesk\StaffUpdate;
// model
use App\Model\helpdesk\Staff\Assign_team_Staff;
use App\Model\helpdesk\Staff\Department;
use App\Model\helpdesk\Staff\Groups;
use App\Model\helpdesk\Staff\Teams;
use App\Model\helpdesk\Utility\CountryCode;
use App\Model\helpdesk\Utility\Timezones;
use App\Staff;
// classes
use DB;
use Exception;
use GeoIP;
use Hash;
use Lang;

/**
 * StaffController
 * This controller is used to CRUD Staff.
 *
 * @author      Ladybird <info@ladybirdweb.com>
 */
class StaffController extends Controller
{
    /**
     * Create a new controller instance.
     * constructor to check
     * 1. authentication
     * 2. user roles
     * 3. roles must be staff.
     *
     * @return void
     */
    public function __construct(PhpMailController $PhpMailController)
    {
        // creating an instance for the PhpmailController
        $this->PhpMailController = $PhpMailController;
        // checking authentication
        $this->middleware('auth');
        // checking admin roles
        $this->middleware('roles');
    }

    /**
     * Get all staff list page.
     *
     * @return type view
     */
    public function index()
    {
        try {
            return view('themes.default1.admin.helpdesk.staff.staff.index');
        } catch (Exception $e) {
            return redirect()->back()->with('fails', $e->getMessage());
        }
    }

    /**
     * creating a new staff.
     *
     * @param Assign_team_Staff $team_assign_agent
     * @param Timezones $timezone
     * @param Groups $group
     * @param Department $department
     * @param Teams $team_all
     *
     * @return type view
     */
    public function create(Timezones $timezone, Groups $group, Department $department, Teams $team_all, CountryCode $code)
    {
        try {
            // gte all the teams
            $team = $team_all->where('status', '=', 1)->get();
            // get all the timezones
            $timezones = $timezone->get();
            // get all the groups
            $groups = '';
            // get all department
            $departments = $department->get();
            // list all the teams in a single variable
            $teams = $team->pluck('id', 'name')->toArray();
            $location = GeoIP::getLocation();
            $phonecode = $code->where('iso', '=', $location->iso_code)->first();
            // returns to the page with all the variables and their datas
            $send_otp = DB::table('common_settings')->select('status')->where('option_name', '=', 'send_otp')->first();
            return view('themes.default1.admin.helpdesk.staff.staff.create', compact('assign', 'teams', 'staff', 'timezones', 'groups', 'departments', 'team', 'send_otp'))->with('phonecode', $phonecode->phonecode);
        } catch (Exception $e) {
            // returns if try fails with exception meaagse
            return redirect()->back()->with('fails', $e->getMessage());
        }
    }

    /**
     * store a new staff.
     *
     * @param Staff $staff
     * @param StaffRequest $request
     * @param Assign_team_Staff $team_assign_agent
     *
     * @return type Response
     */
    public function store(Staff $staff, StaffRequest $request)
    {
        $staff_name = strtolower($request->get('user_name'));
        $permission = $request->input('permission');
        $request->merge(['user_name' => $staff_name]);
        if ($request->get('country_code') == '' && ($request->get('phone_number') != '' || $request->get('mobile') != '')) {
            return redirect()->back()->with(['fails2' => Lang::get('lang.country-code-required-error'), 'country_code' => 1])->withInput();
        } else {
            $code = CountryCode::select('phonecode')->where('phonecode', '=', $request->get('country_code'))->get();
            if (!count($code)) {
                return redirect()->back()->with(['fails2' => Lang::get('lang.incorrect-country-code-error'), 'country_code' => 1])->withInput();
            }
        }
        // fixing the user role to staff
        $staff->fill($request->except(['permission', 'primary_department', 'agent_time_zone', 'mobile']))->save();
        if (count($permission) > 0) {
            $staff->permission()->create(['permission' => $permission]);
        }
        if ($request->get('mobile')) {
            $staff->mobile = $request->get('mobile');
        } else {
            $staff->mobile = null;
        }
        $staff->assign_group = $request->group;
        $staff->primary_dpt = $request->primary_department;
        $staff->agent_tzone = $request->agent_time_zone;
        // generate password and has immediately to store
        $password = $this->generateRandomString();
        $staff->password = Hash::make($password);
        // fetching all the team details checked for this user
        $requests = $request->input('team');
        // get user id of the inserted user detail
        $id = $staff->id;
        // insert team
        foreach ($requests as $req) {
            // insert all the selected team id to the team and staff relationship table
            DB::insert('insert into team_assign_agent (team_id, agent_id) values (?,?)', [$req, $id]);
        }
        // save user credentails
        if ($staff->save() == true) {
            // fetch user credentails to send mail
            $name = $staff->first_name;
            $mailbox = $staff->email;
            if ($request->input('send_email')) {
                try {
                    // send mail on registration
                    $this->PhpMailController->sendmail($from = $this->PhpMailController->mailfrom('1', '0'), $to = ['name' => $name, 'email' => $mailbox], $message = ['subject' => null, 'scenario' => 'registration-notification'], $template_variables = ['staff' => $name, 'email_address' => $mailbox, 'user_password' => $password]);
                } catch (Exception $e) {
                    // returns if try fails
                    return redirect('staff')->with('warning', Lang::get('lang.agent_send_mail_error_on_agent_creation'));
                }
            }
            // returns for the success case
            if ($request->input('active') == '0' || $request->input('active') == 0) {
                \Event::fire(new \App\Events\LoginEvent($request));
            }
            return redirect('staff')->with('success', Lang::get('lang.agent_creation_success'));
        } else {
            // returns if fails
            return redirect('staff')->with('fails', Lang::get('lang.failed_to_create_agent'));
        }
    }

    /**
     * Editing a selected staff.
     *
     * @param type int               $id
     * @param type Staff              $staff
     * @param type Assign_team_Staff $team_assign_agent
     * @param type Timezones         $timezone
     * @param type Groups            $group
     * @param type Department        $department
     * @param type Teams             $team
     *
     * @return type Response
     */
    public function edit($id, Staff $staff, Assign_team_Staff $team_assign_agent, Timezones $timezone, Groups $group, Department $department, Teams $team, CountryCode $code)
    {

dd($id);


        try {
            $location = GeoIP::getLocation();
            $phonecode = $code->where('iso', '=', $location->iso_code)->first();
            $staff = $staff->whereId($id)->first();
            $team = $team->where('status', '=', 1)->get();
            $teams1 = $team->pluck('name', 'id');
            $timezones = $timezone->get();
            $groups = ''; //$group->where('group_status', '=', 1)->get();
            $departments = $department->get();
            $table = $team_assign_agent->where('staff_id', $id)->first();
            $teams = $team->pluck('id', 'name')->toArray();
            $assign = $team_assign_agent->where('staff_id', $id)->pluck('team_id')->toArray();
            return view('themes.default1.admin.helpdesk.staff.staff.edit', compact('teams', 'assign', 'table', 'teams1', 'selectedTeams', 'staff', 'timezones', 'groups', 'departments', 'team', 'exp', 'counted'))->with('phonecode', $phonecode->phonecode);
        } catch (Exception $e) {
            return redirect('staff')->with('fail', Lang::get('lang.failed_to_edit_agent'));
        }
    }

    /**
     * Update the specified staff in storage.
     *
     * @param type int               $id
     * @param type Staff              $staff
     * @param type StaffUpdate       $request
     * @param type Assign_team_Staff $team_assign_agent
     *
     * @return type Response
     */
    public function update($id, Staff $staff, StaffUpdate $request, Assign_team_Staff $team_assign_agent)
    {
        $permission = $request->input('permission');
        $staff_name = strtolower($request->get('user_name'));
        $request->merge(['user_name' => $staff_name]);
        if ($request->get('country_code') == '' && ($request->get('phone_number') != '' || $request->get('mobile') != '')) {
            return redirect()->back()->with(['fails2' => Lang::get('lang.country-code-required-error'), 'country_code' => 1])->withInput();
        } else {
            $code = CountryCode::select('phonecode')->where('phonecode', '=', $request->get('country_code'))->get();
            if (!count($code)) {
                return redirect()->back()->with(['fails2' => Lang::get('lang.incorrect-country-code-error'), 'country_code' => 1])->withInput();
            }
        }
        // storing all the details
        $staff = $staff->whereId($id)->first();
        $daylight_save = $request->input('daylight_save');
        $limit_access = $request->input('limit_access');
        $directory_listing = $request->input('directory_listing');
        $vocation_mode = $request->input('vocation_mode');
        //==============================================
        $table = $team_assign_agent->where('staff_id', $id);
        $table->delete();
        $requests = $request->input('team');
        // inserting team details
        foreach ($requests as $req) {
            DB::insert('insert into team_assign_agent (team_id, agent_id) values (?,?)', [$req, $id]);
        }
        //Todo For success and failure conditions
        try {
            if ($request->input('country_code') != '' or $request->input('country_code') != null) {
                $staff->country_code = $request->input('country_code');
            }
            $staff->mobile = ($request->input('mobile') == '') ? null : $request->input('mobile');
            $staff->fill($request->except('daylight_save', 'limit_access', 'directory_listing', 'vocation_mode', 'assign_team', 'mobile'));
            $staff->assign_group = $request->group;
            $staff->primary_dpt = $request->primary_department;
            $staff->agent_tzone = $request->agent_time_zone;
            $staff->save();
            $staff->permission()->updateOrCreate(['user_id' => $staff->id], ['permission' => json_encode($permission)]);
            return redirect('staff')->with('success', Lang::get('lang.agent_updated_sucessfully'));
        } catch (Exception $e) {
            return redirect('staff')->with('fails', Lang::get('lang.unable_to_update_agent') . '<li>' . $e->errorInfo[2] . '</li>');
        }
    }

    /**
     * Remove the specified staff from storage.
     *
     * @param type $id
     * @param Staff $staff
     * @param Assign_team_Staff $team_assign_agent
     *
     * @throws Exception
     *
     * @return type Response
     */
    public function destroy($id, Staff $staff, Assign_team_Staff $team_assign_agent)
    {
        /* Becouse of foreign key we delete team_assign_agent first */
        error_reporting(E_ALL & ~E_NOTICE);
        $team_assign_agent = $team_assign_agent->where('staff_id', $id);
        $team_assign_agent->delete();
        $staff = $staff->whereId($id)->first();
        try {
            $error = Lang::get('lang.this_staff_is_related_to_some_tickets');
            $staff->id;
            $staff->delete();
            throw new \Exception($error);
            return redirect('staff')->with('success', Lang::get('lang.agent_deleted_sucessfully'));
        } catch (\Exception $e) {
            return redirect('staff')->with('fails', $error);
        }
    }

    /**
     * Generate a random string for password.
     *
     * @param type $length
     *
     * @return string
     */
    public function generateRandomString($length = 10)
    {
        // list of supported characters
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        // character length checked
        $charactersLength = strlen($characters);
        // creating an empty variable for random string
        $randomString = '';
        // fetching random string
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        // return random string
        return $randomString;
    }
}
