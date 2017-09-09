<?php
namespace App\Policies;

use App\Staff;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function checkPermission($key)
    {
        $check = true;
        if (\Auth::user() && \Auth::user()->permission()->first()) {
            $permission = \Auth::user()->permission()->first()->permission;
            if (is_array($permission) && checkArray($key, $permission)) {
                $check = true;
            } else {
                $check = false;
            }
        }
        return $check;
    }

    public function create()
    {
        return $this->checkPermission('create_ticket');
    }

    public function edit()
    {
        return $this->checkPermission('edit_ticket');
    }

    public function close()
    {
        return $this->checkPermission('close_ticket');
    }

    public function assign()
    {
        return $this->checkPermission('assign_ticket');
    }

    public function transfer()
    {
        return $this->checkPermission('transfer_ticket');
    }

    public function delete()
    {
        return $this->checkPermission('delete_ticket');
    }

    public function ban()
    {
        return $this->checkPermission('ban_email');
    }

    public function kb()
    {
        return $this->checkPermission('access_kb');
    }

    public function orgUploadDoc()
    {
        return $this->checkPermission('organisation_document_upload');
    }

    public function emailVerification()
    {
        if (\Auth::user()->role == 'admin') {
            return true;
        }
        return $this->checkPermission('email_verification');
    }

    public function mobileVerification()
    {
        if (\Auth::user()->role == 'admin') {
            return true;
        }
        return $this->checkPermission('mobile_verification');
    }

    public function accountActivation()
    {
        if (\Auth::user()->role == 'admin') {
            return true;
        }
        return $this->checkPermission('account_activate');
    }
}
