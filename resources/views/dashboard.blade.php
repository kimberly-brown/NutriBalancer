@extends('master')

@extends('base')

@include('navbar', ['theme_color'=>$theme_color])

<h1> Welcome back, {{ $name }}. </h1>
<p style="padding-left: 30px">Week at a glance:</p>
<div class="meal-images" style="background-color:{{$theme_color}}">
    @if ( count($images) < 8)
      @for ($i=0; $i < count($images); $i++)
        <div>
          <img class="preview-images" src="{{ $images[$i][0] }}" alt="">
          <div class="img-text">{{ $images[$i][1] }}</div>
        </div>
      @endfor
    @else
      @for ($i=0; $i < 8; $i++)
        <div>
          <img class="preview-images" src="{{ $images[$i][0] }}" alt="">
          <div class="img-text">{{ $images[$i][1] }}</div>
        </div>
      @endfor
  @endif
</div>
<p style="color:{{$message_color}}">{{$message}}</p>
<hr>

<div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content" style="padding: 30px">
      <p>Would you like to save this meal plan before generating a new one?</p>
      <br>
      <p style="text-align:center">
        <a href="{{ route('saveAndGenerate', ['id'=>$id]) }}" class="btn btn-primary" role="button">Yes</a>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <a href="{{ route('generateNewPlan', ['id'=>$id]) }}" class="btn btn-secondary" role="button">No</a>
      </p>
    </div>
  </div>
</div>

<div class="dashboard-container">
  <div>
    <h3> Meal plan&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

      &nbsp;
      @if ($meal_names[0] != '')
      <button type="button" data-toggle="modal" data-target="#myModal"
      class="btn btn-secondary btn-lg active">
        Generate New
      </button>
        <a href="/dashboard/save" class="btn btn-secondary btn-lg active" role="button">
          Save
        </a>
      @else
        <a href="{{ route('generateNewPlan', ['id'=>$id]) }}" class="btn btn-secondary btn-lg active" role="button">
          Generate new
        </a>
      @endif
    </h3>
    @if ($meal_names[0] != '')
      @for ($i=0; $i < count($day_order); $i++)
        <h5>{{ $day_order[$i] }}</h5>
        @include('dayPlan', ['start'=> $meals_per_day * $i, 'day'=>$i ])
      @endfor
    @else
      <br>
      <p>Add some favorite foods and click 'Generate New' to get started!</p>
    @endif
  </div>

  <div class="dashboard-right">
    <h3>Average Nutrition Summary</h3>
    @include('averageSummary')
  </div>
</div>
<br>
<br>
<hr>
<div class="dashboard-container">
  <div class="grocery-list">
    <h3 style="margin-left: 20px">Grocery list</h3>
    <p>
      <ul style="list-style-type:none;">
      @for ($i=0; $i< count($shopping_list); $i++)
        <li>{{ $shopping_list[$i][0] }}
          (
          @for ($j=0; $j< count($shopping_list[$i][1]); $j++)
           <a href="#meal{{ $shopping_list[$i][1][$j]}}">{{ $shopping_list[$i][1][$j] }}</a>
           @if ($j != count($shopping_list[$i][1]) - 1)
              ,
           @endif
          @endfor
          )
        </li>
      @endfor
      </ul>
    </p>
    @if ( count($shopping_list) == 0)
      <p>
        Generate a meal plan to see your grocery list!
      </p>
    @endif
  </div>
  <div class="dashboard-right">
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

    <h5>Recipes</h5>
    <p>
      <ul style="list-style-type:none;">
      @for ($i = 0; $i < count($morning_staples); $i++)
        <li>{{ $morning_staples[$i][0] }}</li>
      @endfor
      </ul>
    </p>

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

    <h5>Recipes</h5>
    <p>
      <ul style="list-style-type:none;">
      @for ($i = 0; $i < count($evening_staples); $i++)
        <li>{{ $evening_staples[$i][0] }}</li>
      @endfor
      </ul>
    </p>
  </div>
</div>

<br>
