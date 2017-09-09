<?php
namespace App\Http\Controllers\Staff\helpdesk;

// controllers
use App\Http\Controllers\Controller;
// requests
use App\Http\Requests\helpdesk\RelationRequest;
/* include relation model */
use App\Http\Requests\helpdesk\RelationUpdate;

// models
/* Define RelationRequest to validate the create form */
use App\Model\helpdesk\Agent_panel\Relation;
use App\Model\helpdesk\Agent_panel\User_org;
/* Define RelationUpdate to validate the create form */
use App\Staff;
// classes
use Exception;
use Illuminate\Http\Request;
use Lang;

/**
 * RelationController
 * This controller is used to CRUD relation detail.
 *
 * @author      Ladybird <info@ladybirdweb.com>
 */
class RelationController extends Controller
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
    public function __construct()
    {
        // checking for authentication
        $this->middleware('auth');
        // checking if the role is staff
        $this->middleware('role.staff');
    }

    /**
     * Display a listing of the resource.
     *
     * @param type Relation $org
     *
     * @return type Response
     */
    public function index()
    {
        try {
            /* get all values of table relation */
            return view('themes.default1.staff.helpdesk.relation.index');
        } catch (Exception $e) {
            return redirect()->back()->with('fails', $e->getMessage());
        }
    }

    /**
     * This function is used to display the list of Relations.
     *
     * @return datatable
     */
    public function org_list()
    {
        // chumper datable package call to display Advance datatable
        return \Datatable::collection(Relation::all())
            /* searchable name */
            ->searchColumns('name')
            /* order by name and website */
            ->orderColumns('name', 'website')
            /* column name */
            ->addColumn('name', function ($model) {
                // return $model->name;
                if (strlen($model->name) > 20) {
                    $orgname = substr($model->name, 0, 25);
                    $orgname = substr($orgname, 0, strrpos($orgname, ' ')) . ' ...';
                } else {
                    $orgname = $model->name;
                }
                return $orgname;
            })
            /* column website */
            ->addColumn('website', function ($model) {
                $website = $model->website;
                return $website;
            })
            /* column phone number */
            ->addColumn('phone', function ($model) {
                $phone = $model->phone;
                return $phone;
            })
            /* column action buttons */
            ->addColumn('Actions', function ($model) {
                // displaying action buttons
                // modal popup to delete data
                return '<span  data-toggle="modal" data-target="#deletearticle' . $model->id . '"><a href="#" ><button class="btn btn-danger btn-xs"></a> ' . \Lang::get('lang.delete') . ' </button></span>&nbsp;<a href="' . route('organizations.edit', $model->id) . '" class="btn btn-warning btn-xs">' . \Lang::get('lang.edit') . '</a>&nbsp;<a href="' . route('organizations.show', $model->id) . '" class="btn btn-primary btn-xs">' . \Lang::get('lang.view') . '</a>
				<div class="modal fade" id="deletearticle' . $model->id . '">
			        <div class="modal-dialog">
			            <div class="modal-content">
                			<div class="modal-header">
                    			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    			<h4 class="modal-title">Are You Sure ?</h4>
                			</div>
                			<div class="modal-body">
                				' . $model->user_name . '
                			</div>
                			<div class="modal-footer">
                    			<button type="button" class="btn btn-default pull-left" data-dismiss="modal" id="dismis2">Close</button>
                    			<a href="' . route('org.delete', $model->id) . '"><button class="btn btn-danger">delete</button></a>
                			</div>
            			</div><!-- /.modal-content -->
        			</div><!-- /.modal-dialog -->
    			</div>';
            })
            ->make();
    }

    /**
     * Show the form for creating a new relation.
     *
     * @return type Response
     */
    public function create()
    {
        try {
            return view('themes.default1.staff.helpdesk.relation.create');
        } catch (Exception $e) {
            return redirect()->back()->with('fails', $e->getMessage());
        }
    }

    /**
     * Store a newly created relation in storage.
     *
     * @param type Relation        $org
     * @param type RelationRequest $request
     *
     * @return type Redirect
     */
    public function store(Relation $org, RelationRequest $request)
    {
        try {
            /* Insert the all input request to relation table */
            /* Check whether function success or not */
            if ($org->fill($request->input())->save() == true) {
                /* redirect to Index page with Success Message */
                return redirect('relations')->with('success', Lang::get('lang.relation_created_successfully'));
            } else {
                /* redirect to Index page with Fails Message */
                return redirect('relations')->with('fails', Lang::get('lang.relation_can_not_create'));
            }
        } catch (Exception $e) {
            /* redirect to Index page with Fails Message */
            return redirect('relations')->with('fails', Lang::get('lang.relation_can_not_create'));
        }
    }

    /**
     * Display the specified relation.
     *
     * @param type $id
     * @param type Relation $org
     *
     * @return type view
     */
    public function show($id, Relation $org)
    {
        try {
            /* select the field by id  */
            $orgs = $org->whereId($id)->first();
            /* To view page */
            return view('themes.default1.staff.helpdesk.relation.show', compact('orgs'));
        } catch (Exception $e) {
            return redirect()->back()->with('fails', $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified relation.
     *
     * @param type $id
     * @param type Relation $org
     *
     * @return type view
     */
    public function edit($id, Relation $org)
    {
        try {
            /* select the field by id  */
            $orgs = $org->whereId($id)->first();
            /* To view page */
            return view('themes.default1.staff.helpdesk.relation.edit', compact('orgs'));
        } catch (Exception $e) {
            return redirect()->back()->with('fails', $e->getMessage());
        }
    }

    /**
     * Update the specified relation in storage.
     *
     * @param type $id
     * @param type Relation       $org
     * @param type RelationUpdate $request
     *
     * @return type Redirect
     */
    public function update($id, Relation $org, RelationUpdate $request)
    {
        try {
            /* select the field by id  */
            $orgs = $org->whereId($id)->first();
            /* update the relation table   */
            /* Check whether function success or not */
            if ($orgs->fill($request->input())->save() == true) {
                /* redirect to Index page with Success Message */
                return redirect('relations')->with('success', Lang::get('lang.relation_updated_successfully'));
            } else {
                /* redirect to Index page with Fails Message */
                return redirect('relations')->with('fails', Lang::get('lang.relation_can_not_update'));
            }
        } catch (Exception $e) {
            //            dd($e);
            /* redirect to Index page with Fails Message */
            return redirect('relations')->with('fails', $e->getMessage());
        }
    }

    /**
     * Delete a specified relation from storage.
     *
     * @param type int $id
     *
     * @return type Redirect
     */
    public function destroy($id, Relation $org, User_org $user_org)
    {
        try {
            /* select the field by id  */
            $orgs = $org->whereId($id)->first();
            $user_orgs = $user_org->where('org_id', '=', $id)->get();
            foreach ($user_orgs as $user_org) {
                $user_org->delete();
            }
            /* Delete the field selected from the table */
            /* Check whether function success or not */
            $orgs->delete();
            /* redirect to Index page with Success Message */
            return redirect('relations')->with('success', Lang::get('lang.relation_deleted_successfully'));
        } catch (Exception $e) {
            /* redirect to Index page with Fails Message */
            return redirect('relations')->with('fails', $e->getMessage());
        }
    }

    /**
     * Soring an relation head.
     *
     * @param type $id
     *
     * @return type boolean
     */
    public function Head_Org($id)
    {
        // get the user to make relation head
        $head_user = \Input::get('user');
        // get an instance of the selected relation
        $org_head = Relation::where('id', '=', $id)->first();
        $org_head->head = $head_user;
        // save the user to relation head
        $org_head->save();
        return 1;
    }

    /**
     * get the report of organizations.
     *
     * @param type $id
     * @param type $date111
     * @param type $date122
     *
     * @return type array
     */
    public function orgChartData($id, $date111 = '', $date122 = '')
    {
        $date11 = strtotime($date122);
        $date12 = strtotime($date111);
        if ($date11 && $date12) {
            $date2 = $date12;
            $date1 = $date11;
        } else {
            // generating current date
            $date2 = strtotime(date('Y-m-d'));
            $date3 = date('Y-m-d');
            $format = 'Y-m-d';
            // generating a date range of 1 month
            $date1 = strtotime(date($format, strtotime('-1 month' . $date3)));
        }
        $return = '';
        $last = '';
        for ($i = $date1; $i <= $date2; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            $user_orga_relation_id = '';
            $user_orga_relations = User_org::where('org_id', '=', $id)->get();
            foreach ($user_orga_relations as $user_orga_relation) {
                $user_orga_relation_id[] = $user_orga_relation->user_id;
            }
            $created = \DB::table('tickets')->select('created_at')->whereIn('user_id', $user_orga_relation_id)->where('created_at', 'LIKE', '%' . $thisDate . '%')->count();
            $closed = \DB::table('tickets')->select('closed_at')->whereIn('user_id', $user_orga_relation_id)->where('closed_at', 'LIKE', '%' . $thisDate . '%')->count();
            $reopened = \DB::table('tickets')->select('reopened_at')->whereIn('user_id', $user_orga_relation_id)->where('reopened_at', 'LIKE', '%' . $thisDate . '%')->count();
            $value = ['date' => $thisDate, 'open' => $created, 'closed' => $closed, 'reopened' => $reopened];
            $array = array_map('htmlentities', $value);
            $json = html_entity_decode(json_encode($array));
            $return .= $json . ',';
        }
        $last = rtrim($return, ',');
        $users = Staff::whereId($id)->first();
        return '[' . $last . ']';
    }

    public function getOrgAjax(Request $request)
    {
        $org = new Relation();
        $q = $request->input('term');
        $orgs = $org->where('name', 'LIKE', '%' . $q . '%')
            ->select('name as label', 'id as value')
            ->get()
            ->toJson();
        return $orgs;
    }

    /**
     * This function is used autofill organizations name .
     *
     * @return datatable
     */
    public function organizationAutofill()
    {
        return view('themes.default1.staff.helpdesk.relation.getautocomplete');
    }
}
