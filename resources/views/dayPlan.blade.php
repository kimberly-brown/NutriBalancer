<p>
  @for ($i = 0; $i < count($meals); $i++)
    @if ($meals[$i][2] == "on")
      Meal {{$i+1}}: <a href="{{ $meals[$i][3]}}" target="_blank">{{$meals[$i][0]}}</a>
      <a href="{{route('refreshMeal', ['id'=>$id, 'day'=> $day, 'meal'=>$i])}}">
        <i class="fa fa-refresh btn" style="color:green" role="button"></i>
      </a>
      <a href="{{route('suppressMeal', ['id'=>$id, 'day'=> $day, 'meal'=>$i])}}">
        <i class="fa fa-close btn" style="color:red" role="button"></i>
      </a>
      <br>
    @else
      Meal {{$i+1}}: <a href=""><span style="text-decoration:line-through">{{$meals[$i][0]}}</span></a>
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
