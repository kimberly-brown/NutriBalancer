@extends('master')

@include('navbar', ['theme_color'=>$theme_color])


<p>
Your preferred caloric range is {{$min_cals}} - {{$max_cals}}.
You like to eat {{$meals_per_day}} meals and {{$snacks_per_day}} snacks per day!

Your calories should be around {{$cals_per_snack}} per snack, and {{$cals_per_meal}}
per meal.
</p>

<h3>Theme</h3>

<p>
  @if ($theme_color == "97d67e")
    <a class="btn color-box" style="background-color:#97d67e; border: 2px solid black"
     role="button" href="#">
   </a>
  @else
    <a class="btn color-box" style="background-color:#97d67e;; border: 2px solid #97d67e;"
    role="button" href="{{route('changeThemeColor', ['color'=>'97d67e', 'id'=>$id])}}"></a>
  @endif

  @if ($theme_color == "e3e356")
    <a class="btn color-box" style="background-color:#e3e356; border: 2px solid black"
    role="button" href="#"></a>
  @else
    <a class="btn color-box" style="background-color:#e3e356; border: 2px solid #e3e356"
    role="button" href="{{route('changeThemeColor', ['color' => 'e3e356', 'id'=>$id])}}"></a>
  @endif

  @if ($theme_color == "85cbd4")
    <a class="btn color-box" style="background-color:#85cbd4; border: 2px solid black"
    role="button" href="#"></a>
  @else
    <a class="btn color-box" style="background-color:#85cbd4; border: 2px solid #85cbd4"
    role="button" href="{{route('changeThemeColor', ['color' => '85cbd4', 'id'=>$id])}}"></a>
  @endif
</p>
<h3>Font Size</h3>
<p>
  <a class="sizes">S</a>
  <a class="sizes">M</a>
  <a class="sizes">L</a>
  <a class="sizes">XL</a>
</p>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>

@extends('base')
