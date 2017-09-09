@extends('themes.default1.admin.layout.admin')

@section('Mailboxes')
  active
@stop

@section('mailboxes')
  active
@stop

@section('mailboxes')
  class="active"
  @stop

  @section('HeadInclude')
  @stop
    <!-- header -->
@section('PageHeader')
  <h1>{{Lang::get('lang.mailboxes')}}</h1>
  @stop
    <!-- /header -->
  <!-- breadcrumbs -->
@section('breadcrumbs')
  <ol class="breadcrumb">

  </ol>
  @stop
    <!-- /breadcrumbs -->
  <!-- content -->
@section('content')
  <div class="row">
    <div class="col-md-12">
      <div class="box box-primary">
        <div class="box-header">
          <h2 class="box-title">{!! Lang::get('lang.mailboxes') !!}</h2><a href="{{route('mailboxes.create')}}"
                                                                        class="btn btn-primary pull-right"><span
              class="glyphicon glyphicon-plus"></span> &nbsp;{{Lang::get('lang.create_mailbox')}}</a></div>

        <div class="box-body table-responsive">

          <!-- check whether success or not -->

          @if(Session::has('success'))
            <div class="alert alert-success alert-dismissable">
              <i class="fa  fa-check-circle"></i>
              <b>Success!</b>
              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
              {{Session::get('success')}}
            </div>
            @endif
              <!-- failure message -->
            @if(Session::has('fails'))
              <div class="alert alert-danger alert-dismissable">
                <i class="fa fa-ban"></i>
                <b>Fail!</b>
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                {{Session::get('fails')}}
              </div>
              @endif

              <?php
              $default_system_email = App\Model\helpdesk\Settings\MailboxSettings::where('id', '=', '1')->first();
              if ($default_system_email->sys_email) {
                $default_email = $default_system_email->sys_email;
              } else {
                $default_email = null;
              }
              ?>
                <!-- table -->
              <table class="table table-bordered dataTable" style="overflow:hidden;">
                <tr>
                  <th width="100px">{{Lang::get('lang.email')}}</th>
                  <th width="100px">{{Lang::get('lang.priority')}}</th>
                  <th width="100px">{{Lang::get('lang.department')}}</th>
                  <th width="100px">{{Lang::get('lang.created')}}</th>
                  <th width="100px">{{Lang::get('lang.last_updated')}}</th>
                  <th width="100px">{{Lang::get('lang.action')}}</th>
                </tr>
                @foreach($mailboxes as $mailbox)
                  <tr>

                    <td><a href="{{route('mailboxes.edit', $mailbox->id)}}"> {{$mailbox -> email_address }}</a>
                      @if($default_email == $mailbox->id)
                        ( Default )
                        <?php $disabled = 'disabled'; ?>
                      @else
                        <?php $disabled = ''; ?>
                      @endif
                    </td>
                    <?php $priority = App\Model\helpdesk\Ticket\TicketPriority::where('priority_id', '=', $mailbox->priority)->first(); ?>
                    @if($mailbox->priority == null)
                      <?php $priority = "<a href=" . url('getticket') . ">System Default</a>"; ?>
                    @else
                      <?php $priority = ucfirst($priority->priority_desc); ?>
                    @endif
                    <td>{!! $priority !!}</td>
                    @if($mailbox->department !== null)
                      <?php  $department = App\Model\helpdesk\Staff\Department::where('id', '=', $mailbox->department)->first();
                      $dept = $department->name; ?>
                    @elseif($mailbox->department == null)
                      <?php  $dept = "<a href=" . url('getsystem') . ">System Default</a>"; ?>
                    @endif

                    <td>{!! $dept !!}</td>
                    <td>{!! faveoDate($mailbox->created_at) !!}</td>
                    <td>{!! faveoDate($mailbox->updated_at) !!}</td>
                    <td>
                      {!! Form::open(['route'=>['mailboxes.destroy', $mailbox->id],'method'=>'DELETE']) !!}
                      <a href="{{route('mailboxes.edit', $mailbox->id)}}" class="btn btn-info btn-xs btn-flat"><i
                          class="fa fa-edit" style="color:black;"> </i> Edit</a>
                      <!-- To pop up a confirm Message -->
                      {!! Form::button('<i class="fa fa-trash" style="color:black;"> </i> Delete',
                              ['type' => 'submit',
                              'class'=> 'btn btn-warning btn-xs btn-flat '. $disabled,
                              'onclick'=>'return confirm("Are you sure?")'])
                            !!}
                      {!! Form::close() !!}
                    </td>
                  </tr>
                @endforeach
              </table>
        </div>
      </div>
    </div>
  </div>
@stop
