@extends('master')

@section('content')

@if ($error != '')
    <div class="alert alert-danger">
        {{ $error }}
    </div>
@endif

<div class="well">

  {!! Form::open(['action'=>'UserController@loginValidate', 'class'=>'form-horizontal']) !!}

  <fieldset>
    <legend>Log in</legend>

    <!--Username-->
    <div class="form-group">
      {!! Form::label('username', 'Username:', ['class'=>'col-lg-2 control-label']) !!}
      <div class="col-lg-10">
        {!! Form::text('username', $value=null, ['class'=>'form-control', 'rows'=>1]) !!}
      </div>
    </div>

    <!--Password-->
    <div class="form-group">
      {!! Form::label('password', 'Password:', ['class'=>'col-lg-2 control-label']) !!}
      <div class="col-lg-10">
        {!! Form::password('password', ['class'=>'form-control', 'placeholder'=>'Password', 'type'=>'password']) !!}
      </div>
    </div>

    <!--Submit Button-->
    <div class="form-group">
      <div class="col-lg-10 col-lg-offset-2">
        {!! Form::submit('Log in', ['class'=> 'btn btn-lg btn-info pull-right']) !!}
      </div>
    </div>
</fieldset>
{!! Form::close() !!}

<p>
  <a href="/forgot">
    Forgot username or password?
  </a><br>
  <a href="/signup">
    Create an account
  </a>
</p>
</div>

@endsection
