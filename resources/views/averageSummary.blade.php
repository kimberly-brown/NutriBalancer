@for ($i=0; $i< count($nutrient_summary[0]); $i++)
  <p>{{ $nutrient_summary[0][$i] }}: 
  {{ (int) ($nutrient_summary[1][$i] / 7) }} {{ $nutrient_summary[2][$i] }}</p>
@endfor
