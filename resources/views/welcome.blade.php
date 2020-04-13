@extends('master')

@section('content')

@if (Route::has('login'))
    <div class="top-right links">
        @auth
            <a href="{{ url('/home') }}">Home</a>
        @else
            <a href="{{ route('login') }}">Login</a>

            @if (Route::has('register'))
                <a href="{{ route('register') }}">Register</a>
            @endif
        @endauth
    </div>
@endif
  <div style="text-align:center">

  <br>
  <br>
  <br>
  <br>
  <br>
  <br>
    <h1 style="font-size: 80px; margin-left:100px">
        Welcome to the Nutrition Balancer
    </h1>
    <br>
    <br>
    <br>
    <a href="login" class="btn btn-primary btn-lg active" role="button" aria-pressed="true">
      Log in
    </a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <a href="signup" class="btn btn-secondary btn-lg active" role="button" aria-pressed="true">
      Sign up
    </a>
  </div>

  @endsection
