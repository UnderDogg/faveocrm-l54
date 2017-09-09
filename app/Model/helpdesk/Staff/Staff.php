<?php
namespace App\Model\helpdesk\Staff;

use App\BaseModel;

class Staff extends BaseModel
{
    protected $table = 'staff';
    protected $fillable = [
        'user_name', 'first_name', 'last_name', 'email', 'phone', 'mobile', 'agent_sign',
        'account_type', 'account_status', 'assign_group', 'primary_dpt', 'agent_tzone',
        'daylight_save', 'limit_access', 'directory_listing', 'vocation_mode', 'assign_team',
    ];


    public function getProfilePicAttribute($value)
    {
        $info = $this->avatar();
        $pic = null;
        if ($info) {
            $pic = $this->checkArray('avatar', $info);
        }
        if (!$pic && $value) {
            $pic = '';
            $file = asset('uploads/profilepic/' . $value);
            if ($file) {
                $type = pathinfo($file, PATHINFO_EXTENSION);
                $data = file_get_contents($file);
                $pic = 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }
        if (!$value) {
            $pic = \Gravatar::src($this->attributes['email']);
        }
        return $pic;
    }

    public function avatar()
    {
        $related = 'App\StaffAdditionalInfo';
        $foreignKey = 'owner';
        return $this->hasMany($related, $foreignKey)->select('value')->where('key', 'avatar')->first();
    }

    public function getOrgRelation()
    {
        $related = "App\Model\helpdesk\Agent_panel\User_org";
        $user_relation = $this->hasMany($related, 'user_id');
        $relation = $user_relation->first();
        if ($relation) {
            $org_id = $relation->org_id;
            $orgs = new \App\Model\helpdesk\Agent_panel\Relation();
            $org = $orgs->where('id', $org_id);
            return $org;
        }
    }

    public function getOrganization()
    {
        $name = '';
        if ($this->getOrgRelation()) {
            $org = $this->getOrgRelation()->first();
            if ($org) {
                $name = $org->name;
            }
        }
        return $name;
    }

    public function getOrgWithLink()
    {
        $name = '';
        $org = $this->getOrganization();
        if ($org !== '') {
            $orgs = $this->getOrgRelation()->first();
            if ($orgs) {
                $id = $orgs->id;
                $name = '<a href=' . url('relations/' . $id) . '>' . ucfirst($org) . '</a>';
            }
        }
        return $name;
    }

    public function getEmailAttribute($value)
    {
        if (!$value) {
            $value = \Lang::get('lang.not-available');
        }
        return $value;
    }

    public function getExtraInfo($id = '')
    {
        if ($id === '') {
            $id = $this->attributes['id'];
        }
        $info = new StaffAdditionalInfo();
        $infos = $info->where('owner', $id)->pluck('value', 'key')->toArray();
        return $infos;
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

    public function twitterLink()
    {
        $html = '';
        $info = $this->getExtraInfo();
        $username = $this->checkArray('username', $info);
        if ($username !== '') {
            $html = "<a href='https://twitter.com/" . $username . "' target='_blank'><i class='fa fa-twitter'> </i> Twitter</a>";
        }
        return $html;
    }

    public function name()
    {
        $first_name = $this->first_name;
        $last_name = $this->last_name;
        $name = $this->user_name;
        if ($first_name !== '' && $first_name !== null) {
            if ($last_name !== '' && $last_name !== null) {
                $name = $first_name . ' ' . $last_name;
            } else {
                $name = $first_name;
            }
        }
        return $name;
    }

    public function getFullNameAttribute()
    {
        return $this->name();
    }

    //    public function save() {
    //        dd($this->id);
    //        parent::save();
    //    }
    //    public function save(array $options = array()) {
    //        parent::save($options);
    //        dd($this->where('id',$this->id)->select('first_name','last_name','user_name','email')->get()->toJson());
    //    }
    public function org()
    {
        return $this->hasOne('App\Model\helpdesk\Agent_panel\User_org', 'user_id');
    }

    public function permission()
    {
        return $this->hasOne('App\Model\helpdesk\Staff\Roles', 'user_id');
    }

    public function save(array $options = [])
    {
        $changed = $this->isDirty() ? $this->getDirty() : false;
        $user = parent::save();
        $this->updateDeletedUserDependency($changed);
        return $user;
    }

    public function ticketsAssigned()
    {
        $related = 'App\Model\helpdesk\Ticket\Tickets';
        return $this->hasMany($related, 'assigned_to');
    }

    public function updateDeletedUserDependency($changed)
    {
        if ($changed && checkArray('is_delete', $changed) == 1) {
            $this->ticketsAssigned()->whereHas('statuses.type', function ($query) {
                $query->where('name', 'open');
            })->update(['assigned_to' => null]);
        }
    }

    public function isDeleted()
    {
        $is_deleted = $this->attributes['is_delete'];
        $check = false;
        if ($is_deleted) {
            $check = true;
        }
        return $check;
    }

    public function isBan()
    {
        $is_deleted = $this->attributes['ban'];
        $check = false;
        if ($is_deleted) {
            $check = true;
        }
        return $check;
    }

    public function isActive()
    {
        $is_deleted = $this->attributes['active'];
        $check = false;
        if ($is_deleted) {
            $check = true;
        }
        return $check;
    }

    public function isMobileVerified()
    {
        $is_deleted = $this->attributes['mobile_verify'];
        $check = false;
        if ($is_deleted) {
            $check = true;
        }
        return $check;
    }

}
