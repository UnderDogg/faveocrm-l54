@extends('themes.default1.admin.layout.admin')

@section('Staff')
  active
@stop

@section('staff-bar')
  active
@stop

@section('staff')
  class="active"
  @stop

  @section('HeadInclude')
  @stop
    <!-- header -->
@section('PageHeader')
  <h1>{{ Lang::get('lang.staff')}} </h1>
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
  <div class="box box-primary">
    <div class="box-header">
      <h2 class="box-title">{!! Lang::get('lang.list_of_agents') !!} </h2><a href="{{route('staff.create')}}"
                                                                             class="btn btn-primary pull-right">
        <span class="glyphicon glyphicon-plus"></span> &nbsp;{!! Lang::get('lang.create_an_agent') !!}</a></div>
    <div class="box-body table-responsive">
      <?php
      $user = App\Model\helpdesk\Staff\Staff::where('role', '!=', 'user')->orderBy('id', 'ASC')->paginate(10);
      ?>
        <!-- check whether success or not -->
      @if(Session::has('success'))
        <div class="alert alert-success alert-dismissable">
          <i class="fa  fa-check-circle"></i>
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
          {{Session::get('success')}}
        </div>
        @endif
          <!-- failure message -->
        @if(Session::has('fails'))
          <div class="alert alert-danger alert-dismissable">
            <i class="fa fa-ban"></i>
            <b>{!! Lang::get('lang.fails') !!}!</b>
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{Session::get('fails')}}
          </div>
          @endif
            <!-- Warning Message -->
          @if(Session::has('warning'))
            <div class="alert alert-warning alert-dismissable">
              <i class="fa fa-warning"></i>
              <b>{!! Lang::get('lang.warning') !!}!</b>
              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
              {{Session::get('warning')}}
            </div>
          @endif
              <!-- Staff table -->
            <table class="table table-bordered dataTable" style="overflow:hidden;">
              <tr>
                <th width="100px">{{Lang::get('lang.name')}}</th>
                <th width="100px">{{Lang::get('lang.user_name')}}</th>
                <th width="100px">{{Lang::get('lang.role')}}</th>
                <th width="100px">{{Lang::get('lang.status')}}</th>

                <th width="100px">{{Lang::get('lang.department')}}</th>
                <th width="100px">{{Lang::get('lang.created')}}</th>
                {{-- <th width="100px">{{Lang::get('lang.lastlogin')}}</th> --}}
                <th width="100px">{{Lang::get('lang.action')}}</th>
              </tr>
              @foreach($user as $use)
                {{--@if($use->role == 'admin' || $use->role == 'staff')--}}
                  <tr>
                    <td>&nbsp;</td>
                    <td><a
                        href="{{route('staff.edit', $use->id)}}"> {!! $use->first_name !!} {!! " ". $use->last_name !!}</a>
                    </td>
                    <td>&nbsp;</td>
                    <td>
                      @if($use->active=='1')
                        <span style="color:green">{!! Lang::get('lang.active') !!}</span>
                      @else
                        <span style="color:red">{!! Lang::get('lang.inactive') !!}</span>
                    @endif
                    <?php

                    $department = App\Model\helpdesk\Staff\Department::whereId($use->primary_dpt)->first();
                    ?>
                    <td>{{ $department->name }}</td>
                    <td>{{ faveoDate($use->created_at) }}</td>
                    {{-- <td>{{$use->Lastlogin_at}}</td> --}}
                    <td>
                      {!! Form::open(['route'=>['staff.destroy', $use->id],'method'=>'DELETE']) !!}
                      <a href="{{route('staff.edit', $use->id)}}" class="btn btn-info btn-xs btn-flat"><i
                          class="fa fa-edit" style="color:black;"> </i> {!! Lang::get('lang.edit') !!} </a>
                      <!-- To pop up a confirm Message -->
                      {{-- {!! Form::button(' <i class="fa fa-trash" style="color:black;"> </i> '  . Lang::get('lang.delete') ,['type' => 'submit', 'class'=> 'btn btn-warning btn-xs btn-flat','onclick'=>'return confirm("Are you sure?")']) !!} --}}
                      {!! Form::close() !!}
                    </td>
                  </tr>
                {{--@endif--}}
              @endforeach
            </table>
            <div class="pull-right" style="margin-top : -10px; margin-bottom : -10px;">
              {!! $user->links() !!}
            </div>
    </div>
  </div>
@stop