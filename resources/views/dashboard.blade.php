@extends('master')

@section('content')

@include('navbar', ['theme_color'=>$theme_color])

<h3> Welcome back, {{ $name }}. </h3>
<p style="color:{{$message_color}}">{{$message}}</p>
<hr>
<h3>Meal plan
  <a href="/dashboard/save" class="btn btn-secondary btn-lg active" role="button">
    Save
  </a>
<a href="{{ route('generateNewPlan', ['id' => $id])}}"
   class="btn btn-secondary btn-lg active" role="button" aria-pressed="false">
  Generate new
</a>
</h3>

<h5>Monday</h5>
@include('dayPlan', ['meals'=>$monday, 'day'=>'Monday'])

<h5>Tuesday</h5>
@include('dayPlan', ['meals'=>$tuesday, 'day'=>'Tuesday'])

<h5>Wednesday</h5>
@include('dayPlan', ['meals'=>$wednesday, 'day'=>"Wednesday"])

<h5>Thursday</h5>
@include('dayPlan', ['meals'=>$thursday, 'day'=>"Thursday"])

<h5>Friday</h5>
@include('dayPlan', ['meals'=>$friday, 'day'=>"Friday"])

<h5>Saturday</h5>
@include('dayPlan', ['meals'=>$saturday, 'day'=>"Saturday"])

<h5>Sunday</h5>
@include('dayPlan', ['meals'=>$sunday, 'day'=>"Sunday"])


<hr>
<h3>Morning Staples
<a href="{{ route('modifyFaves', ['id'=>$id, 'type'=>'morning', 'viewIngredients'=>-1])}}"
  class="btn btn-secondary btn-lg active" role="button" aria-pressed="true">
  Edit
</a>
</h3>
<h5>Ingredients</h5>
<p>
<ul style="list-style-type:none;">
@for ($i = 0; $i < count($morning_faves)-1; $i++)
  <li>{{ $morning_faves[$i] }}</li>
@endfor
</ul>
</p>
<br>

<h5>Recipes</h5>
<p>
  <ul style="list-style-type:none;">
  @for ($i = 0; $i < count($morning_staples); $i++)
    <li>{{ $morning_staples[$i][0] }}</li>
  @endfor
  </ul>
</p>

<hr>
<h3>Evening Staples
  <a href="{{ route('modifyFaves', ['id'=>$id, 'type'=>'evening', 'viewIngredients'=>-1])}}"
    class="btn btn-secondary btn-lg active" role="button" aria-pressed="true">
    Edit
  </a>
</h3>
<h5>Ingredients</h5>
<p>
<ul style="list-style-type:none;">
@for ($i = 0; $i < count($evening_faves)-1; $i++)
  <li>{{ $evening_faves[$i] }}</li>
@endfor
</ul>
</p>
<br>

<h5>Recipes</h5>

  <p>
    <ul style="list-style-type:none;">
    @for ($i = 0; $i < count($evening_staples); $i++)
      <li>{{ $evening_staples[$i][0] }}</li>
    @endfor
    </ul>
  </p>

<hr>
<h3>Grocery list</h3>
<p>
  <ul style="list-style-type:none;">
    @for ($i=0; $i< count($shopping_list); $i++)
      <li>{{ $shopping_list[$i] }}</li>
    @endfor
  </ul>
</p>

@endsection
