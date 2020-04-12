@extends('master')

@section('content')

<nav class="navbar" style="background-color: #{{$theme_color}}">
  @include('logo')

  <ul style="list-style-type:none" class="navbar-nav">
    <li class="nav-item active">
      <a class="nav-link" href="/dashboard">Home <span class="sr-only">(current)</span></a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="{{route('settings', ['id'=>$id])}}">Settings</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="/">Log out</a>
    </li>
  </ul>
</nav>
