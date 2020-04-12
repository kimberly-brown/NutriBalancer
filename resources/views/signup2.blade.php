<?php
$minimum_range=1000;
$maximum_range=5000;
?>

@extends('master')

@section('content')

<div class="well">
  {!! Form::open(['action'=>'UserController@processSignup2', 'class'=>'form-horizontal']) !!}
  <fieldset>
    <legend>Create an account</legend>

    <p>
      Please enter some information about yourself as well as your dietary restrictions and preferences.
      As a reminder, this app is intended to be vegan-only, so there are no filters
      related to non-vegan foods.
    </p>
    <!-- Radio Buttons-->
    <div class="form-group">
        <div class="col-md-2"></div>
        {!! Form::label('radios', 'Do you menstruate?', ['class' => 'col-lg-2 control-label']) !!}
        <div class="col-md-6">
            <div class="radio">
                  {!! Form::radio('radio', 'option1', false, ['id' => 'radio1']) !!}
                  Yes

            </div>
            <div class="radio">
                {!! Form::radio('radio', 'option2', false, ['id' => 'radio2']) !!}
                No
            </div>
            <div class="radio">
                {!! Form::radio('radio', 'option3', true, ['id' => 'radio3']) !!}
                Prefer not to say
            </div>
        </div>
        <div class="col-md-2"></div>
    </div>

    <!-- Select With One Default: # meals per day  -->
    <div class="form-group">
        <div class="col-md-2"></div>
        {!! Form::label('select', 'Meals per day', ['class' => 'col-md-2 control-label'] )  !!}
        <div class="col-md-6">
            {!!  Form::select('meals_per_day', ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6'], ['class' => 'form-control' ]) !!}
        </div>
        <div class="col-md-2"></div>
    </div>

    <!-- Select With One Default: # snacks per day  -->
    <div class="form-group">
      <div class="col-md-2"></div>
        {!! Form::label('select', 'Snacks per day', ['class' => 'col-md-2 control-label'] )  !!}
        <div class="col-md-6">
            {!!  Form::select('snacks_per_day', ['0' => '0', '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6'], ['class' => 'form-control' ]) !!}
        </div>
        <div class="col-md-2"></div>
    </div>

    <!-- Select Multiple: dietary restrictions -->
    <div class="form-group">

        {!! Form::label('multipleselect[]', 'Allergens or restrictions:', ['class' => 'col-md-4 control-label'] )  !!}
        <div class="col-md-4">
        {!!  Form::select('dietary_restrictions', ['gluten' => 'Gluten', 'peanuts' => 'Peanuts', 'treeNuts' => 'Tree nuts', 'soy' => 'Soy'], $selected = null, ['class' => 'form-control', 'multiple' => 'multiple']) !!}
        </div>
    </div>
    <!--Later, consider adding an option for them to type in anything not covered! -->

    <!--Morning favorites
    <div class="form-group">

        {!! Form::label('morning_favorites[]', 'Favorite morning foods:', ['class' => 'col-md-4 control-label'] )  !!}
        <div class="col-md-4">
        {!!  Form::select('morning_favorites[]', ['oats' => 'Oats', 'cereal' => 'Cereal', 'toast' => 'Toast', 'fruit' => 'Fruit'], $selected = null, ['class' => 'form-control', 'multiple' => 'multiple']) !!}
        </div>
    </div>

    Evening favorites
    <div class="form-group">

        {!! Form::label('evening_favorites[]', 'Favorite evening foods:', ['class' => 'col-md-4 control-label'] )  !!}
        <div class="col-md-4">
        {!!  Form::select('evening_favorites[]', ['pasta' => 'Pasta', 'rice' => 'Rice', 'stirFry' => 'Stir fry', 'couscous' => 'Couscous', 'quinoa' => 'Quinoa'], null, ['class' => 'form-control', 'multiple' => true]) !!}
        </div>
    </div>
  -->
    <!--attempt at calorie range slider-->
    <div class="form-group">
      <div class="row">
        <div class="col-md-2">
          <label>Calorie range:</label>
          <input type="text" id="amount" name="calorie_range" readonly style="border:0; font-weight:bold;">
        </div>
        <div class="col-md-6" style="padding-top:12px">
          <div id="calorie_range"></div>
        </div>
        <div class="col-md-2">

        </div>
      </div>
      <div id="load_product">
      </div>
    </div>

    <div class="form-group">
      <input type="hidden" name="name" value="{!! $name !!}">
      <input type="hidden" name="username" value="{!! $username !!}">
      <input type="hidden" name="email" value="{!! $email !!}">
      <input type="hidden" name="password" value="{!! $password !!}">
    </div>

    <!-- Submit Button -->
    <div class="form-group">
      <div class="col-md-2"></div>
        <div class="col-md-6 col-md-offset-2">
            {!! Form::submit('Submit', ['class' => 'btn btn-lg btn-info pull-right'] ) !!}
        </div>
        <div class="col-md-2"></div>
    </div>
  </fieldset>
  </div>
</html>
