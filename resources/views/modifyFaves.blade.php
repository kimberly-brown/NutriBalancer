@extends('master')

@section('content')

<div class="well">
  {!! Form::open(['action'=>'PlanController@addFavoriteFood',
      'class'=>'form-horizontal']) !!}


  <fieldset>
    <legend>Edit {{$type}} favorites</legend>
    <h5>Foods to include in {{$type}} recipes: </h5>
    @if (count($favorites) <= 1)
      <p>None so far. Add some to get a more customized meal plan! </p>
    @else
      <ul style="list-style-type:none;">
        @for ($i = 0; $i < count($favorites); $i++)
           <li class="edit-fave-list">
            <div class="fave-item" style="clear: both;">
              <p class="alignleft">
                {{ $favorites[$i] }}
              </p>
              <p class="alignright">
                @if ($i != count($favorites)-1)
                  <a href="{{ route('deleteFavoriteFood', ['index'=>$i,
                          'type'=>$type, 'id'=>$id]) }}">
                    <i class="fa fa-close btn" style="color:red" role="button"></i>
                  </a>
                @endif
              </p>
           </div>
          </li>
        @endfor
      </ul>
      <a href="{{route('clearFavoriteFoods', ['id'=>$id, 'type'=>$type])}}"
         class="btn btn-secondary btn-lg active"
         role="button" aria-pressed="true">
        Clear all
      </a>
    @endif
    <br>
    <br>
    <br>
      <!--Enter food-->
      <div class="form-group">
        {!! Form::label('newFood', 'Add food:',
          ['class'=>'col-lg-2 control-label']) !!}
        <div class="col-lg-10">
          {!! Form::text('newFood', $value=null, ['class'=>'form-control',
            'rows'=>1]) !!}
        </div>
      </div>

      <!--Add food-->
      <div class="form-group">
        <div class="col-lg-10 col-lg-offset-2">
          {!! Form::submit('Add', ['class'=> 'btn btn-lg btn-info pull-right'])
            !!}
        </div>
      </div>

      <div class="form-group">
        <input type="hidden" name="id" value="{!! $id !!}">
        <input type="hidden" name="type" value="{!! $type !!}">
      </div>
    </fieldset>
    {!! Form::close() !!}

    {!! Form::open(['action'=>'PlanController@addStapleRecipe',
        'class'=>'form-horizontal']) !!}

    <fieldset>
      <h5>Your staple {{$type}} recipes: </h5>
      @if (count($staple_recipes) <= 1)
        <p>None so far. Add some, and they'll appear in your meal plan from time
          to time! </p>
      @else
        <ul style="list-style-type:none;">
          @for ($i = 0; $i < count($staple_recipes); $i++)
             <li class="edit-fave-list">
               <div>
                <div class="fave-item" style="clear: both;">
                  <p class="alignleft">
                    @if ($urls[$i] != "")
                      <a href="{{ $urls[$i] }}" target="_blank" class="staple-recipe-link">
                        {{ $staple_recipes[$i][0] }} </a>
                    @else
                      {{ $staple_recipes[$i][0] }}
                    @endif
                    &nbsp;&nbsp;&nbsp;
                    @if ($i == $viewIngredients)
                      <a href="{{ route('viewIngredients', ['id'=>$id, 'type'=>$type, 'viewIngredients'=>'-1']) }}">
                        <i class="fa fa-angle-up" style="color:black" role="button"></i>
                      </a>
                    @elseif ($i != count($staple_recipes)-1)
                      <a href="{{ route('viewIngredients', ['id'=>$id, 'type'=>$type, 'viewIngredients'=>$i]) }}">
                        <i class="fa fa-angle-down" style="color:black" role="button"></i>
                      </a>
                    @endif
                  </p>
                  <p class="alignright">
                    @if ($i != count($staple_recipes)-1)
                      <a href="">
                        <i class="fa fa-pencil btn" style="color:black" role="button"></i>
                      </a>
                    <!--See staple recipe ingredients-->
                      <a href="{{ route('deleteStapleRecipe',
                                  ['index'=>$i, 'type'=>$type, 'id'=>$id]) }}">
                        <i class="fa fa-close btn" style="color:red" role="button"></i>
                      </a>
                    @endif
                  </p>
               </div>
                 @if ($i == $viewIngredients)
                   <br>
                   <br>
                   <ul style="list-style-type:none;" class="ingredient-list">
                      @for ($j = 0; $j < count($staple_recipes[$i][1]); $j++)
                        <li>{{ $staple_recipes[$i][1][$j] }}</li>
                      @endfor
                  </ul>
                  <br>
                @endif
             </div>
            </li>
          @endfor
        </ul>
        <a href="{{route('clearStapleRecipes', ['id'=>$id, 'type'=>$type])}}"
           class="btn btn-secondary btn-lg active"
           role="button" aria-pressed="true">
          Clear all
        </a>
      @endif
    <br>
    <br>
    <br>
    <!--Enter recipe name-->
    <div class="form-group">
      {!! Form::label('newRecipe', 'Recipe name:', ['class'=>'col-lg-2
            control-label']) !!}
      <div class="col-lg-10">
        {!! Form::text('name', $value=null, ['class'=>'form-control',
            'rows'=>1]) !!}
      </div>
    </div>

    <!--Enter ingredients-->
    <div class="form-group">
      {!! Form::label('ingredients', 'Comma separated ingredients:',
        ['class'=>'col-lg-2 control-label']) !!}
      <div class="col-lg-10">
        {!! Form::text('ingredients', $value=null, ['class'=>'form-control',
            'rows'=>1]) !!}
      </div>
    </div>

    <!--Enter Link-->
    <div class="form-group">
      {!! Form::label('ingredients', 'Link (optional):',
        ['class'=>'col-lg-2 control-label']) !!}
      <div class="col-lg-10">
        {!! Form::text('url', $value=null, ['class'=>'form-control',
            'rows'=>1]) !!}
      </div>
    </div>

    <div class="form-group">
      <div class="col-lg-10 col-lg-offset-2">
        {!! Form::submit('Add', ['class'=> 'btn btn-lg btn-info pull-right'])
            !!}
      </div>
    </div>

    <div class="form-group">
      <input type="hidden" name="id" value="{!! $id !!}">
      <input type="hidden" name="type" value="{!! $type !!}">
      <input type="hidden" name="viewIngredients" value="{!! $viewIngredients !!}">
    </div>
  </fieldset>
  {!! Form::close() !!}

  <a href="{{route('dashboard')}}" class="btn btn-primary btn-lg active"
     role="button" aria-pressed="true">
    Done
  </a>
</div>