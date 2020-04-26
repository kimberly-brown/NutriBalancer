<ul style="list-style-type:none;">
@for ($nut=0; $nut < count($day_summary); $nut++)
  <li>{{ $day_summary[$nut][0] }}: {{ $day_summary[$nut][1] }} {{ $day_summary[$nut][2] }}</li>
@endfor
</ul>
