<p>
  @for ($i = 0; $i < $meals_per_day; $i++)
    <a name="meal{{$start + $i + 1}}"></a>
    @if ($meal_statuses[$start + $i] == "on")
      Meal {{$start + $i + 1}}: <a href="{{ $meal_urls[$start + $i] }}" target="_blank">
        {{$meal_names[$start + $i]}}</a>
      <a href="{{route('refreshMeal', ['id'=>$id, 'day'=> $day, 'meal'=>$i])}}">
        <i class="fa fa-refresh btn" style="color:green" role="button"></i>
      </a>
      <a href="{{route('suppressMeal', ['id'=>$id, 'day'=> $day, 'meal'=>$i])}}">
        <i class="fa fa-close btn" style="color:red" role="button"></i>
      </a>
      <br>
    @else
      Meal {{$start + $i + 1}}: <a href=""><span style="text-decoration:line-through">
        {{$meal_names[$start + $i] }}</span></a>
      <a href="{{route('refreshMeal', ['id'=>$id, 'day'=> $day, 'meal'=>$i])}}">
        <i class="fa fa-refresh btn" style="color:green" role="button"></i>
      </a>
      <a href="{{route('unsuppressMeal', ['id'=>$id, 'day'=> $day, 'meal'=>$i])}}">
        <i class="fa fa-check btn" style="color:green" role="button"></i>
      </a>
      <br>
    @endif
  @endfor
</p>
