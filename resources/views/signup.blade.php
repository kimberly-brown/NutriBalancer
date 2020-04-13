@extends('master')

@section('content')

<div class="well">
  {!! Form::open(['action'=>'UserController@processSignup1', 'class'=>'form-horizontal']) !!}

  <fieldset>
    <legend>Create an account</legend>

    <!--First name-->
    <div class="form-group">
      {!! Form::label('text', 'First name:', ['class'=>'col-lg-2 control-label']) !!}
      <div class="col-lg-10">
        {!! Form::text('name', $value=null, ['class'=>'form-control', 'rows'=>1]) !!}
      </div>
    </div>

    <!--Username-->
    <div class="form-group">
      {!! Form::label('text', 'Username:', ['class'=>'col-lg-2 control-label']) !!}
      <div class="col-lg-10">
        {!! Form::text('username', $value=null, ['class'=>'form-control', 'rows'=>1]) !!}
      </div>
    </div>

    <!--Email (Username)-->
    <div class="form-group">
      {!! Form::label('email', 'Email:', ['class'=>'col-lg-2 control-label']) !!}
      <div class="col-lg-10">
        {!! Form::email('email', $value=null, ['class'=>'form-control', 'placeholder'=>'email']) !!}
      </div>
    </div>

    <!--Password-->
    <div class="form-group">
      {!! Form::label('password', 'Password:', ['class'=>'col-lg-2 control-label']) !!}
      <div class="col-lg-10">
        {!! Form::password('password', ['class'=>'form-control', 'placeholder'=>'Password', 'type'=>'password']) !!}
      </div>
    </div>

    <!--Confirm password-->
    <div class="form-group">
      {!! Form::label('confirm-password', 'Confirm password:', ['class'=>'col-lg-2 control-label']) !!}
      <div class="col-lg-10">
        {!! Form::password('confirmPassword', ['class'=>'form-control', 'placeholder'=>'Confirm password', 'type'=>'password']) !!}
      </div>
    </div>

    <!--Submit Button-->
    <div class="form-group">
      <div class="col-lg-10 col-lg-offset-2">
        {!! Form::submit('Next', ['class'=> 'btn btn-lg btn-info pull-right']) !!}
      </div>
    </div>
</fieldset>
{!! Form::close() !!}
</div>

@endsection
